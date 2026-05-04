<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Fase;
use App\Models\Actividad;
use App\Models\ResultadoAprendizaje;
use App\Models\ProyectoFormativo;
use Smalot\PdfParser\Parser;

/**
 * Service to parse the SENA Formative Project PDF and populate the DB.
 *
 * The PDF has a specific structure in section 3 (Planeación del proyecto):
 *   FASE_NAME              (standalone or prefixed: "1.ANÁLISIS", "ANÁLISIS")
 *   N. ACTIVITY_NAME       (multi-line)
 *   RA_CODE  -  [NN]  RA_DESCRIPTION  (multi-line, NN is optional)
 *   COMPETENCIA_CODE  -  COMPETENCIA_NAME
 *
 * This parser:
 * 1. Only processes the "Planeación del proyecto" section (section 3)
 * 2. Creates unique phases and activities (no duplicates)
 * 3. Links RA codes to activities via actividad_resultado
 * 4. Filters RAs by the project's program to prevent cross-contamination
 */
class ProjectPdfParserService
{
    private int $idProyecto;
    private ?int $idPrograma;

    /** Valid SENA phase names (both accented and unaccented) */
    private const VALID_PHASES = [
        'ANÁLISIS', 'PLANEACIÓN', 'EJECUCIÓN', 'EVALUACIÓN',
        'ANALISIS', 'PLANEACION', 'EJECUCION', 'EVALUACION'
    ];

    public function __construct(int $idProyecto)
    {
        $this->idProyecto = $idProyecto;

        // Resolve the program linked to this project for RA filtering
        $proyecto = ProyectoFormativo::findById($idProyecto);
        $this->idPrograma = $proyecto && !empty($proyecto['id_programa'])
            ? (int) $proyecto['id_programa']
            : null;
    }

    public function parse(string $filePath): array
    {
        $parser = new Parser();
        $pdf    = $parser->parseFile($filePath);
        $text   = $pdf->getText();

        $lines = explode("\n", $text);

        // Track state
        $currentPhaseName = null;
        $currentActCode   = null;
        $currentActName   = '';
        $inSection3       = false;
        $pastSection3     = false;

        // Maps to prevent duplicates
        $faseMap = [];     // phase_name => id_fase
        $actMap  = [];     // "act_code|phase_name" => id_actividad
        $stats   = [
            'fases'            => 0,
            'actividades'      => 0,
            'vinculos'         => 0,
            'ra_not_found'     => [],
            'ra_wrong_program' => [],
        ];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Detect start of section 3 (Planeación del proyecto)
            if (preg_match('/^3\.1\.?\s*Fases del Proyecto/i', $line) ||
                preg_match('/^3\.\s*Planeación del proyecto/i', $line)) {
                $inSection3 = true;
                continue;
            }

            // Detect end of section 3 (section 3.5 onwards is resources/budget, not structure)
            if (preg_match('/^3\.5/i', $line) || preg_match('/^4\.\s*Rubros/i', $line)) {
                $pastSection3 = true;
                break; // Stop parsing
            }

            // Skip lines outside section 3
            if (!$inSection3) continue;

            // Skip page headers/footers
            if (preg_match('/^Modelo de Mejora/i', $line)) continue;
            if (preg_match('/^SERVICIO NACIONAL/i', $line)) continue;
            if (preg_match('/^Sistema Integrado/i', $line)) continue;
            if (preg_match('/^PROYECTO FORMATIVO/i', $line)) continue;
            if (preg_match('/^Procedimiento Ejecución/i', $line)) continue;
            if (preg_match('/^Página \d+/i', $line)) continue;
            if (preg_match('/^GFPI-/i', $line)) continue;
            if (preg_match('/^3\.\d/i', $line)) continue; // Skip sub-headers like 3.2, 3.3, 3.4

            // 1. Detect Phase name — two formats:
            //    a) Standalone: "ANÁLISIS"
            //    b) Numbered:   "1.ANÁLISIS" or "1. ANÁLISIS" or "4.EVALUACIÓN"
            $upperLine = mb_strtoupper(trim($line));

            $detectedPhase = null;
            if (in_array($upperLine, self::VALID_PHASES)) {
                $detectedPhase = $upperLine;
            } elseif (preg_match('/^\d+\.\s*(.+)$/u', $upperLine, $pm)) {
                $extracted = trim($pm[1]);
                if (in_array($extracted, self::VALID_PHASES)) {
                    $detectedPhase = $extracted;
                }
            }

            if ($detectedPhase !== null) {
                $currentPhaseName = $detectedPhase;

                if (!isset($faseMap[$currentPhaseName])) {
                    // Find existing or create new phase
                    $existing = $this->findFaseByName($currentPhaseName);
                    if ($existing) {
                        $faseMap[$currentPhaseName] = (int) $existing['id_fase'];
                    } else {
                        $faseMap[$currentPhaseName] = Fase::insert(
                            $currentPhaseName,
                            $this->idProyecto
                        );
                        $stats['fases']++;
                    }
                }
                continue;
            }

            // 2. Detect Activity line: "N. ACTIVITY NAME" or "N- ACTIVITY NAME"
            if (preg_match('/^(\d+)[\.\-]\s+(.+)$/u', $line, $m) && $currentPhaseName) {
                $actCode = $m[1];
                $actNamePart = trim($m[2]);

                // Only accept valid activity numbers (1-9)
                if ((int)$actCode >= 1 && (int)$actCode <= 9) {
                    $actKey = "{$actCode}|{$currentPhaseName}";

                    if (!isset($actMap[$actKey])) {
                        // Start collecting activity name (might be multi-line)
                        $currentActCode = $actCode;
                        $currentActName = $actNamePart;
                        // We'll finalize the activity when we hit an RA code or a new activity
                    } else {
                        // Activity already exists, just set current context
                        $currentActCode = $actCode;
                        $currentActName = '';
                    }
                }
                continue;
            }

            // 3. Detect RA code — two formats:
            //    a) With sequence: "593343  - 01  DESCRIPTION..."
            //    b) Without sequence: "202613  - DESCRIPTION..."
            //    Pattern: 6-digit RA code, then " - " then optional "NN " then description
            if (preg_match_all('/(\d{6})\s*-\s*(?:\d{2}\s+)?([^\n]{10,})/u', $line, $matches, PREG_SET_ORDER) && $currentPhaseName && $currentActCode) {
                foreach ($matches as $m) {
                    $raCode = $m[1];
                    // Strip trailing noise (like tabs or other codes) from the RA code if necessary, 
                    // though \d{6} already limits it.

                // Finalize the activity if it hasn't been created yet
                $actKey = "{$currentActCode}|{$currentPhaseName}";
                if (!isset($actMap[$actKey]) && $currentActCode) {
                    $idFase = $faseMap[$currentPhaseName];
                    $actMap[$actKey] = Actividad::insert($currentActCode, $currentActName, $idFase);
                    $stats['actividades']++;
                }

                // Link RA to activity — filter by program if available
                if (isset($actMap[$actKey])) {
                    $ra = null;
                    if ($this->idPrograma) {
                        $ra = ResultadoAprendizaje::findByCodigoAndPrograma($raCode, $this->idPrograma);
                        if (!$ra) {
                            // Check if the RA exists but belongs to a different program
                            $raGlobal = ResultadoAprendizaje::findByCodigo($raCode);
                            if ($raGlobal) {
                                $stats['ra_wrong_program'][] = $raCode;
                            } else {
                                $stats['ra_not_found'][] = $raCode;
                            }
                        }
                    } else {
                        // Fallback: no program linked, search globally
                        $ra = ResultadoAprendizaje::findByCodigo($raCode);
                        if (!$ra) {
                            $stats['ra_not_found'][] = $raCode;
                        }
                    }

                    if ($ra) {
                        Actividad::asignarResultado($actMap[$actKey], (int)$ra['id_resultado']);
                        $stats['vinculos']++;
                    }
                }
                }
                continue;
            }

            // 4. Detect Competencia code (9 digits) - these are informational, skip
            if (preg_match('/^(\d{9})\s*-\s*/u', $line)) {
                continue;
            }

            // 5. If we're building an activity name (multi-line), append
            if ($currentActCode && !isset($actMap["{$currentActCode}|{$currentPhaseName}"]) && $currentActName) {
                // Only append if it looks like continuation text (uppercase, not a code)
                if (preg_match('/^[A-ZÁÉÍÓÚÑ\s,]+$/u', $line) && strlen($line) > 3) {
                    $currentActName .= ' ' . $line;
                }
            }
        }

        // Finalize any pending activity that was being built but never got an RA
        if ($currentActCode && $currentPhaseName) {
            $actKey = "{$currentActCode}|{$currentPhaseName}";
            if (!isset($actMap[$actKey])) {
                $idFase = $faseMap[$currentPhaseName] ?? null;
                if ($idFase) {
                    $actMap[$actKey] = Actividad::insert($currentActCode, $currentActName, $idFase);
                    $stats['actividades']++;
                }
            }
        }

        return $stats;
    }

    private function findFaseByName(string $name): ?array
    {
        $fases = Fase::findByProyecto($this->idProyecto);
        foreach ($fases as $f) {
            if (mb_strtoupper($f['nombre_fase']) === $name) return $f;
        }
        return null;
    }
}

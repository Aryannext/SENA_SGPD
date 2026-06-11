<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Programa;
use App\Models\Ficha;
use App\Models\Aprendiz;
use App\Models\Competencia;
use App\Models\ResultadoAprendizaje;
use App\Models\Calificacion;
use App\Models\Funcionario;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Parses the SOFIA Plus "Reporte de Juicios Evaluativos" Excel file
 * and imports data into the normalized database.
 *
 * Single Responsibility: only handles Excel → DB transformation.
 */
final class ExcelImportService
{
    private array $stats = [
        'programa'     => 0,
        'ficha'        => 0,
        'aprendices'   => 0,
        'competencias' => 0,
        'resultados'   => 0,
        'calificaciones' => 0,
        'funcionarios' => 0,
        'errores'      => 0,
        'filas_procesadas' => 0,
    ];

    private array $errors = [];

    /**
     * Import the Excel file into the database.
     *
     * @param string $filePath Absolute path to the .xls/.xlsx file
     * @return array{stats: array, errors: array}
     */
    public function import(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();
        $highestRow  = $sheet->getHighestRow();

        // ── Extract metadata from header rows (0-11) ───────────────────
        $fichaNumero   = $this->cleanValue($sheet->getCell('C3')->getValue());
        $codigoPrograma = $this->cleanValue($sheet->getCell('C4')->getValue());
        $nombrePrograma = $this->cleanValue($sheet->getCell('C6')->getValue());
        $estadoFicha   = $this->cleanValue($sheet->getCell('C7')->getValue());
        $modalidad     = $this->cleanValue($sheet->getCell('C10')->getValue());

        // Parse dates (Excel serial dates)
        $fechaInicio = $this->parseExcelDate($sheet->getCell('C8')->getValue());
        $fechaFin    = $this->parseExcelDate($sheet->getCell('C9')->getValue());

        // ── Insert programa and ficha ──────────────────────────
        $idPrograma = Programa::insertIgnore(
            (string) $codigoPrograma,
            $nombrePrograma,
            $this->normalizeModalidad($modalidad)
        );
        if ($idPrograma > 0) $this->stats['programa']++;

        $idFicha = Ficha::insertIgnore(
            (string) $fichaNumero,
            $this->normalizeEstadoFicha($estadoFicha),
            $fechaInicio,
            $fechaFin,
            $idPrograma,
            null // id_proyecto remains null until PDF is uploaded
        );
        if ($idFicha > 0) $this->stats['ficha']++;

        // ── Detect column mapping (Row 13) ──
        $colMap = [
            'tiDoc'       => 'A',
            'nuDoc'       => 'B',
            'nombre'      => 'C',
            'apellido'    => 'D',
            'estado'      => 'E',
            'compRaw'     => 'F',
            'resRaw'      => 'G',
            'juicio'      => 'H',
            'fechaJuicio' => 'I',
            'funcRaw'     => 'J'
        ];

        $headerRow = 13;
        $highestCol = $sheet->getHighestColumn();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

        for ($col = 1; $col <= $highestColIndex; $col++) {
            $val = trim((string)$sheet->getCellByColumnAndRow($col, $headerRow)->getValue());
            if (stripos($val, 'Tipo') !== false && stripos($val, 'Doc') !== false) $colMap['tiDoc'] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            if (stripos($val, 'Número') !== false && stripos($val, 'Doc') !== false) $colMap['nuDoc'] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            if (stripos($val, 'Nombre') !== false && stripos($val, 'Doc') === false) $colMap['nombre'] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            if (stripos($val, 'Apellido') !== false) $colMap['apellido'] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            if (stripos($val, 'Estado') !== false) $colMap['estado'] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            if (stripos($val, 'Competencia') !== false) $colMap['compRaw'] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            if (stripos($val, 'Resultado') !== false) $colMap['resRaw'] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            if (stripos($val, 'Juicio') !== false && stripos($val, 'Fecha') === false && stripos($val, 'Funcionario') === false) $colMap['juicio'] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            if (stripos($val, 'Fecha') !== false && stripos($val, 'Juicio') !== false) $colMap['fechaJuicio'] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            if (stripos($val, 'Funcionario') !== false) $colMap['funcRaw'] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        }

        // ── Process data rows (row 14 onwards) ──
        $dataStartRow = 14;
        $aprendizCache     = [];
        $competenciaCache  = [];
        $resultadoCache    = [];
        $funcionarioCache  = [];

        for ($row = $dataStartRow; $row <= $highestRow; $row++) {
            try {
                $tiDoc       = trim((string) $sheet->getCell("{$colMap['tiDoc']}{$row}")->getValue());
                $nuDoc       = trim((string) $sheet->getCell("{$colMap['nuDoc']}{$row}")->getValue());
                $nombre      = trim((string) $sheet->getCell("{$colMap['nombre']}{$row}")->getValue());
                $apellido    = trim((string) $sheet->getCell("{$colMap['apellido']}{$row}")->getValue());
                $estado      = trim((string) $sheet->getCell("{$colMap['estado']}{$row}")->getValue());
                $compRaw     = trim((string) $sheet->getCell("{$colMap['compRaw']}{$row}")->getValue());
                $resRaw      = trim((string) $sheet->getCell("{$colMap['resRaw']}{$row}")->getValue());
                $juicio      = trim((string) $sheet->getCell("{$colMap['juicio']}{$row}")->getValue());
                $fechaJuicio = $sheet->getCell("{$colMap['fechaJuicio']}{$row}")->getValue();
                $funcRaw     = trim((string) $sheet->getCell("{$colMap['funcRaw']}{$row}")->getValue());

                // Skip empty or header-like rows
                if (empty($nuDoc) || $nuDoc === 'Número de Documento' || !is_numeric(str_replace(['-', '.'], '', $nuDoc))) {
                    continue;
                }

                $this->stats['filas_procesadas']++;

                // ── Aprendiz ──────────────────────────────────────────
                if (!isset($aprendizCache[$nuDoc])) {
                    $idAprendiz = Aprendiz::insertIgnore($tiDoc, $nuDoc, $nombre, $apellido, $estado, $idFicha);
                    $aprendizCache[$nuDoc] = $idAprendiz;
                    $this->stats['aprendices']++;
                }
                $idAprendiz = $aprendizCache[$nuDoc];

                // ── Competencia ───────────────────────────────────────
                [$codComp, $nombreComp] = $this->parseCodigoNombre($compRaw);
                if (!isset($competenciaCache[$codComp]) && $codComp !== '') {
                    $idComp = Competencia::insertIgnore($codComp, $nombreComp);
                    $competenciaCache[$codComp] = $idComp;
                    $this->stats['competencias']++;
                }
                $idCompetencia = $competenciaCache[$codComp] ?? 0;

                if ($idPrograma > 0 && $idCompetencia > 0) {
                    Competencia::asignarPrograma($idPrograma, $idCompetencia);
                }

                // ── Resultado de Aprendizaje ─────────────────────────
                [$codRes, $nombreRes] = $this->parseCodigoNombre($resRaw);
                if (!isset($resultadoCache[$codRes]) && $codRes !== '' && $idCompetencia > 0) {
                    $idRes = ResultadoAprendizaje::insertIgnore($codRes, $nombreRes, $idCompetencia);
                    $resultadoCache[$codRes] = $idRes;
                    $this->stats['resultados']++;
                }
                $idResultado = $resultadoCache[$codRes] ?? 0;

                // ── Funcionario ───────────────────────────────────────
                $idFuncionario = null;
                if (!empty($funcRaw) && $funcRaw !== '-' && trim($funcRaw) !== '-') {
                    $funcData = $this->parseFuncionario($funcRaw);
                    if ($funcData && !empty($funcData['nuDoc'])) {
                        if (!isset($funcionarioCache[$funcData['nuDoc']])) {
                            $idFunc = Funcionario::insertIgnore($funcData['tiDoc'], $funcData['nuDoc'], $funcData['nombre']);
                            $funcionarioCache[$funcData['nuDoc']] = $idFunc;
                            $this->stats['funcionarios']++;
                        }
                        $idFuncionario = $funcionarioCache[$funcData['nuDoc']];
                    }
                }

                // ── Calificación ─────────────────────────────────────
                if ($idAprendiz > 0 && $idResultado > 0) {
                    $juicioNorm  = $this->normalizeJuicio($juicio);
                    $fechaJuicioStr = $this->parseExcelDate($fechaJuicio, true);

                    Calificacion::insertIgnore($idAprendiz, $idResultado, $idFuncionario, $juicioNorm, $fechaJuicioStr);
                    $this->stats['calificaciones']++;
                }
            } catch (\Throwable $e) {
                $this->stats['errores']++;
                $this->errors[] = "Fila {$row}: " . $e->getMessage();
            }
        }

        return [
            'stats'  => $this->stats,
            'errors' => $this->errors,
        ];
    }

    /**
     * Parse "CODE - NAME" format used for competencias and resultados.
     * @return array{0: string, 1: string} [codigo, nombre]
     */
    private function parseCodigoNombre(string $raw): array
    {
        $raw = trim($raw);
        if (empty($raw)) return ['', ''];

        // Format: "593347  - 03  ESTABLECER LOS REQUISITOS..." or "220501092  - Establecer..."
        // Try to split on " - " first
        $parts = preg_split('/\s+-\s+/', $raw, 2);
        if ($parts && count($parts) === 2) {
            return [trim($parts[0]), trim($parts[1])];
        }

        // Fallback: first word is the code
        $spacePos = strpos($raw, ' ');
        if ($spacePos !== false) {
            return [substr($raw, 0, $spacePos), substr($raw, $spacePos + 1)];
        }

        return [$raw, $raw];
    }

    /**
     * Parse funcionario string like "CC 1117523028 - OSCAR CAMILO CASTRO MOPAN"
     * @return array{tiDoc: string, nuDoc: string, nombre: string}|null
     */
    private function parseFuncionario(string $raw): ?array
    {
        $raw = trim($raw);
        if (empty($raw) || $raw === '-' || $raw === '-') return null;

        // Remove leading/trailing spaces and dashes
        $raw = trim($raw, " \t\n\r\0\x0B-");
        if (empty($raw)) return null;

        // Pattern: "CC 1117523028 - OSCAR CAMILO CASTRO MOPAN"
        if (preg_match('/^(\w+)\s+(\d+)\s+-\s+(.+)$/', $raw, $m)) {
            return ['tiDoc' => $m[1], 'nuDoc' => $m[2], 'nombre' => trim($m[3])];
        }

        return null;
    }

    private function parseExcelDate(mixed $value, bool $withTime = false): ?string
    {
        if (empty($value) || !is_numeric($value)) return null;

        try {
            $dateTime = ExcelDate::excelToDateTimeObject((float) $value);
            return $withTime ? $dateTime->format('Y-m-d H:i:s') : $dateTime->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeJuicio(string $juicio): string
    {
        $juicio = mb_strtoupper(trim($juicio));
        return match (true) {
            $juicio === 'A' || str_contains($juicio, 'APROB')  => 'APROBADO',
            $juicio === 'D' || str_contains($juicio, 'NO APR') => 'NO APROBADO',
            default                          => 'POR EVALUAR',
        };
    }

    private function normalizeEstadoFicha(string $estado): string
    {
        $estado = mb_strtoupper(trim($estado));
        return match (true) {
            str_contains($estado, 'EJECU')  => 'EN EJECUCION',
            str_contains($estado, 'TERMI')  => 'TERMINADA',
            str_contains($estado, 'CANCEL') => 'CANCELADA',
            default                          => 'EN EJECUCION',
        };
    }

    private function normalizeModalidad(string $modalidad): string
    {
        $modalidad = mb_strtoupper(trim($modalidad));
        return match (true) {
            str_contains($modalidad, 'PRES')  => 'PRESENCIAL',
            str_contains($modalidad, 'VIRT')  => 'VIRTUAL',
            str_contains($modalidad, 'DIST')  => 'A DISTANCIA',
            default                            => 'PRESENCIAL',
        };
    }

    private function cleanValue(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\ProyectoFormativo;
use App\Models\Fase;
use App\Models\Actividad;
use App\Models\ResultadoAprendizaje;
use App\Models\Ficha;

final class ProyectoController extends Controller
{
    /**
     * Master view: show all projects as cards.
     */
    public function index(): void
    {
        $proyectos = ProyectoFormativo::findAllWithPrograma();

        // Enrich each project with summary counts
        foreach ($proyectos as &$p) {
            $p['num_fases']      = ProyectoFormativo::countFases((int) $p['id_proyecto']);
            $p['num_resultados'] = ProyectoFormativo::countResultadosVinculados((int) $p['id_proyecto']);
        }

        // Load available programs for the "create new" modal
        $programas = static::queryDirectly('
            SELECT p.id_programa, p.codigo_programa, p.nombre_programa, 
                   (SELECT COUNT(*) FROM proyecto_formativo pf WHERE pf.id_programa = p.id_programa) as tiene_proyecto 
            FROM programa p 
            ORDER BY p.nombre_programa
        ');

        $this->view('proyecto.index', [
            'pageTitle'  => 'Proyectos Formativos',
            'proyectos'  => $proyectos,
            'programas'  => $programas,
        ]);
    }

    /**
     * Detail view: show a single project with its phases, activities, and RA.
     */
    public function detalle(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: /SENA_SGPD/proyecto');
            exit;
        }

        $proyecto = ProyectoFormativo::findByIdWithPrograma($id);
        if (!$proyecto) {
            header('Location: /SENA_SGPD/proyecto');
            exit;
        }

        $fases = Fase::findByProyecto($id);
        $fasesConActividades = [];
        foreach ($fases as $fase) {
            $actividades = Actividad::findByFase((int) $fase['id_fase']);
            foreach ($actividades as &$act) {
                $act['resultados'] = Actividad::getResultadosAsignados((int) $act['id_actividad']);
            }
            $fase['actividades'] = $actividades;
            $fasesConActividades[] = $fase;
        }

        $this->view('proyecto.detalle', [
            'pageTitle' => $proyecto['nombre_proyecto'],
            'proyecto'  => $proyecto,
            'fases'     => $fasesConActividades,
        ]);
    }

    public function asignar(): void
    {
        $idProyecto = (int) ($_GET['id'] ?? 0);
        if ($idProyecto <= 0) {
            header('Location: /SENA_SGPD/proyecto');
            exit;
        }

        $proyecto = ProyectoFormativo::findByIdWithPrograma($idProyecto);
        if (!$proyecto) {
            header('Location: /SENA_SGPD/proyecto');
            exit;
        }

        $idPrograma = (int) ($proyecto['id_programa'] ?? 0);

        // Get only unassigned RAs belonging to this project's program
        if ($idPrograma > 0) {
            $noAsignados = Actividad::getResultadosNoAsignadosPorPrograma($idPrograma, $idProyecto);
        } else {
            $noAsignados = Actividad::getResultadosNoAsignados();
        }

        // Get only phases of THIS project
        $fases = Fase::findByProyecto($idProyecto);

        $fasesConActividades = [];
        foreach ($fases as $fase) {
            $fase['actividades'] = Actividad::findByFase((int) $fase['id_fase']);
            $fasesConActividades[] = $fase;
        }

        // Group unassigned by competence for better organization
        $groupedNoAsignados = [];
        foreach ($noAsignados as $res) {
            $key = ($res['cod_competencia'] ?? 'S/C') . ' - ' . ($res['nombre_competencia'] ?? 'Sin Competencia');
            $groupedNoAsignados[$key][] = $res;
        }

        $this->view('proyecto.asignar', [
            'pageTitle'    => 'Asignar Resultados — ' . ($proyecto['nombre_proyecto'] ?? ''),
            'proyecto'     => $proyecto,
            'noAsignados'  => $groupedNoAsignados,
            'fases'        => $fasesConActividades,
        ]);
    }

    public function asignarPost(): void
    {
        $body = $this->getJsonBody();
        $idActividad  = (int) ($body['id_actividad'] ?? 0);
        $idResultado  = (int) ($body['id_resultado'] ?? 0);

        if ($idActividad <= 0 || $idResultado <= 0) {
            $this->json(['success' => false, 'message' => 'Datos inválidos.'], 400);
        }

        Actividad::asignarResultado($idActividad, $idResultado);
        $this->json(['success' => true, 'message' => 'Resultado asignado correctamente.']);
    }

    public function getFases(): void
    {
        $fases = Fase::findAll();
        $result = [];
        foreach ($fases as $fase) {
            $actividades = Actividad::findByFase((int) $fase['id_fase']);
            foreach ($actividades as &$act) {
                $act['resultados'] = Actividad::getResultadosAsignados((int) $act['id_actividad']);
            }
            $fase['actividades'] = $actividades;
            $result[] = $fase;
        }
        $this->json($result);
    }

    /**
     * API: Create a new project with default SENA phases.
     */
    public function crearProyecto(): void
    {
        $body = $this->getJsonBody();
        $codigo     = trim($body['codigo'] ?? '');
        $nombre     = trim($body['nombre'] ?? '');
        $idPrograma = !empty($body['id_programa']) ? (int) $body['id_programa'] : null;

        if (empty($codigo) || empty($nombre) || !$idPrograma) {
            $this->json(['success' => false, 'message' => 'Código, nombre y programa son obligatorios.'], 400);
            return;
        }

        // Check if code already exists
        $existing = ProyectoFormativo::findByCodigo($codigo);
        if ($existing) {
            $this->json(['success' => false, 'message' => 'Ya existe un proyecto con ese código.'], 400);
        }

        // Validate: a program can only have one project
        if ($idPrograma) {
            $existingProject = static::queryDirectly(
                'SELECT id_proyecto, nombre_proyecto FROM proyecto_formativo WHERE id_programa = ?',
                [$idPrograma]
            );
            if (!empty($existingProject)) {
                $this->json([
                    'success' => false,
                    'message' => 'El programa seleccionado ya tiene un proyecto asignado: "' . $existingProject[0]['nombre_proyecto'] . '". Cada programa solo puede tener un proyecto formativo.'
                ], 400);
            }
        }

        $idProyecto = ProyectoFormativo::create($codigo, $nombre, $idPrograma);
        Ficha::linkToProjectByProgram($idProyecto, $idPrograma);

        // Create default SENA phases
        $defaultFases = ['ANÁLISIS', 'PLANEACIÓN', 'EJECUCIÓN', 'EVALUACIÓN'];
        foreach ($defaultFases as $fase) {
            Fase::insert($fase, $idProyecto);
        }

        $this->json([
            'success'     => true,
            'id_proyecto' => $idProyecto,
            'message'     => 'Proyecto creado con 4 fases por defecto.',
        ]);
    }

    public function crearFase(): void
    {
        $body = $this->getJsonBody();
        $id = Fase::insert(
            $body['nombre_fase'] ?? '',
            (int) ($body['id_proyecto'] ?? 0)
        );
        $this->json(['success' => true, 'id_fase' => $id]);
    }

    public function crearActividad(): void
    {
        $body = $this->getJsonBody();
        $id = Actividad::insert(
            $body['cod_actividad'] ?? '',
            $body['nombre_actividad'] ?? '',
            (int) ($body['id_fase'] ?? 0)
        );
        $this->json(['success' => true, 'id_actividad' => $id]);
    }

    public function uploadPdf(): void
    {
        if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => 'Error al subir el archivo.'], 400);
            return;
        }

        $file = $_FILES['pdf'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($ext !== 'pdf') {
            $this->json(['success' => false, 'message' => 'Solo se permiten archivos PDF.'], 400);
            return;
        }

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = 'proyecto_auto_' . time() . '.pdf';
        $destination = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $this->json(['success' => false, 'message' => 'Error al guardar el archivo.'], 500);
            return;
        }

        // Parse PDF to extract metadata automatically
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdfTest = $parser->parseFile($destination);
            $text = $pdfTest->getText();
            $lines = explode("\n", $text);
            
            $projCode = '';
            $progCode = '';
            $projName = '';
            
            foreach ($lines as $l) {
                $l = trim($l);
                // Extract Project Code and Program Code from the header
                // "2480542    228118Código del Programa SOFIA:"
                if (preg_match('/^(\d+)\s+(\d+)Código del Programa SOFIA:/i', $l, $m)) {
                    $projCode = $m[1];
                    $progCode = $m[2];
                }
                
                // Extract Project Name
                // "1.3 Nombre del proyecto: ANÁLISIS Y DESARROLLO..."
                if (preg_match('/1\.3\s+Nombre del proyecto:\s*(.+)$/i', $l, $m)) {
                    $projName = trim($m[1]);
                }
            }
            
            // Sometimes the project name falls onto the next line
            if (empty($projName)) {
                foreach ($lines as $i => $l) {
                    if (preg_match('/1\.3\s+Nombre del proyecto:/i', $l)) {
                        $projName = trim($lines[$i+1] ?? '');
                        break;
                    }
                }
            }

            if (empty($progCode) || empty($projCode)) {
                unlink($destination);
                $this->json([
                    'success' => false, 
                    'message' => 'No se pudo extraer el Código del Programa o el Código del Proyecto del PDF. Verifique que el documento sea el Proyecto Formativo del SENA original.'
                ], 400);
                return;
            }

            // Verify program exists
            $programa = static::queryDirectly('SELECT id_programa FROM programa WHERE codigo_programa = ?', [$progCode]);
            if (empty($programa)) {
                unlink($destination);
                $this->json([
                    'success' => false, 
                    'message' => "El programa $progCode detectado en el PDF no está registrado en el sistema. Debe subir el Excel de SOFIA Plus primero."
                ], 400);
                return;
            }
            
            $idPrograma = (int) $programa[0]['id_programa'];

            // Check if project exists
            $proyecto = ProyectoFormativo::findByCodigo($projCode);
            $idProyecto = 0;
            
            if ($proyecto) {
                $idProyecto = (int) $proyecto['id_proyecto'];
                // Update basic info to ensure it matches the latest PDF
                if (empty($projName)) $projName = $proyecto['nombre_proyecto'];
                static::queryDirectly('UPDATE proyecto_formativo SET nombre_proyecto = ?, id_programa = ? WHERE id_proyecto = ?', [$projName, $idPrograma, $idProyecto]);
                Ficha::linkToProjectByProgram($idProyecto, $idPrograma);
                
                // Clean old phases and activities
                try {
                    $db = new class extends \Core\Model {
                        public static function exec($sql, $params = []) { static::execute($sql, $params); }
                    };
                    $db::exec("
                        DELETE ar FROM actividad_resultado ar
                        INNER JOIN actividad a ON ar.id_actividad = a.id_actividad
                        INNER JOIN fase f ON a.id_fase = f.id_fase
                        WHERE f.id_proyecto = ?
                    ", [$idProyecto]);
                    $db::exec("
                        DELETE a FROM actividad a
                        INNER JOIN fase f ON a.id_fase = f.id_fase
                        WHERE f.id_proyecto = ?
                    ", [$idProyecto]);
                    $db::exec("DELETE FROM fase WHERE id_proyecto = ?", [$idProyecto]);
                } catch (\Exception $e) {}
            } else {
                // Create new project
                if (empty($projName)) $projName = "Proyecto $projCode";
                $idProyecto = ProyectoFormativo::create($projCode, $projName, $idPrograma);
                Ficha::linkToProjectByProgram($idProyecto, $idPrograma);
            }

            ProyectoFormativo::updatePdf($idProyecto, '/public/uploads/' . $fileName);

            // Parse activities and RAs using the service
            $parserService = new \App\Services\ProjectPdfParserService($idProyecto);
            $stats = $parserService->parse($destination);
            
            $msg = "Proyecto $projCode procesado exitosamente: {$stats['fases']} fases, {$stats['actividades']} actividades, {$stats['vinculos']} vínculos RA.";
            $this->json(['success' => true, 'message' => $msg, 'ruta' => '/public/uploads/' . $fileName, 'stats' => $stats]);
            
        } catch (\Exception $e) {
            unlink($destination);
            $this->json(['success' => false, 'message' => 'Error al procesar el PDF: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Helper: direct query without a model.
     */
    private static function queryDirectly(string $sql, array $params = []): array
    {
        $config = require dirname(__DIR__, 2) . '/config/database.php';
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new \PDO($dsn, $config['username'], $config['password'], $config['options']);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

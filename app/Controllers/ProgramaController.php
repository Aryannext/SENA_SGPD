<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\Programa;
use App\Models\Ficha;
use App\Models\Aprendiz;
use App\Models\Calificacion;

final class ProgramaController extends Controller
{
    /**
     * Master view: show all programs as cards.
     */
    public function index(): void
    {
        $programas = Programa::findAllWithStats();

        $this->view('programa.index', [
            'pageTitle'  => 'Programas de Formación',
            'programas'  => $programas,
        ]);
    }

    /**
     * Detail view: show a single program with its fichas.
     */
    public function detalle(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: /SENA_SGPD/programa');
            exit;
        }

        $programa = Programa::findByIdWithStats($id);
        if (!$programa) {
            header('Location: /SENA_SGPD/programa');
            exit;
        }

        $fichas = Programa::getFichasWithStats($id);

        // Enrich with avance
        foreach ($fichas as &$f) {
            $avance = Programa::getAvanceFicha((int) $f['id_ficha']);
            $f['avance'] = $avance;
        }

        // Check if there is a formative project associated
        $config = require dirname(__DIR__, 2) . '/config/database.php';
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new \PDO($dsn, $config['username'], $config['password'], $config['options']);
        $stmt = $pdo->prepare('SELECT id_proyecto FROM proyecto_formativo WHERE id_programa = ? LIMIT 1');
        $stmt->execute([$id]);
        $proyecto = $stmt->fetch();
        $idProyecto = $proyecto ? $proyecto['id_proyecto'] : null;

        $this->view('programa.detalle', [
            'pageTitle'  => $programa['nombre_programa'],
            'programa'   => $programa,
            'fichas'     => $fichas,
            'idProyecto' => $idProyecto,
        ]);
    }

    /**
     * Ficha detail: show aprendices of a specific ficha with filtering.
     */
    public function ficha(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: /SENA_SGPD/programa');
            exit;
        }

        $ficha = Ficha::findById($id);
        if (!$ficha) {
            header('Location: /SENA_SGPD/programa');
            exit;
        }

        $programa = Programa::findById((int) $ficha['id_programa']);

        $this->view('programa.ficha', [
            'pageTitle' => 'Ficha ' . $ficha['nu_ficha'],
            'ficha'     => $ficha,
            'programa'  => $programa,
        ]);
    }

    // --- API Endpoints ---

    /**
     * API: Get aprendices of a ficha with optional filters.
     */
    public function aprendicesFicha(): void
    {
        $idFicha    = (int) ($_GET['id_ficha'] ?? 0);
        $estado     = $_GET['estado'] ?? '';
        $buscar     = $_GET['buscar'] ?? '';
        $ordenar    = $_GET['ordenar'] ?? 'apellido';
        $dir        = strtoupper($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        if ($idFicha <= 0) {
            $this->json(['success' => false, 'message' => 'Ficha no especificada'], 400);
        }

        $where  = ['a.id_ficha = ?'];
        $params = [$idFicha];

        if ($estado) {
            $where[]  = 'a.estado = ?';
            $params[] = $estado;
        }

        if ($buscar) {
            $where[]  = "(a.nombre LIKE ? OR a.apellido LIKE ? OR a.nu_documento LIKE ?)";
            $like = '%' . $buscar . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $allowedOrder = ['nombre', 'apellido', 'nu_documento', 'estado', 'porcentaje_avance'];
        if (!in_array($ordenar, $allowedOrder)) $ordenar = 'apellido';

        $whereSql = implode(' AND ', $where);

        $config = require dirname(__DIR__, 2) . '/config/database.php';
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new \PDO($dsn, $config['username'], $config['password'], $config['options']);

        $sql = "
            SELECT a.id_aprendiz, a.ti_documento, a.nu_documento, a.nombre, a.apellido, a.estado,
                   COUNT(c.id_calificacion) as total_resultados,
                   SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) as aprobados,
                   SUM(CASE WHEN c.jui_evaluativo = 'POR EVALUAR' THEN 1 ELSE 0 END) as por_evaluar,
                   COALESCE(ROUND(SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(c.id_calificacion),0), 1), 0) as porcentaje_avance
            FROM aprendiz a
            LEFT JOIN calificacion c ON a.id_aprendiz = c.id_aprendiz
            WHERE {$whereSql}
            GROUP BY a.id_aprendiz
            ORDER BY {$ordenar} {$dir}
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $aprendices = $stmt->fetchAll();

        // Stats
        $stmtStats = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado LIKE '%FORMACION%' THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN estado LIKE '%RETIRO%' OR estado LIKE '%CANCELADO%' THEN 1 ELSE 0 END) as retirados,
                SUM(CASE WHEN estado LIKE '%TRASLADADO%' THEN 1 ELSE 0 END) as trasladados
            FROM aprendiz WHERE id_ficha = ?
        ");
        $stmtStats->execute([$idFicha]);
        $stats = $stmtStats->fetch();

        $avance = Programa::getAvanceFicha($idFicha);

        $this->json([
            'success'     => true,
            'aprendices'  => $aprendices,
            'stats'       => $stats,
            'avance'      => $avance,
        ]);
    }

    /**
     * API: Delete a ficha and all its apprentices/grades.
     */
    public function deleteFicha(): void
    {
        $body = $this->getJsonBody();
        $idFicha = (int) ($body['id_ficha'] ?? 0);
        if ($idFicha <= 0) {
            $this->json(['success' => false, 'message' => 'Ficha inválida'], 400);
        }

        $config = require dirname(__DIR__, 2) . '/config/database.php';
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new \PDO($dsn, $config['username'], $config['password'], $config['options']);

        // Delete calificaciones → aprendices → ficha
        $pdo->prepare("DELETE c FROM calificacion c JOIN aprendiz a ON c.id_aprendiz = a.id_aprendiz WHERE a.id_ficha = ?")->execute([$idFicha]);
        $pdo->prepare("DELETE FROM aprendiz WHERE id_ficha = ?")->execute([$idFicha]);
        $pdo->prepare("DELETE FROM ficha WHERE id_ficha = ?")->execute([$idFicha]);

        $this->json(['success' => true, 'message' => 'Ficha eliminada correctamente.']);
    }
}

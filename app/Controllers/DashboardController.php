<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\Aprendiz;
use App\Models\Calificacion;
use App\Models\Competencia;
use App\Models\Fase;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->view('dashboard.index', [
            'pageTitle' => 'Dashboard',
            'extraJs'   => ['dashboard.js'],
            'extraCss'  => ['dashboard.css'],
        ]);
    }

    public function fases(): void
    {
        $this->view('dashboard.fases', [
            'pageTitle' => 'Dashboard de Fases',
            'extraJs'   => ['dashboard.js'],
            'extraCss'  => ['dashboard.css'],
        ]);
    }

    public function stats(): void
    {
        $totalAprendices   = Aprendiz::countTotal();
        $aprendicesPorEstado = Aprendiz::countByEstado();
        $juiciosPorTipo    = Calificacion::countByJuicio();
        $totalAprobados    = Calificacion::totalAprobados();
        $totalPorEvaluar   = Calificacion::totalPorEvaluar();
        $avancePorAprendiz = Calificacion::avancePorAprendiz();
        $avancePorComp     = Calificacion::avancePorCompetencia();
        $pendientesPorEstado = Calificacion::pendientesPorEstado();

        $totalCalif = $totalAprobados + $totalPorEvaluar;
        $pctGlobal  = $totalCalif > 0 ? round($totalAprobados * 100.0 / $totalCalif, 1) : 0;

        $this->json([
            'total_aprendices'      => $totalAprendices,
            'aprendices_por_estado' => $aprendicesPorEstado,
            'total_aprobados'       => $totalAprobados,
            'total_por_evaluar'     => $totalPorEvaluar,
            'porcentaje_global'     => $pctGlobal,
            'juicios_por_tipo'      => $juiciosPorTipo,
            'avance_por_aprendiz'   => $avancePorAprendiz,
            'avance_por_competencia' => $avancePorComp,
            'pendientes_por_estado' => $pendientesPorEstado,
        ]);
    }

    public function filtrar(): void
    {
        $estado      = $_GET['estado'] ?? '';
        $competencia = $_GET['competencia'] ?? '';
        $documento   = $_GET['documento'] ?? '';
        $busqueda    = $_GET['q'] ?? '';

        $aprendizConditions = [];
        $aprendizParams     = [];

        if ($estado) {
            $aprendizConditions[] = 'a.estado = ?';
            $aprendizParams[]     = $estado;
        }
        if ($documento) {
            $aprendizConditions[] = 'a.nu_documento LIKE ?';
            $aprendizParams[]     = "%{$documento}%";
        }
        if ($busqueda) {
            $aprendizConditions[] = '(a.nombre LIKE ? OR a.apellido LIKE ?)';
            $aprendizParams[]     = "%{$busqueda}%";
            $aprendizParams[]     = "%{$busqueda}%";
        }

        // --- 1. Tabla de Aprendices ---
        $tablaConditions = $aprendizConditions;
        $tablaParams     = $aprendizParams;
        if ($competencia) {
            $tablaConditions[] = 'comp.id_competencia = ?';
            $tablaParams[]     = $competencia;
        }

        $sqlTabla = "
            SELECT a.id_aprendiz, a.nombre, a.apellido, a.nu_documento, a.estado,
                   COUNT(c.id_calificacion) as total_resultados,
                   SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) as aprobados,
                   SUM(CASE WHEN c.jui_evaluativo = 'POR EVALUAR' THEN 1 ELSE 0 END) as por_evaluar,
                   ROUND(SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(c.id_calificacion), 0), 1) as porcentaje_avance
            FROM aprendiz a
            LEFT JOIN calificacion c ON a.id_aprendiz = c.id_aprendiz
            LEFT JOIN resultado_aprendizaje r ON c.id_resultado = r.id_resultado
            LEFT JOIN competencia comp ON r.id_competencia = comp.id_competencia
        ";

        if ($tablaConditions) {
            $sqlTabla .= ' WHERE ' . implode(' AND ', $tablaConditions);
        }
        $sqlTabla .= ' GROUP BY a.id_aprendiz ORDER BY porcentaje_avance DESC';

        $pdo  = \Core\Model::class;
        $db   = (new class extends \Core\Model { public static function run(string $sql, array $p): array { return static::query($sql, $p); } });
        $tablaData = $db::run($sqlTabla, $tablaParams);

        // --- 2. Gráfica de Competencias (Completa) ---
        // We want all competencies. The join with calificaciones only includes those from the filtered apprentices.
        $subqueryWhere = '';
        if ($aprendizConditions) {
            $subqueryWhere = ' WHERE ' . implode(' AND ', $aprendizConditions);
        }
        
        $sqlComp = "
            SELECT comp.id_competencia, comp.cod_competencia, comp.nombre as nombre_competencia,
                   COUNT(c.id_calificacion) as total,
                   SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) as aprobados,
                   COALESCE(ROUND(SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(c.id_calificacion), 0), 1), 0) as porcentaje
            FROM competencia comp
            LEFT JOIN resultado_aprendizaje r ON comp.id_competencia = r.id_competencia
            LEFT JOIN calificacion c ON r.id_resultado = c.id_resultado
                AND c.id_aprendiz IN (SELECT a.id_aprendiz FROM aprendiz a $subqueryWhere)
        ";

        $compConditions = [];
        $compParams = $aprendizParams; // For the subquery

        if ($competencia) {
            $compConditions[] = 'comp.id_competencia = ?';
            $compParams[] = $competencia;
        }

        if ($compConditions) {
            $sqlComp .= ' WHERE ' . implode(' AND ', $compConditions);
        }
        $sqlComp .= ' GROUP BY comp.id_competencia ORDER BY porcentaje DESC';

        $compData = $db::run($sqlComp, $compParams);

        $this->json([
            'tabla' => $tablaData,
            'competencias' => $compData
        ]);
    }

    public function fasesStats(): void
    {
        $fases = Fase::cumplimientoPorFase();
        $this->json($fases);
    }

    public function faseDetalle(): void
    {
        $idFase = (int) ($_GET['id_fase'] ?? 0);
        $idFicha = (int) ($_GET['id_ficha'] ?? 0);

        if (!$idFase || !$idFicha) {
            $this->json(['error' => 'Faltan parámetros'], 400);
            return;
        }

        $sql = "
            SELECT ap.id_aprendiz, ap.nombre, ap.apellido, ap.nu_documento, ap.estado,
                   COUNT(c.id_calificacion) as total_calificaciones,
                   COALESCE(SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END), 0) as aprobados,
                   COALESCE(SUM(CASE WHEN c.jui_evaluativo = 'POR EVALUAR' THEN 1 ELSE 0 END), 0) as pendientes,
                   COALESCE(ROUND(SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) * 100.0 / NULLIF((
                       SELECT COUNT(DISTINCT ar2.id_resultado)
                       FROM actividad a2
                       JOIN actividad_resultado ar2 ON a2.id_actividad = ar2.id_actividad
                       WHERE a2.id_fase = ?
                   ), 0), 1), 0) as porcentaje_avance
            FROM aprendiz ap
            LEFT JOIN calificacion c ON ap.id_aprendiz = c.id_aprendiz
                 AND c.id_resultado IN (
                     SELECT DISTINCT ar3.id_resultado
                     FROM actividad a3
                     JOIN actividad_resultado ar3 ON a3.id_actividad = ar3.id_actividad
                     WHERE a3.id_fase = ?
                 )
            WHERE ap.id_ficha = ?
            GROUP BY ap.id_aprendiz
            ORDER BY porcentaje_avance DESC, ap.nombre
        ";

        $pdo = \Core\Model::class;
        $db = (new class extends \Core\Model { public static function run(string $sql, array $p): array { return static::query($sql, $p); } });
        $data = $db::run($sql, [$idFase, $idFase, $idFicha]);

        $this->json(['aprendices' => $data]);
    }
}

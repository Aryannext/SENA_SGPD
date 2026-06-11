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

    public function fichasActivas(): void
    {
        $db = clone (new class extends \Core\Model { public static function run(string $sql, array $p=[]): array { return static::query($sql, $p); } });
        $fichas = $db::run("
            SELECT f.id_ficha, f.nu_ficha, f.estado, f.fecha_inicio, f.fecha_fin, p.nombre_programa, p.codigo_programa, p.modalidad
            FROM ficha f
            JOIN programa p ON f.id_programa = p.id_programa
            ORDER BY f.id_ficha DESC
        ");
        $this->json(['fichas' => $fichas]);
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
        $idFicha     = isset($_GET['id_ficha']) ? (int)$_GET['id_ficha'] : 0;
        $estado      = $_GET['estado'] ?? '';
        $competencia = $_GET['competencia'] ?? '';
        $documento   = $_GET['documento'] ?? '';
        $busqueda    = $_GET['q'] ?? '';
        
        $db = clone (new class extends \Core\Model { 
            public static function run(string $sql, array $p=[]): array { return static::query($sql, $p); } 
            public static function val(string $sql, array $p=[]): string|int { $r = static::queryOne($sql, $p); return $r ? current($r) : 0; }
        });

        // ---------------------------------------------------------
        // 1. CONDICIONES GLOBALES (Métricas de Ficha)
        // Solo aplica a aprendices activos (EN FORMACION)
        // ---------------------------------------------------------
        $condsGlobal = ["a.estado LIKE '%FORMACION%'"];
        $paramsGlobal = [];
        
        if ($idFicha > 0) {
            $condsGlobal[] = "a.id_ficha = ?";
            $paramsGlobal[] = $idFicha;
        }
        $whereGlobal = "WHERE " . implode(' AND ', $condsGlobal);
        $whereFicha = $idFicha > 0 ? "WHERE id_ficha = $idFicha" : "";

        // Calcular Promedio Real de Avance del Grupo (Baseline)
        $avgProgress = (float)$db::val("
            SELECT COALESCE(ROUND(SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(c.id_calificacion), 0), 1), 0)
            FROM aprendiz a
            LEFT JOIN calificacion c ON a.id_aprendiz = c.id_aprendiz
            $whereGlobal
        ", $paramsGlobal);
        
        // Calcular Tiempo Transcurrido Cronológico (solo para UI)
        $tiempoTranscurrido = 0;
        $fechasFicha = null;
        if ($idFicha > 0) {
            $fichaRow = $db::run("SELECT fecha_inicio, fecha_fin FROM ficha WHERE id_ficha = ?", [$idFicha]);
            if ($fichaRow && !empty($fichaRow[0]['fecha_inicio']) && !empty($fichaRow[0]['fecha_fin'])) {
                $fechasFicha = [
                    'inicio' => $fichaRow[0]['fecha_inicio'],
                    'fin'    => $fichaRow[0]['fecha_fin']
                ];
                $inicio = strtotime($fichaRow[0]['fecha_inicio']);
                $fin = strtotime($fichaRow[0]['fecha_fin']);
                $hoy = time();
                
                if ($inicio && $fin && $fin > $inicio) {
                    if ($hoy < $inicio) {
                        $tiempoTranscurrido = 0;
                    } elseif ($hoy > $fin) {
                        $tiempoTranscurrido = 100;
                    } else {
                        $tiempoTranscurrido = round(($hoy - $inicio) / ($fin - $inicio) * 100, 1);
                    }
                }
            }
        }

        // Global Stats (Usando $whereGlobal)
        $totalAprendices = (int)$db::val("SELECT COUNT(*) FROM aprendiz a $whereGlobal", $paramsGlobal);
        $whereFichaAndNotEmtpy = $whereFicha ? $whereFicha . " AND a.estado != '' AND a.estado IS NOT NULL" : "WHERE a.estado != '' AND a.estado IS NOT NULL"; $aprendicesPorEstado = $db::run("SELECT a.estado, COUNT(*) as total FROM aprendiz a $whereFichaAndNotEmtpy GROUP BY a.estado ORDER BY total DESC");
        
        $juiciosPorTipo = $db::run("
            SELECT c.jui_evaluativo, COUNT(*) as total 
            FROM calificacion c 
            JOIN aprendiz a ON c.id_aprendiz = a.id_aprendiz
            $whereGlobal
            GROUP BY c.jui_evaluativo ORDER BY total DESC
        ", $paramsGlobal);
        
        $totalAprobados = (int)$db::val("
            SELECT COUNT(*) FROM calificacion c 
            JOIN aprendiz a ON c.id_aprendiz = a.id_aprendiz 
            $whereGlobal AND c.jui_evaluativo = 'APROBADO'
        ", $paramsGlobal);
        
        $totalPorEvaluar = (int)$db::val("
            SELECT COUNT(*) FROM calificacion c 
            JOIN aprendiz a ON c.id_aprendiz = a.id_aprendiz 
            $whereGlobal AND c.jui_evaluativo = 'POR EVALUAR'
        ", $paramsGlobal);

        // ---------------------------------------------------------
        // 2. CONDICIONES DE TABLA (Directorio General Filtrado)
        // Aquí SÍ aplicamos los filtros de usuario (estado, busqueda, etc.)
        // ---------------------------------------------------------
        $condsTabla = [];
        $paramsTabla = [];
        if ($idFicha > 0) {
            $condsTabla[] = "a.id_ficha = ?";
            $paramsTabla[] = $idFicha;
        }
        if ($estado) {
            $condsTabla[] = "a.estado = ?";
            $paramsTabla[] = $estado;
        }
        if ($documento) {
            $condsTabla[] = "a.nu_documento LIKE ?";
            $paramsTabla[] = "%{$documento}%";
        }
        if ($busqueda) {
            $condsTabla[] = "(a.nombre LIKE ? OR a.apellido LIKE ?)";
            $paramsTabla[] = "%{$busqueda}%";
            $paramsTabla[] = "%{$busqueda}%";
        }
        if ($competencia) {
            $condsTabla[] = "comp.id_competencia = ?";
            $paramsTabla[] = $competencia;
        }
        $whereTabla = count($condsTabla) > 0 ? "WHERE " . implode(' AND ', $condsTabla) : "";

        $avancePorAprendiz = $db::run("
            SELECT a.id_aprendiz, a.nombre, a.apellido, a.nu_documento, a.estado,
                   COUNT(c.id_calificacion) as total_resultados,
                   SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) as aprobados,
                   SUM(CASE WHEN c.jui_evaluativo = 'POR EVALUAR' THEN 1 ELSE 0 END) as por_evaluar,
                   COALESCE(ROUND(SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(c.id_calificacion), 0), 1), 0) as porcentaje_avance,
                   (
                       SELECT COUNT(c_deuda.id_calificacion)
                       FROM calificacion c_deuda
                       WHERE c_deuda.id_aprendiz = a.id_aprendiz
                         AND c_deuda.jui_evaluativo = 'POR EVALUAR'
                         AND c_deuda.id_resultado IN (
                             SELECT c_ap.id_resultado
                             FROM calificacion c_ap
                             JOIN aprendiz a_ap ON c_ap.id_aprendiz = a_ap.id_aprendiz
                             WHERE a_ap.id_ficha = a.id_ficha AND c_ap.jui_evaluativo = 'APROBADO'
                         )
                   ) as en_deuda
            FROM aprendiz a
            LEFT JOIN calificacion c ON a.id_aprendiz = c.id_aprendiz
            LEFT JOIN resultado_aprendizaje r ON c.id_resultado = r.id_resultado
            LEFT JOIN competencia comp ON r.id_competencia = comp.id_competencia
            $whereTabla
            GROUP BY a.id_aprendiz
            ORDER BY porcentaje_avance DESC
        ", $paramsTabla);

        // Global Stats: Avance por Competencia (Solo Activos)
        $avancePorComp = $db::run("
            SELECT comp.id_competencia, comp.cod_competencia, comp.nombre as nombre_competencia,
                   COUNT(c.id_calificacion) as total,
                   SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) as aprobados,
                   COALESCE(ROUND(SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(c.id_calificacion), 0), 1), 0) as porcentaje
            FROM competencia comp
            LEFT JOIN resultado_aprendizaje r ON comp.id_competencia = r.id_competencia
            LEFT JOIN calificacion c ON r.id_resultado = c.id_resultado
            LEFT JOIN aprendiz a ON c.id_aprendiz = a.id_aprendiz
            $whereGlobal
            GROUP BY comp.id_competencia
            ORDER BY porcentaje DESC
        ", $paramsGlobal);

        // Global Stats: Pendientes por Estado (Todos los estados, pero esto es informativo)
        // Wait, for pendientes, we usually want to know how many are pending overall.
        $pendientesPorEstado = $db::run("
            SELECT a.estado, COUNT(c.id_calificacion) as pendientes
            FROM calificacion c
            JOIN aprendiz a ON c.id_aprendiz = a.id_aprendiz
            WHERE c.jui_evaluativo = 'POR EVALUAR' " . ($idFicha > 0 ? "AND a.id_ficha = $idFicha" : "") . "
            GROUP BY a.estado
            ORDER BY pendientes DESC
        ");
        
        // Fase stats (Aislado a la ficha mediante el proyecto formativo)
        $idFichaParam = $idFicha > 0 ? $idFicha : 0;
        $fasesStats = $db::run("
            SELECT f.id_fase, f.nombre_fase,
                   (SELECT COUNT(DISTINCT ar.id_resultado) 
                    FROM actividad act 
                    JOIN actividad_resultado ar ON act.id_actividad = ar.id_actividad 
                    WHERE act.id_fase = f.id_fase) as total_resultados_fase,
                   COUNT(c.id_calificacion) as total_calificaciones,
                   SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) as aprobados,
                   COALESCE(ROUND(SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(c.id_calificacion), 0), 1), 0) as porcentaje_cumplimiento
            FROM fase f
            JOIN proyecto_formativo pf ON f.id_proyecto = pf.id_proyecto
            JOIN ficha fi ON pf.id_proyecto = fi.id_proyecto
            LEFT JOIN (
                SELECT DISTINCT act.id_fase, ar.id_resultado
                FROM actividad act
                JOIN actividad_resultado ar ON act.id_actividad = ar.id_actividad
            ) fr ON f.id_fase = fr.id_fase
            LEFT JOIN aprendiz ap ON fi.id_ficha = ap.id_ficha
            LEFT JOIN calificacion c ON fr.id_resultado = c.id_resultado AND ap.id_aprendiz = c.id_aprendiz
            WHERE fi.id_ficha = $idFichaParam
            GROUP BY f.id_fase, fi.id_ficha, f.nombre_fase
            HAVING total_resultados_fase > 0
            ORDER BY f.id_fase ASC
        ");

        $totalCalif = $totalAprobados + $totalPorEvaluar;
        $pctGlobal  = $totalCalif > 0 ? round($totalAprobados * 100.0 / $totalCalif, 1) : 0;

        // Nuevos cálculos para Insights (Rendimiento Relativo al PROMEDIO DE LA CLASE)
        $enRiesgo = 0;
        $destacados = 0;
        $histograma = [
            'Crítico' => 0,
            'Rezagado' => 0,
            'Al Día' => 0,
            'Adelantado' => 0
        ];

        // Lista rápida de aprendices en riesgo (Foco de atención)
        $topRiesgo = [];

        // Hacemos el insight SOLO SOBRE LA LISTA COMPLETA DE APRENDICES ACTIVOS (para que el filtro no dañe la gráfica)
        $avanceParaInsights = $db::run("
            SELECT a.id_aprendiz, a.nombre, a.apellido, a.nu_documento, a.estado,
                   COALESCE(ROUND(SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(c.id_calificacion), 0), 1), 0) as porcentaje_avance,
                   (
                       SELECT COUNT(c_deuda.id_calificacion)
                       FROM calificacion c_deuda
                       WHERE c_deuda.id_aprendiz = a.id_aprendiz
                         AND c_deuda.jui_evaluativo = 'POR EVALUAR'
                         AND c_deuda.id_resultado IN (
                             SELECT c_ap.id_resultado
                             FROM calificacion c_ap
                             JOIN aprendiz a_ap ON c_ap.id_aprendiz = a_ap.id_aprendiz
                             WHERE a_ap.id_ficha = a.id_ficha AND c_ap.jui_evaluativo = 'APROBADO'
                         )
                   ) as en_deuda
            FROM aprendiz a
            LEFT JOIN calificacion c ON a.id_aprendiz = c.id_aprendiz
            $whereGlobal
            GROUP BY a.id_aprendiz
        ", $paramsGlobal);

        // Usar $avgProgress como la meta (en lugar del tiempo, porque el Excel puede ser viejo)
        $expectedBaseline = $avgProgress;

        foreach ($avanceParaInsights as &$ap) {
            $pct = (float)$ap['porcentaje_avance'];
            $enDeuda = (int)$ap['en_deuda'];
            
            // Lógica de Categorización Relativa al Grupo
            $dif = $pct - $expectedBaseline;
            
            if ($dif < -15 || $enDeuda >= 5) {
                $cat = 'Crítico';
                $enRiesgo++;
            } elseif ($dif < -5 || $enDeuda >= 2) {
                $cat = 'Rezagado';
            } elseif ($dif <= 5) {
                $cat = 'Al Día';
            } else {
                $cat = 'Adelantado';
                $destacados++;
            }
            
            $ap['categoria'] = $cat;
            $histograma[$cat]++;

            // Foco de Atención: Todos aquí ya son 'EN FORMACION'
            $scoreRiesgo = $dif - ($enDeuda * 5); 
            $ap['score_riesgo'] = $scoreRiesgo;
            $topRiesgo[] = $ap;
        }

        // Ordenar topRiesgo de menor score a mayor
        usort($topRiesgo, function($a, $b) {
            return $a['score_riesgo'] <=> $b['score_riesgo'];
        });
        
        $soloRiesgo = array_filter($topRiesgo, function($a) { return in_array($a["categoria"], ["Cr�tico", "Rezagado"]); }); usort($soloRiesgo, function($a, $b) { return $a["score_riesgo"] <=> $b["score_riesgo"]; }); $topRiesgo = array_values($soloRiesgo);

        $this->json([
            'tiempo_transcurrido'   => $tiempoTranscurrido,
            'avance_promedio'       => $avgProgress,
            'fechas_ficha'          => $fechasFicha,
            'total_aprendices'      => $totalAprendices,
            'aprendices_por_estado' => $aprendicesPorEstado,
            'total_aprobados'       => $totalAprobados,
            'total_por_evaluar'     => $totalPorEvaluar,
            'porcentaje_global'     => $pctGlobal,
            'juicios_por_tipo'      => $juiciosPorTipo,
            'avance_por_aprendiz'   => $avancePorAprendiz,
            'avance_por_competencia' => $avancePorComp,
            'pendientes_por_estado' => $pendientesPorEstado,
            'fases'                 => $fasesStats,
            'insights' => [
                'en_riesgo' => $enRiesgo,
                'destacados' => $destacados,
                'histograma' => $histograma,
                'top_riesgo' => $topRiesgo
            ]
        ]);
    }

    public function filtrar(): void
    {
        $idFicha     = isset($_GET['id_ficha']) ? (int)$_GET['id_ficha'] : 0;
        $estado      = $_GET['estado'] ?? '';
        $competencia = $_GET['competencia'] ?? '';
        $documento   = $_GET['documento'] ?? '';
        $busqueda    = $_GET['q'] ?? '';

        $aprendizConditions = [];
        $aprendizParams     = [];

        if ($idFicha > 0) {
            $aprendizConditions[] = 'a.id_ficha = ?';
            $aprendizParams[]     = $idFicha;
        }

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

    public function deudasAprendiz(): void
    {
        $idAprendiz = isset($_GET['id_aprendiz']) ? (int)$_GET['id_aprendiz'] : 0;
        if ($idAprendiz === 0) {
            $this->json(['error' => 'ID inválido'], 400);
            return;
        }

        $db = clone (new class extends \Core\Model { 
            public static function run(string $sql, array $p=[]): array { return static::query($sql, $p); } 
        });

        // Obtener la ficha del aprendiz
        $ap = $db::run("SELECT id_ficha FROM aprendiz WHERE id_aprendiz = ?", [$idAprendiz]);
        if (!$ap) {
            $this->json(['error' => 'Aprendiz no encontrado'], 404);
            return;
        }
        $idFicha = $ap[0]['id_ficha'];

        // Consultar los resultados en deuda
        $deudas = $db::run("
                        SELECT 
                r.nombre_resultado as resultado, 
                comp.nombre as competencia,
                (
                    SELECT f.nombre 
                    FROM calificacion c2 
                    JOIN aprendiz a2 ON c2.id_aprendiz = a2.id_aprendiz
                    JOIN funcionario f ON c2.id_funcionario = f.id_funcionario
                    WHERE a2.id_ficha = ? 
                      AND c2.id_resultado = r.id_resultado 
                      AND c2.jui_evaluativo = 'APROBADO'
                    LIMIT 1
                ) as instructor_responsable
            FROM calificacion c
            JOIN resultado_aprendizaje r ON c.id_resultado = r.id_resultado
            JOIN competencia comp ON r.id_competencia = comp.id_competencia
            WHERE c.id_aprendiz = ?
              AND c.jui_evaluativo = 'POR EVALUAR'
              AND c.id_resultado IN (
                  SELECT c_ap.id_resultado
                  FROM calificacion c_ap
                  JOIN aprendiz a_ap ON c_ap.id_aprendiz = a_ap.id_aprendiz
                  WHERE a_ap.id_ficha = ? AND c_ap.jui_evaluativo = 'APROBADO'
              )
            ORDER BY comp.nombre ASC
        ", [$idFicha, $idAprendiz, $idFicha]);

        $this->json($deudas);
    }
}





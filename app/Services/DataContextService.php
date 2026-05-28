<?php

declare(strict_types=1);

namespace App\Services;

use Core\Model;

/**
 * Analyzes user intent and builds a data context from the database
 * to feed into the AI's system prompt.
 *
 * This is the bridge between the database and the AI model.
 * Single Responsibility: intent detection + data retrieval for AI context.
 */
final class DataContextService extends Model
{
    /**
     * Analyze the user's message and return relevant database context and actions.
     *
     * @param string $message The user's message
     * @return array { 'context_string': string, 'actions': array }
     */
    public function buildContextAndActions(string $message): array
    {
        $message = mb_strtolower($message);
        $context = [];
        $actions = [];

        // Always provide a summary
        $context[] = $this->getSummary();

        // Detect intent by keywords
        if ($this->mentions($message, ['aprendiz', 'aprendices', 'estudiante', 'alumnos', 'quién', 'quien'])) {
            $context[] = $this->getAprendicesContext($message);
        }

        if ($this->mentions($message, ['avance', 'progreso', 'porcentaje', 'cómo va', 'como va', 'rendimiento'])) {
            $context[] = $this->getAvanceContext($message);
        }

        if ($this->mentions($message, ['pendiente', 'por evaluar', 'falta', 'sin aprobar', 'atrasado'])) {
            $context[] = $this->getPendientesContext();
        }

        if ($this->mentions($message, ['competencia', 'competencias'])) {
            $context[] = $this->getCompetenciasContext();
        }

        if ($this->mentions($message, ['fase', 'fases', 'cumplimiento', 'proyecto'])) {
            $context[] = $this->getFasesContext();
        }

        if ($this->mentions($message, ['ficha', 'programa', 'formación'])) {
            $context[] = $this->getFichaContext();
        }

        if ($this->mentions($message, ['mejor', 'peor', 'ranking', 'top', 'comparar', 'comparación'])) {
            $context[] = $this->getRankingContext();
        }

        if ($this->mentions($message, ['riesgo', 'alerta', 'crítico', 'problema', 'bajo'])) {
            $context[] = $this->getRiskContext();
        }

        // --- SPECIFIC ENTITY DETECTION (REGEX) ---
        // Ficha number detection (e.g., 3142784)
        if (preg_match('/\b(\d{6,8})\b/', $message, $matches)) {
            $fichaNumber = $matches[1];
            $context[] = $this->getSpecificFichaContext($fichaNumber);
        }

        // Action detection
        $wantsGraph = $this->mentions($message, ['grafica', 'gráfica', 'graficar', 'grafico', 'gráfico', 'chart']);
        $wantsReport = $this->mentions($message, ['reporte', 'descargar', 'excel', 'csv', 'exportar']);

        if ($wantsGraph) {
            if ($this->mentions($message, ['fase', 'proyecto'])) {
                $actions[] = $this->generateFasesGraph();
            } else if ($this->mentions($message, ['competencia'])) {
                $actions[] = $this->generateCompetenciasGraph();
            } else {
                // Default to general avance
                $actions[] = $this->generateGeneralGraph();
            }
        }

        if ($wantsReport) {
            if ($this->mentions($message, ['riesgo', 'alerta', 'crítico'])) {
                $actions[] = $this->generateRiskReport();
            } else if ($this->mentions($message, ['pendiente', 'atrasado'])) {
                $actions[] = $this->generatePendientesReport();
            } else {
                // Default to full status report
                $actions[] = $this->generateAvanceReport();
            }
        }

        return [
            'context_string' => implode("\n\n", array_filter($context)),
            'actions'        => $actions,
        ];
    }

    private function mentions(string $text, array $keywords): bool
    {
        foreach ($keywords as $kw) {
            if (str_contains($text, $kw)) return true;
        }
        return false;
    }

    private function getSummary(): string
    {
        $total     = static::queryOne("SELECT COUNT(*) as n FROM aprendiz")['n'] ?? 0;
        $activos   = static::queryOne("SELECT COUNT(*) as n FROM aprendiz WHERE estado LIKE '%FORMACION%'")['n'] ?? 0;
        $retirados = static::queryOne("SELECT COUNT(*) as n FROM aprendiz WHERE estado LIKE '%RETIRO%'")['n'] ?? 0;
        
        $aprobados = static::queryOne("SELECT COUNT(*) as n FROM calificacion WHERE jui_evaluativo='APROBADO'")['n'] ?? 0;
        $pendientes = static::queryOne("SELECT COUNT(*) as n FROM calificacion WHERE jui_evaluativo='POR EVALUAR'")['n'] ?? 0;
        $totalCal  = $aprobados + $pendientes;
        $pct       = $totalCal > 0 ? round($aprobados * 100.0 / $totalCal, 1) : 0;

        return "RESUMEN GENERAL: {$total} aprendices registrados en total ({$activos} activos/en formación, {$retirados} retirados). {$aprobados} juicios aprobados, {$pendientes} pendientes. Avance global: {$pct}%.";
    }

    private function getAprendicesContext(string $message): string
    {
        // Try to find a specific aprendiz by name
        $rows = static::query("
            SELECT a.nombre, a.apellido, a.nu_documento, a.estado,
                   COUNT(c.id_calificacion) as total,
                   SUM(CASE WHEN c.jui_evaluativo='APROBADO' THEN 1 ELSE 0 END) as aprobados
            FROM aprendiz a LEFT JOIN calificacion c ON a.id_aprendiz = c.id_aprendiz
            GROUP BY a.id_aprendiz ORDER BY a.apellido LIMIT 30
        ");

        $lines = ["APRENDICES:"];
        foreach ($rows as $r) {
            $pct = $r['total'] > 0 ? round($r['aprobados'] * 100.0 / $r['total'], 1) : 0;
            $lines[] = "- {$r['nombre']} {$r['apellido']} (Doc: {$r['nu_documento']}, Estado: {$r['estado']}, Avance: {$pct}%, Aprobados: {$r['aprobados']}/{$r['total']})";
        }

        return implode("\n", $lines);
    }

    private function getAvanceContext(string $message): string
    {
        $rows = static::query("
            SELECT a.nombre, a.apellido,
                   COUNT(c.id_calificacion) as total,
                   SUM(CASE WHEN c.jui_evaluativo='APROBADO' THEN 1 ELSE 0 END) as aprobados,
                   ROUND(SUM(CASE WHEN c.jui_evaluativo='APROBADO' THEN 1 ELSE 0 END)*100.0/COUNT(c.id_calificacion),1) as pct
            FROM aprendiz a JOIN calificacion c ON a.id_aprendiz = c.id_aprendiz
            GROUP BY a.id_aprendiz ORDER BY pct DESC
        ");

        $lines = ["AVANCE POR APRENDIZ (ordenado de mayor a menor):"];
        foreach ($rows as $r) {
            $lines[] = "- {$r['nombre']} {$r['apellido']}: {$r['pct']}% ({$r['aprobados']}/{$r['total']})";
        }
        return implode("\n", $lines);
    }

    private function getPendientesContext(): string
    {
        $rows = static::query("
            SELECT a.nombre, a.apellido,
                   SUM(CASE WHEN c.jui_evaluativo='POR EVALUAR' THEN 1 ELSE 0 END) as pendientes
            FROM aprendiz a JOIN calificacion c ON a.id_aprendiz = c.id_aprendiz
            GROUP BY a.id_aprendiz ORDER BY pendientes DESC LIMIT 10
        ");

        $lines = ["APRENDICES CON MÁS PENDIENTES (top 10):"];
        foreach ($rows as $r) {
            $lines[] = "- {$r['nombre']} {$r['apellido']}: {$r['pendientes']} juicios pendientes";
        }
        return implode("\n", $lines);
    }

    private function getCompetenciasContext(): string
    {
        $rows = static::query("
            SELECT comp.cod_competencia, comp.nombre,
                   COUNT(c.id_calificacion) as total,
                   SUM(CASE WHEN c.jui_evaluativo='APROBADO' THEN 1 ELSE 0 END) as aprobados,
                   ROUND(SUM(CASE WHEN c.jui_evaluativo='APROBADO' THEN 1 ELSE 0 END)*100.0/COUNT(c.id_calificacion),1) as pct
            FROM competencia comp
            JOIN resultado_aprendizaje r ON comp.id_competencia = r.id_competencia
            JOIN calificacion c ON r.id_resultado = c.id_resultado
            GROUP BY comp.id_competencia ORDER BY pct DESC
        ");

        $lines = ["AVANCE POR COMPETENCIA:"];
        foreach ($rows as $r) {
            $nombre = mb_substr($r['nombre'], 0, 60);
            $lines[] = "- {$r['cod_competencia']} ({$nombre}): {$r['pct']}% aprobación ({$r['aprobados']}/{$r['total']})";
        }
        return implode("\n", $lines);
    }

    private function getFasesContext(): string
    {
        $rows = static::query("
            SELECT f.nombre_fase,
                   COUNT(DISTINCT ar.id_resultado) as resultados,
                   COUNT(c.id_calificacion) as total_cal,
                   SUM(CASE WHEN c.jui_evaluativo='APROBADO' THEN 1 ELSE 0 END) as aprobados
            FROM fase f
            LEFT JOIN actividad a ON f.id_fase = a.id_fase
            LEFT JOIN actividad_resultado ar ON a.id_actividad = ar.id_actividad
            LEFT JOIN calificacion c ON ar.id_resultado = c.id_resultado
            GROUP BY f.id_fase ORDER BY f.id_fase
        ");

        $lines = ["CUMPLIMIENTO POR FASE:"];
        foreach ($rows as $r) {
            $pct = $r['total_cal'] > 0 ? round($r['aprobados'] * 100.0 / $r['total_cal'], 1) : 0;
            $lines[] = "- {$r['nombre_fase']}: {$pct}% cumplimiento, {$r['resultados']} resultados asignados, {$r['aprobados']} aprobados";
        }
        return implode("\n", $lines);
    }

    private function getFichaContext(): string
    {
        $rows = static::query("
            SELECT f.id_ficha, f.nu_ficha, f.estado, f.fecha_inicio, f.fecha_fin,
                   p.nombre_programa, p.codigo_programa, p.modalidad
            FROM ficha f LEFT JOIN programa p ON f.id_programa = p.id_programa
        ");

        if (empty($rows)) return "No hay fichas registradas.";
        
        $lines = ["FICHAS Y PROGRAMAS:"];
        foreach ($rows as $row) {
            $activos   = static::queryOne("SELECT COUNT(*) as n FROM aprendiz WHERE id_ficha = ? AND estado LIKE '%FORMACION%'", [$row['id_ficha']])['n'] ?? 0;
            $retirados = static::queryOne("SELECT COUNT(*) as n FROM aprendiz WHERE id_ficha = ? AND estado LIKE '%RETIRO%'", [$row['id_ficha']])['n'] ?? 0;
            $lines[] = "- Ficha: {$row['nu_ficha']}, Estado: {$row['estado']}, Programa: {$row['nombre_programa']} ({$row['codigo_programa']}), Modalidad: {$row['modalidad']}, Inicio: {$row['fecha_inicio']}, Fin: {$row['fecha_fin']}. Aprendices activos: {$activos}. Aprendices retirados: {$retirados}.";
        }
        return implode("\n", $lines);
    }

    private function getSpecificFichaContext(string $nuFicha): string
    {
        $ficha = static::queryOne("
            SELECT f.id_ficha, f.nu_ficha, f.estado, p.nombre_programa, p.codigo_programa
            FROM ficha f JOIN programa p ON f.id_programa = p.id_programa
            WHERE f.nu_ficha = ?
        ", [$nuFicha]);

        if (!$ficha) {
            return "No encontré ninguna ficha con el número {$nuFicha} en la base de datos.";
        }

        $idFicha = $ficha['id_ficha'];
        $activos = static::queryOne("SELECT COUNT(*) as n FROM aprendiz WHERE id_ficha = ? AND estado LIKE '%FORMACION%'", [$idFicha])['n'] ?? 0;
        $retirados = static::queryOne("SELECT COUNT(*) as n FROM aprendiz WHERE id_ficha = ? AND estado LIKE '%RETIRO%'", [$idFicha])['n'] ?? 0;

        $aprendices = static::query("
            SELECT a.nombre, a.apellido, a.estado,
                   COUNT(c.id_calificacion) as total,
                   SUM(CASE WHEN c.jui_evaluativo='APROBADO' THEN 1 ELSE 0 END) as aprobados
            FROM aprendiz a LEFT JOIN calificacion c ON a.id_aprendiz = c.id_aprendiz
            WHERE a.id_ficha = ?
            GROUP BY a.id_aprendiz
            ORDER BY a.apellido
        ", [$idFicha]);

        $lines = ["DATOS EXACTOS DE LA FICHA {$nuFicha}:"];
        $lines[] = "- Programa: {$ficha['nombre_programa']} ({$ficha['codigo_programa']})";
        $lines[] = "- Estado: {$ficha['estado']}";
        $lines[] = "- Aprendices Activos: {$activos}. Retirados: {$retirados}.";
        
        $fichaAprobados = 0;
        $fichaTotal = 0;
        foreach ($aprendices as $r) {
            $fichaAprobados += $r['aprobados'];
            $fichaTotal += $r['total'];
        }
        $fichaPendientes = $fichaTotal - $fichaAprobados;
        $fichaPct = $fichaTotal > 0 ? round($fichaAprobados * 100.0 / $fichaTotal, 1) : 0;
        
        $lines[] = "- Avance Global de la Ficha: {$fichaPct}%";
        $lines[] = "- Total Juicios Aprobados en la ficha: {$fichaAprobados}";
        $lines[] = "- Total Juicios Pendientes en la ficha: {$fichaPendientes}";
        
        if (count($aprendices) > 0) {
            $lines[] = "LISTADO DE APRENDICES EN LA FICHA {$nuFicha}:";
            foreach ($aprendices as $r) {
                $pct = $r['total'] > 0 ? round($r['aprobados'] * 100.0 / $r['total'], 1) : 0;
                $lines[] = "- {$r['nombre']} {$r['apellido']} (Estado: {$r['estado']}, Avance: {$pct}%)";
            }
        } else {
            $lines[] = "No hay aprendices registrados en esta ficha aún.";
        }

        return implode("\n", $lines);
    }

    private function getRankingContext(): string
    {
        $top = static::query("
            SELECT a.nombre, a.apellido,
                   ROUND(SUM(CASE WHEN c.jui_evaluativo='APROBADO' THEN 1 ELSE 0 END)*100.0/COUNT(c.id_calificacion),1) as pct
            FROM aprendiz a JOIN calificacion c ON a.id_aprendiz = c.id_aprendiz
            GROUP BY a.id_aprendiz ORDER BY pct DESC LIMIT 5
        ");

        $bottom = static::query("
            SELECT a.nombre, a.apellido,
                   ROUND(SUM(CASE WHEN c.jui_evaluativo='APROBADO' THEN 1 ELSE 0 END)*100.0/COUNT(c.id_calificacion),1) as pct
            FROM aprendiz a JOIN calificacion c ON a.id_aprendiz = c.id_aprendiz
            GROUP BY a.id_aprendiz ORDER BY pct ASC LIMIT 5
        ");

        $lines = ["TOP 5 CON MEJOR AVANCE:"];
        foreach ($top as $i => $r) { $lines[] = ($i+1) . ". {$r['nombre']} {$r['apellido']}: {$r['pct']}%"; }
        $lines[] = "\nTOP 5 CON MENOR AVANCE:";
        foreach ($bottom as $i => $r) { $lines[] = ($i+1) . ". {$r['nombre']} {$r['apellido']}: {$r['pct']}%"; }

        return implode("\n", $lines);
    }

    private function getRiskContext(): string
    {
        $rows = static::query("
            SELECT a.nombre, a.apellido, a.estado,
                   SUM(CASE WHEN c.jui_evaluativo='POR EVALUAR' THEN 1 ELSE 0 END) as pendientes,
                   COUNT(c.id_calificacion) as total,
                   ROUND(SUM(CASE WHEN c.jui_evaluativo='APROBADO' THEN 1 ELSE 0 END)*100.0/COUNT(c.id_calificacion),1) as pct
            FROM aprendiz a JOIN calificacion c ON a.id_aprendiz = c.id_aprendiz
            GROUP BY a.id_aprendiz HAVING pct < 30
            ORDER BY pct ASC
        ");

        $lines = ["APRENDICES EN RIESGO (avance < 30%):"];
        if (empty($rows)) {
            $lines[] = "No hay aprendices con avance menor al 30%.";
        } else {
            foreach ($rows as $r) {
                $lines[] = "- {$r['nombre']} {$r['apellido']} (Estado: {$r['estado']}, Avance: {$r['pct']}%, Pendientes: {$r['pendientes']})";
            }
        }
        return implode("\n", $lines);
    }

    // --- Action Generators ---

    private function generateFasesGraph(): array
    {
        $rows = static::query("
            SELECT f.nombre_fase,
                   COUNT(c.id_calificacion) as total_cal,
                   SUM(CASE WHEN c.jui_evaluativo='APROBADO' THEN 1 ELSE 0 END) as aprobados
            FROM fase f
            LEFT JOIN actividad a ON f.id_fase = a.id_fase
            LEFT JOIN actividad_resultado ar ON a.id_actividad = ar.id_actividad
            LEFT JOIN calificacion c ON ar.id_resultado = c.id_resultado
            GROUP BY f.id_fase ORDER BY f.id_fase
        ");

        $labels = []; $data = [];
        foreach ($rows as $r) {
            $labels[] = $r['nombre_fase'];
            $data[] = $r['total_cal'] > 0 ? round($r['aprobados'] * 100.0 / $r['total_cal'], 1) : 0;
        }

        return [
            'type' => 'graph',
            'graphType' => 'bar',
            'title' => 'Cumplimiento por Fase',
            'labels' => $labels,
            'data' => $data,
            'color' => '#8b5cf6'
        ];
    }

    private function generateCompetenciasGraph(): array
    {
        $rows = static::query("
            SELECT comp.cod_competencia,
                   COUNT(c.id_calificacion) as total,
                   SUM(CASE WHEN c.jui_evaluativo='APROBADO' THEN 1 ELSE 0 END) as aprobados
            FROM competencia comp
            JOIN resultado_aprendizaje r ON comp.id_competencia = r.id_competencia
            JOIN calificacion c ON r.id_resultado = c.id_resultado
            GROUP BY comp.id_competencia ORDER BY comp.cod_competencia
        ");

        $labels = []; $data = [];
        foreach ($rows as $r) {
            $labels[] = $r['cod_competencia'];
            $data[] = $r['total'] > 0 ? round($r['aprobados'] * 100.0 / $r['total'], 1) : 0;
        }

        return [
            'type' => 'graph',
            'graphType' => 'line',
            'title' => 'Avance por Competencia',
            'labels' => $labels,
            'data' => $data,
            'color' => '#3b82f6'
        ];
    }

    private function generateGeneralGraph(): array
    {
        $aprobados = static::queryOne("SELECT COUNT(*) as n FROM calificacion WHERE jui_evaluativo='APROBADO'")['n'] ?? 0;
        $pendientes = static::queryOne("SELECT COUNT(*) as n FROM calificacion WHERE jui_evaluativo='POR EVALUAR'")['n'] ?? 0;

        return [
            'type' => 'graph',
            'graphType' => 'doughnut',
            'title' => 'Distribución General de Juicios',
            'labels' => ['Aprobados', 'Por Evaluar'],
            'data' => [(float)$aprobados, (float)$pendientes],
            'color' => ['#39d353', '#f59e0b']
        ];
    }

    private function generateRiskReport(): array
    {
        $rows = static::query("
            SELECT a.nu_documento, a.nombre, a.apellido, a.estado,
                   COUNT(c.id_calificacion) as total_resultados,
                   SUM(CASE WHEN c.jui_evaluativo='APROBADO' THEN 1 ELSE 0 END) as aprobados,
                   ROUND(SUM(CASE WHEN c.jui_evaluativo='APROBADO' THEN 1 ELSE 0 END)*100.0/COUNT(c.id_calificacion),1) as pct
            FROM aprendiz a JOIN calificacion c ON a.id_aprendiz = c.id_aprendiz
            GROUP BY a.id_aprendiz HAVING pct < 30
            ORDER BY pct ASC
        ");

        return $this->formatAsCsvAction('Aprendices_Riesgo.csv', $rows, ['nu_documento', 'nombre', 'apellido', 'estado', 'total_resultados', 'aprobados', 'pct']);
    }

    private function generatePendientesReport(): array
    {
        $rows = static::query("
            SELECT a.nu_documento, a.nombre, a.apellido,
                   SUM(CASE WHEN c.jui_evaluativo='POR EVALUAR' THEN 1 ELSE 0 END) as pendientes
            FROM aprendiz a JOIN calificacion c ON a.id_aprendiz = c.id_aprendiz
            GROUP BY a.id_aprendiz HAVING pendientes > 0
            ORDER BY pendientes DESC
        ");

        return $this->formatAsCsvAction('Aprendices_Pendientes.csv', $rows, ['nu_documento', 'nombre', 'apellido', 'pendientes']);
    }

    private function generateAvanceReport(): array
    {
        $rows = static::query("
            SELECT a.nu_documento, a.nombre, a.apellido, a.estado,
                   COUNT(c.id_calificacion) as total_resultados,
                   SUM(CASE WHEN c.jui_evaluativo='APROBADO' THEN 1 ELSE 0 END) as aprobados,
                   ROUND(SUM(CASE WHEN c.jui_evaluativo='APROBADO' THEN 1 ELSE 0 END)*100.0/COUNT(c.id_calificacion),1) as pct
            FROM aprendiz a JOIN calificacion c ON a.id_aprendiz = c.id_aprendiz
            GROUP BY a.id_aprendiz
            ORDER BY a.apellido ASC
        ");

        return $this->formatAsCsvAction('Reporte_General_Avance.csv', $rows, ['nu_documento', 'nombre', 'apellido', 'estado', 'total_resultados', 'aprobados', 'pct']);
    }

    private function formatAsCsvAction(string $filename, array $rows, array $headers): array
    {
        if (empty($rows)) {
            $csv = implode(',', $headers) . "\nNo hay datos disponibles.";
        } else {
            $csv = implode(',', $headers) . "\n";
            foreach ($rows as $row) {
                $line = [];
                foreach ($headers as $h) {
                    $val = $row[$h] ?? '';
                    // Escape quotes and wrap in quotes if contains comma
                    $val = str_replace('"', '""', (string)$val);
                    if (str_contains($val, ',')) {
                        $val = '"' . $val . '"';
                    }
                    $line[] = $val;
                }
                $csv .= implode(',', $line) . "\n";
            }
        }

        // Return base64 encoded to avoid JSON parsing issues with newlines
        return [
            'type' => 'report',
            'filename' => $filename,
            'content_b64' => base64_encode($csv)
        ];
    }
}

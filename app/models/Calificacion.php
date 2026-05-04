<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Calificacion extends Model
{
    public static function insertIgnore(
        int $idAprendiz,
        int $idResultado,
        ?int $idFuncionario,
        string $juicio,
        ?string $fechaRegistro
    ): void {
        static::execute(
            'INSERT IGNORE INTO calificacion (id_aprendiz, id_resultado, id_funcionario, jui_evaluativo, fecha_registro)
             VALUES (?, ?, ?, ?, ?)',
            [$idAprendiz, $idResultado, $idFuncionario, $juicio, $fechaRegistro]
        );
    }

    public static function countByJuicio(): array
    {
        return static::query(
            'SELECT jui_evaluativo, COUNT(*) as total
             FROM calificacion GROUP BY jui_evaluativo ORDER BY total DESC'
        );
    }

    public static function totalAprobados(): int
    {
        $row = static::queryOne("SELECT COUNT(*) as total FROM calificacion WHERE jui_evaluativo = 'APROBADO'");
        return $row ? (int) $row['total'] : 0;
    }

    public static function totalPorEvaluar(): int
    {
        $row = static::queryOne("SELECT COUNT(*) as total FROM calificacion WHERE jui_evaluativo = 'POR EVALUAR'");
        return $row ? (int) $row['total'] : 0;
    }

    public static function avancePorAprendiz(): array
    {
        return static::query("
            SELECT a.id_aprendiz, a.nombre, a.apellido, a.nu_documento, a.estado,
                   COUNT(c.id_calificacion) as total_resultados,
                   SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) as aprobados,
                   SUM(CASE WHEN c.jui_evaluativo = 'POR EVALUAR' THEN 1 ELSE 0 END) as por_evaluar,
                   ROUND(SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) * 100.0 / COUNT(c.id_calificacion), 1) as porcentaje_avance
            FROM aprendiz a
            LEFT JOIN calificacion c ON a.id_aprendiz = c.id_aprendiz
            GROUP BY a.id_aprendiz
            ORDER BY porcentaje_avance DESC
        ");
    }

    public static function avancePorCompetencia(): array
    {
        return static::query("
            SELECT comp.id_competencia, comp.cod_competencia, comp.nombre as nombre_competencia,
                   COUNT(c.id_calificacion) as total,
                   SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) as aprobados,
                   ROUND(SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) * 100.0 / COUNT(c.id_calificacion), 1) as porcentaje
            FROM calificacion c
            JOIN resultado_aprendizaje r ON c.id_resultado = r.id_resultado
            JOIN competencia comp ON r.id_competencia = comp.id_competencia
            GROUP BY comp.id_competencia
            ORDER BY porcentaje DESC
        ");
    }

    public static function detalleAprendiz(int $idAprendiz): array
    {
        return static::query("
            SELECT c.*, r.cod_resultado, r.nombre_resultado,
                   comp.cod_competencia, comp.nombre as nombre_competencia,
                   f.nombre as nombre_funcionario
            FROM calificacion c
            JOIN resultado_aprendizaje r ON c.id_resultado = r.id_resultado
            JOIN competencia comp ON r.id_competencia = comp.id_competencia
            LEFT JOIN funcionario f ON c.id_funcionario = f.id_funcionario
            WHERE c.id_aprendiz = ?
            ORDER BY comp.cod_competencia, r.cod_resultado
        ", [$idAprendiz]);
    }

    public static function pendientesPorEstado(): array
    {
        return static::query("
            SELECT a.estado, COUNT(c.id_calificacion) as pendientes
            FROM calificacion c
            JOIN aprendiz a ON c.id_aprendiz = a.id_aprendiz
            WHERE c.jui_evaluativo = 'POR EVALUAR'
            GROUP BY a.estado
            ORDER BY pendientes DESC
        ");
    }
}

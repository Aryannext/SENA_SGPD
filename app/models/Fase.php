<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Fase extends Model
{
    public static function findAll(): array
    {
        return static::query(
            'SELECT f.*, pf.nombre_proyecto
             FROM fase f
             LEFT JOIN proyecto_formativo pf ON f.id_proyecto = pf.id_proyecto
             ORDER BY f.id_fase'
        );
    }

    public static function findById(int $id): ?array
    {
        return static::queryOne('SELECT * FROM fase WHERE id_fase = ?', [$id]);
    }

    public static function findByProyecto(int $idProyecto): array
    {
        return static::query(
            'SELECT * FROM fase WHERE id_proyecto = ? ORDER BY id_fase',
            [$idProyecto]
        );
    }

    public static function insert(string $nombre, int $idProyecto): int
    {
        static::execute(
            'INSERT INTO fase (nombre_fase, id_proyecto) VALUES (?, ?)',
            [$nombre, $idProyecto]
        );
        return (int) static::lastInsertId();
    }

    /**
     * Get cumplimiento stats per phase, grouped by ficha.
     * Returns: fase, ficha, programa, aprobados, pendientes, total, porcentaje
     */
    public static function cumplimientoPorFase(): array
    {
        return static::query("
            SELECT f.id_fase, f.nombre_fase,
                   fi.id_ficha, fi.nu_ficha, fi.estado as estado_ficha,
                   p.nombre_programa, p.codigo_programa,
                   (SELECT COUNT(DISTINCT ar.id_resultado) 
                    FROM actividad a 
                    JOIN actividad_resultado ar ON a.id_actividad = ar.id_actividad 
                    WHERE a.id_fase = f.id_fase) as total_resultados_fase,
                   COUNT(c.id_calificacion) as total_calificaciones,
                   SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) as aprobados,
                   SUM(CASE WHEN c.jui_evaluativo = 'POR EVALUAR' THEN 1 ELSE 0 END) as pendientes,
                   COALESCE(ROUND(SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(c.id_calificacion), 0), 1), 0) as porcentaje_cumplimiento
            FROM fase f
            JOIN proyecto_formativo pf ON f.id_proyecto = pf.id_proyecto
            JOIN ficha fi ON pf.id_proyecto = fi.id_proyecto
            JOIN programa p ON fi.id_programa = p.id_programa
            LEFT JOIN (
                SELECT DISTINCT a.id_fase, ar.id_resultado
                FROM actividad a
                JOIN actividad_resultado ar ON a.id_actividad = ar.id_actividad
            ) fr ON f.id_fase = fr.id_fase
            LEFT JOIN aprendiz ap ON fi.id_ficha = ap.id_ficha
            LEFT JOIN calificacion c ON fr.id_resultado = c.id_resultado AND ap.id_aprendiz = c.id_aprendiz
            GROUP BY f.id_fase, fi.id_ficha, p.nombre_programa, p.codigo_programa, fi.nu_ficha, fi.estado, f.nombre_fase
            HAVING total_resultados_fase > 0
            ORDER BY fi.nu_ficha, f.id_fase
        ");
    }
}

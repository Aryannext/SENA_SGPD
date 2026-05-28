<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class ProyectoFormativo extends Model
{
    public static function findAll(): array
    {
        return static::query('SELECT * FROM proyecto_formativo ORDER BY nombre_proyecto');
    }

    /**
     * Retrieve all projects joined with their associated program name.
     */
    public static function findAllWithPrograma(): array
    {
        return static::query('
            SELECT pf.*, p.nombre_programa, p.codigo_programa, p.modalidad
            FROM proyecto_formativo pf
            LEFT JOIN programa p ON pf.id_programa = p.id_programa
            ORDER BY pf.nombre_proyecto
        ');
    }

    public static function findById(int $id): ?array
    {
        return static::queryOne('SELECT * FROM proyecto_formativo WHERE id_proyecto = ?', [$id]);
    }

    /**
     * Retrieve a single project with its program info.
     */
    public static function findByIdWithPrograma(int $id): ?array
    {
        return static::queryOne('
            SELECT pf.*, p.nombre_programa, p.codigo_programa, p.modalidad
            FROM proyecto_formativo pf
            LEFT JOIN programa p ON pf.id_programa = p.id_programa
            WHERE pf.id_proyecto = ?
        ', [$id]);
    }

    public static function findByCodigo(string $codigo): ?array
    {
        return static::queryOne('SELECT * FROM proyecto_formativo WHERE codigo_proyecto = ?', [$codigo]);
    }

    public static function insertIgnore(string $codigo, string $nombre): int
    {
        static::execute(
            'INSERT IGNORE INTO proyecto_formativo (codigo_proyecto, nombre_proyecto) VALUES (?, ?)',
            [$codigo, $nombre]
        );
        $row = static::findByCodigo($codigo);
        return $row ? (int) $row['id_proyecto'] : 0;
    }

    /**
     * Create a new project with a linked program.
     */
    public static function create(string $codigo, string $nombre, ?int $idPrograma): int
    {
        static::execute(
            'INSERT INTO proyecto_formativo (codigo_proyecto, nombre_proyecto, id_programa) VALUES (?, ?, ?)',
            [$codigo, $nombre, $idPrograma]
        );
        $row = static::findByCodigo($codigo);
        return $row ? (int) $row['id_proyecto'] : 0;
    }

    public static function updatePdf(int $id, ?string $rutaPdf): void
    {
        static::execute('UPDATE proyecto_formativo SET ruta_pdf = ? WHERE id_proyecto = ?', [$rutaPdf, $id]);
    }

    /**
     * Count the number of phases linked to a project.
     */
    public static function countFases(int $idProyecto): int
    {
        $row = static::queryOne('SELECT COUNT(*) as n FROM fase WHERE id_proyecto = ?', [$idProyecto]);
        return (int) ($row['n'] ?? 0);
    }

    /**
     * Count the number of RA linked through activities of this project's phases.
     */
    public static function countResultadosVinculados(int $idProyecto): int
    {
        $row = static::queryOne('
            SELECT COUNT(DISTINCT ar.id_resultado) as n
            FROM fase f
            JOIN actividad a ON f.id_fase = a.id_fase
            JOIN actividad_resultado ar ON a.id_actividad = ar.id_actividad
            WHERE f.id_proyecto = ?
        ', [$idProyecto]);
        return (int) ($row['n'] ?? 0);
    }
}

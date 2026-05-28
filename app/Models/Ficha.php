<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Ficha extends Model
{
    public static function findAll(): array
    {
        return static::query(
            'SELECT f.*, p.nombre_programa FROM ficha f
             LEFT JOIN programa p ON f.id_programa = p.id_programa
             ORDER BY f.nu_ficha'
        );
    }

    public static function findById(int $id): ?array
    {
        return static::queryOne('SELECT * FROM ficha WHERE id_ficha = ?', [$id]);
    }

    public static function findByNumero(string $numero): ?array
    {
        return static::queryOne('SELECT * FROM ficha WHERE nu_ficha = ?', [$numero]);
    }

    public static function insertIgnore(
        string $numero,
        string $estado,
        ?string $fechaInicio,
        ?string $fechaFin,
        int $idPrograma,
        ?int $idProyecto = null
    ): int {
        static::execute(
            'INSERT IGNORE INTO ficha (nu_ficha, estado, fecha_inicio, fecha_fin, id_programa, id_proyecto)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$numero, $estado, $fechaInicio, $fechaFin, $idPrograma, $idProyecto]
        );
        $row = static::findByNumero($numero);
        return $row ? (int) $row['id_ficha'] : 0;
    }

    /**
     * Link all fichas of a specific program to a project.
     */
    public static function linkToProjectByProgram(int $idProyecto, int $idPrograma): void
    {
        static::execute(
            'UPDATE ficha SET id_proyecto = ? WHERE id_programa = ? AND id_proyecto IS NULL',
            [$idProyecto, $idPrograma]
        );
    }
}

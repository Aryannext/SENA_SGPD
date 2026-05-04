<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Programa extends Model
{
    public static function findAll(): array
    {
        return static::query('SELECT * FROM programa ORDER BY nombre_programa');
    }

    /**
     * Retrieve all programs with summary counts.
     */
    public static function findAllWithStats(): array
    {
        return static::query("
            SELECT p.*,
                   COUNT(DISTINCT f.id_ficha) as total_fichas,
                   COUNT(DISTINCT a.id_aprendiz) as total_aprendices,
                   SUM(CASE WHEN a.estado LIKE '%FORMACION%' THEN 1 ELSE 0 END) as activos,
                   SUM(CASE WHEN a.estado LIKE '%RETIRO%' OR a.estado LIKE '%CANCELADO%' THEN 1 ELSE 0 END) as retirados
            FROM programa p
            LEFT JOIN ficha f ON p.id_programa = f.id_programa
            LEFT JOIN aprendiz a ON f.id_ficha = a.id_ficha
            GROUP BY p.id_programa
            ORDER BY p.nombre_programa
        ");
    }

    public static function findById(int $id): ?array
    {
        return static::queryOne('SELECT * FROM programa WHERE id_programa = ?', [$id]);
    }

    /**
     * Get a program with its summary stats.
     */
    public static function findByIdWithStats(int $id): ?array
    {
        return static::queryOne("
            SELECT p.*,
                   COUNT(DISTINCT f.id_ficha) as total_fichas,
                   COUNT(DISTINCT a.id_aprendiz) as total_aprendices,
                   SUM(CASE WHEN a.estado LIKE '%FORMACION%' THEN 1 ELSE 0 END) as activos,
                   SUM(CASE WHEN a.estado LIKE '%RETIRO%' OR a.estado LIKE '%CANCELADO%' THEN 1 ELSE 0 END) as retirados
            FROM programa p
            LEFT JOIN ficha f ON p.id_programa = f.id_programa
            LEFT JOIN aprendiz a ON f.id_ficha = a.id_ficha
            WHERE p.id_programa = ?
            GROUP BY p.id_programa
        ", [$id]);
    }

    public static function findByCodigo(string $codigo): ?array
    {
        return static::queryOne('SELECT * FROM programa WHERE codigo_programa = ?', [$codigo]);
    }

    public static function insertIgnore(string $codigo, string $nombre, string $modalidad): int
    {
        static::execute(
            'INSERT IGNORE INTO programa (codigo_programa, nombre_programa, modalidad) VALUES (?, ?, ?)',
            [$codigo, $nombre, $modalidad]
        );
        $row = static::findByCodigo($codigo);
        return $row ? (int) $row['id_programa'] : 0;
    }

    /**
     * Get fichas belonging to a program, with counts.
     */
    public static function getFichasWithStats(int $idPrograma): array
    {
        return static::query("
            SELECT f.*,
                   COUNT(DISTINCT a.id_aprendiz) as total_aprendices,
                   SUM(CASE WHEN a.estado LIKE '%FORMACION%' THEN 1 ELSE 0 END) as activos,
                   SUM(CASE WHEN a.estado LIKE '%RETIRO%' OR a.estado LIKE '%CANCELADO%' THEN 1 ELSE 0 END) as retirados,
                   SUM(CASE WHEN a.estado LIKE '%TRASLADADO%' THEN 1 ELSE 0 END) as trasladados
            FROM ficha f
            LEFT JOIN aprendiz a ON f.id_ficha = a.id_ficha
            WHERE f.id_programa = ?
            GROUP BY f.id_ficha
            ORDER BY f.nu_ficha
        ", [$idPrograma]);
    }

    /**
     * Get avance global for a ficha.
     */
    public static function getAvanceFicha(int $idFicha): array
    {
        $row = static::queryOne("
            SELECT COUNT(c.id_calificacion) as total,
                   SUM(CASE WHEN c.jui_evaluativo = 'APROBADO' THEN 1 ELSE 0 END) as aprobados,
                   SUM(CASE WHEN c.jui_evaluativo = 'POR EVALUAR' THEN 1 ELSE 0 END) as pendientes
            FROM calificacion c
            JOIN aprendiz a ON c.id_aprendiz = a.id_aprendiz
            WHERE a.id_ficha = ?
        ", [$idFicha]);
        $total = (int) ($row['total'] ?? 0);
        $aprobados = (int) ($row['aprobados'] ?? 0);
        $pendientes = (int) ($row['pendientes'] ?? 0);
        $pct = $total > 0 ? round($aprobados * 100.0 / $total, 1) : 0;
        return ['total' => $total, 'aprobados' => $aprobados, 'pendientes' => $pendientes, 'porcentaje' => $pct];
    }
}

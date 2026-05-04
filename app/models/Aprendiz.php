<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Aprendiz extends Model
{
    public static function findAll(): array
    {
        return static::query(
            'SELECT a.*, f.nu_ficha FROM aprendiz a
             LEFT JOIN ficha f ON a.id_ficha = f.id_ficha
             ORDER BY a.apellido, a.nombre'
        );
    }

    public static function findById(int $id): ?array
    {
        return static::queryOne(
            'SELECT a.*, f.nu_ficha, p.nombre_programa
             FROM aprendiz a
             LEFT JOIN ficha f ON a.id_ficha = f.id_ficha
             LEFT JOIN programa p ON f.id_programa = p.id_programa
             WHERE a.id_aprendiz = ?',
            [$id]
        );
    }

    public static function findByDocumento(string $documento): ?array
    {
        return static::queryOne('SELECT * FROM aprendiz WHERE nu_documento = ?', [$documento]);
    }

    public static function insertIgnore(
        string $tiDoc,
        string $nuDoc,
        string $nombre,
        string $apellido,
        string $estado,
        int $idFicha
    ): int {
        static::execute(
            'INSERT IGNORE INTO aprendiz (ti_documento, nu_documento, nombre, apellido, estado, id_ficha)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$tiDoc, $nuDoc, $nombre, $apellido, $estado, $idFicha]
        );
        $row = static::findByDocumento($nuDoc);
        return $row ? (int) $row['id_aprendiz'] : 0;
    }

    public static function countByEstado(): array
    {
        return static::query(
            'SELECT estado, COUNT(*) as total FROM aprendiz GROUP BY estado ORDER BY total DESC'
        );
    }

    public static function countTotal(): int
    {
        $row = static::queryOne('SELECT COUNT(*) as total FROM aprendiz');
        return $row ? (int) $row['total'] : 0;
    }
}

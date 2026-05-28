<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class ResultadoAprendizaje extends Model
{
    public static function findAll(): array
    {
        return static::query(
            'SELECT r.*, c.cod_competencia, c.nombre as nombre_competencia
             FROM resultado_aprendizaje r
             LEFT JOIN competencia c ON r.id_competencia = c.id_competencia
             ORDER BY r.cod_resultado'
        );
    }

    public static function findById(int $id): ?array
    {
        return static::queryOne('SELECT * FROM resultado_aprendizaje WHERE id_resultado = ?', [$id]);
    }

    public static function findByCodigo(string $codigo): ?array
    {
        return static::queryOne('SELECT * FROM resultado_aprendizaje WHERE cod_resultado = ?', [$codigo]);
    }

    /**
     * Find an RA by code, but ONLY if it belongs to a competency linked to the given program.
     * This prevents cross-program contamination when parsing project PDFs.
     */
    public static function findByCodigoAndPrograma(string $codigo, int $idPrograma): ?array
    {
        return static::queryOne('
            SELECT DISTINCT r.*
            FROM resultado_aprendizaje r
            JOIN competencia c ON r.id_competencia = c.id_competencia
            JOIN programa_competencia pc ON c.id_competencia = pc.id_competencia
            WHERE r.cod_resultado = ? AND pc.id_programa = ?
        ', [$codigo, $idPrograma]);
    }

    public static function insertIgnore(string $codigo, string $nombre, int $idCompetencia): int
    {
        static::execute(
            'INSERT IGNORE INTO resultado_aprendizaje (cod_resultado, nombre_resultado, id_competencia)
             VALUES (?, ?, ?)',
            [$codigo, $nombre, $idCompetencia]
        );
        $row = static::findByCodigo($codigo);
        return $row ? (int) $row['id_resultado'] : 0;
    }

    public static function countTotal(): int
    {
        $row = static::queryOne('SELECT COUNT(*) as total FROM resultado_aprendizaje');
        return $row ? (int) $row['total'] : 0;
    }
}

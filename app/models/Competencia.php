<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Competencia extends Model
{
    public static function findAll(): array
    {
        return static::query('SELECT * FROM competencia ORDER BY cod_competencia');
    }

    public static function findById(int $id): ?array
    {
        return static::queryOne('SELECT * FROM competencia WHERE id_competencia = ?', [$id]);
    }

    public static function findByCodigo(string $codigo): ?array
    {
        return static::queryOne('SELECT * FROM competencia WHERE cod_competencia = ?', [$codigo]);
    }

    public static function insertIgnore(string $codigo, string $nombre): int
    {
        static::execute(
            'INSERT IGNORE INTO competencia (cod_competencia, nombre) VALUES (?, ?)',
            [$codigo, $nombre]
        );
        $row = static::findByCodigo($codigo);
        return $row ? (int) $row['id_competencia'] : 0;
    }

    public static function asignarPrograma(int $idPrograma, int $idCompetencia): void
    {
        static::execute(
            'INSERT IGNORE INTO programa_competencia (id_programa, id_competencia) VALUES (?, ?)',
            [$idPrograma, $idCompetencia]
        );
    }
}

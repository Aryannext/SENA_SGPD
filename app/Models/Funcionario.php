<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Funcionario extends Model
{
    public static function findByDocumento(string $documento): ?array
    {
        return static::queryOne('SELECT * FROM funcionario WHERE nu_documento = ?', [$documento]);
    }

    public static function insertIgnore(string $tiDoc, string $nuDoc, string $nombre): int
    {
        static::execute(
            'INSERT IGNORE INTO funcionario (ti_documento, nu_documento, nombre) VALUES (?, ?, ?)',
            [$tiDoc, $nuDoc, $nombre]
        );
        $row = static::findByDocumento($nuDoc);
        return $row ? (int) $row['id_funcionario'] : 0;
    }
}

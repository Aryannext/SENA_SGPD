<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

/**
 * Usuarios del sistema.
 *
 * Se mantiene aparte de `funcionario` a propósito: los funcionarios llegan
 * importados del Excel de Sofía Plus y son quienes registran juicios, no
 * necesariamente quienes usan el sistema. Mezclar credenciales en una tabla que
 * el importador escribe sería pedir un accidente. La relación es opcional, por
 * si un usuario corresponde a un funcionario conocido.
 */
class Usuario extends Model
{
    public static function findByUsuario(string $usuario): ?array
    {
        return static::queryOne(
            'SELECT * FROM usuario WHERE usuario = ? LIMIT 1',
            [$usuario]
        );
    }

    public static function findById(int $id): ?array
    {
        return static::queryOne('SELECT * FROM usuario WHERE id_usuario = ?', [$id]);
    }

    public static function findAll(): array
    {
        return static::query(
            'SELECT u.id_usuario, u.usuario, u.nombre, u.rol, u.activo, u.ultimo_acceso, f.nombre AS funcionario
               FROM usuario u
               LEFT JOIN funcionario f ON u.id_funcionario = f.id_funcionario
              ORDER BY u.usuario'
        );
    }

    public static function contarTotal(): int
    {
        $fila = static::queryOne('SELECT COUNT(*) AS n FROM usuario');
        return (int) ($fila['n'] ?? 0);
    }

    /**
     * Crea un usuario. La contraseña se recibe en claro y se almacena hasheada:
     * nunca se guarda ni se registra el valor original.
     */
    public static function crear(
        string $usuario,
        string $contrasena,
        string $nombre,
        string $rol = 'INSTRUCTOR',
        ?int $idFuncionario = null
    ): int {
        static::execute(
            'INSERT INTO usuario (usuario, password_hash, nombre, rol, id_funcionario)
             VALUES (?, ?, ?, ?, ?)',
            [$usuario, password_hash($contrasena, PASSWORD_DEFAULT), $nombre, $rol, $idFuncionario]
        );

        $fila = static::findByUsuario($usuario);
        return $fila ? (int) $fila['id_usuario'] : 0;
    }

    public static function cambiarContrasena(int $idUsuario, string $contrasena): void
    {
        static::execute(
            'UPDATE usuario SET password_hash = ? WHERE id_usuario = ?',
            [password_hash($contrasena, PASSWORD_DEFAULT), $idUsuario]
        );
    }

    public static function registrarAcceso(int $idUsuario): void
    {
        static::execute(
            'UPDATE usuario SET ultimo_acceso = CURRENT_TIMESTAMP WHERE id_usuario = ?',
            [$idUsuario]
        );
    }
}

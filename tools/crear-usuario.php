<?php

declare(strict_types=1);

/**
 * Crea un usuario del sistema desde la terminal.
 *
 *   php tools/crear-usuario.php <usuario> [ROL]
 *
 * El rol puede ser ADMIN, COORDINADOR o INSTRUCTOR (por defecto INSTRUCTOR).
 *
 * Se hace por consola y no por una pantalla web a propósito: un instalador web
 * que cree al primer administrador es una puerta abierta si alguien olvida
 * borrarlo. La contraseña se pide de forma interactiva y no se acepta como
 * argumento, para que no quede registrada en el historial de la terminal.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Esta herramienta solo se ejecuta desde la terminal.\n");
}

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Usuario;
use Core\Auth;

$usuario = $argv[1] ?? '';
$rol     = strtoupper($argv[2] ?? 'INSTRUCTOR');

if ($usuario === '') {
    exit("Uso: php tools/crear-usuario.php <usuario> [ADMIN|COORDINADOR|INSTRUCTOR]\n");
}

if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $usuario)) {
    exit("El usuario debe tener entre 3 y 50 caracteres: letras, números, punto, guion o guion bajo.\n");
}

if (!in_array($rol, Auth::ROLES, true)) {
    exit("Rol no válido: {$rol}. Debe ser " . implode(', ', Auth::ROLES) . ".\n");
}

try {
    if (Usuario::findByUsuario($usuario) !== null) {
        exit("Ya existe un usuario llamado «{$usuario}».\n");
    }
} catch (Throwable $e) {
    exit("No se pudo consultar la base de datos: {$e->getMessage()}\n"
        . "Revisa config/database.php y que la tabla `usuario` exista\n"
        . "(docs/migraciones/2026-09-10_usuarios.sql).\n");
}

echo "Nombre completo: ";
$nombre = trim((string) fgets(STDIN));
if ($nombre === '') {
    $nombre = $usuario;
}

/** Lee una contraseña sin mostrarla en pantalla cuando el sistema lo permite. */
$leerClave = static function (string $etiqueta): string {
    echo $etiqueta;

    if (DIRECTORY_SEPARATOR !== '\\' && stream_isatty(STDIN)) {
        shell_exec('stty -echo');
        $clave = trim((string) fgets(STDIN));
        shell_exec('stty echo');
        echo "\n";
        return $clave;
    }

    // En Windows no hay forma portable de ocultarla; se avisa.
    $clave = trim((string) fgets(STDIN));
    return $clave;
};

$clave = $leerClave('Contraseña (mínimo 8 caracteres): ');
if (strlen($clave) < 8) {
    exit("La contraseña debe tener al menos 8 caracteres.\n");
}

$confirmacion = $leerClave('Repite la contraseña: ');
if (!hash_equals($clave, $confirmacion)) {
    exit("Las contraseñas no coinciden.\n");
}

try {
    $id = Usuario::crear($usuario, $clave, $nombre, $rol);
} catch (Throwable $e) {
    exit("No se pudo crear el usuario: {$e->getMessage()}\n");
}

echo "\nUsuario «{$usuario}» creado con rol {$rol} (id {$id}).\n";
echo "Ya puedes iniciar sesión en /SENA_SGPD/login\n";

<?php

declare(strict_types=1);

/**
 * Prepara la base de datos: crea el esquema si está vacía y aplica las
 * migraciones pendientes.
 *
 *   php tools/migrar.php
 *
 * Es idempotente: ejecutarlo dos veces no cambia nada. Por eso el contenedor lo
 * llama en cada arranque —así un despliegue nuevo levanta con la base al día sin
 * intervención manual— y se puede ejecutar a mano sin miedo.
 *
 * Las migraciones aplicadas se registran en la tabla `migracion`, de modo que
 * añadir un archivo a docs/migraciones/ basta para que se aplique en el próximo
 * arranque.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Esta herramienta solo se ejecuta desde la terminal.\n");
}

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Env;

Env::cargar();

$config = require __DIR__ . '/../config/database.php';
$raiz   = dirname(__DIR__);

/** Divide un archivo SQL en sentencias, ignorando comentarios de línea. */
function sentencias(string $sql): array
{
    $sql = preg_replace('/^\s*--[^\n]*$/m', '', $sql) ?? $sql;

    return array_values(array_filter(
        array_map('trim', explode(';', $sql)),
        static fn(string $s): bool => $s !== ''
    ));
}

function paso(string $texto): void
{
    echo '  ', $texto, "\n";
}

// ── Conexión ────────────────────────────────────────────────────────────────
$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $config['host'],
    $config['port'],
    $config['dbname'],
    $config['charset']
);

try {
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
} catch (Throwable $e) {
    fwrite(STDERR, "No se pudo conectar a la base de datos {$config['dbname']} en {$config['host']}:{$config['port']}\n");
    fwrite(STDERR, "  {$e->getMessage()}\n");
    exit(1);
}

echo "Base de datos: {$config['dbname']} en {$config['host']}:{$config['port']}\n";

// ── Esquema inicial ─────────────────────────────────────────────────────────
$tablas = $pdo->query("SHOW TABLES LIKE 'aprendiz'")->fetchAll();

if ($tablas === []) {
    paso('Base vacía: creando el esquema desde docs/SGPD_SENA.sql');

    $sql = file_get_contents($raiz . '/docs/SGPD_SENA.sql');
    if ($sql === false) {
        fwrite(STDERR, "No se encontró docs/SGPD_SENA.sql\n");
        exit(1);
    }

    // El script fija la base de producción; aquí ya estamos conectados a la que toca.
    $sql = preg_replace('/^\s*(CREATE DATABASE|USE)\b[^;]*;/mi', '', $sql) ?? $sql;

    $creadas = 0;
    foreach (sentencias($sql) as $sentencia) {
        $pdo->exec($sentencia);
        $creadas++;
    }
    paso("Esquema creado: {$creadas} sentencias");
} else {
    paso('El esquema ya existe');
}

// ── Registro de migraciones ─────────────────────────────────────────────────
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migracion (
        archivo    VARCHAR(190) PRIMARY KEY,
        aplicada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
     ) ENGINE=InnoDB'
);

$aplicadas = array_column(
    $pdo->query('SELECT archivo FROM migracion')->fetchAll(),
    'archivo'
);

$pendientes = glob($raiz . '/docs/migraciones/*.sql') ?: [];
sort($pendientes);

$nuevas = 0;
foreach ($pendientes as $ruta) {
    $nombre = basename($ruta);
    if (in_array($nombre, $aplicadas, true)) {
        continue;
    }

    paso("Aplicando {$nombre}");

    foreach (sentencias((string) file_get_contents($ruta)) as $sentencia) {
        try {
            $pdo->exec($sentencia);
        } catch (PDOException $e) {
            // Una migración puede haberse aplicado a mano antes de existir el
            // registro: los errores de «ya existe» no son un fallo.
            $duplicado = in_array($e->errorInfo[1] ?? 0, [1050, 1060, 1061, 1091, 1826], true);
            if (!$duplicado) {
                fwrite(STDERR, "  Error en {$nombre}: {$e->getMessage()}\n");
                exit(1);
            }
        }
    }

    $pdo->prepare('INSERT INTO migracion (archivo) VALUES (?)')->execute([$nombre]);
    $nuevas++;
}

paso($nuevas === 0 ? 'Sin migraciones pendientes' : "{$nuevas} migración(es) aplicada(s)");

// ── Aviso final ─────────────────────────────────────────────────────────────
$usuarios = (int) $pdo->query('SELECT COUNT(*) FROM usuario')->fetchColumn();
if ($usuarios === 0) {
    echo "\n  ⚠  No hay ningún usuario. Crea el primero con:\n";
    echo "     php tools/crear-usuario.php admin ADMIN\n";
}

echo "\nBase de datos lista.\n";

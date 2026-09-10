<?php

declare(strict_types=1);

/**
 * Ejecutor de la suite de pruebas.
 *
 *   php tests/run.php              todas las pruebas
 *   php tests/run.php Unit         solo las de un directorio
 *   php tests/run.php RutasTest    solo una clase
 *
 * Devuelve código de salida 0 si todo pasa y 1 si algo falla, de modo que sirve
 * tal cual en un hook de pre-commit o en integración continua.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/TestCase.php';

const RAIZ = __DIR__ . '/..';

$filtro = $argv[1] ?? '';

// ── Descubrir los archivos de prueba ────────────────────────────────────────
$archivos = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));
foreach ($it as $archivo) {
    if ($archivo->isFile() && str_ends_with($archivo->getFilename(), 'Test.php')) {
        $archivos[] = $archivo->getPathname();
    }
}
sort($archivos);

if ($filtro !== '') {
    $archivos = array_values(array_filter(
        $archivos,
        static fn(string $a): bool => str_contains(str_replace('\\', '/', $a), $filtro)
    ));
}

if ($archivos === []) {
    fwrite(STDERR, "No se encontraron pruebas" . ($filtro ? " para el filtro «{$filtro}»" : '') . ".\n");
    exit(1);
}

// ── Ejecutar ────────────────────────────────────────────────────────────────
$totalCasos = 0;
$totalPasan = 0;
$totalFallan = 0;
$totalOmitidos = 0;
$fallos = [];
$inicio = microtime(true);

foreach ($archivos as $archivo) {
    $clasesAntes = get_declared_classes();
    require_once $archivo;
    $nuevas = array_diff(get_declared_classes(), $clasesAntes);

    foreach ($nuevas as $clase) {
        if (!is_subclass_of($clase, Tests\TestCase::class)) {
            continue;
        }

        $corto = substr($clase, strrpos($clase, '\\') + 1);
        /** @var Tests\TestCase $instancia */
        $instancia = new $clase();

        $motivo = $instancia->prepararClase();
        if ($motivo !== null) {
            printf("\n  %s\n    ~ omitida: %s\n", $corto, $motivo);
            $totalOmitidos++;
            continue;
        }

        printf("\n  %s\n", $corto);

        $metodos = array_filter(
            get_class_methods($instancia),
            static fn(string $m): bool => str_starts_with($m, 'test')
        );

        foreach ($metodos as $metodo) {
            $instancia->resultados = [];
            $error = null;

            try {
                $instancia->$metodo();
            } catch (Throwable $e) {
                $error = get_class($e) . ': ' . $e->getMessage();
            }

            $malas = array_filter($instancia->resultados, static fn(array $r): bool => !$r['ok']);
            $totalCasos++;

            if ($error !== null) {
                $totalFallan++;
                printf("    \xE2\x9C\x97 %s\n", $metodo);
                printf("        excepción: %s\n", $error);
                $fallos[] = "{$corto}::{$metodo} — excepción: {$error}";
            } elseif ($malas !== []) {
                $totalFallan++;
                printf("    \xE2\x9C\x97 %s\n", $metodo);
                foreach ($malas as $r) {
                    printf("        %s\n", $r['mensaje']);
                    $fallos[] = "{$corto}::{$metodo} — {$r['mensaje']}";
                }
            } else {
                $totalPasan++;
                printf("    \xE2\x9C\x93 %s (%d aserciones)\n", $metodo, count($instancia->resultados));
            }
        }

        $instancia->limpiarClase();
    }
}

// ── Resumen ─────────────────────────────────────────────────────────────────
$duracion = round(microtime(true) - $inicio, 2);

echo "\n", str_repeat('─', 68), "\n";
printf("  %d casos · %d pasan · %d fallan · %d clases omitidas · %ss\n",
    $totalCasos, $totalPasan, $totalFallan, $totalOmitidos, $duracion);

if ($fallos !== []) {
    echo "\n  Fallos:\n";
    foreach ($fallos as $i => $f) {
        printf("    %d) %s\n", $i + 1, $f);
    }
}

echo str_repeat('─', 68), "\n";

exit($totalFallan > 0 ? 1 : 0);

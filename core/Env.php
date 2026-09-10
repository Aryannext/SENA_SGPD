<?php

declare(strict_types=1);

namespace Core;

/**
 * Variables de entorno.
 *
 * En un contenedor la configuración llega por el entorno: Dokploy inyecta las
 * variables al arrancar y la imagen es la misma en desarrollo y en producción.
 * Para trabajar en local se admite además un archivo `.env` en la raíz, que
 * nunca se versiona.
 *
 * Sin dependencias externas a propósito: son treinta líneas y el proyecto se
 * construyó sin frameworks.
 */
final class Env
{
    /** @var array<string, string> */
    private static array $vars = [];
    private static bool $cargado = false;

    /** Lee el archivo .env si existe. El entorno real siempre tiene prioridad. */
    public static function cargar(?string $archivo = null): void
    {
        if (self::$cargado) {
            return;
        }
        self::$cargado = true;

        $archivo ??= dirname(__DIR__) . '/.env';
        if (!is_readable($archivo)) {
            return;
        }

        foreach (file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $linea) {
            $linea = trim($linea);
            if ($linea === '' || str_starts_with($linea, '#')) {
                continue;
            }

            $partes = explode('=', $linea, 2);
            if (count($partes) !== 2) {
                continue;
            }

            $clave  = trim($partes[0]);
            $valor  = trim($partes[1]);

            // Se admiten comillas alrededor del valor.
            if (strlen($valor) > 1 && (
                ($valor[0] === '"' && str_ends_with($valor, '"')) ||
                ($valor[0] === "'" && str_ends_with($valor, "'"))
            )) {
                $valor = substr($valor, 1, -1);
            }

            self::$vars[$clave] = $valor;
        }
    }

    public static function get(string $clave, ?string $porDefecto = null): ?string
    {
        self::cargar();

        // El entorno del proceso manda sobre el archivo .env.
        $valor = getenv($clave);
        if ($valor === false) {
            $valor = $_ENV[$clave] ?? self::$vars[$clave] ?? null;
        }

        if ($valor === null || $valor === '') {
            return $porDefecto;
        }

        return (string) $valor;
    }

    public static function int(string $clave, int $porDefecto = 0): int
    {
        $valor = self::get($clave);
        return $valor === null ? $porDefecto : (int) $valor;
    }

    public static function bool(string $clave, bool $porDefecto = false): bool
    {
        $valor = self::get($clave);
        if ($valor === null) {
            return $porDefecto;
        }

        return in_array(strtolower($valor), ['1', 'true', 'yes', 'on', 'si', 'sí'], true);
    }

    /** Solo para las pruebas: reinicia el estado cargado. */
    public static function reiniciar(): void
    {
        self::$vars = [];
        self::$cargado = false;
    }
}

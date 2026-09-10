<?php

declare(strict_types=1);

namespace Core;

/**
 * Acceso a la configuración general y a las rutas del sistema de archivos.
 *
 * Antes la ruta base `/SENA_SGPD` estaba escrita a mano en treinta sitios de
 * quince archivos, y el documento de despliegue pedía editarlos uno a uno para
 * publicar en producción. Ahora vive en una sola variable de entorno.
 */
final class App
{
    /** @var array<string, mixed>|null */
    private static ?array $config = null;

    /** @return array<string, mixed> */
    private static function config(): array
    {
        if (self::$config === null) {
            self::$config = require dirname(__DIR__) . '/config/app.php';
        }

        return self::$config;
    }

    /** Subdirectorio en el que vive la aplicación, sin barra final. */
    public static function basePath(): string
    {
        return (string) self::config()['base_path'];
    }

    /**
     * Construye una URL interna anteponiendo la ruta base.
     *
     *   App::url('/dashboard')  →  /dashboard        en un subdominio
     *                           →  /SENA_SGPD/dashboard  bajo XAMPP
     */
    public static function url(string $ruta = ''): string
    {
        if ($ruta !== '' && !str_starts_with($ruta, '/')) {
            $ruta = '/' . $ruta;
        }

        return self::basePath() . $ruta;
    }

    /** Directorio de almacenamiento, fuera de la raíz web. */
    public static function storagePath(string $sub = ''): string
    {
        $base = (string) self::config()['storage_path'];

        return $sub === '' ? $base : $base . '/' . ltrim($sub, '/');
    }

    /** Directorio de archivos subidos, creado si no existe. */
    public static function uploadsPath(): string
    {
        return self::asegurarDirectorio(self::storagePath('uploads'));
    }

    /** Directorio de audio generado por el servicio de voz. */
    public static function audioPath(): string
    {
        return self::asegurarDirectorio(self::storagePath('audio'));
    }

    public static function maxUploadBytes(): int
    {
        return ((int) self::config()['max_upload_mb']) * 1024 * 1024;
    }

    public static function debug(): bool
    {
        return (bool) self::config()['debug'];
    }

    private static function asegurarDirectorio(string $ruta): string
    {
        if (!is_dir($ruta)) {
            mkdir($ruta, 0755, true);
        }

        return $ruta;
    }

    /** Solo para las pruebas: fuerza la relectura de la configuración. */
    public static function reiniciar(): void
    {
        self::$config = null;
    }
}

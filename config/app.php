<?php

declare(strict_types=1);

use Core\Env;

/**
 * Configuración general de la aplicación.
 *
 * Todo se resuelve por variables de entorno para que la misma imagen de Docker
 * sirva en local y en el VPS sin tocar una línea de código.
 */
return [
    /**
     * Subdirectorio en el que vive la aplicación, sin barra final.
     *
     *   ''            cuando ocupa la raíz de un dominio o subdominio (Docker)
     *   '/SENA_SGPD'  cuando vive en una subcarpeta (XAMPP local)
     */
    'base_path' => rtrim((string) Env::get('APP_BASE_PATH', ''), '/'),

    /**
     * Directorio de archivos subidos y de audio generado.
     *
     * Vive FUERA de la raíz web: los reportes de Sofía Plus contienen datos
     * personales y no pueden quedar expuestos a quien adivine un nombre de
     * archivo (vulnerabilidad V-04). Se sirven desde `ArchivoController`, que
     * exige sesión iniciada.
     *
     * En Docker es un volumen montado, porque el contenedor es efímero.
     */
    'storage_path' => rtrim(
        (string) Env::get('APP_STORAGE_PATH', dirname(__DIR__) . '/storage'),
        '/\\'
    ),

    /** Tamaño máximo admitido en las cargas, en megabytes. */
    'max_upload_mb' => Env::int('APP_MAX_UPLOAD_MB', 20),

    /**
     * Muestra los errores en pantalla. En producción debe quedar desactivado:
     * los mensajes de excepción filtran rutas del sistema de archivos y
     * estructura de la base de datos.
     */
    'debug' => Env::bool('APP_DEBUG', false),
];

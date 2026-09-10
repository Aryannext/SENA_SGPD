<?php

declare(strict_types=1);

namespace App\Services;

use Core\App;

/**
 * Recepcion segura de archivos subidos.
 *
 * Antes solo se miraba la extension del nombre: un archivo cualquiera renombrado
 * a .xls entraba al servidor y quedaba en un directorio servido por Apache, con
 * un nombre enumerable del tipo import_<timestamp>.xls (vulnerabilidad V-04).
 *
 * Ahora se comprueba el error de la carga, el tamano y el tipo MIME real del
 * contenido, y el archivo se guarda con un nombre aleatorio fuera de la raiz web.
 */
final class CargaArchivoService
{
    /** Tipos admitidos por extension declarada. */
    private const TIPOS = [
        'xls'  => ['application/vnd.ms-excel', 'application/msword', 'application/octet-stream', 'application/CDFV2'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'pdf'  => ['application/pdf'],
    ];

    /**
     * Valida y almacena. Devuelve la ruta absoluta del archivo guardado.
     *
     * @param array<string, mixed> $archivo Una entrada de $_FILES
     * @param array<int, string>   $extensiones Extensiones admitidas
     * @throws \RuntimeException con un mensaje apto para mostrar al usuario
     */
    public function recibir(array $archivo, array $extensiones, string $prefijo): string
    {
        $this->comprobarError($archivo['error'] ?? UPLOAD_ERR_NO_FILE);

        $tmp = (string) ($archivo['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new \RuntimeException('El archivo no llego correctamente. Intentalo de nuevo.');
        }

        $tamano = (int) ($archivo['size'] ?? 0);
        if ($tamano <= 0) {
            throw new \RuntimeException('El archivo esta vacio.');
        }
        if ($tamano > App::maxUploadBytes()) {
            throw new \RuntimeException(sprintf(
                'El archivo pesa %s y el limite es %s.',
                $this->formatear($tamano),
                $this->formatear(App::maxUploadBytes())
            ));
        }

        $extension = strtolower(pathinfo((string) ($archivo['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, $extensiones, true)) {
            throw new \RuntimeException(
                'Solo se admiten archivos ' . implode(' o ', array_map(static fn($e) => '.' . $e, $extensiones)) . '.'
            );
        }

        $this->comprobarContenido($tmp, $extension);

        // Nombre aleatorio: el patron anterior se podia enumerar por fecha.
        $destino = App::uploadsPath() . '/' . $prefijo . '_' . bin2hex(random_bytes(16)) . '.' . $extension;

        if (!move_uploaded_file($tmp, $destino)) {
            throw new \RuntimeException('No se pudo guardar el archivo en el servidor.');
        }

        chmod($destino, 0644);

        return $destino;
    }

    /** El tipo MIME real del contenido, no el que declara el nombre. */
    private function comprobarContenido(string $ruta, string $extension): void
    {
        if (!function_exists('finfo_open')) {
            return; // sin ext-fileinfo no se puede comprobar; el resto de controles siguen
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return;
        }

        $mime = finfo_file($finfo, $ruta);
        finfo_close($finfo);

        $admitidos = self::TIPOS[$extension] ?? [];
        if ($mime === false || $admitidos === [] || in_array($mime, $admitidos, true)) {
            return;
        }

        throw new \RuntimeException(sprintf(
            'El contenido del archivo no corresponde a un .%s (se detecto %s).',
            $extension,
            $mime
        ));
    }

    private function comprobarError(int $codigo): void
    {
        $mensaje = match ($codigo) {
            UPLOAD_ERR_OK         => null,
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el tamano maximo permitido por el servidor.',
            UPLOAD_ERR_PARTIAL    => 'La carga se interrumpio antes de terminar.',
            UPLOAD_ERR_NO_FILE    => 'No se recibio ningun archivo.',
            UPLOAD_ERR_NO_TMP_DIR => 'El servidor no tiene directorio temporal configurado.',
            UPLOAD_ERR_CANT_WRITE => 'El servidor no pudo escribir el archivo en disco.',
            UPLOAD_ERR_EXTENSION  => 'Una extension de PHP detuvo la carga.',
            default               => 'Error desconocido al subir el archivo.',
        };

        if ($mensaje !== null) {
            throw new \RuntimeException($mensaje);
        }
    }

    private function formatear(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576, 1) . ' MB'
            : round($bytes / 1024) . ' KB';
    }
}
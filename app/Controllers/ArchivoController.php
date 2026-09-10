<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\App;
use Core\Controller;
use App\Models\ProyectoFormativo;

/**
 * Entrega los archivos guardados fuera de la raíz web.
 *
 * Antes, `public/uploads/` vivía bajo la raíz y el `.htaccess` servía cualquier
 * archivo existente: los reportes de Sofía Plus quedaban descargables por quien
 * adivinara un nombre del tipo `import_<timestamp>.xls` (vulnerabilidad V-04).
 * Ahora nada de eso es alcanzable por URL directa; solo se sirve lo que este
 * controlador decide, y solo a quien tenga sesión iniciada.
 *
 * Que el directorio esté fuera de la raíz resuelve además la persistencia en
 * Docker: es un único volumen montado y el contenedor puede recrearse sin
 * perder los archivos.
 */
final class ArchivoController extends Controller
{
    /** El PDF del proyecto formativo. */
    public function proyecto(string $id): void
    {
        $proyecto = ProyectoFormativo::findById((int) $id);

        if ($proyecto === null || empty($proyecto['ruta_pdf'])) {
            $this->noEncontrado();
            return;
        }

        $this->entregar(
            App::uploadsPath() . '/' . basename((string) $proyecto['ruta_pdf']),
            'application/pdf',
            'Proyecto ' . ($proyecto['codigo_proyecto'] ?? $id) . '.pdf'
        );
    }

    /** El audio sintetizado por el servicio de voz. */
    public function audio(string $nombre): void
    {
        // Solo se admite el patrón que genera TTSService: nada más.
        if (!preg_match('/^tts_[0-9a-f]{32}\.wav$/', $nombre)) {
            $this->noEncontrado();
            return;
        }

        $this->entregar(App::audioPath() . '/' . $nombre, 'audio/wav', $nombre, true);
    }

    /**
     * Envía un archivo del almacenamiento comprobando que no se salga de él.
     *
     * @param bool $enLinea true reproduce en el navegador; false descarga
     */
    private function entregar(string $ruta, string $tipo, string $nombre, bool $enLinea = false): void
    {
        $real = realpath($ruta);
        $base = realpath(App::storagePath());

        // Defensa contra travesía de directorios: el archivo tiene que estar
        // dentro del almacenamiento, pase lo que pase con el nombre recibido.
        if ($real === false || $base === false || !str_starts_with($real, $base) || !is_file($real)) {
            $this->noEncontrado();
            return;
        }

        header('Content-Type: ' . $tipo);
        header('Content-Length: ' . filesize($real));
        header('X-Content-Type-Options: nosniff');
        header(sprintf(
            'Content-Disposition: %s; filename="%s"',
            $enLinea ? 'inline' : 'attachment',
            str_replace('"', '', $nombre)
        ));
        // Contiene datos personales: ni cachés intermedias ni historial.
        header('Cache-Control: private, no-store');

        readfile($real);
        exit;
    }

    private function noEncontrado(): void
    {
        http_response_code(404);
        echo '<h1>404 — Archivo no encontrado</h1>';
        exit;
    }
}

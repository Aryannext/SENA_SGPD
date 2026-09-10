<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Model;

/**
 * Comprobación de salud para Docker, Dokploy y cualquier monitor externo.
 *
 * Es la única ruta pública además del acceso, así que no revela nada: ni
 * versiones, ni rutas del sistema de archivos, ni el motivo exacto de un fallo
 * de base de datos. Solo dice si el servicio está en pie.
 */
final class SaludController extends Controller
{
    public function index(): void
    {
        $baseDatos = $this->baseDatosResponde();

        $this->json(
            [
                'estado'    => $baseDatos ? 'ok' : 'degradado',
                'servicio'  => 'sgpd',
                'basedatos' => $baseDatos ? 'ok' : 'sin conexion',
            ],
            $baseDatos ? 200 : 503
        );
    }

    private function baseDatosResponde(): bool
    {
        try {
            $db = new class extends Model {
                public static function ping(): bool
                {
                    return static::queryOne('SELECT 1 AS ok') !== null;
                }
            };

            return $db::ping();
        } catch (\Throwable) {
            // El detalle va al registro del servidor, no a la respuesta.
            return false;
        }
    }
}

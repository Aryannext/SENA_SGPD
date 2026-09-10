<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\App;
use Core\Env;
use Tests\TestCase;

/**
 * Configuración por entorno y preparación para el despliegue en contenedor.
 *
 * El proyecto nació atado a una instalación de XAMPP: credenciales escritas en
 * el código, la ruta base `/SENA_SGPD` repetida en 45 sitios y los archivos
 * subidos bajo la raíz web. Nada de eso funciona en Docker, donde la imagen es
 * la misma en todos los entornos y el contenedor es efímero.
 *
 * Estas pruebas fijan que la configuración salga del entorno y que el
 * almacenamiento viva fuera de lo que sirve el servidor web.
 */
final class DespliegueTest extends TestCase
{
    // ── Variables de entorno ────────────────────────────────────────────────

    public function testElEntornoDelProcesoTienePrioridad(): void
    {
        putenv('SGPD_PRUEBA_ENV=desde-el-proceso');

        $this->assertSame('desde-el-proceso', Env::get('SGPD_PRUEBA_ENV'));
        $this->assertSame('por-defecto', Env::get('SGPD_NO_EXISTE', 'por-defecto'));
        $this->assertSame(null, Env::get('SGPD_NO_EXISTE'));

        putenv('SGPD_PRUEBA_ENV');
    }

    public function testConvierteNumerosYBooleanos(): void
    {
        putenv('SGPD_PRUEBA_NUM=4096');
        $this->assertSame(4096, Env::int('SGPD_PRUEBA_NUM'));
        $this->assertSame(99, Env::int('SGPD_NO_EXISTE', 99));

        foreach (['true', '1', 'yes', 'on', 'si'] as $verdadero) {
            putenv("SGPD_PRUEBA_BOOL={$verdadero}");
            $this->assertTrue(Env::bool('SGPD_PRUEBA_BOOL'), "«{$verdadero}» debe leerse como verdadero");
        }

        foreach (['false', '0', 'no', 'off'] as $falso) {
            putenv("SGPD_PRUEBA_BOOL={$falso}");
            $this->assertFalse(Env::bool('SGPD_PRUEBA_BOOL'), "«{$falso}» debe leerse como falso");
        }

        putenv('SGPD_PRUEBA_NUM');
        putenv('SGPD_PRUEBA_BOOL');
    }

    // ── Almacenamiento ──────────────────────────────────────────────────────

    /**
     * V-04: los archivos subidos no pueden estar donde el servidor web los
     * sirva. Antes vivían en `public/uploads/` y eran descargables por quien
     * adivinara un nombre del tipo `import_<timestamp>.xls`.
     */
    public function testElAlmacenamientoNoEstaBajoLaRaizWeb(): void
    {
        $storage = str_replace('\\', '/', App::storagePath());
        $publico = str_replace('\\', '/', RAIZ . '/public');

        $this->assertFalse(
            str_starts_with($storage, $publico),
            'storage no puede vivir dentro de public/: quedaría servido por el servidor web'
        );
        $this->assertStringContainsString('uploads', str_replace('\\', '/', App::uploadsPath()));
        $this->assertStringContainsString('audio', str_replace('\\', '/', App::audioPath()));
    }

    /** El código no puede volver a escribir dentro de public/. */
    public function testNingunServicioEscribeEnLaRaizWeb(): void
    {
        $archivos = array_merge(
            glob(RAIZ . '/app/Controllers/*.php') ?: [],
            glob(RAIZ . '/app/Services/*.php') ?: []
        );

        foreach ($archivos as $archivo) {
            $codigo = file_get_contents($archivo) ?: '';
            $this->assertStringNotContainsString(
                "/public/uploads",
                $codigo,
                basename($archivo) . ' escribe bajo la raíz web: debe usar App::uploadsPath()'
            );
        }
    }

    public function testElLimiteDeCargaEsRazonable(): void
    {
        $limite = App::maxUploadBytes();

        $this->assertTrue($limite > 0, 'debe haber un límite');
        $this->assertTrue($limite >= 1048576, 'un reporte de Sofía Plus pesa cientos de KB: el límite no puede ser menor a 1 MB');
    }

    // ── Archivos del despliegue ─────────────────────────────────────────────

    public function testExistenLosArchivosQueNecesitaDocker(): void
    {
        foreach ([
            'Dockerfile',
            'docker-compose.yml',
            '.dockerignore',
            '.env.example',
            'docker/entrypoint.sh',
            'tools/migrar.php',
        ] as $archivo) {
            $this->assertTrue(is_file(RAIZ . '/' . $archivo), "falta {$archivo}");
        }
    }

    /** Un `.env` con credenciales reales nunca puede acabar en el repositorio. */
    public function testElArchivoDeEntornoNoSeVersionaNiEntraEnLaImagen(): void
    {
        $gitignore     = file_get_contents(RAIZ . '/.gitignore') ?: '';
        $dockerignore  = file_get_contents(RAIZ . '/.dockerignore') ?: '';

        foreach (['.gitignore' => $gitignore, '.dockerignore' => $dockerignore] as $nombre => $contenido) {
            $lineas = array_map('trim', explode("\n", $contenido));
            $this->assertTrue(in_array('.env', $lineas, true), "{$nombre} debe excluir .env");
        }

        $this->assertTrue(
            in_array('storage/', array_map('trim', explode("\n", $dockerignore)), true),
            '.dockerignore debe excluir storage/: los datos no van dentro de la imagen'
        );
    }

    /** El entrypoint tiene que llevar saltos de línea de Unix. */
    public function testElEntrypointNoLlevaRetornosDeCarro(): void
    {
        $script = file_get_contents(RAIZ . '/docker/entrypoint.sh') ?: '';

        $this->assertStringNotContainsString(
            "\r",
            $script,
            'con retornos de carro el contenedor falla al arrancar con un «not found» incomprensible'
        );
        $this->assertTrue(str_starts_with($script, '#!/bin/sh'), 'falta la línea del intérprete');
    }

    /** Producción no puede mostrar errores en pantalla. */
    public function testLaImagenDesactivaLaSalidaDeErrores(): void
    {
        $dockerfile = file_get_contents(RAIZ . '/Dockerfile') ?: '';

        $this->assertStringContainsString('display_errors = Off', $dockerfile);
        $this->assertStringContainsString('expose_php = Off', $dockerfile);
        $this->assertStringContainsString('a2enmod rewrite', $dockerfile, 'sin mod_rewrite el enrutador no funciona');
    }

    /** Las credenciales salen del entorno, no del código versionado. */
    public function testLaConfiguracionNoLlevaCredencialesEscritas(): void
    {
        $config = file_get_contents(RAIZ . '/config/database.php') ?: '';

        $this->assertStringContainsString("Env::get('DB_HOST'", $config);
        $this->assertStringContainsString("Env::get('DB_PASSWORD'", $config);
    }
}

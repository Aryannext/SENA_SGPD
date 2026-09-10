<?php

declare(strict_types=1);

namespace Tests\Contract;

use Core\Auth;
use Tests\TestCase;

/**
 * CP-38 · Control de acceso (RF-40, RF-41 · RNF-06, RNF-07).
 *
 * Hasta la versión 2.0 ninguna de las rutas validaba sesión, rol ni token: se
 * comprobó que `POST /api/programa/delete-ficha` respondía `{"success":true}`
 * sin credencial alguna, borrando en cascada aprendices y calificaciones.
 *
 * Estas pruebas fijan el contrato para que eso no pueda repetirse.
 */
final class AutenticacionTest extends TestCase
{
    private \Core\Router $router;

    public function prepararClase(): ?string
    {
        $this->router = require RAIZ . '/routes.php';

        if (session_status() === PHP_SESSION_NONE) {
            // La suite corre en consola: basta con el arreglo de sesión.
            $GLOBALS['_SESSION'] = [];
            $_SESSION = [];
        }

        return null;
    }

    /**
     * Solo pueden ser públicas el acceso al sistema y la comprobación de salud.
     *
     * La de salud existe porque Docker y Dokploy necesitan preguntar si el
     * servicio está en pie antes de enrutar tráfico hacia él, y no puede exigir
     * sesión. A cambio no revela nada: ni versiones, ni rutas, ni el motivo de
     * un fallo de base de datos.
     */
    public function testSoloLasRutasDeAccesoYSaludSonPublicas(): void
    {
        $publicas = [];
        foreach ($this->router->getRoutes() as $ruta) {
            if ($ruta['publica'] ?? false) {
                $publicas[] = $ruta['method'] . ' ' . $ruta['uri'];
            }
        }

        sort($publicas);
        $this->assertSame(
            ['GET /login', 'GET /salud', 'POST /login'],
            $publicas,
            'ninguna otra ruta debe quedar accesible sin iniciar sesión'
        );
    }

    /** Ninguna ruta de API queda fuera del guardián. */
    public function testNingunaRutaDeApiEsPublica(): void
    {
        foreach ($this->router->getRoutes() as $ruta) {
            if (!str_starts_with($ruta['uri'], '/api/')) {
                continue;
            }
            $this->assertFalse(
                (bool) ($ruta['publica'] ?? false),
                sprintf('%s expone datos sin sesión', $ruta['uri'])
            );
        }
    }

    /** Ninguna ruta que muestre datos de aprendices es pública. */
    public function testNingunaVistaDeDatosPersonalesEsPublica(): void
    {
        $sensibles = ['/dashboard', '/aprendiz', '/programa', '/desercion', '/import', '/proyecto'];

        foreach ($this->router->getRoutes() as $ruta) {
            foreach ($sensibles as $prefijo) {
                if (str_starts_with($ruta['uri'], $prefijo)) {
                    $this->assertFalse(
                        (bool) ($ruta['publica'] ?? false),
                        sprintf('%s muestra datos personales y no puede ser pública', $ruta['uri'])
                    );
                }
            }
        }
    }

    // ── CSRF ────────────────────────────────────────────────────────────────

    public function testElTokenCsrfEsEstableDentroDeLaSesion(): void
    {
        $_SESSION = [];
        $primero = Auth::tokenCsrf();
        $segundo = Auth::tokenCsrf();

        $this->assertSame($primero, $segundo, 'el token no debe cambiar en cada llamada');
        $this->assertSame(64, strlen($primero), 'deben ser 32 bytes en hexadecimal');
    }

    public function testElTokenCsrfSoloValidaElValorExacto(): void
    {
        $_SESSION = [];
        $token = Auth::tokenCsrf();

        $this->assertTrue(Auth::tokenValido($token));
        $this->assertFalse(Auth::tokenValido(null), 'sin token no se valida');
        $this->assertFalse(Auth::tokenValido(''), 'un token vacío no se valida');
        $this->assertFalse(Auth::tokenValido('0000'), 'un token distinto no se valida');
        $this->assertFalse(Auth::tokenValido(substr($token, 0, -1)), 'un prefijo no basta');
    }

    public function testSinSesionNoHayTokenValido(): void
    {
        $_SESSION = [];
        $this->assertFalse(Auth::tokenValido('cualquier-cosa'));
    }

    // ── Roles ───────────────────────────────────────────────────────────────

    public function testElRolSeRespetaAlComprobarPermisos(): void
    {
        $_SESSION = ['auth_usuario' => ['id_usuario' => 1, 'usuario' => 'x', 'nombre' => 'X', 'rol' => 'INSTRUCTOR']];

        $this->assertTrue(Auth::autenticado());
        $this->assertSame('INSTRUCTOR', Auth::rol());
        $this->assertTrue(Auth::tieneRol(['INSTRUCTOR']));
        $this->assertFalse(Auth::tieneRol(['ADMIN', 'COORDINADOR']), 'un instructor no puede borrar fichas');

        $_SESSION = [];
        $this->assertFalse(Auth::autenticado());
        $this->assertFalse(Auth::tieneRol(['INSTRUCTOR']), 'sin sesión no hay rol');
    }

    // ── Redirección posterior al acceso ─────────────────────────────────────

    /** El destino guardado no puede llevar a otro sitio web. */
    public function testElDestinoTrasEntrarNuncaSaleDelSistema(): void
    {
        foreach (['https://ejemplo.com/robo', '//ejemplo.com/robo', 'javascript:alert(1)'] as $malicioso) {
            $_SESSION = ['auth_destino' => $malicioso];
            $this->assertSame('/dashboard', Auth::destinoTrasEntrar(), "no debe redirigir a {$malicioso}");
        }

        $_SESSION = ['auth_destino' => '/programa/ficha?id=3'];
        $this->assertSame('/programa/ficha?id=3', Auth::destinoTrasEntrar(), 'una ruta interna sí se respeta');

        $_SESSION = [];
        $this->assertSame('/dashboard', Auth::destinoTrasEntrar(), 'sin destino, al tablero');
    }

    /**
     * Regresión: el destino se guardaba con la ruta base incluida y
     * `Controller::redirect()` la anteponía otra vez, produciendo
     * `/SENA_SGPD/SENA_SGPD/…`. Y guardar `/logout` cerraba la sesión recién
     * abierta. Ambos se detectaron probando el acceso en un navegador real.
     */
    public function testElDestinoSeNormalizaAntesDeGuardarse(): void
    {
        $normalizar = new \ReflectionMethod(Auth::class, 'normalizarDestino');
        $normalizar->setAccessible(true);

        // La entrada llega tal cual la pide el navegador: con la ruta base que
        // esté configurada, sea cual sea.
        $base = \Core\App::basePath();

        $this->assertSame(
            '/programa/ficha?id=1',
            $normalizar->invoke(null, $base . '/programa/ficha?id=1'),
            'la ruta base no debe quedar duplicada'
        );
        $this->assertSame(
            '/dashboard',
            $normalizar->invoke(null, '/dashboard'),
            'una ruta ya normalizada se conserva'
        );
        $this->assertSame(null, $normalizar->invoke(null, $base . '/logout'), 'volver a /logout cerraría la sesión');
        $this->assertSame(null, $normalizar->invoke(null, $base . '/login'), 'volver a /login sería un bucle');
        $this->assertSame(null, $normalizar->invoke(null, ''), 'sin URI no hay destino');
    }

    /**
     * La ruta base sale de la configuración, no está escrita en el código.
     *
     * Estaba incrustada en 45 sitios de 22 archivos, y el documento de
     * despliegue pedía editarlos uno a uno para publicar en producción
     * (RNF-22). Ahora sale de `APP_BASE_PATH`, que es lo que permite desplegar
     * la misma imagen en un subdominio y en un subdirectorio.
     */
    public function testLaRutaBaseSaleDeLaConfiguracion(): void
    {
        $base = \Core\App::basePath();

        $this->assertTrue(
            $base === '' || str_starts_with($base, '/'),
            'la ruta base debe estar vacía o empezar por barra'
        );
        $this->assertFalse(str_ends_with($base, '/'), 'no debe llevar barra final');

        $this->assertSame($base . '/dashboard', \Core\App::url('/dashboard'));
        $this->assertSame($base . '/dashboard', \Core\App::url('dashboard'), 'la barra inicial es opcional');
        $this->assertSame($base, \Core\App::url(''), 'sin ruta devuelve solo la base');
    }

    /** Ningún archivo del proyecto puede llevar la ruta base escrita a mano. */
    public function testNingunArchivoIncrustaLaRutaBase(): void
    {
        $archivos = array_merge(
            glob(RAIZ . '/core/*.php') ?: [],
            glob(RAIZ . '/app/Controllers/*.php') ?: [],
            glob(RAIZ . '/app/Services/*.php') ?: [],
            glob(RAIZ . '/views/*/*.php') ?: [],
            glob(RAIZ . '/public/js/*.js') ?: []
        );
        $this->assertTrue($archivos !== [], 'no se encontraron archivos que revisar');

        foreach ($archivos as $archivo) {
            $codigo = file_get_contents($archivo) ?: '';

            // Se ignoran los comentarios, que sí pueden citarla como ejemplo.
            $codigo = preg_replace('#(/\*.*?\*/|//[^\n]*|\*[^\n]*)#s', '', $codigo) ?? $codigo;

            $this->assertStringNotContainsString(
                '/SENA_SGPD',
                $codigo,
                basename($archivo) . ' incrusta la ruta base: debe usar App::url() o APP.basePath'
            );
        }
    }

    // ── Contraseñas ─────────────────────────────────────────────────────────

    /** Nunca se almacena la contraseña en claro. */
    public function testLasContrasenasSeGuardanHasheadas(): void
    {
        $clave = 'ClaveDePrueba2026';
        $hash  = password_hash($clave, PASSWORD_DEFAULT);

        $this->assertStringNotContainsString($clave, $hash, 'el hash no puede contener la contraseña');
        $this->assertTrue(password_verify($clave, $hash));
        $this->assertFalse(password_verify('otra-cosa', $hash));
        $this->assertTrue(str_starts_with($hash, '$2y$'), 'PASSWORD_DEFAULT debe producir bcrypt');
    }

    /** El modelo de usuarios nunca expone una columna de contraseña en claro. */
    public function testElModeloNoGuardaContrasenasEnClaro(): void
    {
        $codigo = file_get_contents(RAIZ . '/app/Models/Usuario.php') ?: '';

        $this->assertStringContainsString('password_hash($contrasena', $codigo);
        $this->assertStringNotContainsString('$contrasena]', $codigo, 'la contraseña nunca se pasa cruda a la consulta');
    }

    // ── Cabeceras y cookie ──────────────────────────────────────────────────

    public function testElFrontControllerEmiteLasCabecerasDeSeguridad(): void
    {
        $codigo = file_get_contents(RAIZ . '/index.php') ?: '';

        foreach ([
            'X-Content-Type-Options: nosniff',
            'X-Frame-Options: SAMEORIGIN',
            'Referrer-Policy: same-origin',
            'Content-Security-Policy',
        ] as $cabecera) {
            $this->assertStringContainsString($cabecera, $codigo, "falta la cabecera {$cabecera}");
        }
    }

    public function testLaCookieDeSesionEstaProtegida(): void
    {
        $codigo = file_get_contents(RAIZ . '/core/Auth.php') ?: '';

        $this->assertStringContainsString("'httponly' => true", $codigo);
        $this->assertStringContainsString("'samesite' => 'Strict'", $codigo);
        $this->assertStringContainsString('session_regenerate_id(true)', $codigo, 'debe cerrarse la fijación de sesión');
    }

    /** El front controller debe exigir el token en toda escritura. */
    public function testTodaEscrituraExigeTokenCsrf(): void
    {
        $codigo = file_get_contents(RAIZ . '/index.php') ?: '';

        $this->assertStringContainsString("if (\$method === 'POST') {", $codigo);
        $this->assertStringContainsString('Auth::exigirTokenCsrf', $codigo);
        $this->assertStringContainsString('Auth::exigirSesion', $codigo);
    }
}

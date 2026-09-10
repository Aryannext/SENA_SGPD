<?php

declare(strict_types=1);

namespace Core;

use App\Models\Usuario;

/**
 * Autenticación por sesión y protección contra CSRF.
 *
 * Hasta la versión 2.0 el sistema no tenía ninguna barrera: cualquiera que
 * alcanzara la URL podía consultar los datos personales de los aprendices y
 * borrar una ficha completa con una sola petición anónima (vulnerabilidades
 * V-02 y RNF-06/RNF-07). Esta clase es esa barrera.
 *
 * Decisiones:
 *  - Las contraseñas se guardan con `password_hash()` (bcrypt por defecto). El
 *    sistema nunca almacena ni registra la contraseña en claro.
 *  - La cookie de sesión se emite con HttpOnly y SameSite=Strict, y con Secure
 *    cuando la petición llega por HTTPS.
 *  - El identificador de sesión se regenera al iniciar sesión, para cerrar la
 *    fijación de sesión.
 *  - El token CSRF es único por sesión y se compara con hash_equals().
 */
final class Auth
{
    private const CLAVE_USUARIO = 'auth_usuario';
    private const CLAVE_CSRF    = 'auth_csrf';
    private const CLAVE_DESTINO = 'auth_destino';

    /** Jerarquía de roles: cada uno incluye los permisos de los siguientes. */
    public const ROLES = ['ADMIN', 'COORDINADOR', 'INSTRUCTOR'];

    /**
     * Arranca la sesión con parámetros de cookie seguros.
     * Debe llamarse antes de cualquier salida.
     */
    public static function iniciarSesion(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure'   => $https,
        ]);
        session_start();
    }

    // ── Estado ──────────────────────────────────────────────────────────────

    public static function autenticado(): bool
    {
        return isset($_SESSION[self::CLAVE_USUARIO]['id_usuario']);
    }

    /** @return array<string, mixed>|null */
    public static function usuario(): ?array
    {
        return $_SESSION[self::CLAVE_USUARIO] ?? null;
    }

    public static function rol(): ?string
    {
        return $_SESSION[self::CLAVE_USUARIO]['rol'] ?? null;
    }

    /**
     * ¿El usuario actual tiene al menos uno de los roles indicados?
     *
     * @param array<int, string> $roles
     */
    public static function tieneRol(array $roles): bool
    {
        $rol = self::rol();
        return $rol !== null && in_array($rol, $roles, true);
    }

    // ── Inicio y cierre de sesión ───────────────────────────────────────────

    /**
     * Verifica las credenciales e inicia la sesión.
     *
     * Devuelve null si son correctas, o un mensaje de error si no. El mensaje es
     * deliberadamente el mismo para usuario inexistente y contraseña incorrecta:
     * distinguirlos permitiría enumerar usuarios válidos.
     */
    public static function entrar(string $usuario, string $contrasena): ?string
    {
        $usuario = trim($usuario);
        if ($usuario === '' || $contrasena === '') {
            return 'Escribe tu usuario y tu contraseña.';
        }

        $fila = Usuario::findByUsuario($usuario);

        // Se calcula un hash aunque el usuario no exista, para que el tiempo de
        // respuesta no revele si el nombre es válido.
        $hash = $fila['password_hash'] ?? '$2y$10$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvalidin';
        $correcta = password_verify($contrasena, $hash);

        if ($fila === null || !$correcta) {
            return 'Usuario o contraseña incorrectos.';
        }

        if ((int) $fila['activo'] !== 1) {
            return 'Esta cuenta está desactivada. Comunícate con el administrador.';
        }

        // Cierra la fijación de sesión: el identificador previo deja de valer.
        session_regenerate_id(true);

        // El token obtenido antes de autenticarse tampoco debe seguir sirviendo:
        // se rota junto con el identificador de sesión.
        unset($_SESSION[self::CLAVE_CSRF]);

        $_SESSION[self::CLAVE_USUARIO] = [
            'id_usuario' => (int) $fila['id_usuario'],
            'usuario'    => $fila['usuario'],
            'nombre'     => $fila['nombre'],
            'rol'        => $fila['rol'],
        ];

        Usuario::registrarAcceso((int) $fila['id_usuario']);

        return null;
    }

    public static function salir(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path'],
                'domain'   => $p['domain'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'] ?? 'Strict',
            ]);
        }

        session_destroy();
    }

    // ── Guardias ────────────────────────────────────────────────────────────

    /**
     * Corta la petición si no hay sesión.
     * Las rutas de API responden 401 en JSON; las web redirigen al inicio de sesión.
     */
    public static function exigirSesion(bool $esApi, string $uriSolicitada = ''): void
    {
        if (self::autenticado()) {
            return;
        }

        if ($esApi) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Debes iniciar sesión.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Se recuerda a dónde quería ir para volver allí tras entrar.
        $destino = self::normalizarDestino($uriSolicitada);
        if ($destino !== null) {
            $_SESSION[self::CLAVE_DESTINO] = $destino;
        }

        header('Location: ' . Router::BASE_PATH . '/login');
        exit;
    }

    /**
     * Convierte la URI solicitada en una ruta interna reutilizable.
     *
     * Quita la ruta base —que `Controller::redirect()` vuelve a anteponer— y
     * descarta destinos que no tienen sentido: volver a `/login` sería un bucle
     * y volver a `/logout` cerraría la sesión recién iniciada.
     */
    private static function normalizarDestino(string $uri): ?string
    {
        if ($uri === '') {
            return null;
        }

        $ruta = parse_url($uri, PHP_URL_PATH) ?: '';
        if (str_starts_with($ruta, Router::BASE_PATH)) {
            $ruta = substr($ruta, strlen(Router::BASE_PATH)) ?: '/';
        }

        if ($ruta === '' || !str_starts_with($ruta, '/') || str_starts_with($ruta, '//')) {
            return null;
        }

        if (in_array($ruta, ['/login', '/logout'], true)) {
            return null;
        }

        $consulta = parse_url($uri, PHP_URL_QUERY);

        return $consulta ? $ruta . '?' . $consulta : $ruta;
    }

    /**
     * Corta la petición si el usuario no tiene uno de los roles indicados.
     *
     * @param array<int, string> $roles
     */
    public static function exigirRol(array $roles, bool $esApi = true): void
    {
        if (self::tieneRol($roles)) {
            return;
        }

        http_response_code(403);
        if ($esApi) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(
                ['error' => 'No tienes permiso para realizar esta acción.'],
                JSON_UNESCAPED_UNICODE
            );
        } else {
            echo '<h1>403 — No tienes permiso para ver esta página</h1>';
        }
        exit;
    }

    /** Ruta a la que volver tras iniciar sesión. */
    public static function destinoTrasEntrar(): string
    {
        $destino = $_SESSION[self::CLAVE_DESTINO] ?? '/dashboard';
        unset($_SESSION[self::CLAVE_DESTINO]);

        // Solo se aceptan rutas internas: nunca una URL absoluta de otro sitio.
        if (!str_starts_with($destino, '/') || str_starts_with($destino, '//')) {
            return '/dashboard';
        }

        return $destino;
    }

    // ── CSRF ────────────────────────────────────────────────────────────────

    /** Token de la sesión; se genera la primera vez que se pide. */
    public static function tokenCsrf(): string
    {
        if (empty($_SESSION[self::CLAVE_CSRF])) {
            $_SESSION[self::CLAVE_CSRF] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CLAVE_CSRF];
    }

    /** ¿El token recibido coincide con el de la sesión? */
    public static function tokenValido(?string $recibido): bool
    {
        $esperado = $_SESSION[self::CLAVE_CSRF] ?? '';

        return $esperado !== ''
            && is_string($recibido)
            && $recibido !== ''
            && hash_equals($esperado, $recibido);
    }

    /**
     * Corta la petición si el token no acompaña a una escritura.
     *
     * Se acepta en la cabecera `X-CSRF-Token` (peticiones de JavaScript) o en el
     * campo `_csrf` (formularios y cuerpos JSON).
     */
    public static function exigirTokenCsrf(bool $esApi): void
    {
        $recibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? null);

        if ($recibido === null) {
            $crudo = file_get_contents('php://input');
            if ($crudo !== false && $crudo !== '') {
                $cuerpo = json_decode($crudo, true);
                if (is_array($cuerpo)) {
                    $recibido = $cuerpo['_csrf'] ?? null;
                }
            }
        }

        if (self::tokenValido(is_string($recibido) ? $recibido : null)) {
            return;
        }

        http_response_code(419);
        if ($esApi) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(
                ['error' => 'Tu sesión expiró o la petición no es válida. Recarga la página.'],
                JSON_UNESCAPED_UNICODE
            );
        } else {
            echo '<h1>419 — La sesión expiró. Vuelve a cargar la página.</h1>';
        }
        exit;
    }
}

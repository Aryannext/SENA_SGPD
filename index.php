<?php

declare(strict_types=1);

/**
 * Front Controller — single entry point for all HTTP requests.
 * Bootstraps autoloading, defines routes, and dispatches to controllers.
 */

// ── Autoload ────────────────────────────────────────────────────────────────
require_once __DIR__ . '/vendor/autoload.php';

// ── Sesión ──────────────────────────────────────────────────────────────────
// La cookie se emite con HttpOnly y SameSite=Strict (RNF-10).
Core\Auth::iniciarSesion();

// ── Cabeceras de seguridad (RNF-10) ─────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
header(
    "Content-Security-Policy: default-src 'self'; "
    . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
    . "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; "
    . "font-src 'self' https://cdnjs.cloudflare.com; "
    . "img-src 'self' data:; "
    . "connect-src 'self'; "
    . "frame-ancestors 'self'; "
    . "base-uri 'self'; "
    . "form-action 'self'"
);

// ── Router Setup ────────────────────────────────────────────────────────────
// Las rutas se definen en routes.php para que puedan cargarse sin despachar
// una petición (ver docs/16_PLAN_PRUEBAS.md, caso CP-51).
$router = require __DIR__ . '/routes.php';

// ── Dispatch ────────────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$uri    = $_SERVER['REQUEST_URI'];

$match = $router->resolve($uri, $method);

if ($match === null) {
    http_response_code(404);
    echo '<h1>404 — Página no encontrada</h1>';
    exit;
}

$controllerClass = $match['controller'];
$action          = $match['action'];
$params          = $match['params'];
$esApi           = str_starts_with($match['uri'], '/api/');

// ── Guardias de seguridad ───────────────────────────────────────────────────
// Toda ruta que no esté marcada como pública exige sesión iniciada (RNF-06), y
// toda escritura exige además un token CSRF válido (RNF-07).
if (!$match['publica']) {
    Core\Auth::exigirSesion($esApi, $uri);
}

if ($method === 'POST') {
    Core\Auth::exigirTokenCsrf($esApi);
}

if (!class_exists($controllerClass)) {
    http_response_code(500);
    echo "<h1>500 — Controller not found: {$controllerClass}</h1>";
    exit;
}

$controller = new $controllerClass();

if (!method_exists($controller, $action)) {
    http_response_code(500);
    echo "<h1>500 — Action not found: {$action}</h1>";
    exit;
}

// Call the action with route params as arguments
$controller->$action(...array_values($params));

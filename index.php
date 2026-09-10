<?php

declare(strict_types=1);

/**
 * Front Controller — single entry point for all HTTP requests.
 * Bootstraps autoloading, defines routes, and dispatches to controllers.
 */

// ── Autoload ────────────────────────────────────────────────────────────────
require_once __DIR__ . '/vendor/autoload.php';

// ── Session ─────────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

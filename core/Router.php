<?php

declare(strict_types=1);

namespace Core;

/**
 * Simple router with support for parameterized routes and HTTP method matching.
 * Routes are grouped into web (HTML) and api (JSON) categories.
 *
 * Single Responsibility: only resolves URI → controller action.
 */
final class Router
{
    /** @var array<string, array{controller: string, action: string, pattern: string}> */
    private array $routes = [];

    /**
     * @param bool $publica Accesible sin iniciar sesión (solo el acceso al sistema)
     */
    public function get(string $uri, string $controller, string $action, bool $publica = false): void
    {
        $this->addRoute('GET', $uri, $controller, $action, $publica);
    }

    public function post(string $uri, string $controller, string $action, bool $publica = false): void
    {
        $this->addRoute('POST', $uri, $controller, $action, $publica);
    }

    private function addRoute(string $method, string $uri, string $controller, string $action, bool $publica = false): void
    {
        // Convert URI pattern like /aprendiz/{id} to regex
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $uri);
        $pattern = '#^' . $pattern . '$#';

        $key = $method . '|' . $uri;
        $this->routes[$key] = [
            'controller' => $controller,
            'action'     => $action,
            'pattern'    => $pattern,
            'method'     => $method,
            'uri'        => $uri,
            'publica'    => $publica,
        ];
    }

    /**
     * Todas las rutas registradas.
     *
     * Permite verificar sin despachar una petición que cada ruta apunta a un
     * controlador y a una acción que existen (ver docs/16, caso CP-51).
     *
     * @return array<string, array{controller: string, action: string, pattern: string, method: string, uri: string}>
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Resolve the current request URI and method to a controller action.
     *
     * @return array{controller: string, action: string, params: array<string, string>}|null
     */
    public function resolve(string $uri, string $method): ?array
    {
        // Strip query string and trailing slash
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';
        $uri = rtrim($uri, '/') ?: '/';

        // Quita el subdirectorio en el que vive la aplicacion (APP_BASE_PATH)
        $basePath = App::basePath();
        if (str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath)) ?: '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                return [
                    'controller' => $route['controller'],
                    'action'     => $route['action'],
                    'params'     => $params,
                    'publica'    => $route['publica'] ?? false,
                    'uri'        => $route['uri'],
                ];
            }
        }

        return null;
    }
}

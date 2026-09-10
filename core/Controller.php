<?php

declare(strict_types=1);

namespace Core;

/**
 * Abstract base controller.
 * Provides view rendering and JSON response helpers.
 *
 * Open/Closed: extend via child controllers without modifying this class.
 */
abstract class Controller
{
    /**
     * Render a view inside the main layout.
     *
     * @param string               $viewPath  Dot-notation path (e.g., 'dashboard.index')
     * @param array<string, mixed> $data      Variables available in the view
     * @param string               $layout    Layout file to wrap the view
     */
    protected function view(string $viewPath, array $data = [], string $layout = 'layouts.main'): void
    {
        $viewFile   = $this->resolveViewPath($viewPath);
        $layoutFile = $this->resolveViewPath($layout);

        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo "View not found: {$viewPath}";
            return;
        }

        // Render the inner view content
        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Render layout with $content injected
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    /**
     * Send a JSON response and terminate.
     *
     * @param mixed $data
     */
    protected function json(mixed $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Redirect to a given URI.
     */
    protected function redirect(string $uri): void
    {
        header('Location: ' . App::url($uri));
        exit;
    }

    /**
     * Get JSON body from a POST request.
     *
     * @return array<string, mixed>
     */
    protected function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw ?: '{}', true) ?: [];
    }

    /**
     * Convert dot-notation view path to file path.
     */
    private function resolveViewPath(string $dotPath): string
    {
        $relativePath = str_replace('.', DIRECTORY_SEPARATOR, $dotPath);
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $relativePath . '.php';
    }
}

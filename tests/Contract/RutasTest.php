<?php

declare(strict_types=1);

namespace Tests\Contract;

use Tests\TestCase;

/**
 * CP-51 · Contrato de rutas.
 *
 * Recorre todas las rutas registradas y comprueba que apunten a un controlador y
 * a una acción que existen de verdad.
 *
 * Es la prueba de mejor relación valor/esfuerzo del proyecto: una sola aserción
 * cubre las 36 rutas. Habría detectado el defecto F-03 —la ruta `/api/chat/speak`
 * apuntando a un método `speak()` inexistente— el mismo día en que se introdujo.
 */
final class RutasTest extends TestCase
{
    private \Core\Router $router;

    public function prepararClase(): ?string
    {
        $this->router = require RAIZ . '/routes.php';
        return null;
    }

    public function testCadaRutaApuntaAUnControladorExistente(): void
    {
        foreach ($this->router->getRoutes() as $ruta) {
            $this->assertTrue(
                class_exists($ruta['controller']),
                sprintf('%s %s → la clase %s no existe', $ruta['method'], $ruta['uri'], $ruta['controller'])
            );
        }
    }

    public function testCadaRutaApuntaAUnaAccionExistente(): void
    {
        foreach ($this->router->getRoutes() as $ruta) {
            if (!class_exists($ruta['controller'])) {
                continue; // ya lo reporta la prueba anterior
            }
            $this->assertTrue(
                method_exists($ruta['controller'], $ruta['action']),
                sprintf('%s %s → %s::%s() no existe', $ruta['method'], $ruta['uri'], $ruta['controller'], $ruta['action'])
            );
        }
    }

    public function testCadaAccionEsPublica(): void
    {
        foreach ($this->router->getRoutes() as $ruta) {
            if (!class_exists($ruta['controller']) || !method_exists($ruta['controller'], $ruta['action'])) {
                continue;
            }
            $metodo = new \ReflectionMethod($ruta['controller'], $ruta['action']);
            $this->assertTrue(
                $metodo->isPublic() && !$metodo->isStatic(),
                sprintf('%s %s → %s::%s() debe ser pública y de instancia', $ruta['method'], $ruta['uri'], $ruta['controller'], $ruta['action'])
            );
        }
    }

    /**
     * Las rutas con parámetros —por ejemplo `/aprendiz/{id}`— pasan cada segmento
     * como argumento posicional, así que la acción debe aceptarlos.
     */
    public function testLasRutasConParametrosTienenAccionesQueLosAceptan(): void
    {
        foreach ($this->router->getRoutes() as $ruta) {
            preg_match_all('/\{([a-zA-Z_]+)\}/', $ruta['uri'], $m);
            $parametros = count($m[1]);
            if ($parametros === 0 || !class_exists($ruta['controller']) || !method_exists($ruta['controller'], $ruta['action'])) {
                continue;
            }
            $metodo = new \ReflectionMethod($ruta['controller'], $ruta['action']);
            $this->assertTrue(
                $metodo->getNumberOfParameters() >= $parametros,
                sprintf('%s espera %d parámetro(s) pero %s::%s() acepta %d',
                    $ruta['uri'], $parametros, $ruta['controller'], $ruta['action'], $metodo->getNumberOfParameters())
            );
        }
    }

    /** Regresión de F-03: la ruta que usa el frontend debe estar registrada. */
    public function testLaRutaDeSintesisDeVozEstaRegistrada(): void
    {
        $uris = array_column($this->router->getRoutes(), 'uri');

        $this->assertTrue(
            in_array('/api/chat/synthesize', $uris, true),
            'falta /api/chat/synthesize, que es la ruta que llama public/js/chat.js'
        );
        $this->assertFalse(
            in_array('/api/chat/speak', $uris, true),
            '/api/chat/speak no debe existir: apuntaba a un método inexistente (F-03)'
        );
    }

    /** Toda ruta que el JavaScript invoca tiene que existir en el router. */
    public function testTodaRutaInvocadaDesdeElFrontendExiste(): void
    {
        $uris = array_column($this->router->getRoutes(), 'uri');
        $encontradas = [];

        foreach (glob(RAIZ . '/public/js/*.js') as $archivo) {
            $codigo = file_get_contents($archivo) ?: '';
            preg_match_all('#[\'"`](?:/SENA_SGPD)?(/api/[a-zA-Z0-9/_-]+)#', $codigo, $m);
            foreach ($m[1] as $uri) {
                $encontradas[$uri] = basename($archivo);
            }
        }

        $this->assertTrue($encontradas !== [], 'no se encontró ninguna llamada a la API en public/js');

        foreach ($encontradas as $uri => $origen) {
            $this->assertTrue(
                in_array($uri, $uris, true),
                sprintf('%s llama a %s, que no está registrada en routes.php', $origen, $uri)
            );
        }
    }
}

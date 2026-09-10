<?php

declare(strict_types=1);

namespace Tests\Regression;

use Tests\TestCase;

/**
 * CP-52 · Escapado de la salida (RNF-08).
 *
 * Prueba de regresión de la vulnerabilidad V-01: los nombres importados desde el
 * Excel de Sofía Plus llegaban sin escapar al navegador. Un aprendiz llamado
 * `<img src=x onerror=…>` ejecutaba código en la sesión del instructor.
 *
 * Se verifican los dos frentes:
 *   - Servidor: las plantillas escapan `$pageTitle`.
 *   - Cliente: los puntos que interpolaban datos de la base en `innerHTML` pasan
 *     por `APP.esc()`, y la salida del modelo de IA por `APP.mdSafe()`.
 */
final class EscapadoTest extends TestCase
{
    private const PAYLOAD = '<img src=x onerror=alert(document.domain)>';

    /** Renderiza una plantilla aislada y devuelve su salida. */
    private function render(string $ruta, array $variables): string
    {
        extract($variables, EXTR_SKIP);
        ob_start();
        require RAIZ . '/' . $ruta;
        return (string) ob_get_clean();
    }

    // ── Servidor ────────────────────────────────────────────────────────────

    public function testElBreadcrumbEscapaElTituloDePagina(): void
    {
        $html = $this->render('views/partials/header.php', ['pageTitle' => self::PAYLOAD]);

        $this->assertStringNotContainsString('<img src=x', $html, 'V-01: el payload no debe llegar como HTML activo');
        $this->assertStringContainsString('&lt;img src=x', $html, 'debe aparecer escapado');
    }

    public function testElBreadcrumbEscapaLasComillas(): void
    {
        $html = $this->render('views/partials/header.php', ['pageTitle' => 'a"b\'c']);

        $this->assertStringNotContainsString('a"b', $html, 'las comillas dobles deben escaparse');
        $this->assertStringContainsString('&quot;', $html);
        $this->assertStringContainsString('&#039;', $html);
    }

    public function testElLayoutEscapaElTitulo(): void
    {
        $codigo = file_get_contents(RAIZ . '/views/layouts/main.php') ?: '';

        $this->assertStringContainsString(
            'htmlspecialchars($pageTitle ?? \'Dashboard\', ENT_QUOTES, \'UTF-8\')',
            $codigo,
            'V-01: el <title> debe escapar $pageTitle'
        );
        $this->assertStringNotContainsString(
            '<title>SGPD SENA — <?= $pageTitle',
            $codigo,
            'no debe quedar la interpolación sin escapar'
        );
    }

    public function testElLayoutCargaDompurify(): void
    {
        $codigo = file_get_contents(RAIZ . '/views/layouts/main.php') ?: '';
        $this->assertStringContainsString('purify.min.js', $codigo, 'DOMPurify sanea la salida del modelo de IA');
    }

    // ── Cliente ─────────────────────────────────────────────────────────────

    /**
     * Guardia contra la reaparición de los patrones exactos que se corrigieron.
     * Si alguien vuelve a interpolar un campo de la base sin escapar, falla aquí
     * y no en producción.
     */
    public function testNingunCampoDeLaBaseSeInterpolaSinEscapar(): void
    {
        $prohibidos = [
            '${a.nombre} ${a.apellido}',
            '${a.nu_documento}',
            '${a.estado}',
            '${a.ti_documento}',
            '${d.competencia}',
            '${d.resultado}',
            '${d.instructor_responsable}',
            '${row.aprendiz}',
            '${row.nu_documento}',
            '${row.motivo',
            '${data.prediccion}',
            '${f.nombre_fase}',
            '${ficha.codigo_programa}',
            '${ficha.estado_ficha}',
        ];

        $archivos = array_merge(
            glob(RAIZ . '/public/js/*.js') ?: [],
            glob(RAIZ . '/views/*/*.php') ?: []
        );
        $this->assertTrue($archivos !== [], 'no se encontraron archivos que revisar');

        foreach ($archivos as $archivo) {
            $codigo = file_get_contents($archivo) ?: '';
            foreach ($prohibidos as $patron) {
                $this->assertFalse(
                    str_contains($codigo, $patron),
                    sprintf('%s interpola %s sin APP.esc() — regresión de V-01', basename($archivo), $patron)
                );
            }
        }
    }

    public function testExisteElHelperDeEscapado(): void
    {
        $app = file_get_contents(RAIZ . '/public/js/app.js') ?: '';

        $this->assertStringContainsString('esc(value)', $app, 'APP.esc() debe existir');
        $this->assertStringContainsString('mdSafe(markdown)', $app, 'APP.mdSafe() debe existir');

        foreach (['&amp;', '&lt;', '&gt;', '&quot;', '&#39;'] as $entidad) {
            $this->assertStringContainsString($entidad, $app, "APP.esc() debe convertir a {$entidad}");
        }
    }

    /** La respuesta del modelo no puede llegar cruda a innerHTML. */
    public function testLaSalidaDelModeloSeSanea(): void
    {
        $chat = file_get_contents(RAIZ . '/public/js/chat.js') ?: '';

        $this->assertStringNotContainsString(
            'innerHTML = marked.parse(',
            $chat,
            'V-01: marked.parse() sin sanear permite HTML activo desde el modelo'
        );
        $this->assertStringContainsString('APP.mdSafe(', $chat);
    }

    /**
     * Los controladores de evento en línea mezclaban contexto HTML y JavaScript,
     * donde escapar comillas simples no basta. Ahora el dato viaja por `data-*`.
     */
    public function testLosControladoresEnLineaNoInterpolanTexto(): void
    {
        $dashboard = file_get_contents(RAIZ . '/public/js/dashboard.js') ?: '';
        $fases     = file_get_contents(RAIZ . '/views/dashboard/fases.php') ?: '';

        $this->assertStringContainsString('this.dataset.nombre', $dashboard, 'verDeudas debe leer el nombre del dataset');
        $this->assertStringNotContainsString(".replace(/'/g", $dashboard, 'escapar solo comillas simples era insuficiente');
        $this->assertStringContainsString('this.dataset.faseNombre', $fases, 'showFaseDetalle debe leer el nombre del dataset');
    }
}

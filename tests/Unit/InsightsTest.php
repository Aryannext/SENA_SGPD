<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\DashboardController;
use Tests\TestCase;

/**
 * CP-13 · Categorización de riesgo y panel «Foco de Atención».
 *
 * Prueba de regresión del defecto F-01: el filtro del panel comparaba contra un
 * literal corrompido («Cr\xEF\xBF\xBDtico», con el carácter de reemplazo U+FFFD)
 * que jamás coincidía con la categoría 'Crítico'. El resultado era que el KPI
 * contaba a los aprendices críticos pero la lista los omitía, mostrando en su
 * lugar a los sanos.
 *
 * Reglas verificadas: RN-16 (en deuda) y RN-17 (categoría relativa al promedio).
 */
final class InsightsTest extends TestCase
{
    /** @return array<int, array<string, mixed>> */
    private function aprendiz(string $nombre, float $avance, int $enDeuda = 0): array
    {
        return ['nombre' => $nombre, 'apellido' => 'PRUEBA', 'porcentaje_avance' => $avance, 'en_deuda' => $enDeuda];
    }

    /**
     * El caso exacto que fallaba: un aprendiz muy por debajo del promedio debe
     * contarse en el KPI y además aparecer en la lista.
     */
    public function testElAprendizCriticoApareceEnElFocoDeAtencion(): void
    {
        $aprendices = [$this->aprendiz('CRITICO', 13.3, 35)];
        for ($i = 0; $i < 24; $i++) {
            $aprendices[] = $this->aprendiz("SANO{$i}", 54.7, 0);
        }

        $r = DashboardController::construirInsights($aprendices, 53.4);

        $this->assertSame(1, $r['en_riesgo'], 'el KPI debe contar un aprendiz en riesgo');
        $this->assertSame(1, $r['histograma']['Crítico'], 'el histograma debe registrar un Crítico');

        $categorias = array_column($r['top_riesgo'], 'categoria');
        $this->assertTrue(
            in_array('Crítico', $categorias, true),
            'F-01: el aprendiz Crítico debe aparecer en el panel, no solo en el KPI'
        );
    }

    /** El peor debe ir primero: es un panel de atención, no un listado. */
    public function testElPeorAprendizEncabezaLaLista(): void
    {
        $r = DashboardController::construirInsights([
            $this->aprendiz('MEDIO', 40.0, 2),
            $this->aprendiz('PEOR', 10.0, 30),
            $this->aprendiz('LEVE', 48.0, 2),
        ], 54.0);

        $this->assertTrue($r['top_riesgo'] !== [], 'debe haber aprendices en el panel');
        $this->assertSame('PEOR', $r['top_riesgo'][0]['nombre'], 'el de menor score debe ir primero');

        $scores = array_column($r['top_riesgo'], 'score_riesgo');
        $ordenados = $scores;
        sort($ordenados);
        $this->assertSame($ordenados, $scores, 'la lista debe quedar ordenada de forma ascendente por score');
    }

    /** Todo aprendiz contado como en riesgo tiene que estar en la lista. */
    public function testElKpiYLaListaNuncaSeContradicen(): void
    {
        $r = DashboardController::construirInsights([
            $this->aprendiz('A', 5.0),
            $this->aprendiz('B', 20.0),
            $this->aprendiz('C', 55.0),
            $this->aprendiz('D', 90.0),
        ], 55.0);

        $criticosEnLista = count(array_filter(
            $r['top_riesgo'],
            static fn(array $a): bool => $a['categoria'] === 'Crítico'
        ));

        $this->assertSame($r['en_riesgo'], $criticosEnLista, 'el KPI en_riesgo debe coincidir con los Críticos listados');
    }

    /** CP-10 · el histograma tiene que cuadrar con el total de aprendices. */
    public function testElHistogramaSumaElTotalDeAprendices(): void
    {
        $aprendices = [
            $this->aprendiz('A', 5.0), $this->aprendiz('B', 30.0),
            $this->aprendiz('C', 50.0), $this->aprendiz('D', 52.0),
            $this->aprendiz('E', 95.0),
        ];

        $r = DashboardController::construirInsights($aprendices, 50.0);

        $this->assertSame(count($aprendices), array_sum($r['histograma']), 'cada aprendiz cae en exactamente una categoría');
        $this->assertCount(4, $r['histograma'], 'deben existir las cuatro categorías');
    }

    /** RN-17 · los umbrales son relativos al promedio, no absolutos. */
    public function testLosUmbralesSonRelativosAlPromedio(): void
    {
        $ap = [$this->aprendiz('X', 40.0)];

        $conPromedioAlto = DashboardController::construirInsights($ap, 60.0);
        $this->assertSame(1, $conPromedioAlto['histograma']['Crítico'], '40 % con promedio 60 % es Crítico (dif = −20)');

        $conPromedioIgual = DashboardController::construirInsights($ap, 40.0);
        $this->assertSame(1, $conPromedioIgual['histograma']['Al Día'], 'el mismo 40 % con promedio 40 % está Al Día');
    }

    /** RN-16 · la deuda basta para marcar riesgo aunque el avance sea normal. */
    public function testLaDeudaPorSiSolaMarcaRiesgo(): void
    {
        $r = DashboardController::construirInsights([
            $this->aprendiz('AL_DIA_PERO_DEBE', 50.0, 5),
        ], 50.0);

        $this->assertSame(1, $r['histograma']['Crítico'], 'cinco resultados en deuda son Crítico aunque el avance esté en la media');
        $this->assertSame(1, $r['en_riesgo']);
    }

    /** Sin aprendices no debe reventar ni inventar categorías. */
    public function testElConjuntoVacioNoRompe(): void
    {
        $r = DashboardController::construirInsights([], 0.0);

        $this->assertSame(0, $r['en_riesgo']);
        $this->assertSame(0, $r['destacados']);
        $this->assertSame([], $r['top_riesgo']);
        $this->assertSame(0, array_sum($r['histograma']));
    }

    /** Un grupo homogéneo —el caso real del SENA— no debe generar falsas alarmas. */
    public function testUnGrupoHomogeneoNoGeneraFalsasAlarmas(): void
    {
        $aprendices = [];
        for ($i = 0; $i < 24; $i++) {
            $aprendices[] = $this->aprendiz("APRENDIZ{$i}", 54.7, 0);
        }

        $r = DashboardController::construirInsights($aprendices, 54.7);

        $this->assertSame(0, $r['en_riesgo'], 'si todos van igual, nadie está en riesgo');
        $this->assertSame(24, $r['histograma']['Al Día']);
        $this->assertSame([], $r['top_riesgo'], 'el panel debe quedar vacío, no lleno de aprendices sanos');
    }
}

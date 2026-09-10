<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\NovedadRetiroService;
use Tests\TestCase;

/**
 * Clasificación de los estados que constituyen una salida de la formación.
 *
 * Es la decisión sobre la que se apoya todo el módulo de deserción (RF-29): si
 * un estado no se reconoce como salida, ese aprendiz nunca genera novedad y
 * desaparece del análisis.
 */
final class NovedadRetiroTest extends TestCase
{
    public function testReconoceLosEstadosDeSalida(): void
    {
        foreach ([
            'RETIRO VOLUNTARIO',
            'Retiro voluntario',
            'RETIRO POR BAJO RENDIMIENTO',
            'TRASLADADO',
            'CANCELADO',
            'CANCELAMIENTO POR DESERCION',
            'DESERCION',
        ] as $estado) {
            $this->assertTrue(
                NovedadRetiroService::esSalida($estado),
                "«{$estado}» debe contarse como salida de la formación"
            );
        }
    }

    public function testNoConfundeLosEstadosDePermanencia(): void
    {
        foreach ([
            'EN FORMACION',
            'CONDICIONADO',
            'APLAZADO',
            'CERTIFICADO',
            'POR CERTIFICAR',
        ] as $estado) {
            $this->assertFalse(
                NovedadRetiroService::esSalida($estado),
                "«{$estado}» no es una salida: el aprendiz sigue vinculado"
            );
        }
    }

    /** RN-04: el estado puede llegar vacío, y eso no es un retiro. */
    public function testElEstadoVacioNoEsUnaSalida(): void
    {
        $this->assertFalse(NovedadRetiroService::esSalida(''));
        $this->assertFalse(NovedadRetiroService::esSalida('   '));
    }

    /**
     * «APLAZADO» contiene «PLAZ», no «CANCELAD»; comprobación de que las marcas
     * no producen coincidencias accidentales.
     */
    public function testLasMarcasNoProducenFalsosPositivos(): void
    {
        $this->assertFalse(NovedadRetiroService::esSalida('APLAZADO'));
        $this->assertFalse(NovedadRetiroService::esSalida('EN FORMACION'));
        $this->assertTrue(NovedadRetiroService::esSalida('TRASLADADO'));
    }
}

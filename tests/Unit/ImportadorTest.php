<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ExcelImportService;
use Tests\TestCase;

/**
 * Pruebas de las funciones puras del importador: parseo y normalización.
 *
 * Son deterministas y no necesitan base de datos ni archivos, así que son las
 * más baratas de escribir y las más rápidas de ejecutar. Cubren las reglas
 * RN-12 (valores controlados del juicio), RN-23 (estado de la ficha) y RN-24
 * (modalidad), además del parseo del formato de Sofía Plus.
 */
final class ImportadorTest extends TestCase
{
    private ExcelImportService $servicio;

    public function prepararClase(): ?string
    {
        $this->servicio = new ExcelImportService();
        return null;
    }

    /** Invoca un método privado del servicio. */
    private function invocar(string $metodo, mixed ...$args): mixed
    {
        $r = new \ReflectionMethod(ExcelImportService::class, $metodo);
        $r->setAccessible(true);
        return $r->invoke($this->servicio, ...$args);
    }

    // ── parseCodigoNombre ───────────────────────────────────────────────────

    public function testSeparaElCodigoDelNombre(): void
    {
        $this->assertSame(
            ['220501092', 'Establecer relaciones'],
            $this->invocar('parseCodigoNombre', '220501092  - Establecer relaciones')
        );
        $this->assertSame(
            ['2', 'RESULTADOS DE APRENDIZAJE ETAPA PRACTICA'],
            $this->invocar('parseCodigoNombre', '2 - RESULTADOS DE APRENDIZAJE ETAPA PRACTICA')
        );
    }

    public function testSoportaGuionesDentroDelNombre(): void
    {
        // Caso real: "36180 - Enrique Low Murtra-Interactuar en el contexto..."
        [$codigo, $nombre] = $this->invocar('parseCodigoNombre', '36180 - Enrique Low Murtra-Interactuar en el contexto');
        $this->assertSame('36180', $codigo);
        $this->assertStringContainsString('Murtra-Interactuar', $nombre, 'el guion interno no debe partir el nombre');
    }

    public function testDevuelveVacioAnteEntradaVacia(): void
    {
        $this->assertSame(['', ''], $this->invocar('parseCodigoNombre', ''));
        $this->assertSame(['', ''], $this->invocar('parseCodigoNombre', '   '));
    }

    /**
     * Defecto F-07: el número de secuencia del resultado queda pegado al nombre.
     *
     * La prueba documenta el comportamiento actual; cuando se corrija F-07 hay
     * que invertir la aserción para que exija que el prefijo se haya retirado.
     */
    public function testElNumeroDeSecuenciaQuedaPegadoAlNombre(): void
    {
        [$codigo, $nombre] = $this->invocar('parseCodigoNombre', '593147 - 02  ESTABLECER RELACIONES');
        $this->assertSame('593147', $codigo);
        $this->assertTrue(
            str_starts_with($nombre, '02'),
            'F-07 sigue presente: el nombre arrastra el número de secuencia'
        );
    }

    // ── parseFuncionario ────────────────────────────────────────────────────

    public function testExtraeLosDatosDelFuncionario(): void
    {
        $r = $this->invocar('parseFuncionario', 'CC 1117523028 - OSCAR CAMILO CASTRO MOPAN');
        $this->assertSame('CC', $r['tiDoc']);
        $this->assertSame('1117523028', $r['nuDoc']);
        $this->assertSame('OSCAR CAMILO CASTRO MOPAN', $r['nombre']);
    }

    /** Los juicios pendientes traen "  -  " en lugar de funcionario (RN-13). */
    public function testDevuelveNuloCuandoNoHayFuncionario(): void
    {
        $this->assertSame(null, $this->invocar('parseFuncionario', '  -  '));
        $this->assertSame(null, $this->invocar('parseFuncionario', '-'));
        $this->assertSame(null, $this->invocar('parseFuncionario', ''));
        $this->assertSame(null, $this->invocar('parseFuncionario', 'texto sin formato'));
    }

    // ── normalizeJuicio · RN-12 ─────────────────────────────────────────────

    public function testNormalizaLosJuiciosConocidos(): void
    {
        $this->assertSame('APROBADO', $this->invocar('normalizeJuicio', 'APROBADO'));
        $this->assertSame('APROBADO', $this->invocar('normalizeJuicio', 'aprobado'));
        $this->assertSame('APROBADO', $this->invocar('normalizeJuicio', 'A'));
        $this->assertSame('NO APROBADO', $this->invocar('normalizeJuicio', 'NO APROBADO'));
        $this->assertSame('NO APROBADO', $this->invocar('normalizeJuicio', 'D'));
        $this->assertSame('POR EVALUAR', $this->invocar('normalizeJuicio', 'POR EVALUAR'));
    }

    /**
     * RN-12: cualquier valor desconocido cae en POR EVALUAR. Es deliberado —no
     * se pierde la fila— pero conviene tenerlo bajo prueba, porque significa que
     * un cambio de formato en el origen no produce ningún error visible.
     */
    public function testCualquierValorDesconocidoCaeEnPorEvaluar(): void
    {
        $this->assertSame('POR EVALUAR', $this->invocar('normalizeJuicio', 'XYZ'));
        $this->assertSame('POR EVALUAR', $this->invocar('normalizeJuicio', ''));
        $this->assertSame('POR EVALUAR', $this->invocar('normalizeJuicio', 'PENDIENTE'));
    }

    /** «NO APROBADO» contiene «APROB»: el orden de evaluación importa. */
    public function testNoAprobadoNoSeConfundeConAprobado(): void
    {
        $this->assertSame('NO APROBADO', $this->invocar('normalizeJuicio', 'NO APROBADO'));
        $this->assertSame('NO APROBADO', $this->invocar('normalizeJuicio', 'no aprobado'));
    }

    // ── normalizeEstadoFicha · RN-23 ────────────────────────────────────────

    public function testNormalizaElEstadoDeLaFicha(): void
    {
        $this->assertSame('EN EJECUCION', $this->invocar('normalizeEstadoFicha', 'EN EJECUCION'));
        $this->assertSame('EN EJECUCION', $this->invocar('normalizeEstadoFicha', 'En Ejecución'));
        $this->assertSame('TERMINADA', $this->invocar('normalizeEstadoFicha', 'TERMINADA'));
        $this->assertSame('CANCELADA', $this->invocar('normalizeEstadoFicha', 'CANCELADA'));
        $this->assertSame('EN EJECUCION', $this->invocar('normalizeEstadoFicha', 'desconocido'));
    }

    // ── normalizeModalidad · RN-24 ──────────────────────────────────────────

    public function testNormalizaLaModalidad(): void
    {
        $this->assertSame('PRESENCIAL', $this->invocar('normalizeModalidad', 'PRESENCIAL'));
        $this->assertSame('VIRTUAL', $this->invocar('normalizeModalidad', 'Virtual'));
        $this->assertSame('A DISTANCIA', $this->invocar('normalizeModalidad', 'A DISTANCIA'));
        $this->assertSame('PRESENCIAL', $this->invocar('normalizeModalidad', ''));
    }

    // ── parseExcelDate ──────────────────────────────────────────────────────

    /** Las fechas llegan como número de serie de Excel, no como texto. */
    public function testConvierteElNumeroDeSerieDeExcel(): void
    {
        // 45698 es el 10 de febrero de 2025, la fecha de inicio de la ficha 3142784
        $this->assertSame('2025-02-10', $this->invocar('parseExcelDate', 45698));
        $this->assertSame('2027-05-10', $this->invocar('parseExcelDate', 46517));
    }

    public function testDevuelveNuloAnteFechaAusenteOInvalida(): void
    {
        $this->assertSame(null, $this->invocar('parseExcelDate', ''));
        $this->assertSame(null, $this->invocar('parseExcelDate', null));
        $this->assertSame(null, $this->invocar('parseExcelDate', 'no es una fecha'));
    }

    public function testIncluyeLaHoraCuandoSePide(): void
    {
        $conHora = $this->invocar('parseExcelDate', 45698.5, true);
        $this->assertStringContainsString('2025-02-10', (string) $conHora);
        $this->assertStringContainsString('12:00:00', (string) $conHora);
    }
}

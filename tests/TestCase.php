<?php

declare(strict_types=1);

namespace Tests;

/**
 * Clase base de las pruebas.
 *
 * El proyecto se construyó sin frameworks y sin Composer instalado en la máquina
 * de desarrollo, así que la suite usa un ejecutor propio en lugar de PHPUnit. Las
 * aserciones llevan los nombres de PHPUnit a propósito: el día que se instale,
 * migrar consiste en extender `PHPUnit\Framework\TestCase` y borrar este archivo.
 *
 * Cada método público que empiece por `test` se ejecuta como un caso.
 */
abstract class TestCase
{
    /** @var array<int, array{ok: bool, mensaje: string}> */
    public array $resultados = [];

    /** Motivo por el que se omite la clase completa, si aplica. */
    public ?string $omitida = null;

    /**
     * Se ejecuta una vez antes de los métodos de la clase.
     * Devolver un texto omite la clase entera con ese motivo.
     */
    public function prepararClase(): ?string
    {
        return null;
    }

    /** Se ejecuta después de todos los métodos de la clase. */
    public function limpiarClase(): void
    {
    }

    // ── Aserciones ──────────────────────────────────────────────────────────

    protected function assertSame(mixed $esperado, mixed $obtenido, string $mensaje = ''): void
    {
        $this->registrar(
            $esperado === $obtenido,
            $mensaje ?: 'assertSame',
            sprintf('esperado %s, obtenido %s', $this->describir($esperado), $this->describir($obtenido))
        );
    }

    protected function assertEquals(mixed $esperado, mixed $obtenido, string $mensaje = ''): void
    {
        $this->registrar(
            $esperado == $obtenido,
            $mensaje ?: 'assertEquals',
            sprintf('esperado %s, obtenido %s', $this->describir($esperado), $this->describir($obtenido))
        );
    }

    protected function assertTrue(bool $condicion, string $mensaje = ''): void
    {
        $this->registrar($condicion, $mensaje ?: 'assertTrue', 'la condición fue falsa');
    }

    protected function assertFalse(bool $condicion, string $mensaje = ''): void
    {
        $this->registrar(!$condicion, $mensaje ?: 'assertFalse', 'la condición fue verdadera');
    }

    protected function assertCount(int $esperado, array $valor, string $mensaje = ''): void
    {
        $this->registrar(
            count($valor) === $esperado,
            $mensaje ?: 'assertCount',
            sprintf('esperados %d elementos, obtenidos %d', $esperado, count($valor))
        );
    }

    protected function assertStringContainsString(string $aguja, string $pajar, string $mensaje = ''): void
    {
        $this->registrar(
            str_contains($pajar, $aguja),
            $mensaje ?: 'assertStringContainsString',
            sprintf('no se encontró %s en %s', $this->describir($aguja), $this->describir(mb_substr($pajar, 0, 120)))
        );
    }

    protected function assertStringNotContainsString(string $aguja, string $pajar, string $mensaje = ''): void
    {
        $this->registrar(
            !str_contains($pajar, $aguja),
            $mensaje ?: 'assertStringNotContainsString',
            sprintf('se encontró %s cuando no debía', $this->describir($aguja))
        );
    }

    /** El valor está dentro de una tolerancia; para comparar decimales. */
    protected function assertEqualsWithDelta(float $esperado, float $obtenido, float $delta, string $mensaje = ''): void
    {
        $this->registrar(
            abs($esperado - $obtenido) <= $delta,
            $mensaje ?: 'assertEqualsWithDelta',
            sprintf('esperado %s ± %s, obtenido %s', $esperado, $delta, $obtenido)
        );
    }

    // ── Interno ─────────────────────────────────────────────────────────────

    private function registrar(bool $ok, string $mensaje, string $detalle): void
    {
        $this->resultados[] = [
            'ok'      => $ok,
            'mensaje' => $ok ? $mensaje : $mensaje . ' — ' . $detalle,
        ];
    }

    private function describir(mixed $valor): string
    {
        return match (true) {
            is_string($valor) => '"' . $valor . '"',
            is_bool($valor)   => $valor ? 'true' : 'false',
            is_null($valor)   => 'null',
            is_array($valor)  => 'array(' . count($valor) . ')',
            default           => (string) $valor,
        };
    }
}

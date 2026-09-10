<?php

declare(strict_types=1);

namespace App\Services;

use Core\Model;

/**
 * Deriva las novedades de retiro a partir del estado de los aprendices.
 *
 * El reporte de Sofía Plus no trae una hoja de novedades: solo trae el estado
 * actual de cada aprendiz («RETIRO VOLUNTARIO», «TRASLADADO», «CANCELADO»…).
 * Este servicio reconstruye la novedad a partir de ese estado y de la huella que
 * dejaron los juicios evaluativos:
 *
 *   motivo      el propio estado del aprendiz
 *   fecha       la del último juicio que se le registró
 *   funcionario quien registró ese último juicio evaluado
 *   fase        aquella a la que pertenece el último resultado que aprobó
 *
 * Resuelve el defecto F-02: la tabla `novedad_retiro` existía y el módulo de
 * análisis la consultaba, pero ninguna línea del sistema escribía en ella, así
 * que las tres gráficas y la tabla de trazabilidad salían siempre vacías.
 *
 * Es idempotente: se puede ejecutar después de cada importación. Si un aprendiz
 * vuelve a formación, su novedad se elimina.
 *
 * Single Responsibility: solo sincroniza novedades de retiro.
 */
final class NovedadRetiroService extends Model
{
    /**
     * Estados que constituyen una salida de la formación.
     * Se comparan con LIKE porque Sofía Plus añade sufijos («RETIRO VOLUNTARIO
     * POR TRASLADO», «CANCELAMIENTO POR DESERCIÓN»…).
     */
    private const ESTADOS_DE_SALIDA = [
        'RETIRO',
        'CANCELAD',
        'CANCELAMIENTO',
        'TRASLADAD',
        'DESERC',
    ];

    /**
     * Sincroniza las novedades con el estado actual de los aprendices.
     *
     * @param int|null $idFicha Limita la sincronización a una ficha; null = todas
     * @return array{creadas: int, actualizadas: int, eliminadas: int}
     */
    public function sincronizar(?int $idFicha = null): array
    {
        $stats = ['creadas' => 0, 'actualizadas' => 0, 'eliminadas' => 0];

        $condiciones = [];
        $params      = [];
        if ($idFicha !== null && $idFicha > 0) {
            $condiciones[] = 'a.id_ficha = ?';
            $params[]      = $idFicha;
        }
        $where = $condiciones === [] ? '' : 'WHERE ' . implode(' AND ', $condiciones);

        $aprendices = static::query(
            "SELECT a.id_aprendiz, a.estado FROM aprendiz a {$where}",
            $params
        );

        foreach ($aprendices as $aprendiz) {
            $idAprendiz = (int) $aprendiz['id_aprendiz'];
            $estado     = (string) ($aprendiz['estado'] ?? '');

            if (!self::esSalida($estado)) {
                // Volvió a formación (o nunca salió): no debe quedar novedad.
                $stats['eliminadas'] += static::execute(
                    'DELETE FROM novedad_retiro WHERE id_aprendiz = ?',
                    [$idAprendiz]
                );
                continue;
            }

            $rastro = $this->rastroDelAprendiz($idAprendiz);
            $existente = static::queryOne(
                'SELECT id_novedad FROM novedad_retiro WHERE id_aprendiz = ? LIMIT 1',
                [$idAprendiz]
            );

            if ($existente !== null) {
                static::execute(
                    'UPDATE novedad_retiro
                        SET motivo = ?, id_funcionario = ?, id_fase = ?, fecha = COALESCE(?, fecha)
                      WHERE id_novedad = ?',
                    [$estado, $rastro['id_funcionario'], $rastro['id_fase'], $rastro['fecha'], (int) $existente['id_novedad']]
                );
                $stats['actualizadas']++;
                continue;
            }

            static::execute(
                'INSERT INTO novedad_retiro (id_aprendiz, id_funcionario, id_fase, motivo, observaciones, fecha)
                 VALUES (?, ?, ?, ?, ?, COALESCE(?, CURRENT_TIMESTAMP))',
                [
                    $idAprendiz,
                    $rastro['id_funcionario'],
                    $rastro['id_fase'],
                    $estado,
                    'Novedad derivada automáticamente del estado reportado en Sofía Plus.',
                    $rastro['fecha'],
                ]
            );
            $stats['creadas']++;
        }

        return $stats;
    }

    /** ¿El estado corresponde a una salida de la formación? */
    public static function esSalida(string $estado): bool
    {
        $estado = mb_strtoupper(trim($estado));
        if ($estado === '') {
            return false;
        }

        foreach (self::ESTADOS_DE_SALIDA as $marca) {
            if (str_contains($estado, $marca)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reconstruye el contexto del retiro a partir de los juicios del aprendiz.
     *
     * @return array{fecha: ?string, id_funcionario: ?int, id_fase: ?int}
     */
    private function rastroDelAprendiz(int $idAprendiz): array
    {
        // Último juicio con fecha: marca el momento en que se le perdió el rastro.
        $ultimo = static::queryOne(
            'SELECT c.fecha_registro, c.id_funcionario
               FROM calificacion c
              WHERE c.id_aprendiz = ? AND c.fecha_registro IS NOT NULL
              ORDER BY c.fecha_registro DESC
              LIMIT 1',
            [$idAprendiz]
        );

        // Fase del último resultado que alcanzó a aprobar.
        $fase = static::queryOne(
            'SELECT f.id_fase
               FROM calificacion c
               JOIN actividad_resultado ar ON ar.id_resultado = c.id_resultado
               JOIN actividad act ON act.id_actividad = ar.id_actividad
               JOIN fase f ON f.id_fase = act.id_fase
              WHERE c.id_aprendiz = ? AND c.jui_evaluativo = ?
              ORDER BY c.fecha_registro DESC, f.id_fase DESC
              LIMIT 1',
            [$idAprendiz, 'APROBADO']
        );

        return [
            'fecha'          => $ultimo['fecha_registro'] ?? null,
            'id_funcionario' => isset($ultimo['id_funcionario']) ? (int) $ultimo['id_funcionario'] : null,
            'id_fase'        => isset($fase['id_fase']) ? (int) $fase['id_fase'] : null,
        ];
    }
}

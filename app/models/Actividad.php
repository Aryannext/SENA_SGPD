<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class Actividad extends Model
{
    public static function findByFase(int $idFase): array
    {
        return static::query('SELECT * FROM actividad WHERE id_fase = ? ORDER BY id_actividad', [$idFase]);
    }

    public static function insert(string $codigo, string $nombre, int $idFase): int
    {
        static::execute(
            'INSERT INTO actividad (cod_actividad, nombre_actividad, id_fase) VALUES (?, ?, ?)',
            [$codigo, $nombre, $idFase]
        );
        return (int) static::lastInsertId();
    }

    public static function asignarResultado(int $idActividad, int $idResultado): void
    {
        static::execute(
            'INSERT IGNORE INTO actividad_resultado (id_actividad, id_resultado) VALUES (?, ?)',
            [$idActividad, $idResultado]
        );
    }

    public static function getResultadosAsignados(int $idActividad): array
    {
        return static::query(
            'SELECT r.*, c.cod_competencia, c.nombre as nombre_competencia
             FROM actividad_resultado ar
             JOIN resultado_aprendizaje r ON ar.id_resultado = r.id_resultado
             JOIN competencia c ON r.id_competencia = c.id_competencia
             WHERE ar.id_actividad = ?
             ORDER BY r.cod_resultado',
            [$idActividad]
        );
    }

    public static function getResultadosNoAsignados(): array
    {
        return static::query("
            SELECT r.*, c.cod_competencia, c.nombre as nombre_competencia
            FROM resultado_aprendizaje r
            JOIN competencia c ON r.id_competencia = c.id_competencia
            WHERE r.id_resultado NOT IN (SELECT id_resultado FROM actividad_resultado)
              AND EXISTS (SELECT 1 FROM calificacion cal WHERE cal.id_resultado = r.id_resultado)
            ORDER BY c.cod_competencia, r.cod_resultado
        ");
    }

    /**
     * Get RAs that belong to a specific program but are NOT yet assigned
     * to any activity within the given project.
     */
    public static function getResultadosNoAsignadosPorPrograma(int $idPrograma, int $idProyecto): array
    {
        $rows = static::query("
            SELECT r.*, c.cod_competencia, c.nombre as nombre_competencia
            FROM resultado_aprendizaje r
            JOIN competencia c ON r.id_competencia = c.id_competencia
            JOIN programa_competencia pc ON c.id_competencia = pc.id_competencia
            WHERE pc.id_programa = ?
              AND r.id_resultado NOT IN (
                SELECT ar.id_resultado FROM actividad_resultado ar
                JOIN actividad a ON ar.id_actividad = a.id_actividad
                JOIN fase f ON a.id_fase = f.id_fase
                WHERE f.id_proyecto = ?
              )
              AND EXISTS (
                SELECT 1 FROM calificacion cal
                JOIN aprendiz ap ON cal.id_aprendiz = ap.id_aprendiz
                JOIN ficha fi ON ap.id_ficha = fi.id_ficha
                WHERE cal.id_resultado = r.id_resultado AND fi.id_programa = ?
              )
            GROUP BY r.id_resultado
            ORDER BY c.cod_competencia, r.cod_resultado DESC
        ", [$idPrograma, $idProyecto, $idPrograma]);

        $results = [];
        foreach ($rows as $row) {
            $results[] = $row;
        }

        // Re-sort ascending for clean display
        usort($results, function($a, $b) {
            if ($a['cod_competencia'] === $b['cod_competencia']) {
                return strcmp((string)$a['cod_resultado'], (string)$b['cod_resultado']);
            }
            return strcmp((string)$a['cod_competencia'], (string)$b['cod_competencia']);
        });

        return $results;
    }
}

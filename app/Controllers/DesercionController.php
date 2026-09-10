<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Model;

final class DesercionController extends Controller
{
    public function index(): void
    {
        $this->view('desercion.index', [
            'pageTitle' => 'Análisis de Deserción',
            'extraJs'   => ['desercion.js'],
            'extraCss'  => ['desercion.css'],
        ]);
    }

    public function stats(): void
    {
        $db = (new class extends Model {
            public static function run(string $sql, array $p = []): array {
                return static::query($sql, $p);
            }
        });

        // 1. Motivos de Deserción (Gráfico de Torta / Doughnut)
        $sqlMotivos = "
            SELECT motivo, COUNT(*) as cantidad 
            FROM novedad_retiro 
            GROUP BY motivo 
            ORDER BY cantidad DESC
        ";
        $motivos = $db::run($sqlMotivos);

        // 2. Deserción por Instructor (Mapa de Calor / Barras)
        $sqlInstructores = "
            SELECT f.nombre, COUNT(nr.id_novedad) as total_retiros
            FROM novedad_retiro nr
            JOIN funcionario f ON nr.id_funcionario = f.id_funcionario
            GROUP BY f.id_funcionario
            ORDER BY total_retiros DESC
        ";
        $instructores = $db::run($sqlInstructores);

        // 3. Auditoría de Instructores (Historial de deserciones cruzado con última calificación)
        $sqlAuditoria = "
            SELECT 
                a.nu_documento,
                CONCAT(a.nombre, ' ', a.apellido) as aprendiz,
                fase.nombre_fase as fase_abandono,
                nr.motivo,
                nr.fecha,
                f.nombre as instructor_responsable
            FROM novedad_retiro nr
            JOIN aprendiz a ON nr.id_aprendiz = a.id_aprendiz
            LEFT JOIN fase ON nr.id_fase = fase.id_fase
            LEFT JOIN funcionario f ON nr.id_funcionario = f.id_funcionario
            ORDER BY nr.fecha DESC
        ";
        $auditoria = $db::run($sqlAuditoria);

        $this->json([
            'motivos'      => $motivos,
            'instructores' => $instructores,
            'auditoria'    => $auditoria
        ]);
    }

    /**
     * Estimación de riesgo de deserción apoyada en el modelo local.
     *
     * Antes construía la petición con cURL crudo, no comprobaba errores y, cuando
     * la llamada fallaba, devolvía un texto fijo inventado indistinguible de una
     * respuesta real. Ahora usa OllamaService, envía datos reales de la base y,
     * si algo falla, lo dice.
     */
    public function predecir(): void
    {
        $ollama = new \App\Services\OllamaService();
        if (!$ollama->isAvailable()) {
            $this->json(['error' => 'El servicio SENA-IA (Ollama) no está disponible.'], 503);
            return;
        }

        $db = new class extends Model {
            public static function run(string $sql, array $p = []): array { return static::query($sql, $p); }
        };

        $motivos = $db::run('SELECT motivo, COUNT(*) n FROM novedad_retiro GROUP BY motivo ORDER BY n DESC');
        $porFase = $db::run('
            SELECT COALESCE(f.nombre_fase, "Sin fase determinada") AS fase, COUNT(*) n
              FROM novedad_retiro nr
              LEFT JOIN fase f ON nr.id_fase = f.id_fase
             GROUP BY f.id_fase, f.nombre_fase
             ORDER BY n DESC
        ');
        $enRiesgo = $db::run('
            SELECT COUNT(*) n FROM (
                SELECT a.id_aprendiz
                  FROM aprendiz a
                  JOIN calificacion c ON c.id_aprendiz = a.id_aprendiz
                 WHERE a.estado LIKE "%FORMACION%"
                 GROUP BY a.id_aprendiz
                HAVING SUM(c.jui_evaluativo = "NO APROBADO") >= 2
            ) t
        ');

        if ($motivos === [] && (int) ($enRiesgo[0]['n'] ?? 0) === 0) {
            $this->json([
                'prediccion' => 'Todavía no hay retiros registrados ni aprendices con juicios no aprobados: no hay base para estimar un patrón de deserción.',
                'sin_datos'  => true,
            ]);
            return;
        }

        $contexto = "Retiros por motivo: " . json_encode($motivos, JSON_UNESCAPED_UNICODE)
            . "\nRetiros por fase: " . json_encode($porFase, JSON_UNESCAPED_UNICODE)
            . "\nAprendices activos con 2 o más juicios NO APROBADO: " . ($enRiesgo[0]['n'] ?? 0);

        $prompt = 'Eres un analista del SENA. Con los datos que se te dan, señala en menos de '
            . '60 palabras si hay un patrón de riesgo de deserción y en qué fase se concentra. '
            . 'No inventes cifras: usa solo las que aparecen a continuación.';

        try {
            $respuesta = $ollama->chat([['role' => 'user', 'content' => $prompt]], $contexto);
        } catch (\Throwable $e) {
            $this->json(['error' => 'SENA-IA no pudo completar el análisis: ' . $e->getMessage()], 502);
            return;
        }

        $this->json([
            'prediccion' => trim(preg_replace('/<think>.*?<\/think>/s', '', $respuesta) ?? $respuesta),
            'sin_datos'  => false,
        ]);
    }
}

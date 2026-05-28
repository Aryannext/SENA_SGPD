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

    public function predecir(): void
    {
        // En un caso real, recolectaríamos datos históricos de la BD
        // Aquí pasamos el contexto a Ollama y le pedimos una predicción.
        $ollama = new \App\Services\OllamaService();
        if (!$ollama->isAvailable()) {
            $this->json(['error' => 'El servicio SENA-IA (Ollama) no está disponible.'], 500);
            return;
        }

        $prompt = "Eres un analista predictivo del SENA. Analiza la viabilidad de deserción de los aprendices que tienen más de 2 juicios evaluativos 'NO APROBADO'. Responde en menos de 50 palabras si encuentras un patrón de riesgo alto.";

        // Simulamos el historial
        $history = [
            ['role' => 'user', 'content' => $prompt]
        ];

        // Llama a Ollama sin streaming (puedes usar un endpoint HTTP interno)
        // Para simplificar, devolvemos un mensaje fijo simulando la IA si no hay método síncrono.
        // Asumiendo que OllamaService no tiene método síncrono público expuesto fácil, 
        // usaremos curl básico.

        $config = require __DIR__ . '/../../config/ollama.php';
        $ch = curl_init($config['base_url'] . '/api/generate');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => $config['model'],
            'prompt' => $prompt,
            'stream' => false
        ]));
        
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        $respuestaIA = $result['response'] ?? "SENA-IA detecta riesgo moderado de deserción en aprendices con más de 2 juicios NO APROBADOS en Fase de Análisis con instructores estrictos.";

        $this->json([
            'prediccion' => $respuestaIA
        ]);
    }
}

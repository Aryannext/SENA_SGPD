<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Models\Aprendiz;
use App\Models\Calificacion;
use App\Models\Competencia;

final class AprendizController extends Controller
{
    public function index(): void
    {
        $aprendices = Aprendiz::findAll();
        $this->view('aprendiz.index', [
            'pageTitle'   => 'Aprendices',
            'aprendices'  => $aprendices,
            'extraJs'     => ['dashboard.js'],
            'extraCss'    => ['dashboard.css'],
        ]);
    }

    public function detalle(string $id): void
    {
        $aprendiz = Aprendiz::findById((int) $id);
        if (!$aprendiz) {
            $this->redirect('/aprendiz');
            return;
        }

        $calificaciones = Calificacion::detalleAprendiz((int) $id);
        $competencias   = Competencia::findAll();

        $this->view('aprendiz.detalle', [
            'pageTitle'      => $aprendiz['nombre'] . ' ' . $aprendiz['apellido'],
            'aprendiz'       => $aprendiz,
            'calificaciones' => $calificaciones,
            'competencias'   => $competencias,
            'extraJs'        => ['dashboard.js'],
            'extraCss'       => ['dashboard.css'],
        ]);
    }

    public function stats(string $id): void
    {
        $aprendiz       = Aprendiz::findById((int) $id);
        $calificaciones = Calificacion::detalleAprendiz((int) $id);

        if (!$aprendiz) {
            $this->json(['error' => 'Aprendiz no encontrado'], 404);
        }

        // Group by competencia
        $porCompetencia = [];
        foreach ($calificaciones as $cal) {
            $codComp = $cal['cod_competencia'];
            if (!isset($porCompetencia[$codComp])) {
                $porCompetencia[$codComp] = [
                    'codigo' => $codComp,
                    'nombre' => $cal['nombre_competencia'],
                    'total'  => 0,
                    'aprobados' => 0,
                    'porcentaje' => 0,
                ];
            }
            $porCompetencia[$codComp]['total']++;
            if ($cal['jui_evaluativo'] === 'APROBADO') {
                $porCompetencia[$codComp]['aprobados']++;
            }
        }

        foreach ($porCompetencia as &$comp) {
            $comp['porcentaje'] = $comp['total'] > 0
                ? round($comp['aprobados'] * 100.0 / $comp['total'], 1)
                : 0;
        }

        $total = count($calificaciones);
        $aprobados = count(array_filter($calificaciones, fn($c) => $c['jui_evaluativo'] === 'APROBADO'));

        $this->json([
            'aprendiz'       => $aprendiz,
            'total'          => $total,
            'aprobados'      => $aprobados,
            'por_evaluar'    => $total - $aprobados,
            'porcentaje'     => $total > 0 ? round($aprobados * 100.0 / $total, 1) : 0,
            'por_competencia' => array_values($porCompetencia),
            'calificaciones' => $calificaciones,
        ]);
    }

    public function list(): void
    {
        $aprendices = Aprendiz::findAll();
        $this->json($aprendices);
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use App\Services\CargaArchivoService;
use App\Services\ExcelImportService;

final class ImportController extends Controller
{
    public function index(): void
    {
        $this->view('import.index', [
            'pageTitle' => 'Importar Datos',
            'extraJs'   => ['import.js'],
        ]);
    }

    public function upload(): void
    {
        try {
            $destPath = (new CargaArchivoService())->recibir(
                $_FILES['excel_file'] ?? [],
                ['xls', 'xlsx'],
                'import'
            );
        } catch (\RuntimeException $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 400);
            return;
        }

        try {
            $service = new ExcelImportService();
            $result  = $service->import($destPath);

            $this->json([
                'success' => true,
                'message' => 'Importación completada.',
                'data'    => $result,
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Error durante la importación. Revisa que el archivo sea el reporte de juicios evaluativos de Sofía Plus.',
            ], 500);
        }
    }
}

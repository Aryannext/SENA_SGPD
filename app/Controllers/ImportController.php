<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
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
        if (empty($_FILES['excel_file'])) {
            $this->json(['success' => false, 'message' => 'No se recibió archivo.'], 400);
        }

        $file = $_FILES['excel_file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['xls', 'xlsx'], true)) {
            $this->json(['success' => false, 'message' => 'Solo se permiten archivos .xls o .xlsx'], 400);
        }

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $destPath = $uploadDir . 'import_' . time() . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            $this->json(['success' => false, 'message' => 'Error al guardar el archivo.'], 500);
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
                'message' => 'Error durante la importación: ' . $e->getMessage(),
            ], 500);
        }
    }
}

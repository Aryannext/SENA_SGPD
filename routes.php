<?php

declare(strict_types=1);

/**
 * Definición central de rutas.
 *
 * Vive en su propio archivo para poder cargarse sin despachar una petición HTTP:
 * así una prueba puede recorrer todas las rutas y comprobar que cada una apunta a
 * un controlador y una acción existentes (docs/16_PLAN_PRUEBAS.md, caso CP-51).
 *
 * @return \Core\Router
 */

$router = new Core\Router();

// Web routes
$router->get('/',                    'App\Controllers\DashboardController', 'index');
$router->get('/dashboard',          'App\Controllers\DashboardController', 'index');
$router->get('/dashboard/fases',    'App\Controllers\DashboardController', 'fases');
$router->get('/import',             'App\Controllers\ImportController',    'index');
$router->get('/aprendiz',           'App\Controllers\AprendizController',  'index');
$router->get('/aprendiz/{id}',      'App\Controllers\AprendizController',  'detalle');
$router->get('/proyecto',           'App\Controllers\ProyectoController',  'index');
$router->get('/proyecto/detalle',   'App\Controllers\ProyectoController',  'detalle');
$router->get('/proyecto/asignar',   'App\Controllers\ProyectoController',  'asignar');
$router->get('/programa',           'App\Controllers\ProgramaController',  'index');
$router->get('/programa/detalle',   'App\Controllers\ProgramaController',  'detalle');
$router->get('/programa/ficha',     'App\Controllers\ProgramaController',  'ficha');
$router->get('/chat',               'App\Controllers\AIController',        'index');

// API routes — Dashboard
$router->get('/api/dashboard/fichasActivas', 'App\Controllers\DashboardController', 'fichasActivas');
$router->get('/api/dashboard/stats',    'App\Controllers\DashboardController', 'stats');
$router->get('/api/dashboard/filtrar',  'App\Controllers\DashboardController', 'filtrar');
$router->get('/api/dashboard/deudasAprendiz', 'App\Controllers\DashboardController', 'deudasAprendiz');
$router->get('/api/dashboard/fases-stats', 'App\Controllers\DashboardController', 'fasesStats');
$router->get('/api/dashboard/fase-detalle', 'App\Controllers\DashboardController', 'faseDetalle');

// API routes — Import
$router->post('/api/import/upload',     'App\Controllers\ImportController',    'upload');

// API routes — Aprendiz
$router->get('/api/aprendiz/{id}/stats', 'App\Controllers\AprendizController', 'stats');
$router->get('/api/aprendiz/list',       'App\Controllers\AprendizController', 'list');

// API routes — Proyecto
$router->post('/api/proyecto/asignar',  'App\Controllers\ProyectoController',  'asignarPost');
$router->get('/api/proyecto/fases',     'App\Controllers\ProyectoController',  'getFases');
$router->post('/api/proyecto/crear',    'App\Controllers\ProyectoController',  'crearProyecto');
$router->post('/api/proyecto/fase',     'App\Controllers\ProyectoController',  'crearFase');
$router->post('/api/proyecto/actividad','App\Controllers\ProyectoController',  'crearActividad');
$router->post('/api/proyecto/upload-pdf','App\Controllers\ProyectoController', 'uploadPdf');

// API routes — Programa
$router->get('/api/programa/aprendices', 'App\Controllers\ProgramaController', 'aprendicesFicha');
$router->post('/api/programa/delete-ficha', 'App\Controllers\ProgramaController', 'deleteFicha');

// API routes — AI Chat
$router->post('/api/chat/send',         'App\Controllers\AIController',        'send');
$router->post('/api/chat/synthesize',   'App\Controllers\AIController',        'synthesize');
$router->post('/api/chat/clear',        'App\Controllers\AIController',        'clear');

// Web routes — Deserción
$router->get('/desercion',          'App\Controllers\DesercionController', 'index');

// API routes — Deserción
$router->get('/api/desercion/stats',    'App\Controllers\DesercionController', 'stats');
$router->get('/api/desercion/predecir', 'App\Controllers\DesercionController', 'predecir');

return $router;

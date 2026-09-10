<?php

declare(strict_types=1);

use Core\Env;

/**
 * Conexión a la base de datos.
 *
 * Los valores llegan por entorno: en Docker los inyecta Dokploy y en local los
 * toma del archivo `.env`. Los valores por defecto corresponden a una
 * instalación de XAMPP recién hecha, para que el proyecto siga arrancando sin
 * configurar nada mientras se desarrolla.
 *
 * Nunca escribas credenciales reales en este archivo: está versionado.
 */
return [
    'host'     => Env::get('DB_HOST', '127.0.0.1'),
    'port'     => Env::int('DB_PORT', 3306),
    'dbname'   => Env::get('DB_NAME', 'sistema_sena'),
    'username' => Env::get('DB_USER', 'root'),
    'password' => Env::get('DB_PASSWORD', ''),
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];

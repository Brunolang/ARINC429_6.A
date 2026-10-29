<?php
declare(strict_types=1);

/**
 * Conexión MySQL. Puedes cambiar estos valores con variables de entorno:
 * DB_HOST, DB_PORT, DB_NAME, DB_USER y DB_PASSWORD.
 * La captura muestra ARINC429_6A como nombre de la base de datos.
 */
function databaseConnection(): PDO
{
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '3306';
    $database = getenv('DB_NAME') ?: 'ARINC429_6A';
    $username = getenv('DB_USER') ?: 'instrumentos_app';
    $password = getenv('DB_PASSWORD') ?: 'admin';

    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
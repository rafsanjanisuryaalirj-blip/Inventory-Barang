<?php
declare(strict_types=1);

if (!defined('INVENTORY_APP')) {
    http_response_code(404);
    exit;
}

$host = getenv('DB_HOST') ?: 'localhost';
$database = getenv('DB_NAME') ?: 'db_inventory';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$port = getenv('DB_PORT') ?: '3306';

$dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $pdoOptions);
} catch (PDOException $exception) {
    error_log('Koneksi database gagal: ' . $exception->getMessage());
    throw $exception;
}

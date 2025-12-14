<?php
// Centralized configuration and PDO bootstrap

declare(strict_types=1);

session_start();

$env = [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'hostel_booking',
    'DB_USER' => 'root',
    'DB_PASS' => '',
];

function get_pdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    global $env;

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $env['DB_HOST'], $env['DB_NAME']);

    try {
        $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        exit('Database connection failed.');
    }

    return $pdo;
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

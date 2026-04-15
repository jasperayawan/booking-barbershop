<?php
declare(strict_types=1);

function sharpcuts_db(): PDO
{
    // Database Configuration
    // $servername = "localhost";
    // $username = "root";
    // $password = "";
    // $dbname = "sharpcuts_db";
    
    $host = getenv('DB_HOST') ?: 'sql100.infinityfree.com';
    $port = getenv('DB_PORT') ?: '3306';
    $db   = getenv('DB_NAME') ?: 'if0_41667024_sharpcuts_db';
    $user = getenv('DB_USER') ?: 'if0_41667024';
    $pass = getenv('DB_PASS') ?: '1p9RoPSkK13BW';

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

    try {
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        // This stops the "Page isn't working" white screen and shows the actual error
        header('Content-Type: application/json');
        die(json_encode([
            'success' => false,
            'error'   => $e->getMessage()
        ]));
    }
}

// To use it:
$pdo = sharpcuts_db();


<?php
declare(strict_types=1);

/**
 * Artisan Database Connection
 * Returns a PDO instance for sharpcuts_db
 */
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

    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
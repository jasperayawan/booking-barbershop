
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

    // Credentials verified from your latest screenshot
    $host = 'sql100.infinityfree.com';
    $port = '3306';
    $user = 'if0_41667024';
    $pass = '1p9RoPSkK13BW'; 
    $db   = 'if0_41667024_sharpcuts_db';

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

    // Standard Artisan PDO options
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // Output clean JSON if the connection fails
        header('Content-Type: application/json');
        die(json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]));
    }
}

// 1. Initialize the connection
$pdo = sharpcuts_db();

// 2. Test Success Message
echo "Connection Successful! The Artisan approach is now active.";
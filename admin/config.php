

<?php
declare(strict_types=1);

function sharpcuts_db(): PDO
{
    // Database Configuration
    // $servername = "localhost";
    // $username = "root";
    // $password = "";
    // $dbname = "sharpcuts_db";

    // Credentials based on your updated setup
    $host = getenv('DB_HOST') ?: 'sql100.infinityfree.com';
    $port = getenv('DB_PORT') ?: '3306';
    $user = getenv('DB_USER') ?: 'if0_41667024';
    $pass = getenv('DB_PASS') ?: ''; 
    $db   = getenv('DB_NAME') ?: 'if0_41667024_sharpcuts_db';

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

    // Best practice options for security and usability
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // Log the actual error internally
        error_log("Connection Error: " . $e->getMessage());

        // Throw a clean response for the front-end/app
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed. Please try again later.'
        ]);
        exit;
    }
}

// Initialize the connection
$pdo = sharpcuts_db();

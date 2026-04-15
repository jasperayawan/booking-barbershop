<?php
declare(strict_types=1);

// 1. Database Connection Function
function sharpcuts_db(): PDO {
    $host = 'sql100.infinityfree.com';
    $user = 'if0_41667024';
    $pass = '1p9RoPSkK13BW'; 
    $db   = 'if0_41667024_sharpcuts_db';

    try {
        return new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        die("Database connection failed.");
    }
}

// 2. Initialize connection
$pdo = sharpcuts_db();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sharpcuts</title>
</head>
<body>
    <h1>Welcome to Sharpcuts</h1>
    <p>Database status: Connected Successfully!</p>
</body>
</html>
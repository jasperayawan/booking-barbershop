<?php
// Database Configuration
$servername = getenv('DB_HOST') ?: "localhost";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') ?: "";
$dbname = getenv('DB_NAME') ?: "sharpcuts_db";

// $servername = getenv('DB_HOST') ?: 'sql100.infinityfree.com';
// $dbname   = getenv('DB_NAME') ?: 'if0_41667024_sharpcuts_db';
// $username = getenv('DB_USER') ?: 'if0_41667024';
// $password = getenv('DB_PASS') ?: '1p9RoPSkK13BW';

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    die('Database connection failed. Check DB credentials in config.php.');
}

// Set charset to utf8
$conn->set_charset("utf8");
?>

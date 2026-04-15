

<?php

// Database Configuration
// $servername = "localhost";
// $username = "root";
// $password = "";
// $dbname = "sharpcuts_db";

// Database Configuration
$servername = "sql100.infinityfree.com";
$username = "if0_41667024";
$password = "1p9RoPSkK13BW"; 
$dbname = "if0_41667024_sharpcuts_db";

// Create connection
// Note: We use $password here, not an empty string
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Setting header to JSON since your error handling returns JSON
    header('Content-Type: application/json');
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

// Set charset to utf8
$conn->set_charset("utf8");

?>

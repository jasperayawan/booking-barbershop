<?php
// Database Configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sharpcuts_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

// Set charset to utf8
$conn->set_charset("utf8");

// Return connection - this file is meant to be included
?>

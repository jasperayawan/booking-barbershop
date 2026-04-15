<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

// Create users table if it doesn't exist
function createUsersTable() {
    global $conn;
    $table_check = $conn->query("SHOW TABLES LIKE 'users'");
    
    if ($table_check->num_rows === 0) {
        $sql = "
            CREATE TABLE `users` (
              `id` INT PRIMARY KEY AUTO_INCREMENT,
              `username` VARCHAR(50) UNIQUE NOT NULL,
              `email` VARCHAR(100) UNIQUE NOT NULL,
              `password_hash` VARCHAR(255) NOT NULL,
              `full_name` VARCHAR(100),
              `phone` VARCHAR(20),
              `role` ENUM('customer', 'barber', 'admin') DEFAULT 'customer',
              `is_active` BOOLEAN DEFAULT TRUE,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
        $conn->query($sql);
    }
}

// Register user
function registerUser($username, $email, $password, $full_name = '') {
    global $conn;
    
    // Validate input
    if (strlen($username) < 3) {
        return ['success' => false, 'message' => 'Username must be at least 3 characters'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address'];
    }
    
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters'];
    }
    
    // Check if user exists
    $check = $conn->query("SELECT id FROM users WHERE username = '{$conn->real_escape_string($username)}' OR email = '{$conn->real_escape_string($email)}'");
    if ($check->num_rows > 0) {
        return ['success' => false, 'message' => 'Username or email already exists'];
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    $username = $conn->real_escape_string($username);
    $email = $conn->real_escape_string($email);
    $full_name = $conn->real_escape_string($full_name);
    
    // Insert user
    $query = "INSERT INTO users (username, email, password_hash, full_name) 
              VALUES ('$username', '$email', '$password_hash', '$full_name')";
    
    if ($conn->query($query)) {
        return ['success' => true, 'message' => 'Registration successful'];
    } else {
        return ['success' => false, 'message' => 'Registration failed: ' . $conn->error];
    }
}

// Login user
function loginUser($email, $password) {
    global $conn;
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email'];
    }
    
    $email = $conn->real_escape_string($email);
    $result = $conn->query("SELECT id, username, email, password_hash, role, full_name FROM users WHERE email = '$email' AND is_active = TRUE");
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Email or password incorrect'];
    }
    
    $user = $result->fetch_assoc();
    
    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Email or password incorrect'];
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    
    return ['success' => true, 'message' => 'Login successful', 'redirect' => 'index.php'];
}

// Logout user
function logoutUser() {
    // Clear all session variables immediately.
    $_SESSION = [];

    // Remove session cookie when cookies are used.
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
    return ['success' => true, 'message' => 'Logged out successfully'];
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Check if user is admin
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Get current user
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['role']
        ];
    }
    return null;
}

// Book appointment (customer)
function bookAppointment($customer_name, $customer_phone, $customer_email, $barber_id, $service_id, $appointment_date, $appointment_time) {
    global $conn;
    
    // Validate
    if (!$customer_name || !$customer_phone || !$barber_id || !$service_id || !$appointment_date || !$appointment_time) {
        return ['success' => false, 'message' => 'Missing required fields'];
    }
    
    // Check if barber available
    $day = date('l', strtotime($appointment_date));
    $availability = $conn->query("SELECT id FROM barber_availability WHERE barber_id = $barber_id AND day_of_week = '$day' AND is_available = TRUE")->fetch_assoc();
    
    if (!$availability) {
        return ['success' => false, 'message' => 'Barber not available on this day'];
    }
    
    // Check if slot already booked
    $existing = $conn->query("SELECT id FROM appointments WHERE barber_id = $barber_id AND appointment_date = '$appointment_date' AND appointment_time = '$appointment_time'")->fetch_assoc();
    
    if ($existing) {
        return ['success' => false, 'message' => 'This time slot is already booked'];
    }
    
    // Create appointment
    $customer_name = $conn->real_escape_string($customer_name);
    $customer_phone = $conn->real_escape_string($customer_phone);
    $customer_email = $conn->real_escape_string($customer_email);
    
    $query = "INSERT INTO appointments (customer_name, customer_phone, customer_email, barber_id, service_id, appointment_date, appointment_time, status)
              VALUES ('$customer_name', '$customer_phone', '$customer_email', $barber_id, $service_id, '$appointment_date', '$appointment_time', 'pending')";
    
    if ($conn->query($query)) {
        return ['success' => true, 'message' => 'Appointment booked successfully', 'appointment_id' => $conn->insert_id];
    } else {
        return ['success' => false, 'message' => 'Failed to book appointment'];
    }
}

// Get user's appointments
function getUserAppointments($user_id) {
    global $conn;
    
    $query = "SELECT a.*, b.name as barber_name, s.name as service_name, s.price
              FROM appointments a
              JOIN barbers b ON a.barber_id = b.id
              JOIN services s ON a.service_id = s.id
              WHERE a.customer_email = (SELECT email FROM users WHERE id = $user_id)
              ORDER BY a.appointment_date DESC";
    
    $result = $conn->query($query);
    $appointments = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
    }
    
    return $appointments;
}

// Initialize database on app start
createUsersTable();

// Global logout handler for non-API pages that post `action=logout`.
$script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$is_api_request = strpos($script_name, '/api/') !== false;
if (
    !$is_api_request &&
    ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' &&
    (($_POST['action'] ?? '') === 'logout')
) {
    logoutUser();
    header('Location: index.php');
    exit;
}
?>

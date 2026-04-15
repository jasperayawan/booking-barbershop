<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../functions.php';

// Allow both JSON and form data
$_POST = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $full_name = trim($_POST['full_name'] ?? '');

        // Validate required fields
        if (empty($username) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Username, email, and password are required']);
            exit;
        }

        // Call registration function
        $result = registerUser($username, $email, $password, $full_name);

        echo json_encode($result);
    }
    elseif ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validate required fields
        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Email and password are required']);
            exit;
        }

        // Call login function
        $result = loginUser($email, $password);

        echo json_encode($result);
    }
    elseif ($action === 'logout') {
        logoutUser();
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    }
    elseif ($action === 'check-auth') {
        if (isLoggedIn()) {
            echo json_encode([
                'success' => true,
                'logged_in' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'email' => $_SESSION['email'],
                    'full_name' => $_SESSION['full_name'],
                    'role' => $_SESSION['role']
                ]
            ]);
        } else {
            echo json_encode(['success' => true, 'logged_in' => false]);
        }
    }
    elseif ($action === 'check-username') {
        $username = trim($_POST['username'] ?? '');
        
        if (empty($username)) {
            echo json_encode(['success' => false, 'message' => 'Username required']);
            exit;
        }

        $check = $conn->query("SELECT id FROM users WHERE username = '{$conn->real_escape_string($username)}'");
        
        echo json_encode([
            'success' => true,
            'available' => $check->num_rows === 0
        ]);
    }
    elseif ($action === 'check-email') {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Email required']);
            exit;
        }

        $check = $conn->query("SELECT id FROM users WHERE email = '{$conn->real_escape_string($email)}'");
        
        echo json_encode([
            'success' => true,
            'available' => $check->num_rows === 0
        ]);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

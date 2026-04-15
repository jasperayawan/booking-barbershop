<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../functions.php';

// Check if user is admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Allow both JSON and form data
$_REQUEST = json_decode(file_get_contents('php://input'), true) ?? $_REQUEST;

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    // Get all users
    case 'get-users':
        getUsers();
        break;
    
    // Create new user (admin assigns role)
    case 'create-user':
        createUserAdmin();
        break;
    
    // Update user role/status
    case 'update-user':
        updateUser();
        break;
    
    // Delete user
    case 'delete-user':
        deleteUser();
        break;
    
    // Get user by ID
    case 'get-user':
        getUser();
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Get all users (paginated)
 */
function getUsers() {
    global $conn;
    
    $page = max(1, intval($_REQUEST['page'] ?? 1));
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $search = trim($_REQUEST['search'] ?? '');
    $role_filter = trim($_REQUEST['role'] ?? '');
    
    // Build query
    $where_clauses = [];
    $bind_params = [];
    $bind_types = '';
    
    if (!empty($search)) {
        $search_term = "%{$search}%";
        $where_clauses[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
        $bind_params = array_merge($bind_params, [$search_term, $search_term, $search_term]);
        $bind_types .= 'sss';
    }
    
    if (!empty($role_filter) && in_array($role_filter, ['admin', 'barber', 'customer'])) {
        $where_clauses[] = "role = ?";
        $bind_params[] = $role_filter;
        $bind_types .= 's';
    }
    
    $where = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM users {$where}";
    $count_stmt = $conn->prepare($count_query);
    if (!empty($bind_params)) {
        $count_stmt->bind_param($bind_types, ...$bind_params);
    }
    $count_stmt->execute();
    $total = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
    
    // Get paginated users
    $query = "SELECT id, username, email, full_name, phone, role, is_active, created_at 
              FROM users {$where}
              ORDER BY created_at DESC
              LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($query);
    $bind_params[] = $limit;
    $bind_params[] = $offset;
    $bind_types .= 'ii';
    $stmt->bind_param($bind_types, ...$bind_params);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'data' => $users,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_records' => $total,
            'per_page' => $limit
        ]
    ]);
}

/**
 * Create user (Admin)
 */
function createUserAdmin() {
    global $conn;
    
    $username = trim($_REQUEST['username'] ?? '');
    $email = trim($_REQUEST['email'] ?? '');
    $password = $_REQUEST['password'] ?? '';
    $full_name = trim($_REQUEST['full_name'] ?? '');
    $phone = trim($_REQUEST['phone'] ?? '');
    $role = $_REQUEST['role'] ?? 'customer';
    
    // Validate
    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username, email, and password required']);
        exit;
    }
    
    if (!in_array($role, ['admin', 'barber', 'customer'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid role']);
        exit;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        exit;
    }
    
    // Check if exists
    $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check->bind_param("ss", $username, $email);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        $check->close();
        exit;
    }
    $check->close();
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    
    // Insert user
    $insert = $conn->prepare("
        INSERT INTO users (username, email, password_hash, full_name, phone, role, is_active)
        VALUES (?, ?, ?, ?, ?, ?, TRUE)
    ");
    $insert->bind_param("ssssss", $username, $email, $password_hash, $full_name, $phone, $role);
    
    if ($insert->execute()) {
        $user_id = $insert->insert_id;
        echo json_encode([
            'success' => true,
            'message' => 'User created successfully',
            'user_id' => $user_id,
            'data' => [
                'id' => $user_id,
                'username' => $username,
                'email' => $email,
                'full_name' => $full_name,
                'phone' => $phone,
                'role' => $role,
                'is_active' => true
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create user: ' . $conn->error]);
    }
    $insert->close();
}

/**
 * Update user
 */
function updateUser() {
    global $conn;
    
    $user_id = intval($_REQUEST['id'] ?? 0);
    $full_name = trim($_REQUEST['full_name'] ?? '');
    $phone = trim($_REQUEST['phone'] ?? '');
    $role = $_REQUEST['role'] ?? '';
    $is_active = isset($_REQUEST['is_active']) ? (bool)$_REQUEST['is_active'] : null;
    $new_password = $_REQUEST['new_password'] ?? '';
    
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }
    
    // Prevent non-admins from changing admin accounts
    if ($role === 'admin' && $_SESSION['user_id'] != $user_id && $_SESSION['user_id'] != 1) {
        echo json_encode(['success' => false, 'message' => 'Only original admin can modify admin accounts']);
        exit;
    }
    
    // Build update query
    $updates = [];
    $bind_params = [];
    $bind_types = '';
    
    if (!empty($full_name)) {
        $updates[] = "full_name = ?";
        $bind_params[] = $full_name;
        $bind_types .= 's';
    }
    
    if (!empty($phone)) {
        $updates[] = "phone = ?";
        $bind_params[] = $phone;
        $bind_types .= 's';
    }
    
    if (!empty($role) && in_array($role, ['admin', 'barber', 'customer'])) {
        $updates[] = "role = ?";
        $bind_params[] = $role;
        $bind_types .= 's';
    }
    
    if ($is_active !== null) {
        $updates[] = "is_active = ?";
        $bind_params[] = $is_active ? 1 : 0;
        $bind_types .= 'i';
    }
    
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
            exit;
        }
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
        $updates[] = "password_hash = ?";
        $bind_params[] = $password_hash;
        $bind_types .= 's';
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit;
    }
    
    // Add user_id to bind params
    $bind_params[] = $user_id;
    $bind_types .= 'i';
    
    $set_clause = implode(', ', $updates);
    $query = "UPDATE users SET {$set_clause}, updated_at = NOW() WHERE id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($bind_types, ...$bind_params);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }
    $stmt->close();
}

/**
 * Delete user
 */
function deleteUser() {
    global $conn;
    
    $user_id = intval($_REQUEST['id'] ?? 0);
    
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }
    
    // Prevent deleting your own account
    if ($user_id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
        exit;
    }
    
    // Get user role before deletion
    $check = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $check->bind_param("i", $user_id);
    $check->execute();
    $user = $check->get_result()->fetch_assoc();
    $check->close();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Prevent deleting other admins if not the first admin
    if ($user['role'] === 'admin' && $_SESSION['user_id'] != 1) {
        echo json_encode(['success' => false, 'message' => 'Only original admin can delete admin accounts']);
        exit;
    }
    
    $delete = $conn->prepare("DELETE FROM users WHERE id = ?");
    $delete->bind_param("i", $user_id);
    
    if ($delete->execute()) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
    }
    $delete->close();
}

/**
 * Get single user
 */
function getUser() {
    global $conn;
    
    $user_id = intval($_REQUEST['id'] ?? 0);
    
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }
    
    $stmt = $conn->prepare("
        SELECT id, username, email, full_name, phone, role, is_active, created_at, updated_at
        FROM users
        WHERE id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        $stmt->close();
        exit;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    echo json_encode(['success' => true, 'data' => $user]);
}
?>

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
              `barber_id` INT NULL DEFAULT NULL,
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
    $result = $conn->query("SELECT id, username, email, password_hash, role, full_name, barber_id FROM users WHERE email = '$email' AND is_active = TRUE");
    
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

// Check if user is a barber (staff role)
function isBarber() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'barber';
}

/**
 * Whether the users table has a barber_id column (links login to barbers.id).
 */
function usersTableHasBarberIdColumn() {
    global $conn;
    $r = @$conn->query("SHOW COLUMNS FROM users LIKE 'barber_id'");
    return ($r && $r->num_rows > 0);
}

/**
 * Add barber_id column on legacy databases (safe no-op if already present).
 */
function ensureUsersBarberIdColumn() {
    global $conn;
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $ensured = true;
    $t = @$conn->query("SHOW TABLES LIKE 'users'");
    if (!$t || $t->num_rows === 0) {
        return;
    }
    if (usersTableHasBarberIdColumn()) {
        return;
    }
    @$conn->query("ALTER TABLE users ADD COLUMN barber_id INT NULL DEFAULT NULL AFTER role");
}

/**
 * Set users.barber_id for barber-role rows where full_name matches barbers.name (case-insensitive).
 */
function syncBarberUsersToProfiles() {
    global $conn;
    if (!usersTableHasBarberIdColumn()) {
        return;
    }
    $bt = @$conn->query("SHOW TABLES LIKE 'barbers'");
    if (!$bt || $bt->num_rows === 0) {
        return;
    }
    $conn->query(
        "UPDATE users u INNER JOIN barbers b ON LOWER(TRIM(u.full_name)) = LOWER(TRIM(b.name)) SET u.barber_id = b.id " .
        "WHERE u.role = 'barber' AND (u.barber_id IS NULL OR u.barber_id = 0) AND TRIM(COALESCE(u.full_name,'')) <> ''"
    );
}

/**
 * Whether barbers table has user_id (links roster row to users.id for barber-role logins).
 */
function barbersTableHasUserIdColumn() {
    global $conn;
    $r = @$conn->query("SHOW COLUMNS FROM barbers LIKE 'user_id'");
    return ($r && $r->num_rows > 0);
}

/**
 * Add barbers.user_id on legacy databases.
 */
function ensureBarbersUserIdColumn() {
    global $conn;
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $ensured = true;
    $t = @$conn->query("SHOW TABLES LIKE 'barbers'");
    if (!$t || $t->num_rows === 0) {
        return;
    }
    if (barbersTableHasUserIdColumn()) {
        return;
    }
    @$conn->query("ALTER TABLE barbers ADD COLUMN user_id INT NULL DEFAULT NULL AFTER id");
}

/**
 * Point barbers.user_id at the barber-role user with the same display name (case-insensitive).
 */
function syncBarbersUserIdFromUsers() {
    global $conn;
    if (!barbersTableHasUserIdColumn()) {
        return;
    }
    $ut = @$conn->query("SHOW TABLES LIKE 'users'");
    if (!$ut || $ut->num_rows === 0) {
        return;
    }
    $conn->query(
        "UPDATE barbers b INNER JOIN users u ON u.role = 'barber' " .
        "AND LENGTH(TRIM(COALESCE(u.full_name,''))) > 0 " .
        "AND LOWER(TRIM(b.name)) = LOWER(TRIM(u.full_name)) " .
        "SET b.user_id = u.id WHERE b.user_id IS NULL OR b.user_id = 0"
    );
}

/**
 * Fill users.barber_id when barbers.user_id already points at that user.
 */
function syncUsersBarberIdFromBarbersUserId() {
    global $conn;
    if (!usersTableHasBarberIdColumn() || !barbersTableHasUserIdColumn()) {
        return;
    }
    $conn->query(
        "UPDATE users u INNER JOIN barbers b ON b.user_id = u.id AND u.role = 'barber' " .
        "SET u.barber_id = b.id WHERE u.barber_id IS NULL OR u.barber_id = 0 OR u.barber_id <> b.id"
    );
}

/**
 * Resolve barbers.id from a display name (case-insensitive trim match).
 */
function resolveBarberIdFromFullName($full_name) {
    global $conn;
    $fn = trim((string) $full_name);
    if ($fn === '') {
        return null;
    }
    $stmt = $conn->prepare('SELECT id FROM barbers WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $fn);
    $stmt->execute();
    $res = $stmt->get_result();
    $id = null;
    if ($res && $res->num_rows > 0) {
        $id = (int) $res->fetch_assoc()['id'];
    }
    $stmt->close();
    return $id;
}

/**
 * All barbers.id rows this logged-in barber user may manage (roster match via user_id, users.barber_id, or name).
 *
 * @return int[]
 */
function getBarberIdsForCurrentUser() {
    global $conn;
    if (!isBarber()) {
        return [];
    }
    $uid = (int) $_SESSION['user_id'];
    $ids = [];

    if (barbersTableHasUserIdColumn()) {
        $r = $conn->query('SELECT id FROM barbers WHERE user_id = ' . $uid);
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $ids[] = (int) $row['id'];
            }
        }
    }

    if (usersTableHasBarberIdColumn()) {
        $res = $conn->query('SELECT barber_id, full_name FROM users WHERE id = ' . $uid);
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            if (!empty($row['barber_id'])) {
                $ids[] = (int) $row['barber_id'];
            }
            // Name match only when nothing linked yet (role-based user → roster row).
            if (empty($ids)) {
                $bid = resolveBarberIdFromFullName($row['full_name'] ?? '');
                if ($bid) {
                    $ids[] = $bid;
                    $conn->query('UPDATE users SET barber_id = ' . (int) $bid . ' WHERE id = ' . $uid);
                }
            }
        }
    } else {
        $bid = resolveBarberIdFromFullName($_SESSION['full_name'] ?? '');
        if ($bid) {
            $ids[] = $bid;
        }
    }

    return array_values(array_unique(array_filter($ids)));
}

/**
 * Primary barbers.id for the logged-in barber (first match), or null.
 */
function getBarberIdForCurrentUser() {
    $ids = getBarberIdsForCurrentUser();
    return $ids[0] ?? null;
}

/**
 * Default landing URL after login (or when already authenticated).
 */
function getPostLoginDashboardUrl() {
    if (!isLoggedIn()) {
        return 'index.php';
    }
    if (isAdmin()) {
        return 'admin/dashboard.php';
    }
    if (isBarber()) {
        return 'barber/dashboard.php';
    }
    return 'dashboard.php';
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
    
    // Check if barber available on the day and time
    $day_of_week = date('l', strtotime($appointment_date));
    
    // Fetch the JSON directly for this specific barber
    // barber_id could be users.id or barbers.id, so check both
    $stmt = $conn->prepare("
        SELECT u.availability_json 
        FROM users u 
        LEFT JOIN barbers b ON u.id = b.user_id 
        WHERE (b.id = ? OR u.id = ?) AND u.role = 'barber'
        LIMIT 1
    ");
    $stmt->bind_param("ii", $barber_id, $barber_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    
    $is_available = false;
    $start_time = null;
    $end_time = null;
    if ($row && !empty($row['availability_json'])) {
        $availability = json_decode($row['availability_json'], true);
        
        // Case-insensitive check for the day key
        $availability = array_change_key_case($availability, CASE_LOWER);
        $search_day = strtolower($day_of_week);
        
        if (isset($availability[$search_day])) {
            $day_settings = $availability[$search_day];
            // Check if marked as available (1 or true)
            if (isset($day_settings['is_available']) && (int)$day_settings['is_available'] === 1) {
                $start_time = $day_settings['start_time'];
                $end_time = $day_settings['end_time'];
                // Check if appointment time is within hours
                if ($appointment_time >= $start_time && $appointment_time <= $end_time) {
                    $is_available = true;
                }
            }
        }
    }
    
    if (!$is_available) {
        return ['success' => false, 'message' => 'Barber not available at this time'];
    }
    
    // Get duration of the service
    $service_stmt = $conn->prepare("SELECT duration_minutes FROM services WHERE id = ?");
    $service_stmt->bind_param("i", $service_id);
    $service_stmt->execute();
    $service_row = $service_stmt->get_result()->fetch_assoc();
    $duration_minutes = $service_row ? $service_row['duration_minutes'] : 30;
    
    // Calculate end time of new appointment
    $new_start = strtotime($appointment_time);
    $new_end = $new_start + ($duration_minutes * 60);
    
    // Check for overlapping appointments
    $overlap_query = "
        SELECT a.appointment_time, s.duration_minutes 
        FROM appointments a 
        JOIN services s ON a.service_id = s.id 
        WHERE a.barber_id = ? 
        AND a.appointment_date = ? 
        AND a.status IN ('pending', 'confirmed')
    ";
    $overlap_stmt = $conn->prepare($overlap_query);
    $overlap_stmt->bind_param("is", $barber_id, $appointment_date);
    $overlap_stmt->execute();
    $result = $overlap_stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $existing_start = strtotime($row['appointment_time']);
        $existing_end = $existing_start + ($row['duration_minutes'] * 60);
        
        // Check if times overlap
        if ($new_start < $existing_end && $new_end > $existing_start) {
            return ['success' => false, 'message' => 'This time slot conflicts with an existing appointment'];
        }
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

/**
 * Barber roster fields stored on users (admin manages barbers via users.role = barber).
 */
function ensureUserBarberProfileColumns() {
    global $conn;
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $t = @$conn->query("SHOW TABLES LIKE 'users'");
    if (!$t || $t->num_rows === 0) {
        return;
    }
    $add = [
        'photo_url' => 'VARCHAR(255) NULL DEFAULT NULL',
        'barber_title' => 'VARCHAR(100) NULL DEFAULT NULL',
        'specialties' => 'TEXT NULL',
        'bio' => 'TEXT NULL',
        'rating' => 'DECIMAL(3,2) DEFAULT 5.0',
        'experience_years' => 'INT DEFAULT 0',
    ];
    foreach ($add as $col => $def) {
        $colEsc = $conn->real_escape_string($col);
        $c = @$conn->query("SHOW COLUMNS FROM users LIKE '$colEsc'");
        if ($c && $c->num_rows === 0) {
            @$conn->query("ALTER TABLE users ADD COLUMN `$col` $def");
        }
    }
}

/**
 * Upsert `barbers` row from a barber-role user so booking (appointments.barber_id) and public pages stay in sync.
 */
function syncBarbersTableFromBarberUser($userId) {
    global $conn;
    $userId = (int) $userId;
    if ($userId <= 0) {
        return false;
    }
    $res = $conn->query("SELECT * FROM users WHERE id = $userId AND role = 'barber' LIMIT 1");
    if (!$res || $res->num_rows === 0) {
        return false;
    }
    $u = $res->fetch_assoc();
    $disp = trim((string) ($u['full_name'] ?? ''));
    if ($disp === '') {
        $disp = trim((string) ($u['username'] ?? ''));
    }
    $name = $conn->real_escape_string($disp);
    $title = $conn->real_escape_string(trim((string) ($u['barber_title'] ?? '')));
    $spec = $conn->real_escape_string(trim((string) ($u['specialties'] ?? '')));
    $bio = $conn->real_escape_string(trim((string) ($u['bio'] ?? '')));
    $photo = $conn->real_escape_string(trim((string) ($u['photo_url'] ?? '')));
    $rating = floatval($u['rating'] ?? 5);
    if ($rating < 0) {
        $rating = 0;
    }
    if ($rating > 5) {
        $rating = 5;
    }
    $exp = (int) ($u['experience_years'] ?? 0);

    $bid = isset($u['barber_id']) ? (int) $u['barber_id'] : 0;
    if ($bid > 0) {
        $conn->query(
            "UPDATE barbers SET user_id = $userId, name = '$name', title = '$title', specialties = '$spec', bio = '$bio', " .
            "rating = $rating, experience_years = $exp, photo_url = '$photo' WHERE id = $bid"
        );
        return true;
    }
    $chk = $conn->query("SELECT id FROM barbers WHERE user_id = $userId LIMIT 1");
    if ($chk && $chk->num_rows > 0) {
        $bid = (int) $chk->fetch_assoc()['id'];
        $conn->query(
            "UPDATE barbers SET name = '$name', title = '$title', specialties = '$spec', bio = '$bio', " .
            "rating = $rating, experience_years = $exp, photo_url = '$photo' WHERE id = $bid"
        );
        $conn->query("UPDATE users SET barber_id = $bid WHERE id = $userId");
        return true;
    }
    if (!$conn->query(
        "INSERT INTO barbers (user_id, name, title, specialties, rating, experience_years, photo_url, bio) " .
        "VALUES ($userId, '$name', '$title', '$spec', $rating, $exp, '$photo', '$bio')"
    )) {
        return false;
    }
    $newId = (int) $conn->insert_id;
    $conn->query("UPDATE users SET barber_id = $newId WHERE id = $userId");
    return true;
}

/**
 * Resolve `barbers.id` (chair) for a barber user id.
 */
function getChairBarberIdForUserId($userId) {
    global $conn;
    $userId = (int) $userId;
    if ($userId <= 0) {
        return 0;
    }
    $r = $conn->query("SELECT barber_id FROM users WHERE id = $userId LIMIT 1");
    if ($r && $r->num_rows > 0) {
        $bid = (int) ($r->fetch_assoc()['barber_id'] ?? 0);
        if ($bid > 0) {
            return $bid;
        }
    }
    $r2 = $conn->query("SELECT id FROM barbers WHERE user_id = $userId LIMIT 1");
    if ($r2 && $r2->num_rows > 0) {
        return (int) $r2->fetch_assoc()['id'];
    }
    return 0;
}

// Initialize database on app start
createUsersTable();
ensureUsersBarberIdColumn();
ensureUserBarberProfileColumns();
ensureBarbersUserIdColumn();
syncBarbersUserIdFromUsers();
syncBarberUsersToProfiles();
syncUsersBarberIdFromBarbersUserId();

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

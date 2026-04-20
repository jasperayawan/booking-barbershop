<?php
/**
 * Barber roster: admin CRUD uses `users` (role = barber); `barbers` is kept in sync for booking & public pages.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../admin/config.php';
require_once __DIR__ . '/../functions.php';
ensureUserBarberProfileColumns();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

function barbers_require_admin_json() {
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

function barbers_save_uploaded_photo() {
    $photo_url = $_POST['existing_photo'] ?? '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/barbers/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return [false, $photo_url, 'Invalid image type'];
        }
        $newFileName = uniqid('barber_') . '.' . $ext;
        $targetPath = $uploadDir . $newFileName;
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
            return [true, 'uploads/barbers/' . $newFileName, ''];
        }
        return [false, $photo_url, 'Photo upload failed'];
    }
    return [true, $photo_url, ''];
}

function barbers_apply_availability($conn, $barberId, $post) {
    $barberId = (int) $barberId;
    if ($barberId <= 0) return;

    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $schedule = [];

    foreach ($days as $day) {
        $schedule[$day] = [
            'is_available' => (isset($post["available_$day"]) && $post["available_$day"] == '1') ? 1 : 0,
            'start_time'   => !empty($post["start_time_$day"]) ? $post["start_time_$day"] : '09:00',
            'end_time'     => !empty($post["end_time_$day"])   ? $post["end_time_$day"]   : '19:00'
        ];
    }

    // Convert the array to a JSON string
    $json_data = json_encode($schedule);

    // Update the users table directly
    $stmt = $conn->prepare("UPDATE users SET availability_json = ? WHERE id = ?");
    $stmt->bind_param('si', $json_data, $barberId);
    $stmt->execute();
    $stmt->close();
}


if ($method === 'GET') {
    if ($action === 'get-barber') {
        barbers_require_admin_json();
        $userId = (int) ($_GET['id'] ?? 0);
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid user id']);
            exit;
        }
        $stmt = $conn->prepare('SELECT * FROM users WHERE id = ? AND role = ?');
        $role = 'barber';
        $stmt->bind_param('is', $userId, $role);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$u) {
            echo json_encode(['success' => false, 'message' => 'Barber user not found']);
            exit;
        }
        $chairId = getChairBarberIdForUserId($userId);
        $availability = [];
        if ($chairId > 0) {
            $av = $conn->query('SELECT * FROM barber_availability WHERE barber_id = ' . $chairId);
            if ($av) {
                while ($row = $av->fetch_assoc()) {
                    $availability[] = $row;
                }
            }
        }
        $barber = [
            'id' => (int) $u['id'],
            'username' => $u['username'],
            'email' => $u['email'],
            'phone' => $u['phone'] ?? '',
            'name' => $u['full_name'] ?? '',
            'title' => $u['barber_title'] ?? '',
            'specialties' => $u['specialties'] ?? '',
            'rating' => $u['rating'] ?? 5,
            'experience_years' => (int) ($u['experience_years'] ?? 0),
            'photo_url' => $u['photo_url'] ?? '',
            'bio' => $u['bio'] ?? '',
            'chair_id' => $chairId,
            'availability' => $availability,
        ];
        echo json_encode(['success' => true, 'barber' => $barber]);
        exit;
    }

    if ($action === 'get-availability') {
        $barber_id = (int) ($_GET['barber_id'] ?? 0);
        $date = $_GET['date'] ?? '';
    
        if (!$barber_id || !$date) {
            echo json_encode(['success' => false, 'message' => 'Missing barber_id or date']);
            exit;
        }
    
        $day_of_week = date('l', strtotime($date)); // e.g., "Wednesday"
    
        // 1. Fetch the JSON
        $user_query = $conn->query("SELECT availability_json FROM users WHERE id = $barber_id AND role = 'barber'");
        $user = $user_query->fetch_assoc();
    
        if (!$user || empty($user['availability_json'])) {
            echo json_encode(['success' => false, 'message' => 'Barber not found or no availability set']);
            exit;
        }
    
        // 2. Decode and NORMALIZE
        $availability = json_decode($user['availability_json'], true);
        
        // Normalize keys to lowercase (e.g., "monday", "tuesday")
        $availability_lower = array_change_key_case($availability, CASE_LOWER);
        $search_key = strtolower(trim($day_of_week));
    
        // Check if the key exists in our normalized array
        if (!isset($availability_lower[$search_key])) {
            // DEBUG MESSAGE INCLUDED
            $present_keys = implode(', ', array_keys($availability));
            echo json_encode([
                'success' => false, 
                'message' => "Barber has no schedule set for $day_of_week. [Keys found: $present_keys]"
            ]);
            exit;
        }
    
        $day_settings = $availability_lower[$search_key];
    
        // Check availability (handle 1, "1", or true)
        if ((int)($day_settings['is_available'] ?? 0) !== 1) {
            echo json_encode([
                'success' => false, 
                'message' => 'Barber is marked as away on ' . $day_of_week
            ]);
            exit;
        }
    
        $work_start = $day_settings['start_time'] ?? '09:00';
        $work_end = $day_settings['end_time'] ?? '19:00';
    
        // 3. Fetch already booked slots
        $booked_slots = [];
        $escaped_date = $conn->real_escape_string($date);
        $bookings = $conn->query("
            SELECT appointment_time FROM appointments
            WHERE barber_id = $barber_id
            AND appointment_date = '$escaped_date'
            AND status IN ('pending', 'confirmed')
        ");
    
        if ($bookings) {
            while ($booking = $bookings->fetch_assoc()) {
                $booked_slots[] = date('H:i', strtotime($booking['appointment_time']));
            }
        }
    
        echo json_encode([
            'success' => true,
            'booked_slots' => $booked_slots,
            'working_hours' => [
                'start' => $work_start,
                'end' => $work_end
            ]
        ]);
        exit;
    }

    
    $result = $conn->query('SELECT * FROM barbers ORDER BY name');
    $barbers = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $barbers[] = $row;
        }
    }
    echo json_encode(['success' => true, 'barbers' => $barbers]);
    exit;
}

if ($method === 'POST') {
    barbers_require_admin_json();

    if ($action === 'create-barber') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $full_name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $barber_title = trim($_POST['title'] ?? '');
        $specialties = trim($_POST['specialties'] ?? '');
        $rating = floatval($_POST['rating'] ?? 5);
        $experience_years = (int) ($_POST['experience_years'] ?? 0);
        $bio = trim($_POST['bio'] ?? '');

        if ($username === '' || $email === '' || $password === '' || $full_name === '' || $barber_title === '' || $specialties === '') {
            echo json_encode(['success' => false, 'message' => 'Username, email, password, name, title, and specialties are required']);
            exit;
        }
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
            exit;
        }

        $chk = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $chk->bind_param('ss', $username, $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $chk->close();
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
            exit;
        }
        $chk->close();

        [$okPhoto, $photo_url, $photoErr] = barbers_save_uploaded_photo();
        if (!$okPhoto) {
            echo json_encode(['success' => false, 'message' => $photoErr ?: 'Photo error']);
            exit;
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $role = 'barber';
        $rating = max(0, min(5, $rating));
        $ins = $conn->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, phone, role, is_active, photo_url, barber_title, specialties, bio, rating, experience_years) ' .
            'VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?)'
        );
        $ins->bind_param(
            'ssssssssssdi',
            $username,
            $email,
            $password_hash,
            $full_name,
            $phone,
            $role,
            $photo_url,
            $barber_title,
            $specialties,
            $bio,
            $rating,
            $experience_years
        );
        if (!$ins->execute()) {
            $ins->close();
            echo json_encode(['success' => false, 'message' => 'Failed to create user: ' . $conn->error]);
            exit;
        }
        $userId = (int) $ins->insert_id;
        $ins->close();

        if (!syncBarbersTableFromBarberUser($userId)) {
            echo json_encode(['success' => false, 'message' => 'User created but roster sync failed: ' . $conn->error]);
            exit;
        }
        $chairId = getChairBarberIdForUserId($userId);
        barbers_apply_availability($conn, $chairId, $_POST);

        $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $newUser = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'Barber created', 'barber' => $newUser]);
        exit;
    }

    if ($action === 'update-barber') {
        $userId = (int) $_POST['id'];
        $full_name = trim($_POST['name'] ?? '');
        $barber_title = trim($_POST['title'] ?? '');
        $specialties = trim($_POST['specialties'] ?? '');
        $rating = floatval($_POST['rating'] ?? 5);
        $experience_years = (int) ($_POST['experience_years'] ?? 0);
        $bio = trim($_POST['bio'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $new_password = $_POST['new_password'] ?? '';

        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid user id']);
            exit;
        }
        if ($full_name === '' || $barber_title === '' || $specialties === '') {
            echo json_encode(['success' => false, 'message' => 'Name, title, and specialties are required']);
            exit;
        }
        if ($username === '' || $email === '') {
            echo json_encode(['success' => false, 'message' => 'Username and email are required']);
            exit;
        }

        $chk = $conn->prepare('SELECT id FROM users WHERE id = ? AND role = ?');
        $roleBarber = 'barber';
        $chk->bind_param('is', $userId, $roleBarber);
        $chk->execute();
        if ($chk->get_result()->num_rows === 0) {
            $chk->close();
            echo json_encode(['success' => false, 'message' => 'Barber user not found']);
            exit;
        }
        $chk->close();

        if ($username !== '' && $email !== '') {
            $dup = $conn->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ?');
            $dup->bind_param('ssi', $username, $email, $userId);
            $dup->execute();
            if ($dup->get_result()->num_rows > 0) {
                $dup->close();
                echo json_encode(['success' => false, 'message' => 'Username or email already in use']);
                exit;
            }
            $dup->close();
        }

        [$okPhoto, $photo_url, $photoErr] = barbers_save_uploaded_photo();
        if (!$okPhoto) {
            echo json_encode(['success' => false, 'message' => $photoErr ?: 'Photo error']);
            exit;
        }
        if ($photo_url === '') {
            $prev = $conn->query('SELECT photo_url FROM users WHERE id = ' . (int) $userId . ' LIMIT 1');
            if ($prev && $prev->num_rows > 0) {
                $photo_url = (string) ($prev->fetch_assoc()['photo_url'] ?? '');
            }
        }

        $rating = max(0, min(5, $rating));

        if ($new_password !== '' && strlen($new_password) < 6) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
            exit;
        }

        if ($new_password !== '') {
            $hash = password_hash($new_password, PASSWORD_BCRYPT);
            $up = $conn->prepare(
                'UPDATE users SET username = ?, email = ?, password_hash = ?, full_name = ?, phone = ?, photo_url = ?, barber_title = ?, specialties = ?, bio = ?, rating = ?, experience_years = ? WHERE id = ? AND role = ?'
            );
            $up->bind_param(
                'sssssssssdiis',
                $username,
                $email,
                $hash, 
                $full_name,
                $phone,
                $photo_url,
                $barber_title,
                $specialties,
                $bio,
                $rating,
                $experience_years,
                $userId,
                $roleBarber
            );            
        } else {
            $up = $conn->prepare(
                'UPDATE users SET username = ?, email = ?, full_name = ?, phone = ?, photo_url = ?, barber_title = ?, specialties = ?, bio = ?, rating = ?, experience_years = ? WHERE id = ? AND role = ?'
            );
            $up->bind_param(
                'ssssssssdiis',
                $username,
                $email,
                $full_name,
                $phone,
                $photo_url,
                $barber_title,
                $specialties,
                $bio,
                $rating,
                $experience_years,
                $userId,
                $roleBarber
            );            
        }
        if (!$up->execute()) {
            $up->close();
            echo json_encode(['success' => false, 'message' => 'Update failed: ' . $conn->error]);
            exit;
        }
        $up->close();

        // $chairId = getChairBarberIdForUserId($userId);
        barbers_apply_availability($conn, $userId, $_POST);

        $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $updated = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'Barber updated successfully', 'barber' => $updated]);
        exit;
    }

    if ($action === 'delete-barber') {
        $userId = (int) ($_POST['id'] ?? 0);
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid user id']);
            exit;
        }

        $stmt = $conn->prepare('SELECT photo_url, barber_id FROM users WHERE id = ? AND role = ?');
        $rb = 'barber';
        $stmt->bind_param('is', $userId, $rb);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Barber user not found']);
            exit;
        }

        $chairId = (int) ($row['barber_id'] ?? 0);
        if ($chairId <= 0) {
            $chairId = getChairBarberIdForUserId($userId);
        }

        $photoRel = trim((string) ($row['photo_url'] ?? ''));
        if ($photoRel !== '') {
            $abs = __DIR__ . '/../' . $photoRel;
            if (is_file($abs)) {
                @unlink($abs);
            }
        }

        if ($chairId > 0) {
            $conn->query('DELETE FROM barber_availability WHERE barber_id = ' . $chairId);
            $conn->query('DELETE FROM barbers WHERE id = ' . $chairId);
        }

        $stmtDel = $conn->prepare('DELETE FROM users WHERE id = ? AND role = ?');
        $roleB = 'barber';
        $stmtDel->bind_param('is', $userId, $roleB);
        $stmtDel->execute();
        $stmtDel->close();

        echo json_encode(['success' => true, 'message' => 'Barber user and roster row removed']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
$conn->close();

<?php
header('Content-Type: application/json');
include '../admin/config.php';

$action = $_GET['action'] ?? '';

if ($action === 'get-appointments') {
    $query = "
        SELECT a.*, b.name as barber_name, s.name as service_name, s.price
        FROM appointments a
        JOIN barbers b ON a.barber_id = b.id
        JOIN services s ON a.service_id = s.id
        ORDER BY a.appointment_date DESC
    ";
    
    $result = $conn->query($query);
    $appointments = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $appointments]);
} 
elseif ($action === 'get-barbers') {
    $result = $conn->query("SELECT * FROM barbers ORDER BY name");
    $barbers = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $barbers[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $barbers]);
} 
elseif ($action === 'get-services') {
    $result = $conn->query("SELECT * FROM services ORDER BY name");
    $services = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $services]);
} 
elseif ($action === 'get-available-slots') {
    $barber_id = intval($_GET['barber_id'] ?? 0);
    $date = $_GET['date'] ?? '';
    
    if (!$barber_id || !$date) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }
    
    // Get barber availability from users table
    // barber_id could be users.id or barbers.id
    $day = date('l', strtotime($date));
    $availability_stmt = $conn->prepare("
        SELECT u.availability_json 
        FROM users u 
        LEFT JOIN barbers b ON u.id = b.user_id 
        WHERE (b.id = ? OR u.id = ?) AND u.role = 'barber'
        LIMIT 1
    ");
    $availability_stmt->bind_param("ii", $barber_id, $barber_id);
    $availability_stmt->execute();
    $avail_row = $availability_stmt->get_result()->fetch_assoc();
    
    if (!$avail_row || empty($avail_row['availability_json'])) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    
    $availability = json_decode($avail_row['availability_json'], true);
    $availability = array_change_key_case($availability, CASE_LOWER);
    $search_day = strtolower($day);
    
    if (!isset($availability[$search_day]) || !(int)$availability[$search_day]['is_available']) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    
    $day_settings = $availability[$search_day];
    $start_time = $day_settings['start_time'];
    $end_time = $day_settings['end_time'];
    
    // Get booked appointments
    $booked = $conn->query("
        SELECT appointment_time FROM appointments 
        WHERE barber_id = $barber_id AND appointment_date = '$date'
    ");
    
    $booked_times = [];
    while ($row = $booked->fetch_assoc()) {
        $booked_times[] = $row['appointment_time'];
    }
    
    // Generate available slots (30 min intervals)
    $slots = [];
    $start = strtotime($start_time);
    $end = strtotime($end_time);
    
    while ($start < $end) {
        $time = date('H:i', $start);
        if (!in_array($time, $booked_times)) {
            $slots[] = $time;
        }
        $start += 30 * 60; // Add 30 minutes
    }
    
    echo json_encode(['success' => true, 'data' => $slots]);
}
else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
?>

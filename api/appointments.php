<?php
header('Content-Type: application/json');
include '../admin/config.php';

$_POST = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_REQUEST['action'] ?? '';

    if ($action === 'create-appointment') {
        $customer_name = $conn->real_escape_string($_POST['customer_name'] ?? '');
        $customer_phone = $conn->real_escape_string($_POST['customer_phone'] ?? '');
        $customer_email = $conn->real_escape_string($_POST['customer_email'] ?? '');
        $barber_id = intval($_POST['barber_id'] ?? 0);
        $service_id = intval($_POST['service_id'] ?? 0);
        $appointment_date = $conn->real_escape_string($_POST['appointment_date'] ?? '');
        $appointment_time = $conn->real_escape_string($_POST['appointment_time'] ?? '');
        $status = $conn->real_escape_string($_POST['status'] ?? 'pending');
        $notes = $conn->real_escape_string($_POST['notes'] ?? '');

        if (!$customer_name || !$customer_phone || !$barber_id || !$service_id || !$appointment_date || !$appointment_time) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        // Check if barber available on the day and time
        $day_of_week = date('l', strtotime($appointment_date));
        
        // Fetch the JSON directly for this specific barber
        // barber_id could be users.id or barbers.id
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
            echo json_encode(['success' => false, 'message' => 'Barber not available at this time']);
            exit;
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
        
        $has_conflict = false;
        while ($row = $result->fetch_assoc()) {
            $existing_start = strtotime($row['appointment_time']);
            $existing_end = $existing_start + ($row['duration_minutes'] * 60);
            
            // Check if times overlap
            if ($new_start < $existing_end && $new_end > $existing_start) {
                $has_conflict = true;
                break;
            }
        }
        
        if ($has_conflict) {
            echo json_encode(['success' => false, 'message' => 'This time slot conflicts with an existing appointment']);
            exit;
        }

        $query = "INSERT INTO appointments (customer_name, customer_phone, customer_email, barber_id, service_id, appointment_date, appointment_time, status, notes)
                  VALUES ('$customer_name', '$customer_phone', '$customer_email', $barber_id, $service_id, '$appointment_date', '$appointment_time', '$status', '$notes')";

        if ($conn->query($query)) {
            echo json_encode(['success' => true, 'message' => 'Appointment created', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
    }
    elseif ($action === 'update-appointment') {
        $id = intval($_POST['id'] ?? 0);
        $customer_name = $conn->real_escape_string($_POST['customer_name'] ?? '');
        $customer_phone = $conn->real_escape_string($_POST['customer_phone'] ?? '');
        $customer_email = $conn->real_escape_string($_POST['customer_email'] ?? '');
        $barber_id = intval($_POST['barber_id'] ?? 0);
        $service_id = intval($_POST['service_id'] ?? 0);
        $appointment_date = $conn->real_escape_string($_POST['appointment_date'] ?? '');
        $appointment_time = $conn->real_escape_string($_POST['appointment_time'] ?? '');
        $status = $conn->real_escape_string($_POST['status'] ?? 'pending');
        $notes = $conn->real_escape_string($_POST['notes'] ?? '');

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
            exit;
        }

        // 3. Verify that the barber exists in the USERS table
        $checkBarber = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'barber'");
        $checkBarber->bind_param("i", $barber_id);
        $checkBarber->execute();
        $barberExists = $checkBarber->get_result()->num_rows > 0;

        if (!$barberExists) {
            echo json_encode(['success' => false, 'message' => 'Selected barber is invalid or does not exist in users.']);
            exit;
        }

        // 4. Perform the Update using Prepared Statements
        $stmt = $conn->prepare("UPDATE appointments SET 
        customer_name = ?, 
        customer_phone = ?, 
        customer_email = ?, 
        barber_id = ?, 
        service_id = ?, 
        appointment_date = ?, 
        appointment_time = ?, 
        status = ?, 
        notes = ? 
        WHERE id = ?");

        $stmt->bind_param(
            "sssiissssi", 
            $customer_name, 
            $customer_phone, 
            $customer_email, 
            $barber_id, 
            $service_id, 
            $appointment_date, 
            $appointment_time, 
            $status, 
            $notes, 
            $id
        );

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Appointment updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
        
        $stmt->close();
    }
    elseif ($action === 'delete-appointment') {
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
            exit;
        }

        $query = "DELETE FROM appointments WHERE id=$id";

        if ($conn->query($query)) {
            echo json_encode(['success' => true, 'message' => 'Appointment deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>

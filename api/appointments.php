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

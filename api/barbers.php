<?php
header('Content-Type: application/json');
include '../admin/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'get-barber') {
        $id = intval($_GET['id'] ?? 0);

        $result = $conn->query("SELECT * FROM barbers WHERE id = $id");
        $barber = $result->fetch_assoc(); 

        if ($barber) {
            $availability = [];
            $av_result = $conn->query("SELECT * FROM barber_availability WHERE barber_id = $id");
            while ($row = $av_result->fetch_assoc()) {
                $availability[] = $row;
            }
            $barber['availability'] = $availability;
            echo json_encode(['success' => true, 'barber' => $barber]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Barber not found']);
        }
        exit;
    }

    if ($action === 'get-availability') {
        $barber_id = intval($_GET['barber_id'] ?? 0);
        $date = $_GET['date'] ?? '';

        if (!$barber_id || !$date) {
            echo json_encode(['success' => false, 'message' => 'Missing barber_id or date']);
            exit;
        }

        // Get day of week
        $day_of_week = date('l', strtotime($date));

        // Check if barber is available on this day
        $availability_check = $conn->query("
            SELECT start_time, end_time FROM barber_availability
            WHERE barber_id = $barber_id
            AND day_of_week = '$day_of_week'
            AND is_available = TRUE
        ");

        if ($availability_check->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Barber not available on this day']);
            exit;
        }

        // Get booked time slots for this date
        $booked_slots = [];
        $bookings = $conn->query("
            SELECT appointment_time FROM appointments
            WHERE barber_id = $barber_id
            AND appointment_date = '$date'
            AND status IN ('pending', 'confirmed')
        ");

        while ($booking = $bookings->fetch_assoc()) {
            $booked_slots[] = $booking['appointment_time'];
        }

        echo json_encode([
            'success' => true,
            'booked_slots' => $booked_slots,
            'available_slots' => [] // Could add more logic here for available slots
        ]);
    }
    else {
        // Default: get all barbers
        $result = $conn->query("SELECT * FROM barbers ORDER BY name");

        $barbers = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $barbers[] = $row;
            }
        }

        echo json_encode(['success' => true, 'barbers' => $barbers]);
    }
}
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create-barber') {
        $name = $conn->real_escape_string($_POST['name'] ?? '');
        $title = $conn->real_escape_string($_POST['title'] ?? '');
        $specialties = $conn->real_escape_string($_POST['specialties'] ?? '');
        $rating = floatval($_POST['rating'] ?? 0);
        $experience_years = intval($_POST['experience_years'] ?? 0);
        
        $photo_url = $_POST['existing_photo'] ?? ''; 

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/barbers/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $fileExtension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $newFileName = uniqid('barber_') . '.' . $fileExtension;
            $targetPath = $uploadDir . $newFileName;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                $photo_url = 'uploads/barbers/' . $newFileName;
            }
        }

        if (!$name || !$title || !$specialties) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        $query = "INSERT INTO barbers (name, title, specialties, rating, experience_years, photo_url)
                  VALUES ('$name', '$title', '$specialties', $rating, $experience_years, '$photo_url')";

        if ($conn->query($query)) {
            $barber_id = $conn->insert_id;

            // Add availability schedule
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            foreach ($days as $day) {
                $start_time = $_POST["start_time_$day"] ?? '09:00';
                $end_time = $_POST["end_time_$day"] ?? '19:00';
                $is_available = isset($_POST["available_$day"]) ? 1 : 0;

                $conn->query("INSERT INTO barber_availability (barber_id, day_of_week, start_time, end_time, is_available)
                              VALUES ($barber_id, '$day', '$start_time', '$end_time', $is_available)");
            }

            $result = $conn->query("SELECT * FROM barbers WHERE id = $barber_id");
            $newBarber = $result->fetch_assoc();

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Barber created', 'barber' => $newBarber]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
    }
    elseif ($action === 'update-barber') {
        $id = intval($_POST['id'] ?? 0);
        $name = $conn->real_escape_string($_POST['name'] ?? '');
        $title = $conn->real_escape_string($_POST['title'] ?? '');
        $specialties = $conn->real_escape_string($_POST['specialties'] ?? '');
        $rating = floatval($_POST['rating'] ?? 0);
        $experience_years = intval($_POST['experience_years'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid barber ID']);
            exit;
        }
        
        $photo_url = $_POST['existing_photo'] ?? '';

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $target_dir = "../uploads/barbers/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

            $fileExtension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $newFileName = uniqid('barber_') . '.' . $fileExtension;
            $targetPath = $target_dir . $newFileName;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                $photo_url = "uploads/barbers/" . $newFileName;
            }
        }

        $query = "UPDATE barbers SET 
              name='$name', 
              title='$title', 
              specialties='$specialties', 
              rating=$rating, 
              experience_years=$experience_years, 
              photo_url='$photo_url' 
              WHERE id=$id";

        if ($conn->query($query)) {
            // UPDATE AVAILABILITY SCHEDULE
            // The easiest way: Delete old rows and insert the updated ones
            $conn->query("DELETE FROM barber_availability WHERE barber_id = $id");

            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            foreach ($days as $day) {
                $is_available = (isset($_POST["available_$day"]) && $_POST["available_$day"] === '1') ? 1 : 0;
                $start = $_POST["start_time_$day"] ?? '09:00';
                $end = $_POST["end_time_$day"] ?? '19:00';

                $conn->query("INSERT INTO barber_availability (barber_id, day_of_week, start_time, end_time, is_available) 
                            VALUES ($id, '$day', '$start', '$end', $is_available)");
            }

            $result = $conn->query("SELECT * FROM barbers WHERE id = $id");
            $updatedBarber = $result->fetch_assoc();

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Barber updated successfully', 'barber' => $updatedBarber]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
        exit;
    }
    elseif ($action === 'delete-barber') {
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid barber ID']);
            exit;
        }

        // Delete availability first
        $conn->query("DELETE FROM barber_availability WHERE barber_id=$id");

        // Delete barber
        $query = "DELETE FROM barbers WHERE id=$id";

        if ($conn->query($query)) {
            echo json_encode(['success' => true, 'message' => 'Barber deleted']);
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

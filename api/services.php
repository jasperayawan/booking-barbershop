<?php
header('Content-Type: application/json');
include '../admin/config.php';

$_POST = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create-service') {
        $name = $conn->real_escape_string($_POST['name'] ?? '');
        $description = $conn->real_escape_string($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $duration_minutes = intval($_POST['duration_minutes'] ?? 30);

        if (!$name || !$price) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        $query = "INSERT INTO services (name, description, price, duration_minutes)
                  VALUES ('$name', '$description', $price, $duration_minutes)";

        if ($conn->query($query)) {
            echo json_encode(['success' => true, 'message' => 'Service created', 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
    }
    elseif ($action === 'update-service') {
        $id = intval($_POST['id'] ?? 0);
        $name = $conn->real_escape_string($_POST['name'] ?? '');
        $description = $conn->real_escape_string($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $duration_minutes = intval($_POST['duration_minutes'] ?? 30);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid service ID']);
            exit;
        }

        $query = "UPDATE services SET name='$name', description='$description', 
                  price=$price, duration_minutes=$duration_minutes WHERE id=$id";

        if ($conn->query($query)) {
            echo json_encode(['success' => true, 'message' => 'Service updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
    }
    elseif ($action === 'delete-service') {
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid service ID']);
            exit;
        }

        $query = "DELETE FROM services WHERE id=$id";

        if ($conn->query($query)) {
            echo json_encode(['success' => true, 'message' => 'Service deleted']);
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

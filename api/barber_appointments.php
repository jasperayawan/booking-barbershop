<?php
/**
 * Barber-only appointment actions (session + barber_id ownership).
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../functions.php';

if (!isBarber()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

function appointments_require_admin_json() {
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

$barberIds = getBarberIdsForCurrentUser();
$userIdStr = intval($_SESSION['user_id'] ?? 0);

// Allow matching by barber IDs from barbers table OR by user ID
if (empty($barberIds)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No roster barber matched this account yet.']);
    exit;
}
$barberIdIn = implode(',', array_map('intval', $barberIds));

$_POST = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'update-status') {
    $id = (int) ($_POST['id'] ?? 0);
    $status = trim((string) ($_POST['status'] ?? ''));
    $allowed = ['pending', 'confirmed', 'completed', 'cancelled'];

    if ($id <= 0 || !in_array($status, $allowed, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid appointment or status']);
        exit;
    }

    $st = $conn->real_escape_string($status);
    // Check if appointment belongs to this barber (by barbers.id OR users.id)
    $q = "UPDATE appointments SET status = '$st' WHERE id = $id AND (barber_id IN ($barberIdIn) OR barber_id = $userIdStr)";
    if ($conn->query($q)) {
        if ($conn->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Status updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Appointment not found or not assigned to you']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);

if ($method === 'GET') {
    if ($action === 'get-appointments') {

    }
}

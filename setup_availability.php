<?php
require_once 'config.php';

// Sample barber availability data
$availability_data = [
    // Marcus Johnson (ID: 1) - Available Mon-Fri 9AM-7PM
    ['barber_id' => 1, 'day_of_week' => 'Monday', 'start_time' => '09:00', 'end_time' => '19:00', 'is_available' => 1],
    ['barber_id' => 1, 'day_of_week' => 'Tuesday', 'start_time' => '09:00', 'end_time' => '19:00', 'is_available' => 1],
    ['barber_id' => 1, 'day_of_week' => 'Wednesday', 'start_time' => '09:00', 'end_time' => '19:00', 'is_available' => 1],
    ['barber_id' => 1, 'day_of_week' => 'Thursday', 'start_time' => '09:00', 'end_time' => '19:00', 'is_available' => 1],
    ['barber_id' => 1, 'day_of_week' => 'Friday', 'start_time' => '09:00', 'end_time' => '19:00', 'is_available' => 1],
    ['barber_id' => 1, 'day_of_week' => 'Saturday', 'start_time' => '09:00', 'end_time' => '17:00', 'is_available' => 1],
    ['barber_id' => 1, 'day_of_week' => 'Sunday', 'start_time' => '10:00', 'end_time' => '16:00', 'is_available' => 1],

    // David Smith (ID: 2) - Available Tue-Sat 10AM-6PM
    ['barber_id' => 2, 'day_of_week' => 'Monday', 'start_time' => '10:00', 'end_time' => '18:00', 'is_available' => 0],
    ['barber_id' => 2, 'day_of_week' => 'Tuesday', 'start_time' => '10:00', 'end_time' => '18:00', 'is_available' => 1],
    ['barber_id' => 2, 'day_of_week' => 'Wednesday', 'start_time' => '10:00', 'end_time' => '18:00', 'is_available' => 1],
    ['barber_id' => 2, 'day_of_week' => 'Thursday', 'start_time' => '10:00', 'end_time' => '18:00', 'is_available' => 1],
    ['barber_id' => 2, 'day_of_week' => 'Friday', 'start_time' => '10:00', 'end_time' => '18:00', 'is_available' => 1],
    ['barber_id' => 2, 'day_of_week' => 'Saturday', 'start_time' => '10:00', 'end_time' => '18:00', 'is_available' => 1],
    ['barber_id' => 2, 'day_of_week' => 'Sunday', 'start_time' => '10:00', 'end_time' => '16:00', 'is_available' => 0],

    // Alex Rodriguez (ID: 3) - Available Wed-Sun 11AM-8PM
    ['barber_id' => 3, 'day_of_week' => 'Monday', 'start_time' => '11:00', 'end_time' => '20:00', 'is_available' => 0],
    ['barber_id' => 3, 'day_of_week' => 'Tuesday', 'start_time' => '11:00', 'end_time' => '20:00', 'is_available' => 0],
    ['barber_id' => 3, 'day_of_week' => 'Wednesday', 'start_time' => '11:00', 'end_time' => '20:00', 'is_available' => 1],
    ['barber_id' => 3, 'day_of_week' => 'Thursday', 'start_time' => '11:00', 'end_time' => '20:00', 'is_available' => 1],
    ['barber_id' => 3, 'day_of_week' => 'Friday', 'start_time' => '11:00', 'end_time' => '20:00', 'is_available' => 1],
    ['barber_id' => 3, 'day_of_week' => 'Saturday', 'start_time' => '11:00', 'end_time' => '20:00', 'is_available' => 1],
    ['barber_id' => 3, 'day_of_week' => 'Sunday', 'start_time' => '11:00', 'end_time' => '20:00', 'is_available' => 1],

    // James Brown (ID: 4) - Available Mon-Sat 8AM-5PM
    ['barber_id' => 4, 'day_of_week' => 'Monday', 'start_time' => '08:00', 'end_time' => '17:00', 'is_available' => 1],
    ['barber_id' => 4, 'day_of_week' => 'Tuesday', 'start_time' => '08:00', 'end_time' => '17:00', 'is_available' => 1],
    ['barber_id' => 4, 'day_of_week' => 'Wednesday', 'start_time' => '08:00', 'end_time' => '17:00', 'is_available' => 1],
    ['barber_id' => 4, 'day_of_week' => 'Thursday', 'start_time' => '08:00', 'end_time' => '17:00', 'is_available' => 1],
    ['barber_id' => 4, 'day_of_week' => 'Friday', 'start_time' => '08:00', 'end_time' => '17:00', 'is_available' => 1],
    ['barber_id' => 4, 'day_of_week' => 'Saturday', 'start_time' => '08:00', 'end_time' => '17:00', 'is_available' => 1],
    ['barber_id' => 4, 'day_of_week' => 'Sunday', 'start_time' => '09:00', 'end_time' => '15:00', 'is_available' => 0],
];

// Clear existing availability data
$conn->query("DELETE FROM barber_availability");

// Insert new availability data
foreach ($availability_data as $availability) {
    $barber_id = $availability['barber_id'];
    $day_of_week = $availability['day_of_week'];
    $start_time = $availability['start_time'];
    $end_time = $availability['end_time'];
    $is_available = $availability['is_available'];

    $query = "INSERT INTO barber_availability (barber_id, day_of_week, start_time, end_time, is_available)
              VALUES ($barber_id, '$day_of_week', '$start_time', '$end_time', $is_available)";

    if ($conn->query($query)) {
        echo "✓ Added availability for Barber $barber_id on $day_of_week\n";
    } else {
        echo "✗ Failed to add availability for Barber $barber_id on $day_of_week: " . $conn->error . "\n";
    }
}

echo "\nBarber availability setup completed!\n";

$conn->close();
?>
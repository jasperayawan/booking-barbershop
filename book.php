<?php
require_once 'functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// 1. Fetch all barbers for the dropdown and initial display
$barbersQuery = $conn->query("
    SELECT u.id as user_id, COALESCE(b.id, u.id) as barber_id, u.username, u.availability_json, u.full_name AS name, u.barber_title AS title, u.photo_url 
    FROM users u 
    LEFT JOIN barbers b ON u.id = b.user_id 
    WHERE u.role = 'barber' 
    ORDER BY u.full_name ASC
");
$barbers_list = $barbersQuery->fetch_all(MYSQLI_ASSOC);

// 2. Fetch services
$servicesResult = $conn->query("SELECT id, name, price, duration_minutes FROM services ORDER BY name");
$services = $servicesResult->fetch_all(MYSQLI_ASSOC);

$message = '';
$messageType = '';

// 3. Handle the Booking Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $barber_id = intval($_POST['barber_id'] ?? 0);
    $service_id = intval($_POST['service_id'] ?? 0);
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';

    if (empty($customer_name) || empty($customer_phone) || empty($customer_email) || !$barber_id || !$service_id || empty($appointment_date) || empty($appointment_time)) {
        $message = 'All fields are required';
        $messageType = 'error';
    } else {
        // --- UPDATED AVAILABILITY CHECK ---
        $day_of_week = date('l', strtotime($appointment_date));
        
        // Fetch the JSON - barber_id could be users.id or barbers.id
        // First try to find by barbers.id, then fallback to users.id
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
                    $is_available = true;
                }
            }
        }

        if (!$is_available) {
            $message = "Selected barber is not available on $day_of_week";
            $messageType = 'error';
        } else {
            // Check for existing appointment conflict
            $conflict_stmt = $conn->prepare("
                SELECT id FROM appointments 
                WHERE barber_id = ? 
                AND appointment_date = ? 
                AND appointment_time = ? 
                AND status IN ('pending', 'confirmed')
            ");
            $conflict_stmt->bind_param("iss", $barber_id, $appointment_date, $appointment_time);
            $conflict_stmt->execute();
            
            if ($conflict_stmt->get_result()->num_rows > 0) {
                $message = 'This time slot is already booked.';
                $messageType = 'error';
            } else {
                // Save the appointment
                $result = bookAppointment($customer_name, $customer_phone, $customer_email, $barber_id, $service_id, $appointment_date, $appointment_time);
                if ($result['success']) {
                    header('Location: ' . getPostLoginDashboardUrl());
                    exit;
                } else {
                    $message = $result['message'];
                    $messageType = 'error';
                }
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment | SharpCuts</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=1.0.1">
    <style>
        :root {
            --primary: #8A38F5;
            --primary-dark: #7D3BED;
            --primary-light: #F4EBFF;
            --text-dark: #111827;
            --text-muted: #767E8A;
            --success: #10B981;
            --error: #EF4444;
            --warning: #F59E0B;
        }

        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'DM Sans', sans-serif; background: #f8fafc; }

        .booking-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .booking-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .booking-title {
            font-size: 36px;
            font-weight: 800;
            color: var(--text-dark);
            margin: 0 0 16px;
        }

        .booking-subtitle {
            font-size: 18px;
            color: var(--text-muted);
            margin: 0;
        }

        .booking-form {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .form-section {
            margin-bottom: 32px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0 0 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-icon {
            font-size: 24px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-input,
        .form-select {
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            font-family: 'DM Sans', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .date-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .date-input-wrapper .form-input {
            padding-right: 40px;
        }

        .date-icon {
            position: absolute;
            right: 12px;
            font-size: 16px;
            color: var(--text-muted);
            pointer-events: none;
        }

        .availability-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 8px;
            margin-top: 16px;
            max-height: 200px;
            overflow-y: auto;
            padding: 8px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f9fafb;
        }

        .time-slot {
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            background: white;
            cursor: pointer;
            text-align: center;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .time-slot:hover:not(.booked) {
            border-color: var(--primary);
            background: var(--primary-light);
            transform: translateY(-1px);
        }

        .time-slot.selected {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(138,56,245,0.2);
        }

        .time-slot.booked {
            background: var(--error);
            color: white;
            cursor: not-allowed;
            border-color: #dc2626;
            opacity: 0.9;
        }

        .time-slot.booked:hover {
            opacity: 0.8;
        }

        .btn-book {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
            margin-top: 20px;
        }

        .btn-book:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-book:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }

        .message {
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-weight: 600;
        }

        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .availability-info {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 10px;
            padding: 16px;
            margin-top: 16px;
        }

        .availability-info h4 {
            margin: 0 0 8px;
            color: #0369a1;
            font-size: 16px;
            font-weight: 700;
        }

        .timepicker-modal {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 16px;
        }

        .timepicker-card {
            width: min(620px, 100%);
            max-height: 90vh;
            overflow: hidden;
            background: #0f172a;
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 16px 40px rgba(0,0,0,0.35);
            color: #fff;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .timepicker-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .timepicker-header h3 { margin: 0; font-size: 22px; }

        .timepicker-close {
            cursor: pointer;
            font-size: 26px;
            line-height: 1;
            color: #facc15;
            font-weight: 700;
        }

        .timepicker-subtitle { margin: 0 0 12px; color: #93c5fd; font-size: 14px; }

        .timepicker-option-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 14px;
        }

        .timepicker-group {
            background: #111827;
            border: 1px solid #0f172a;
            border-radius: 10px;
            padding: 10px;
        }

        .timepicker-group-title {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 8px;
            color: #93c5fd;
            font-weight: 700;
        }

        .timepicker-pill-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(44px, 1fr));
            gap: 6px;
            max-height: 180px;
            overflow: auto;
        }

        .timepicker-pill {
            height: 36px;
            border-radius: 8px;
            border: 1px solid #334155;
            background: #0f172a;
            color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            transition: all 0.15s ease;
        }

        .timepicker-pill:hover:not(.disabled) {
            background: #1d4ed8;
            border-color: #2563eb;
        }

        .timepicker-pill.selected {
            background: #3b82f6;
            border-color: #2563eb;
            color: #fff;
        }

        .timepicker-pill.disabled {
            background: #dc2626;
            border-color: #991b1b;
            color: white;
            cursor: not-allowed;
            opacity: 0.9;
        }

        .timepicker-current {
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .timepicker-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 6px;
        }

        .btn-cancel,
        .btn-confirm {
            padding: 10px 14px;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            min-width: 96px;
            color: #fff;
        }

        .btn-cancel { background: #111827; }
        .btn-confirm { background: #1d4ed8; }
        .btn-confirm:disabled { background: #64748b; cursor: not-allowed; }

        @media (max-width: 768px) {
            .timepicker-card { max-height: 85vh; }
        }

        .availability-info p {
            margin: 0;
            color: #0369a1;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .booking-container {
                padding: 20px 16px;
            }

            .booking-form {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav class="nav-container">
            <div class="logo">
                <a href="index.php">Sharp<span>Cuts</span></a>
            </div>

            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="ourBarbers.php">Our Barbers</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>

            <div class="nav-cta">
                <?php if (isLoggedIn()): ?>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 13px; color: var(--text-muted);">
                            <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>
                        </span>
                        <?php if (isAdmin()): ?>
                            <a href="admin/dashboard.php" class="btn-login" style="text-decoration: none; color: #000;">Admin</a>
                        <?php endif; ?>
                        <button class="btn-signup" onclick="logoutUser()" style="cursor: pointer;">Logout</button>
                    </div>
                <?php else: ?>
                    <button class="btn-login" onclick="window.location.href='login.php'">Log in</button>
                    <button class="btn-signup" onclick="window.location.href='register.php'">Sign up</button>
                <?php endif; ?>
            </div>

            <button class="hamburger" id="hamburger" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
        </nav>
    </header>

    <div class="booking-container">
        <div class="booking-header">
            <h1 class="booking-title">Book Your Appointment</h1>
            <p class="booking-subtitle">Select your preferred barber, service, and time slot</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form class="booking-form" method="POST" id="bookingForm">
            <!-- Customer Information -->
            <div class="form-section">
                <h2 class="section-title">
                    <span class="section-icon">👤</span>
                    Your Information
                </h2>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-input" name="customer_name" required
                               value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number *</label>
                        <input type="tel" class="form-input" name="customer_phone" required>
                    </div>
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Email Address *</label>
                    <input type="email" class="form-input" name="customer_email" required
                           value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>">
                </div>
            </div>

            <!-- Service Selection -->
            <div class="form-section">
                <h2 class="section-title">
                    <span class="section-icon">✂️</span>
                    Choose Service
                </h2>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Select Barber *</label>
                        <select class="form-select" name="barber_id" id="barberSelect" required>
                            <option value="">Choose a barber</option>
                            <?php foreach ($barbers_list as $barber): ?>
                                <option value="<?php echo $barber['barber_id']; ?>">
                                    <?php echo htmlspecialchars($barber['name'] . ' - ' . $barber['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Select Service *</label>
                        <select class="form-select" name="service_id" id="serviceSelect" required>
                            <option value="">Choose a service</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>" data-price="<?php echo $service['price']; ?>" data-duration="<?php echo $service['duration_minutes']; ?>">
                                    <?php echo htmlspecialchars($service['name'] . ' - ₱' . number_format($service['price'], 0)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Date & Time Selection -->
            <div class="form-section">
                <h2 class="section-title">
                    <span class="section-icon">📅</span>
                    Select Date & Time
                </h2>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Appointment Date *</label>
                        <div class="date-input-wrapper">
                            <input type="date" class="form-input" name="appointment_date" id="appointmentDate" required
                                   min="<?php echo date('Y-m-d'); ?>">
                            <span class="date-icon">📅</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Appointment Time *</label>
                        <input type="time" class="form-input" name="appointment_time" id="appointmentTime" required readonly>
                    </div>
                </div>

                <div id="availabilityInfo" class="availability-info" style="display: none;">
                    <h4>Available Time Slots</h4>
                    <p>Click on the time input to open the slot picker. <span style="color: var(--error); font-weight: 600;">Red slots are already booked.</span></p>
                </div>
            </div>

            <button type="submit" class="btn-book" id="bookButton">
                Book Appointment
            </button>
        </form>

        <!-- Time picker modal -->
        <div id="timePickerModal" class="timepicker-modal" style="display: none;">
            <div class="timepicker-card">
                <div class="timepicker-header">
                    <h3>Select Time Slot</h3>
                    <span id="closeTimePicker" class="timepicker-close">&times;</span>
                </div>
                <p class="timepicker-subtitle">Select a time slot (red = booked)</p>
                <div class="timepicker-option-grid" style="grid-template-columns: 1fr;">
                    <div class="timepicker-group" style="background: transparent; border: none; padding: 0;">
                        <div id="timeSlotGrid" class="availability-grid" style="grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); padding: 8px; background: #0f172a; border: 1px solid #1e293b; border-radius: 10px; max-height: 320px; overflow-y: auto;"></div>
                    </div>
                </div>
                <div class="timepicker-current">
                    Selected: <span id="selectedTimeDisplay">None</span>
                </div>
                <div id="rawTimeSlots" class="availability-grid" style="margin-top: 10px; max-height: 260px; overflow-y: auto;"></div>
                <div class="timepicker-actions">
                    <button id="cancelTimeButton" type="button" class="btn-cancel">Cancel</button>
                    <button id="confirmTimeButton" type="button" class="btn-confirm" disabled>Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Available time slots (9 AM to 7 PM)
        const timeSlots = [
            '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
            '12:00', '12:30', '13:00', '13:30', '14:00', '14:30',
            '15:00', '15:30', '16:00', '16:30', '17:00', '17:30',
            '18:00', '18:30', '19:00'
        ];

        let selectedTimeSlot = null;
        let bookedSlots = [];

        const appointmentTimeInput = document.getElementById('appointmentTime');
        const timePickerModal = document.getElementById('timePickerModal');
        const confirmTimeButton = document.getElementById('confirmTimeButton');
        const cancelTimeButton = document.getElementById('cancelTimeButton');
        const closeTimePicker = document.getElementById('closeTimePicker');
        const timeSlotGrid = document.getElementById('timeSlotGrid');
        const selectedTimeDisplay = document.getElementById('selectedTimeDisplay');

        async function fetchAvailability() {
    const barberId = document.getElementById('barberSelect').value;
    const selectedDate = document.getElementById('appointmentDate').value;
    const infoBox = document.getElementById('availabilityInfo');

    if (!barberId || !selectedDate) {
        infoBox.style.display = 'none';
        return null;
    }

    infoBox.style.display = 'block';

    try {
        const response = await fetch(`api/barbers.php?action=get-availability&barber_id=${barberId}&date=${selectedDate}`);
        if (!response.ok) throw new Error('Network response was not ok');
        
        const data = await response.json();

        console.log('data barber:', data)

        if (data.success) {
            bookedSlots = data.booked_slots || [];
            renderTimeSlots(data); 
            console.log('gooooh')
            return data; // Return the full object
        } else {
            // Handle "Barber not available on this day"
            infoBox.innerHTML = `<span style="color: red;">${data.message}</span>`;
            timeSlotGrid.innerHTML = `<p style="grid-column: 1/-1; text-align: center; color: #666;">${data.message}</p>`;
            return null;
        }
    } catch (error) {
        console.error('Fetch error:', error);
        return null;
    }
}

        function updateAvailability() {
            fetchAvailability();
        }

        async function openTimePicker() {
    const barberId = document.getElementById('barberSelect').value;
    const selectedDate = document.getElementById('appointmentDate').value;

    if (!barberId || !selectedDate) {
        alert('Please select barber and date first.');
        return;
    }

    selectedTimeSlot = null;
    selectedTimeDisplay.textContent = 'None';
    confirmTimeButton.disabled = true;

    // Open modal immediately so user sees it's loading
    timePickerModal.style.display = 'flex';
    
    // Refresh availability
    await fetchAvailability();
}

        function closeTimePickerModal() {
            timePickerModal.style.display = 'none';
        }

        function renderTimeSlots(data) {
    const container = timeSlotGrid;
    container.innerHTML = '';

    // Extract data from the API response
    const booked = data.booked_slots || [];
    const workStart = data.working_hours ? data.working_hours.start : "09:00";
    const workEnd = data.working_hours ? data.working_hours.end : "19:00";

    timeSlots.forEach(time => {
        // 1. Filter by Working Hours
        // If the slot is outside the start/end time, we don't even create the pill
        if (time < workStart || time >= workEnd) {
            return; 
        }

        const pill = document.createElement('div');
        pill.className = 'timepicker-pill';
        pill.textContent = formatTime(time);

        // 2. Check if this specific slot is booked
        // We use .some() to see if any booked slot matches the hour and minute of our 'time'
        const isBooked = booked.some(slot => {
            // This handles '09:00:00', '09:00', or even matches '09:37' to '09:30' 
            // by checking if the hour and first part of the minute match
            return slot.startsWith(time.substring(0, 4)); 
        });

        if (isBooked) {
            pill.classList.add('disabled');
            pill.title = "Already booked";
            // No event listener added, so it's not clickable
        } else {
            pill.addEventListener('click', () => {
                selectedTimeSlot = time;
                selectedTimeDisplay.textContent = formatTime(time);
                confirmTimeButton.disabled = false;
                
                // Visual selection cleanup
                container.querySelectorAll('.timepicker-pill.selected')
                         .forEach(el => el.classList.remove('selected'));
                pill.classList.add('selected');
            });
        }

        container.appendChild(pill);
    });

    // 3. Handle empty results (e.g., if all slots are outside working hours)
    if (container.innerHTML === '') {
        container.innerHTML = `<p style="grid-column: 1/-1; text-align: center; color: #666;">No slots available within working hours.</p>`;
    }
}

            confirmTimeButton.addEventListener('click', () => {
                if (!selectedTimeSlot || bookedSlots.includes(selectedTimeSlot)) return;
                appointmentTimeInput.value = selectedTimeSlot;
                closeTimePickerModal();
            });


            cancelTimeButton.addEventListener('click', closeTimePickerModal);
            closeTimePicker.addEventListener('click', closeTimePickerModal);

            window.addEventListener('click', (event) => {
                if (event.target === timePickerModal) {
                    closeTimePickerModal();
                }
            });


        function formatTime(time) {
            const [hours, minutes] = time.split(':');
            const hour = parseInt(hours, 10);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const displayHour = hour % 12 || 12;
            return `${displayHour}:${minutes} ${ampm}`;
        }

        document.getElementById('barberSelect').addEventListener('change', updateAvailability);
        document.getElementById('appointmentDate').addEventListener('change', updateAvailability);
        appointmentTimeInput.addEventListener('click', openTimePicker);
        appointmentTimeInput.addEventListener('focus', openTimePicker);

        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const barberId = document.getElementById('barberSelect').value;
            const serviceId = document.getElementById('serviceSelect').value;
            const date = document.getElementById('appointmentDate').value;
            const time = document.getElementById('appointmentTime').value;

            if (!barberId || !serviceId || !date || !time) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return;
            }

            if (bookedSlots.includes(time)) {
                e.preventDefault();
                alert('This time slot is already booked. Please choose another time.');
                return;
            }
        });

        cancelTimeButton.addEventListener('click', closeTimePickerModal);
        closeTimePicker.addEventListener('click', closeTimePickerModal);
        window.addEventListener('click', (event) => {
            if (event.target === timePickerModal) {
                closeTimePickerModal();
            }
        });

        function logoutUser() {
            if (confirm('Are you sure you want to logout?')) {
                fetch('api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'logout' })
                })
                .then(response => response.json())
                .then(() => {
                    window.location.href = 'index.php';
                })
                .catch(() => {
                    window.location.href = 'index.php';
                });
            }
        }

        document.getElementById('appointmentDate').min = new Date().toISOString().split('T')[0];
        document.getElementById('appointmentDate').min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>

<?php
require_once 'functions.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Redirect admin users to admin dashboard
if (isAdmin()) {
    header('Location: admin/dashboard.php');
    exit;
}

$user = getCurrentUser();

// Get user's appointments
$appointments = $conn->query("
    SELECT a.*, b.name as barber_name, s.name as service_name, s.price
    FROM appointments a
    JOIN barbers b ON a.barber_id = b.id
    JOIN services s ON a.service_id = s.id
    WHERE a.customer_email = '{$conn->real_escape_string($user['email'])}'
    ORDER BY a.appointment_date DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard | SharpCuts</title>
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
            --gray-50: #F9FAFB;
            --danger: #EF4444;
            --success: #10B981;
        }

        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'DM Sans', sans-serif; background: var(--gray-50); }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: var(--text-dark);
            color: white;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .sidebar-logo {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 32px;
        }

        .sidebar-logo span {
            color: var(--primary);
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin-bottom: 12px;
        }

        .sidebar-menu a {
            display: block;
            padding: 12px 16px;
            color: #9CA3AF;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: var(--primary);
            color: white;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .top-bar {
            background: white;
            padding: 20px 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .top-bar h1 {
            margin: 0;
            font-size: 28px;
            color: var(--text-dark);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .logout-btn {
            padding: 10px 20px;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
        }

        .logout-btn:hover {
            background: #DC2626;
        }

        .content {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }

        .card-title {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 20px;
            color: var(--text-dark);
        }

        .appointments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .appointment-card {
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            padding: 20px;
            background: white;
            transition: all 0.2s;
        }

        .appointment-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 16px;
        }

        .appointment-barber {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending {
            background: #FEF3C7;
            color: #92400E;
        }

        .status-confirmed {
            background: #D1FAE5;
            color: #065F46;
        }

        .status-completed {
            background: #D1E7F5;
            color: #1E40AF;
        }

        .status-cancelled {
            background: #FEE2E2;
            color: #7F1D1D;
        }

        .appointment-details {
            display: flex;
            flex-direction: column;
            gap: 12px;
            font-size: 14px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding-bottom: 8px;
            border-bottom: 1px solid #F3F4F6;
        }

        .detail-label {
            color: var(--text-muted);
            font-weight: 500;
        }

        .detail-value {
            color: var(--text-dark);
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .empty-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .empty-desc {
            margin-bottom: 24px;
        }

        .btn-primary {
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #F3F4F6;
        }

        .btn-small {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #E5E7EB;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-small:hover {
            background: var(--gray-50);
            border-color: var(--primary);
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                Sharp<span>Cuts</span>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active">📅 My Appointments</a></li>
                <li><a href="services.php">💈 Book Service</a></li>
                <li><a href="index.php">🏠 Home</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1>My Appointments</h1>
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                    <div>
                        <div style="font-size: 14px; font-weight: 600; color: var(--text-dark);">
                            <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted);">Customer</div>
                    </div>
                    <button class="logout-btn" onclick="logoutUser()">Logout</button>
                </div>
            </div>

            <!-- Content -->
            <div class="content">
                <?php if ($appointments && $appointments->num_rows > 0): ?>
                    <div class="card">
                        <h2 class="card-title">Your Appointments</h2>
                        <div class="appointments-grid">
                            <?php while ($apt = $appointments->fetch_assoc()): ?>
                                <div class="appointment-card">
                                    <div class="appointment-header">
                                        <div class="appointment-barber"><?php echo htmlspecialchars($apt['barber_name']); ?></div>
                                        <span class="status-badge status-<?php echo $apt['status']; ?>">
                                            <?php echo ucfirst($apt['status']); ?>
                                        </span>
                                    </div>

                                    <div class="appointment-details">
                                        <div class="detail-row">
                                            <span class="detail-label">Service:</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($apt['service_name']); ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Date:</span>
                                            <span class="detail-value"><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Time:</span>
                                            <span class="detail-value"><?php echo date('g:i A', strtotime($apt['appointment_time'])); ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="detail-label">Price:</span>
                                            <span class="detail-value">₱<?php echo number_format($apt['price'], 2); ?></span>
                                        </div>
                                    </div>

                                    <div class="action-buttons">
                                        <?php if ($apt['status'] === 'pending'): ?>
                                            <button class="btn-small" onclick="cancelAppointment(<?php echo $apt['id']; ?>)">Cancel</button>
                                        <?php else: ?>
                                            <span style="padding: 8px 12px; color: var(--text-muted); font-size: 12px;">
                                                <?php echo $apt['status'] === 'completed' ? '✓ Completed' : 'Confirmed'; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📭</div>
                        <div class="empty-title">No Appointments Yet</div>
                        <p class="empty-desc">You haven't booked any appointments. Start by booking a service today!</p>
                        <a href="services.php" class="btn-primary">Book Now</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function logoutUser() {
            if (confirm('Are you sure you want to logout?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="logout">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        function cancelAppointment(id) {
            if (confirm('Are you sure you want to cancel this appointment?')) {
                // Send cancel request to API
                fetch('api/appointments.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'delete-appointment',
                        id: id
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Appointment cancelled');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>

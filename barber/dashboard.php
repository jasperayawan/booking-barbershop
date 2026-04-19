<?php
require_once __DIR__ . '/../functions.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if (isAdmin()) {
    header('Location: ../admin/dashboard.php');
    exit;
}

if (!isBarber()) {
    header('Location: ../dashboard.php');
    exit;
}

$user = getCurrentUser();
$barberIds = getBarberIdsForCurrentUser();

$barber_name = '';
$appointments = null;
if (!empty($barberIds)) {
    $idsCsv = implode(',', array_map('intval', $barberIds));
    $bn = $conn->query('SELECT name FROM barbers WHERE id IN (' . $idsCsv . ') ORDER BY id LIMIT 1');
    if ($bn && $bn->num_rows > 0) {
        $barber_name = $bn->fetch_assoc()['name'];
    }
    $appointments = $conn->query('
        SELECT a.*, s.name AS service_name, s.price
        FROM appointments a
        JOIN services s ON a.service_id = s.id
        WHERE a.barber_id IN (' . $idsCsv . ')
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer schedule | SharpCuts</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css?v=1.0.1">
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
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px;
            background: var(--text-dark);
            color: white;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .sidebar-logo { font-size: 24px; font-weight: 700; margin-bottom: 32px; }
        .sidebar-logo span { color: var(--primary); }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu li { margin-bottom: 12px; }
        .sidebar-menu a {
            display: block;
            padding: 12px 16px;
            color: #9CA3AF;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: var(--primary);
            color: white;
        }
        .main-content { flex: 1; display: flex; flex-direction: column; }
        .top-bar {
            background: white;
            padding: 20px 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .top-bar h1 { margin: 0; font-size: 28px; color: var(--text-dark); }
        .user-info { display: flex; align-items: center; gap: 16px; }
        .user-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: var(--primary); color: white;
            display: flex; align-items: center; justify-content: center; font-weight: 700;
        }
        .logout-btn {
            padding: 10px 20px; background: var(--danger); color: white;
            border: none; border-radius: 8px; cursor: pointer; font-weight: 600;
        }
        .logout-btn:hover { background: #DC2626; }
        .content { flex: 1; padding: 40px; overflow-y: auto; }
        .card {
            background: white; border-radius: 12px; padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 24px;
        }
        .card-title { font-size: 20px; font-weight: 700; margin: 0 0 20px; color: var(--text-dark); }
        .appointments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }
        .appointment-card {
            border: 1px solid #E5E7EB; border-radius: 12px; padding: 20px;
            background: white; transition: all 0.2s;
        }
        .appointment-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .appointment-header {
            display: flex; justify-content: space-between; align-items: start;
            margin-bottom: 16px;
        }
        .appointment-customer { font-size: 18px; font-weight: 700; color: var(--text-dark); }
        .status-badge {
            padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        .status-pending { background: #FEF3C7; color: #92400E; }
        .status-confirmed { background: #D1FAE5; color: #065F46; }
        .status-completed { background: #D1E7F5; color: #1E40AF; }
        .status-cancelled { background: #FEE2E2; color: #7F1D1D; }
        .detail-row {
            display: flex; justify-content: space-between;
            padding-bottom: 8px; border-bottom: 1px solid #F3F4F6;
            font-size: 14px;
        }
        .detail-label { color: var(--text-muted); font-weight: 500; }
        .detail-value { color: var(--text-dark); font-weight: 600; text-align: right; max-width: 60%; word-break: break-word; }
        .status-select-wrap { margin-top: 16px; padding-top: 16px; border-top: 1px solid #F3F4F6; }
        .status-select-wrap label { font-size: 12px; color: var(--text-muted); display: block; margin-bottom: 6px; }
        .status-row { display: flex; gap: 8px; align-items: center; }
        .status-select {
            flex: 1; padding: 8px 12px; border-radius: 8px; border: 1px solid #E5E7EB;
            font-family: inherit; font-size: 14px;
        }
        .btn-save {
            padding: 8px 16px; background: var(--primary); color: white;
            border: none; border-radius: 8px; font-weight: 600; cursor: pointer;
        }
        .btn-save:hover { background: var(--primary-dark); }
        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
        .empty-title { font-size: 20px; font-weight: 700; color: var(--text-dark); margin: 16px 0 8px; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-logo">Sharp<span>Cuts</span></div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active">📅 Customer schedule</a></li>
                <li><a href="../book.php">💈 Book (personal)</a></li>
                <li><a href="../index.php">🏠 Home</a></li>
            </ul>
        </aside>

        <div class="main-content">
            <div class="top-bar">
                <h1>Customer schedule</h1>
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                    <div>
                        <div style="font-size: 14px; font-weight: 600; color: var(--text-dark);">
                            <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted);">
                            Barber<?php echo $barber_name !== '' ? ' · ' . htmlspecialchars($barber_name) : ''; ?>
                        </div>
                    </div>
                    <button type="button" class="logout-btn" onclick="logoutUser()">Logout</button>
                </div>
            </div>

            <div class="content">
                <?php if ($appointments && $appointments->num_rows > 0): ?>
                    <div class="card">
                        <h2 class="card-title">Customer appointments</h2>
                        <div class="appointments-grid">
                            <?php while ($apt = $appointments->fetch_assoc()): ?>
                                <div class="appointment-card" data-id="<?php echo (int) $apt['id']; ?>">
                                    <div class="appointment-header">
                                        <div class="appointment-customer"><?php echo htmlspecialchars($apt['customer_name']); ?></div>
                                        <span class="status-badge status-<?php echo htmlspecialchars($apt['status']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($apt['status'])); ?>
                                        </span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Phone</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($apt['customer_phone']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Email</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($apt['customer_email']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Service</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($apt['service_name']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Date</span>
                                        <span class="detail-value"><?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Time</span>
                                        <span class="detail-value"><?php echo date('g:i A', strtotime($apt['appointment_time'])); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Price</span>
                                        <span class="detail-value">₱<?php echo number_format($apt['price'], 2); ?></span>
                                    </div>
                                    <div class="status-select-wrap">
                                        <label for="st-<?php echo (int) $apt['id']; ?>">Update status</label>
                                        <div class="status-row">
                                            <select class="status-select" id="st-<?php echo (int) $apt['id']; ?>" data-original="<?php echo htmlspecialchars($apt['status']); ?>">
                                                <option value="pending" <?php echo $apt['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="confirmed" <?php echo $apt['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="completed" <?php echo $apt['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="cancelled" <?php echo $apt['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                            <button type="button" class="btn-save" onclick="saveStatus(<?php echo (int) $apt['id']; ?>)">Save</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-title">No customers have booked yet</div>
                        <p>When someone books an appointment with you, it will show up here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function logoutUser() {
            if (!confirm('Log out?')) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../index.php';
            form.innerHTML = '<input type="hidden" name="action" value="logout">';
            document.body.appendChild(form);
            form.submit();
        }

        function saveStatus(id) {
            const sel = document.getElementById('st-' + id);
            const status = sel.value;
            fetch('../api/barber_appointments.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'update-status', id: id, status: status })
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    sel.dataset.original = status;
                    location.reload();
                } else {
                    alert(data.message || 'Could not update');
                }
            })
            .catch(function () { alert('Network error'); });
        }
    </script>
</body>
</html>

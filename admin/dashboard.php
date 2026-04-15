<?php
require_once '../functions.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Get statistics
$total_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'];
$pending_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'")->fetch_assoc()['count'];
$completed_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'completed'")->fetch_assoc()['count'];
$total_barbers = $conn->query("SELECT COUNT(*) as count FROM barbers")->fetch_assoc()['count'];

// Get upcoming appointments
$upcoming = $conn->query("
  SELECT a.*, b.name as barber_name, s.name as service_name 
  FROM appointments a
  JOIN barbers b ON a.barber_id = b.id
  JOIN services s ON a.service_id = s.id
  WHERE a.appointment_date >= CURDATE()
  ORDER BY a.appointment_date, a.appointment_time
  LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | SharpCuts</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="admin-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-logo">
        <a href="../index.html">Sharp<span>Cuts</span></a>
      </div>
      <nav class="sidebar-nav">
        <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
        <li><a href="appointments.php">📅 Appointments</a></li>
        <li><a href="barbers.php">✂️ Barbers</a></li>
        <li><a href="services.php">💈 Services</a></li>
        <li><a href="../index.html">🏠 Website</a></li>
      </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Top Bar -->
      <div class="top-bar">
        <h1>Dashboard</h1>
        <div class="top-bar-right">
          <div class="user-info">
            <div class="avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
            <div>
              <div style="font-size: 12px; font-weight: 600;"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
              <div style="font-size: 11px; color: var(--text-muted);"><?php echo htmlspecialchars($_SESSION['role']); ?></div>
            </div>
          </div>
          <button class="logout-btn" onclick="logoutUser()">Logout</button>
        </div>
      </div>

      <!-- Content -->
      <div class="content">
        <!-- Statistics -->
        <div class="grid grid-2">
          <div class="stat-card">
            <div class="stat-value"><?php echo $total_appointments; ?></div>
            <div class="stat-label">Total Appointments</div>
          </div>
          <div class="stat-card">
            <div class="stat-value" style="color: #F59E0B;"><?php echo $pending_appointments; ?></div>
            <div class="stat-label">Pending</div>
          </div>
          <div class="stat-card">
            <div class="stat-value" style="color: #10B981;"><?php echo $completed_appointments; ?></div>
            <div class="stat-label">Completed</div>
          </div>
          <div class="stat-card">
            <div class="stat-value" style="color: var(--primary);"><?php echo $total_barbers; ?></div>
            <div class="stat-label">Team Members</div>
          </div>
        </div>

        <!-- Upcoming Appointments -->
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">Upcoming Appointments</h2>
            <a href="appointments.php" class="btn btn-primary btn-sm">View All</a>
          </div>

          <?php if ($upcoming && $upcoming->num_rows > 0): ?>
            <div class="table-responsive">
              <table>
                <thead>
                  <tr>
                    <th>Customer</th>
                    <th>Barber</th>
                    <th>Service</th>
                    <th>Date & Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($apt = $upcoming->fetch_assoc()): ?>
                    <tr>
                      <td>
                        <strong><?php echo htmlspecialchars($apt['customer_name']); ?></strong><br>
                        <small style="color: var(--text-muted);"><?php echo htmlspecialchars($apt['customer_phone']); ?></small>
                      </td>
                      <td><?php echo htmlspecialchars($apt['barber_name']); ?></td>
                      <td><?php echo htmlspecialchars($apt['service_name']); ?></td>
                      <td>
                        <?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?><br>
                        <small><?php echo date('g:i A', strtotime($apt['appointment_time'])); ?></small>
                      </td>
                      <td>
                        <span class="badge badge-<?php echo $apt['status']; ?>">
                          <?php echo ucfirst($apt['status']); ?>
                        </span>
                      </td>
                      <td>
                        <div class="action-buttons">
                          <button class="icon-btn" title="Edit">✏️</button>
                          <button class="icon-btn" title="Cancel">✕</button>
                        </div>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty-state">
              <div class="empty-state-icon">📭</div>
              <h3>No Upcoming Appointments</h3>
              <p>There are no appointments scheduled yet.</p>
              <a href="appointments.php" class="btn btn-primary">Create Appointment</a>
            </div>
          <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="card">
          <h2 class="card-title" style="margin-bottom: 20px;">Quick Actions</h2>
          <div class="grid grid-3">
            <a href="appointments.php" class="btn btn-primary" style="justify-content: center;">
              <span style="font-size: 20px;">+</span> New Appointment
            </a>
            <a href="barbers.php" class="btn btn-primary" style="justify-content: center;">
              <span style="font-size: 20px;">+</span> Add Barber
            </a>
            <a href="services.php" class="btn btn-primary" style="justify-content: center;">
              <span style="font-size: 20px;">+</span> Add Service
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="js/admin.js"></script>
  <script>
    function logoutUser() {
      if (confirm('Are you sure you want to logout?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../index.php';
        form.innerHTML = '<input type="hidden" name="action" value="logout">';
        document.body.appendChild(form);
        form.submit();
      }
    }
  </script>
</body>
</html>

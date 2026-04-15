<?php
require_once '../functions.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Get all appointments with barber and service details
$result = $conn->query("
  SELECT a.*, b.name as barber_name, s.name as service_name, s.price
  FROM appointments a
  JOIN barbers b ON a.barber_id = b.id
  JOIN services s ON a.service_id = s.id
  ORDER BY a.appointment_date DESC
");

// Get barbers and services for dropdowns
$barbers = $conn->query("SELECT id, name FROM barbers ORDER BY name");
$services = $conn->query("SELECT id, name, duration_minutes, price FROM services ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Appointments | SharpCuts Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css?v=1.0.1">
</head>
<body>
  <div class="admin-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-logo">
        <a href="../index.php">Sharp<span>Cuts</span></a>
      </div>
      <nav class="sidebar-nav">
        <li><a href="dashboard.php">📊 Dashboard</a></li>
        <li><a href="appointments.php" class="active">📅 Appointments</a></li>
        <li><a href="barbers.php">✂️ Barbers</a></li>
        <li><a href="services.php">💈 Services</a></li>
        <li><a href="../index.php">🏠 Website</a></li>
      </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Top Bar -->
      <div class="top-bar">
        <h1>Appointments</h1>
        <div class="top-bar-right">
          <button class="btn btn-primary" id="newAppointmentBtn">+ New Appointment</button>
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
        <!-- Filters -->
        <div class="card">
          <div class="form-row">
            <div class="form-group">
              <label>Filter by Status</label>
              <select id="statusFilter">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div class="form-group">
              <label>Filter by Barber</label>
              <select id="barberFilter">
                <option value="">All Barbers</option>
                <?php while($barber = $barbers->fetch_assoc()): ?>
                  <option value="<?php echo $barber['id']; ?>"><?php echo htmlspecialchars($barber['name']); ?></option>
                <?php endwhile; ?>
              </select>
            </div>
          </div>
        </div>

        <!-- Appointments Table -->
        <div class="card">
          <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-responsive">
              <table id="appointmentsTable">
                <thead>
                  <tr>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Barber</th>
                    <th>Service</th>
                    <th>Date & Time</th>
                    <th>Status</th>
                    <th>Price</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($apt = $result->fetch_assoc()): ?>
                    <tr data-status="<?php echo $apt['status']; ?>" data-barber="<?php echo $apt['barber_id']; ?>">
                      <td><strong><?php echo htmlspecialchars($apt['customer_name']); ?></strong></td>
                      <td>
                        <small><?php echo htmlspecialchars($apt['customer_phone']); ?></small><br>
                        <small style="color: var(--text-muted);"><?php echo htmlspecialchars($apt['customer_email'] ?? ''); ?></small>
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
                      <td>₱<?php echo number_format($apt['price'], 2); ?></td>
                      <td>
                        <div class="action-buttons">
                          <button class="icon-btn edit-btn" data-id="<?php echo $apt['id']; ?>" title="Edit">✏️</button>
                          <button class="icon-btn delete-btn" data-id="<?php echo $apt['id']; ?>" title="Cancel">✕</button>
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
              <h3>No Appointments Yet</h3>
              <p>Start by creating your first appointment.</p>
              <button class="btn btn-primary" id="newAppointmentBtn">+ New Appointment</button>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- New/Edit Appointment Modal -->
  <div class="modal" id="appointmentModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">New Appointment</h2>
        <button class="modal-close" onclick="closeModal('appointmentModal')">✕</button>
      </div>

      <form id="appointmentForm">
        <input type="hidden" id="appointmentId">

        <div class="form-group">
          <label for="customerName">Customer Name *</label>
          <input type="text" id="customerName" required>
        </div>

        <div class="form-group">
          <label for="customerPhone">Phone *</label>
          <input type="tel" id="customerPhone" required>
        </div>

        <div class="form-group">
          <label for="customerEmail">Email</label>
          <input type="email" id="customerEmail">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="barberId">Barber *</label>
            <select id="barberId" required>
              <option value="">Select Barber</option>
              <?php 
                $barbers->data_seek(0);
                while($barber = $barbers->fetch_assoc()): 
              ?>
                <option value="<?php echo $barber['id']; ?>"><?php echo htmlspecialchars($barber['name']); ?></option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="serviceId">Service *</label>
            <select id="serviceId" required>
              <option value="">Select Service</option>
              <?php 
                $services->data_seek(0);
                while($service = $services->fetch_assoc()): 
              ?>
                <option value="<?php echo $service['id']; ?>"><?php echo htmlspecialchars($service['name']); ?></option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="appointmentDate">Date *</label>
            <input type="date" id="appointmentDate" required>
          </div>

          <div class="form-group">
            <label for="appointmentTime">Time *</label>
            <input type="time" id="appointmentTime" required>
          </div>
        </div>

        <div class="form-group">
          <label for="appointmentStatus">Status</label>
          <select id="appointmentStatus">
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        <div class="form-group">
          <label for="appointmentNotes">Notes</label>
          <textarea id="appointmentNotes" placeholder="Add any notes..."></textarea>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Save Appointment</button>
          <button type="button" class="btn btn-secondary" onclick="closeModal('appointmentModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <script src="js/admin.js?v=1.0.1"></script>
  <script>
    const newBtn = document.getElementById('newAppointmentBtn');
    const form = document.getElementById('appointmentForm');
    const modal = document.getElementById('appointmentModal');
    const statusFilter = document.getElementById('statusFilter');
    const barberFilter = document.getElementById('barberFilter');
    const table = document.getElementById('appointmentsTable');

    // Open modal for new appointment
    newBtn.addEventListener('click', () => {
      document.getElementById('appointmentId').value = '';
      form.reset();
      document.querySelector('.modal-title').textContent = 'New Appointment';
      modal.classList.add('active');
    });

    // Edit appointment
    document.querySelectorAll('.edit-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const row = this.closest('tr');
        const id = this.dataset.id;
        // TODO: Load appointment data and populate form
        modal.classList.add('active');
      });
    });

    // Delete appointment
    document.querySelectorAll('.delete-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        if (confirm('Are you sure?')) {
          const id = this.dataset.id;
          // TODO: Delete appointment via API
        }
      });
    });

    // Filter table
    statusFilter.addEventListener('change', filterTable);
    barberFilter.addEventListener('change', filterTable);

    function filterTable() {
      const statusVal = statusFilter.value;
      const barberVal = barberFilter.value;

      table.querySelectorAll('tbody tr').forEach(row => {
        let show = true;
        if (statusVal && row.dataset.status !== statusVal) show = false;
        if (barberVal && row.dataset.barber !== barberVal) show = false;
        row.style.display = show ? '' : 'none';
      });
    }

    // Handle form submission
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      // TODO: Submit form via API
      console.log('Form submitted');
    });

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

<?php
require_once '../functions.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Get all appointments with barber and service details
$result = $conn->query("
  SELECT 
    a.id, 
    a.customer_name, 
    a.customer_phone, 
    a.customer_email, 
    a.appointment_date, 
    a.appointment_time, 
    a.status, 
    a.barber_id, 
    a.service_id,
    u.full_name as barber_name, 
    s.name as service_name,
    s.price           -- <--- ADD THIS LINE
  FROM appointments a
  JOIN users u ON a.barber_id = u.id
  JOIN services s ON a.service_id = s.id
  ORDER BY a.appointment_date DESC
");

// Get barbers and services for dropdowns
$barbers = $conn->query("SELECT id, full_name as name FROM users WHERE role = 'barber' ORDER BY full_name");
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
                    <tr data-status="<?php echo $apt['id']; ?>" data-status="<?php echo $apt['status']; ?>" 
                        data-barber="<?php echo $apt['barber_id']; ?>"
                        data-service="<?php echo $apt['service_id']; ?>"
                        data-date="<?php echo $apt['appointment_date']; ?>"
                        data-time="<?php echo $apt['appointment_time']; ?>">
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
    const appointmentIdInput = document.getElementById('appointmentId');

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

            // 1. Get data from the table cells (prose/text)
            const name = row.querySelector('td:nth-child(1) strong').textContent;
            const phone = row.querySelector('td:nth-child(2) small:nth-child(1)').textContent;
            const email = row.querySelector('td:nth-child(2) small:nth-child(3)') ? 
                          row.querySelector('td:nth-child(2) small:nth-child(3)').textContent : '';

            // 2. Get data from the <tr> attributes (IDs and raw values)
            const barberId = row.dataset.barber;
            const serviceId = row.dataset.service; // New
            const date = row.dataset.date;       // New
            const time = row.dataset.time;       // New
            const status = row.dataset.status;

            // 3. Populate the Modal Fields
            document.getElementById('appointmentId').value = id;
            document.getElementById('customerName').value = name;
            document.getElementById('customerPhone').value = phone;
            document.getElementById('customerEmail').value = email;
            
            // Populate Select Dropdowns
            document.getElementById('barberId').value = barberId;
            document.getElementById('serviceId').value = serviceId; // Now this will work
            document.getElementById('appointmentStatus').value = status;

            // Populate Date and Time
            document.getElementById('appointmentDate').value = date; // Must be YYYY-MM-DD
            document.getElementById('appointmentTime').value = time; // Must be HH:MM:SS

            // Update UI
            document.querySelector('.modal-title').textContent = 'Edit Appointment';
            modal.classList.add('active');
        });
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = appointmentIdInput.value;
        const formData = new FormData(form);
          

        formData.append('action', id ? 'update-appointment' : 'create-appointment');
        if (id) formData.append('id', id);

      
        formData.append('customer_name', document.getElementById('customerName').value);
        formData.append('customer_phone', document.getElementById('customerPhone').value);
        formData.append('customer_email', document.getElementById('customerEmail').value);
        formData.append('barber_id', document.getElementById('barberId').value);
        formData.append('service_id', document.getElementById('serviceId').value);
        formData.append('appointment_date', document.getElementById('appointmentDate').value);
        formData.append('appointment_time', document.getElementById('appointmentTime').value);
        formData.append('status', document.getElementById('appointmentStatus').value);
        formData.append('notes', document.getElementById('appointmentNotes').value);

        try {
            const response = await fetch('../api/appointments.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                alert(id ? 'Appointment updated!' : 'Appointment created!');
                location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to connect to the server.');
        }
    });

    // --- 1. POPULATE MODAL FROM TABLE (Your Preferred Way) ---
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const row = this.closest('tr');
        
        // Pull data from the data attributes you added to the <tr>
        const id = this.dataset.id;
        const barberId = row.dataset.barber;
        const serviceId = row.dataset.service;
        const date = row.dataset.date;
        const time = row.dataset.time;
        const status = row.dataset.status;

        // Pull text from table cells
        const name = row.querySelector('td:nth-child(1) strong').textContent;
        const phone = row.querySelector('td:nth-child(2) small:nth-child(1)').textContent;
        const emailElem = row.querySelector('td:nth-child(2) small:nth-child(3)');
        const email = emailElem ? emailElem.textContent : '';

        // Fill the Modal inputs
        document.getElementById('appointmentId').value = id;
        document.getElementById('customerName').value = name;
        document.getElementById('customerPhone').value = phone;
        document.getElementById('customerEmail').value = email;
        document.getElementById('barberId').value = barberId;
        document.getElementById('serviceId').value = serviceId;
        document.getElementById('appointmentStatus').value = status;
        document.getElementById('appointmentDate').value = date;
        document.getElementById('appointmentTime').value = time;

        // Change title and show modal
        document.querySelector('.modal-title').textContent = 'Edit Appointment';
        modal.classList.add('active');
    });
});


    // Delete appointment
    document.addEventListener('click', async function(e) {
      const deleteBtn = e.target.closest('.delete-btn');

      if(deleteBtn){
        const appointmentId = deleteBtn.getAttribute('data-id');

        if(confirm('Are you sure want you to delete this appointment?')) {
          try{
            const formData = new FormData();
            formData.append('action', 'delete-appointment');
            formData.append('id', appointmentId);

            const response = await fetch('../api/appointments.php', {
              method: 'POST',
              body: formData
            })

            const result = await response.json();
            alert(result.message)
            location.reload();
          } catch (error) {
            console.error('Error:', error)
          }
        }
      }
    })

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

<?php
require_once '../functions.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Get all services
$result = $conn->query("SELECT * FROM services ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Services | SharpCuts Admin</title>
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
        <li><a href="dashboard.php">📊 Dashboard</a></li>
        <li><a href="appointments.php">📅 Appointments</a></li>
        <li><a href="barbers.php">✂️ Barbers</a></li>
        <li><a href="services.php" class="active">💈 Services</a></li>
        <li><a href="../index.html">🏠 Website</a></li>
      </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Top Bar -->
      <div class="top-bar">
        <h1>Services</h1>
        <div class="top-bar-right">
          <button class="btn btn-primary" id="newServiceBtn">+ Add Service</button>
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
        <div class="card">
          <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-responsive">
              <table>
                <thead>
                  <tr>
                    <th>Service Name</th>
                    <th>Description</th>
                    <th>Duration</th>
                    <th>Price</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($service = $result->fetch_assoc()): ?>
                    <tr>
                      <td><strong><?php echo htmlspecialchars($service['name']); ?></strong></td>
                      <td>
                        <small><?php echo htmlspecialchars(substr($service['description'] ?? '', 0, 50)); ?></small>
                      </td>
                      <td><?php echo $service['duration_minutes']; ?> min</td>
                      <td><strong>₱<?php echo number_format($service['price'], 2); ?></strong></td>
                      <td>
                        <div class="action-buttons">
                          <button class="icon-btn edit-btn" data-id="<?php echo $service['id']; ?>">✏️</button>
                          <button class="icon-btn delete-btn" data-id="<?php echo $service['id']; ?>" style="border-color: #EF4444; color: #EF4444;">✕</button>
                        </div>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty-state">
              <div class="empty-state-icon">💈</div>
              <h3>No Services Yet</h3>
              <p>Add your first service to get started.</p>
              <button class="btn btn-primary" id="newServiceBtn">+ Add Service</button>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- New/Edit Service Modal -->
  <div class="modal" id="serviceModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Add Service</h2>
        <button class="modal-close" onclick="closeModal('serviceModal')">✕</button>
      </div>

      <form id="serviceForm">
        <input type="hidden" id="serviceId">

        <div class="form-group">
          <label for="serviceName">Service Name *</label>
          <input type="text" id="serviceName" required>
        </div>

        <div class="form-group">
          <label for="serviceDescription">Description</label>
          <textarea id="serviceDescription" placeholder="Describe what this service includes..."></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="serviceDuration">Duration (Minutes) *</label>
            <input type="number" id="serviceDuration" min="15" step="15" required>
          </div>

          <div class="form-group">
            <label for="servicePrice">Price (₱) *</label>
            <input type="number" id="servicePrice" min="0" step="50" required>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Save Service</button>
          <button type="button" class="btn btn-secondary" onclick="closeModal('serviceModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <script src="js/admin.js"></script>
  <script>
    const newBtn = document.getElementById('newServiceBtn');
    const serviceForm = document.getElementById('serviceForm');
    const serviceModal = document.getElementById('serviceModal');

    // Open modal for new service
    newBtn.addEventListener('click', () => {
      document.getElementById('serviceId').value = '';
      serviceForm.reset();
      document.querySelector('#serviceModal .modal-title').textContent = 'Add Service';
      serviceModal.classList.add('active');
    });

    // Edit service
    document.querySelectorAll('.edit-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const id = this.dataset.id;
        document.querySelector('#serviceModal .modal-title').textContent = 'Edit Service';
        serviceModal.classList.add('active');
      });
    });

    // Delete service
    document.querySelectorAll('.delete-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        if (confirm('Are you sure?')) {
          const id = this.dataset.id;
          // TODO: Delete service via API
        }
      });
    });

    // Handle form submission
    serviceForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      // TODO: Submit form via API
      console.log('Service form submitted');
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

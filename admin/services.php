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
        <li><a href="appointments.php">📅 Appointments</a></li>
        <li><a href="barbers.php">✂️ Barbers</a></li>
        <li><a href="services.php" class="active">💈 Services</a></li>
        <li><a href="../index.php">🏠 Website</a></li>
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
                    <tr data-id="<?php echo $service['id']; ?>" 
                        data-name="<?php echo htmlspecialchars($service['name']); ?>" 
                        data-description="<?php echo htmlspecialchars($service['description'] ?? ''); ?>"
                        data-duration="<?php echo $service['duration_minutes']; ?>"
                        data-price="<?php echo $service['price']; ?>">
                        
                      <td><strong><?php echo htmlspecialchars($service['name']); ?></strong></td>
                      <td>
                        <small><?php echo htmlspecialchars(substr($service['description'] ?? '', 0, 50)); ?>...</small>
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
            <input type="number" id="serviceDuration" required>
          </div>

          <div class="form-group">
            <label for="servicePrice">Price (₱) *</label>
            <input type="number" id="servicePrice" required>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Save Service</button>
          <button type="button" class="btn btn-secondary" onclick="closeModal('serviceModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <script src="js/admin.js?v=1.0.1"></script>
  <script>
    const serviceModal = document.getElementById('serviceModal');
    const serviceForm = document.getElementById('serviceForm');
    const modalTitle = document.querySelector('#serviceModal .modal-title');
    const serviceIdInput = document.getElementById('serviceId');

    // --- OPEN MODAL (NEW SERVICE) ---
    document.getElementById('newServiceBtn').addEventListener('click', () => {
        serviceIdInput.value = ''; // Clear ID so we know it's a "create" action
        serviceForm.reset();
        modalTitle.textContent = 'Add Service';
        serviceModal.classList.add('active');
    });

    // --- OPEN MODAL (EDIT SERVICE - POPULATING DATA) ---
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.edit-btn');
        if (!btn) return;

        const row = btn.closest('tr');
        
        // Populate the Modal inputs using the row's data attributes
        serviceIdInput.value = row.dataset.id;
        document.getElementById('serviceName').value = row.dataset.name;
        document.getElementById('serviceDescription').value = row.dataset.description;
        document.getElementById('serviceDuration').value = row.dataset.duration;
        document.getElementById('servicePrice').value = row.dataset.price;

        modalTitle.textContent = 'Edit Service';
        serviceModal.classList.add('active');
    });

    // --- UNIFIED SUBMIT (CREATE OR UPDATE) ---
    serviceForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = serviceIdInput.value;
        const formData = new FormData();
        
        // Logic to choose which action to send to your PHP API
        formData.append('action', id ? 'update-service' : 'create-service');
        if (id) formData.append('id', id);
        
        formData.append('name', document.getElementById('serviceName').value);
        formData.append('description', document.getElementById('serviceDescription').value);
        formData.append('duration_minutes', document.getElementById('serviceDuration').value);
        formData.append('price', document.getElementById('servicePrice').value);

        try {
            const response = await fetch('../api/services.php', { // Ensure path is correct
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                alert(id ? 'Service updated successfully!' : 'Service created successfully!');
                location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to connect to the server.');
        }
    });

    // --- DELETE SERVICE ---
    document.addEventListener('click', async (e) => {
        const deleteBtn = e.target.closest('.delete-btn');
        if (!deleteBtn) return;

        if (confirm('Are you sure you want to delete this service?')) {
            const formData = new FormData();
            formData.append('action', 'delete-service');
            formData.append('id', deleteBtn.dataset.id);

            try {
                const response = await fetch('../api/services.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
    });

    function logoutUser() {
        if (confirm('Are you sure?')) {
            const f = document.createElement('form');
            f.method = 'POST'; f.action = '../index.php';
            f.innerHTML = '<input type="hidden" name="action" value="logout">';
            document.body.appendChild(f);
            f.submit();
        }
    }
</script>
</body>
</html>

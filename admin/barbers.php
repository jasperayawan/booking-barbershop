<?php
require_once '../functions.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Get all barbers with availability
$barbers = $conn->query("SELECT * FROM barbers ORDER BY name");
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Barbers | SharpCuts Admin</title>
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
        <li><a href="barbers.php" class="active">✂️ Barbers</a></li>
        <li><a href="services.php">💈 Services</a></li>
        <li><a href="../index.html">🏠 Website</a></li>
      </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Top Bar -->
      <div class="top-bar">
        <h1>Barbers</h1>
        <div class="top-bar-right">
          <button class="btn btn-primary" id="newBarberBtn">+ Add Barber</button>
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
        <div class="grid">
          <?php if ($barbers && $barbers->num_rows > 0): ?>
            <?php while ($barber = $barbers->fetch_assoc()): ?>
              <div class="card">
                <div class="card-header">
                  <div>
                    <h3 class="card-title"><?php echo htmlspecialchars($barber['name']); ?></h3>
                    <p style="font-size: 13px; color: var(--primary); font-weight: 600; margin-top: 4px;">
                      <?php echo htmlspecialchars($barber['title']); ?>
                    </p>
                  </div>
                  <div>
                    <button class="icon-btn edit-btn" data-id="<?php echo $barber['id']; ?>">✏️</button>
                    <button class="icon-btn delete-btn" data-id="<?php echo $barber['id']; ?>" style="border-color: #EF4444; color: #EF4444;">✕</button>
                  </div>
                </div>

                <div style="margin-bottom: 16px;">
                  <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 8px;">
                    <strong>Specialties:</strong> <?php echo htmlspecialchars($barber['specialties']); ?>
                  </p>
                  <p style="font-size: 13px; color: var(--text-muted);">
                    <strong>Experience:</strong> <?php echo $barber['experience_years']; ?> years
                  </p>
                </div>

                <div style="display: flex; gap: 16px; margin-bottom: 16px; padding-top: 16px; border-top: 1px solid var(--gray-200);">
                  <div>
                    <div style="font-size: 18px; font-weight: 700; color: var(--primary);">
                      ★ <?php echo $barber['rating']; ?>
                    </div>
                    <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Rating</div>
                  </div>
                </div>

                <div style="padding-top: 16px; border-top: 1px solid var(--gray-200);">
                  <p style="font-size: 12px; font-weight: 600; color: var(--text-dark); margin-bottom: 12px;">Availability</p>
                  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 12px;">
                    <?php
                      $availability = $conn->query("SELECT * FROM barber_availability WHERE barber_id = {$barber['id']} ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')");
                      while($av = $availability->fetch_assoc()):
                    ?>
                      <div>
                        <strong><?php echo substr($av['day_of_week'], 0, 3); ?></strong>
                        <br>
                        <?php 
                          if ($av['is_available']) {
                            echo date('g:i A', strtotime($av['start_time'])) . ' - ' . date('g:i A', strtotime($av['end_time']));
                          } else {
                            echo 'Off';
                          }
                        ?>
                      </div>
                    <?php endwhile; ?>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="empty-state" style="grid-column: 1/-1;">
              <div class="empty-state-icon">✂️</div>
              <h3>No Barbers Yet</h3>
              <p>Add your first barber to get started.</p>
              <button class="btn btn-primary" id="newBarberBtn">+ Add Barber</button>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- New/Edit Barber Modal -->
  <div class="modal" id="barberModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Add Barber</h2>
        <button class="modal-close" onclick="closeModal('barberModal')">✕</button>
      </div>

      <form id="barberForm">
        <input type="hidden" id="barberId">

        <div class="form-group">
          <label for="barberName">Name *</label>
          <input type="text" id="barberName" required>
        </div>

        <div class="form-group">
          <label for="barberTitle">Title (e.g., The Fade King) *</label>
          <input type="text" id="barberTitle" required>
        </div>

        <div class="form-group">
          <label for="barberSpecialties">Specialties *</label>
          <input type="text" id="barberSpecialties" placeholder="e.g., Skin Fades, Drop Fades" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="barberRating">Rating (0-5)</label>
            <input type="number" id="barberRating" min="0" max="5" step="0.1">
          </div>

          <div class="form-group">
            <label for="barberExperience">Experience (Years) *</label>
            <input type="number" id="barberExperience" required>
          </div>
        </div>

        <div class="form-group">
          <label for="barberPhoto">Photo URL</label>
          <input type="url" id="barberPhoto" placeholder="https://...">
        </div>

        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--gray-200);">
          <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 16px;">Availability</h4>
          <div id="availabilitySchedule"></div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Save Barber</button>
          <button type="button" class="btn btn-secondary" onclick="closeModal('barberModal')">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <script src="js/admin.js"></script>
  <script>
    const newBtn = document.getElementById('newBarberBtn');
    const barberForm = document.getElementById('barberForm');
    const barberModal = document.getElementById('barberModal');

    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    // Generate availability form
    function generateAvailabilityForm() {
      const container = document.getElementById('availabilitySchedule');
      let html = '';

      days.forEach(day => {
        html += `
          <div class="form-row" style="margin-bottom: 12px;">
            <div class="form-group" style="grid-column: 1;">
              <label>${day}</label>
              <input type="checkbox" class="day-checkbox" data-day="${day}" checked style="width: auto; margin-top: 8px;">
            </div>
            <div class="form-group">
              <label>Start</label>
              <input type="time" class="start-time" data-day="${day}" value="09:00">
            </div>
            <div class="form-group">
              <label>End</label>
              <input type="time" class="end-time" data-day="${day}" value="19:00">
            </div>
          </div>
        `;
      });

      container.innerHTML = html;
    }

    generateAvailabilityForm();

    // Open modal for new barber
    newBtn.addEventListener('click', () => {
      document.getElementById('barberId').value = '';
      barberForm.reset();
      document.querySelector('#barberModal .modal-title').textContent = 'Add Barber';
      barberModal.classList.add('active');
    });

    // Edit barber
    document.querySelectorAll('.edit-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const id = this.dataset.id;
        document.querySelector('#barberModal .modal-title').textContent = 'Edit Barber';
        barberModal.classList.add('active');
      });
    });

    // Delete barber
    document.querySelectorAll('.delete-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        if (confirm('Are you sure?')) {
          const id = this.dataset.id;
          // TODO: Delete barber via API
        }
      });
    });

    // Handle form submission
    barberForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      // TODO: Submit form via API
      console.log('Barber form submitted');
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

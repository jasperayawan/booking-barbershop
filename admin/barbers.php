<?php
require_once '../functions.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Barber staff: users with role barber (roster + photo on users; chair id for availability from barbers sync)
$barbers = $conn->query("
  SELECT u.id, u.username, u.availability_json, u.email, u.full_name AS name, u.barber_title AS title, u.specialties, u.rating, u.experience_years, u.photo_url, u.barber_id
  FROM users u
  WHERE u.role = 'barber'
  ORDER BY COALESCE(NULLIF(TRIM(u.full_name), ''), u.username)
");
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
        <li><a href="barbers.php" class="active">✂️ Barbers</a></li>
        <li><a href="services.php">💈 Services</a></li>
        <li><a href="../index.php">🏠 Website</a></li>
      </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Top Bar -->
      <div class="top-bar">
        <h1>Barbers</h1>
        <div class="top-bar-right">
          <button type="button" class="btn btn-primary new-barber-open" id="newBarberBtn">+ Add Barber</button>
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
    <?php
      // 1. Unify the ID: We use the User's ID for everything now
      $barberUserId = (int) $barber['id']; 
      
      $thumb = !empty($barber['photo_url']) ? '../' . htmlspecialchars($barber['photo_url']) : '../assets/person.svg';
    ?>
    <div class="card">
      <div class="card-header">
        <div style="display: flex; gap: 14px; align-items: flex-start;">
          <img src="<?php echo $thumb; ?>" alt="" width="56" height="56" style="object-fit: cover; border-radius: 10px; border: 1px solid var(--gray-200);">
          <div>
            <h3 class="card-title" style="margin: 0;"><?php echo htmlspecialchars($barber['name']); ?></h3>
            <p style="font-size: 12px; color: var(--text-muted); margin: 4px 0 0;"><?php echo htmlspecialchars($barber['email'] ?? ''); ?></p>
            <p style="font-size: 13px; color: var(--primary); font-weight: 600; margin-top: 4px;">
              <?php echo htmlspecialchars($barber['title'] ?? ''); ?>
            </p>
          </div>
        </div>
        <div>
          <button type="button" class="icon-btn edit-btn" data-id="<?php echo $barberUserId; ?>">✏️</button>
          <button type="button" class="icon-btn delete-btn" data-id="<?php echo $barberUserId; ?>" style="border-color: #EF4444; color: #EF4444;">✕</button>
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
        $schedule = json_decode($barber['availability_json'] ?? '', true);
        
        if (!empty($schedule) && is_array($schedule)) {
            foreach ($schedule as $day => $data) {
        ?>
                <div>
                    <strong><?php echo substr($day, 0, 3); ?></strong>
                    <br>
                    <?php
                    // 2. Access the array keys directly (matching your new JSON structure)
                    if (isset($data['is_available']) && $data['is_available'] == 1) {
                        $start = date('g:i A', strtotime($data['start_time']));
                        $end = date('g:i A', strtotime($data['end_time']));
                        echo "$start - $end";
                    } else {
                        echo '<span style="color: var(--text-muted);">Off</span>';
                    }
                    ?>
                </div>
        <?php
            }
        } else {
            // This shows if the JSON is empty or has never been saved
        ?>
            <p style="font-size: 12px; color: var(--text-muted); grid-column: 1/-1;">No availability set yet.</p>
        <?php } ?>
    </div>
</div>
    </div>
  <?php endwhile; ?>
<?php else: ?>
            <div class="empty-state" style="grid-column: 1/-1;">
              <div class="empty-state-icon">✂️</div>
              <h3>No Barbers Yet</h3>
              <p>Add your first barber to get started.</p>
              <button type="button" class="btn btn-primary new-barber-open">+ Add Barber</button>
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

        <div class="form-row" id="barberAccountRow">
          <div class="form-group">
            <label for="barberUsername">Username *</label>
            <input type="text" id="barberUsername" autocomplete="username" required>
          </div>
          <div class="form-group">
            <label for="barberEmail">Email *</label>
            <input type="email" id="barberEmail" autocomplete="email" required>
          </div>
        </div>

        <div class="form-group" id="barberPasswordCreateWrap">
          <label for="barberPassword">Password *</label>
          <input type="password" id="barberPassword" autocomplete="new-password" minlength="6">
          <small style="color: var(--text-muted);">At least 6 characters (new barbers only).</small>
        </div>

        <div class="form-group" id="barberPasswordUpdateWrap" style="display: none;">
          <label for="barberNewPassword">New password (optional)</label>
          <input type="password" id="barberNewPassword" autocomplete="new-password" minlength="6">
        </div>

        <div class="form-group">
          <label for="barberPhone">Phone</label>
          <input type="text" id="barberPhone" placeholder="Optional">
        </div>

        <div class="form-group">
          <label for="barberName">Display name *</label>
          <input type="text" id="barberName" required>
        </div>

        <div class="form-group">
          <label for="barberTitle">Title (e.g., The Fade King) *</label>
          <input type="text" id="barberTitle" required>
        </div>

        <div class="form-group">
          <label for="barberBio">Bio</label>
          <textarea id="barberBio" rows="2" placeholder="Optional"></textarea>
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
          <label>Current Photo</label>
          <div id="photoPreviewContainer" style="margin-bottom: 10px;">
            <img id="currentBarberPhoto" src="" alt="Preview" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; display: none; border: 1px solid var(--gray-200);">
          </div>
          
          <label for="barberPhoto">Upload New Photo (Leave blank to keep current)</label>
          <input type="file" id="barberPhoto" accept="image/*">
          
          <input type="hidden" id="existingPhotoPath">
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

  <script src="js/admin.js?v=1.0.1"></script>
  <script>
    const newBarberOpens = document.querySelectorAll('.new-barber-open');
    const barberForm = document.getElementById('barberForm');
    const barberModal = document.getElementById('barberModal');
    const barberPasswordCreateWrap = document.getElementById('barberPasswordCreateWrap');
    const barberPasswordUpdateWrap = document.getElementById('barberPasswordUpdateWrap');
    const barberPasswordInput = document.getElementById('barberPassword');

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

    function openNewBarberModal() {
      barberForm.reset();
      document.getElementById('barberId').value = '';
      document.getElementById('currentBarberPhoto').style.display = 'none';
      document.getElementById('existingPhotoPath').value = '';
      document.getElementById('barberNewPassword').value = '';
      barberPasswordCreateWrap.style.display = 'block';
      barberPasswordUpdateWrap.style.display = 'none';
      barberPasswordInput.required = true;
      generateAvailabilityForm();
      document.querySelector('#barberModal .modal-title').textContent = 'Add Barber';
      barberModal.classList.add('active');
    }

    newBarberOpens.forEach((btn) => btn.addEventListener('click', openNewBarberModal));



    barberForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const formData = new FormData();
      const id = document.getElementById('barberId').value;

      if (!id) {
        const pw = document.getElementById('barberPassword').value;
        if (!pw || pw.length < 6) {
          alert('Password is required for new barbers (at least 6 characters).');
          return;
        }
      }

      formData.append('action', id ? 'update-barber' : 'create-barber');
      if (id) formData.append('id', id);

      formData.append('username', document.getElementById('barberUsername').value.trim());
      formData.append('email', document.getElementById('barberEmail').value.trim());
      formData.append('phone', document.getElementById('barberPhone').value.trim());
      formData.append('bio', document.getElementById('barberBio').value.trim());
      formData.append('name', document.getElementById('barberName').value);
      formData.append('title', document.getElementById('barberTitle').value);
      formData.append('specialties', document.getElementById('barberSpecialties').value);
      formData.append('rating', document.getElementById('barberRating').value || 0);
      formData.append('experience_years', document.getElementById('barberExperience').value);
      formData.append('existing_photo', document.getElementById('existingPhotoPath').value);

      if (id) {
        const np = document.getElementById('barberNewPassword').value;
        if (np) formData.append('new_password', np);
      } else {
        formData.append('password', document.getElementById('barberPassword').value);
      }

      const fileInput = document.getElementById('barberPhoto');
      if (fileInput.files[0]) {
        formData.append('photo', fileInput.files[0]);
      }

      days.forEach(day => {
        const isChecked = document.querySelector(`.day-checkbox[data-day="${day}"]`).checked;
        formData.append(`available_${day}`, isChecked ? '1' : '0');
        formData.append(`start_time_${day}`, document.querySelector(`.start-time[data-day="${day}"]`).value);
        formData.append(`end_time_${day}`, document.querySelector(`.end-time[data-day="${day}"]`).value);
      });

      try {
        const response = await fetch('../api/barbers.php', {
          method: 'POST',
          body: formData
        });
        const result = await response.json();

        if (result.success) {
          barberModal.classList.remove('active');
          barberForm.reset();
          console.log(result)
          alert(id ? 'Barber updated!' : 'Barber added!');
          // location.reload();
        } else {
          alert('Error: ' + result.message);
        }
      } catch (error) {
        console.error('Submission error:', error);
      }
    })


    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
          const id = this.dataset.id;
          const response = await fetch(`../api/barbers.php?action=get-barber&id=${id}`);
          const result = await response.json();

          if (result.success) {
            const data = result.barber;

            document.getElementById('barberId').value = data.id;
            document.getElementById('barberUsername').value = data.username || '';
            document.getElementById('barberEmail').value = data.email || '';
            document.getElementById('barberPhone').value = data.phone || '';
            document.getElementById('barberBio').value = data.bio || '';
            document.getElementById('barberName').value = data.name || '';
            document.getElementById('barberTitle').value = data.title || '';
            document.getElementById('barberSpecialties').value = data.specialties || '';
            document.getElementById('barberRating').value = data.rating;
            document.getElementById('barberExperience').value = data.experience_years;
            document.getElementById('barberPassword').value = '';
            document.getElementById('barberNewPassword').value = '';
            barberPasswordCreateWrap.style.display = 'none';
            barberPasswordUpdateWrap.style.display = 'block';
            barberPasswordInput.required = false;

            document.getElementById('barberPhoto').value = '';

            const previewImg = document.getElementById('currentBarberPhoto');
            const hiddenPath = document.getElementById('existingPhotoPath');
            if (data.photo_url) {
              previewImg.src = `../${data.photo_url}`;
              previewImg.style.display = 'block';
              hiddenPath.value = data.photo_url;
            } else {
              previewImg.style.display = 'none';
              hiddenPath.value = '';
            }

            generateAvailabilityForm();
            if (data.availability) {
              data.availability.forEach((av) => {
                const day = av.day_of_week;
                const checkbox = document.querySelector(`.day-checkbox[data-day="${day}"]`);
                const startInput = document.querySelector(`.start-time[data-day="${day}"]`);
                const endInput = document.querySelector(`.end-time[data-day="${day}"]`);
                if (checkbox) checkbox.checked = (parseInt(av.is_available, 10) === 1);
                if (startInput && av.start_time) startInput.value = av.start_time.substring(0, 5);
                if (endInput && av.end_time) endInput.value = av.end_time.substring(0, 5);
              });
            }

            document.querySelector('#barberModal .modal-title').textContent = 'Edit Barber';
            barberModal.classList.add('active');
          }
        });
      });



    // Delete barber
    document.addEventListener('click', async function(e) {
      const deleteBtn = e.target.closest('.delete-btn');

      if (deleteBtn) {
        const barberId = deleteBtn.getAttribute('data-id');

        if(confirm('Are you sure you want to delete this barber? This will remove their availability.')) {
          try {
            const formData = new FormData();
            formData.append('action','delete-barber');
            formData.append('id', barberId)

            const response = await fetch('../api/barbers.php', {
              method: 'POST',
              body: formData
            })

            const result = await response.json();

            if (result.success) {
              location.reload();
            } else {
              alert('Error: ' + (result.message || 'Unknown'));
            }
          } catch (error) {
            console.error('Error:', error);
            alert('An error occurred during deletion.');
          }
        }
      }
    })


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

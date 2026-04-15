<?php
require_once '../functions.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users - SharpCuts Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css?v=1">
  <style>
    .user-list {
      background: var(--white);
      border-radius: var(--radius-lg);
      padding: 24px;
      box-shadow: var(--shadow-sm);
    }

    .user-row {
      display: grid;
      grid-template-columns: 1fr 1fr 1.2fr 1fr 0.8fr;
      gap: 16px;
      padding: 16px;
      border-bottom: 1px solid #f0f0f0;
      align-items: center;
    }

    .user-row:last-child {
      border-bottom: none;
    }

    .user-row.header {
      background: var(--primary-bg);
      font-weight: 600;
      color: var(--primary);
      border-radius: var(--radius) var(--radius) 0 0;
      margin-bottom: 8px;
    }

    .user-name {
      font-weight: 600;
      color: var(--text-dark);
    }

    .user-email {
      font-size: 13px;
      color: var(--text-muted);
    }

    .role-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }

    .role-badge.admin {
      background: #FECACA;
      color: #991B1B;
    }

    .role-badge.barber {
      background: #BFDBFE;
      color: #1E40AF;
    }

    .role-badge.customer {
      background: #D1D5DB;
      color: #374151;
    }

    .status-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }

    .status-badge.active {
      background: #D1FAE5;
      color: #065F46;
    }

    .status-badge.inactive {
      background: #FEE2E2;
      color: #7F1D1D;
    }

    .user-actions {
      display: flex;
      gap: 8px;
    }

    .btn-icon {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      border: none;
      background: var(--primary-light);
      color: var(--primary);
      cursor: pointer;
      font-size: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all var(--transition);
    }

    .btn-icon:hover {
      background: var(--primary);
      color: var(--white);
    }

    .btn-icon.delete:hover {
      background: #EF4444;
      color: var(--white);
    }

    .header-actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
      gap: 16px;
    }

    .search-box {
      flex: 1;
      max-width: 300px;
    }

    .search-box input {
      width: 100%;
      padding: 10px 14px;
      border: 1px solid #e5e7eb;
      border-radius: var(--radius);
      font-family: var(--font);
      font-size: 14px;
    }

    .search-box input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(138,56,245,0.1);
    }

    .filter-group {
      display: flex;
      gap: 12px;
      align-items: center;
    }

    select {
      padding: 10px 14px;
      border: 1px solid #e5e7eb;
      border-radius: var(--radius);
      font-family: var(--font);
      font-size: 14px;
      cursor: pointer;
      background: var(--white);
    }

    select:focus {
      outline: none;
      border-color: var(--primary);
    }

    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }

    .modal.active {
      display: flex;
    }

    .modal-content {
      background: var(--white);
      border-radius: var(--radius-lg);
      padding: 32px;
      max-width: 500px;
      width: 90%;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }

    .modal-header {
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 20px;
      color: var(--text-dark);
    }

    .form-group {
      margin-bottom: 16px;
    }

    .form-group label {
      display: block;
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 6px;
      color: var(--text-dark);
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #e5e7eb;
      border-radius: var(--radius);
      font-family: var(--font);
      font-size: 14px;
    }

    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(138,56,245,0.1);
    }

    .form-actions {
      display: flex;
      gap: 12px;
      margin-top: 24px;
    }

    .form-actions button {
      flex: 1;
      padding: 12px;
      border: none;
      border-radius: var(--radius);
      font-weight: 600;
      cursor: pointer;
      transition: all var(--transition);
    }

    .btn-submit {
      background: var(--primary);
      color: var(--white);
    }

    .btn-submit:hover {
      background: var(--primary-dark);
    }

    .btn-cancel {
      background: #f3f4f6;
      color: var(--text-dark);
    }

    .btn-cancel:hover {
      background: #e5e7eb;
    }

    .pagination {
      display: flex;
      justify-content: center;
      gap: 8px;
      margin-top: 24px;
    }

    .pagination button {
      padding: 8px 12px;
      border: 1px solid #e5e7eb;
      background: var(--white);
      border-radius: var(--radius);
      cursor: pointer;
      transition: all var(--transition);
    }

    .pagination button.active {
      background: var(--primary);
      color: var(--white);
      border-color: var(--primary);
    }

    .pagination button:hover:not(.active) {
      border-color: var(--primary);
    }

    .empty-state {
      text-align: center;
      padding: 48px 24px;
      color: var(--text-muted);
    }

    .empty-state-icon {
      font-size: 48px;
      margin-bottom: 16px;
    }

    .alert {
      padding: 12px 16px;
      border-radius: var(--radius);
      margin-bottom: 16px;
      display: none;
    }

    .alert.success {
      background: #D1FAE5;
      color: #065F46;
      border: 1px solid #A7F3D0;
      display: block;
    }

    .alert.error {
      background: #FEE2E2;
      color: #7F1D1D;
      border: 1px solid #FECACA;
      display: block;
    }

    @media (max-width: 1024px) {
      .user-row {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
      }

      .user-row.header {
        display: none;
      }

      .user-row {
        display: flex;
        flex-direction: column;
        border: 1px solid #f0f0f0;
        border-radius: var(--radius);
        margin-bottom: 16px;
        padding: 16px;
      }
    }
  </style>
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
        <li><a href="services.php">💈 Services</a></li>
        <li><a href="users.php" class="active">👥 Users</a></li>
        <li><a href="../index.php">🏠 Website</a></li>
      </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Top Bar -->
      <div class="top-bar">
        <h1>Manage Users</h1>
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
        <!-- Header Actions -->
        <div class="header-actions">
          <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search by name, email, or username...">
          </div>
          <div class="filter-group">
            <select id="roleFilter">
              <option value="">All Roles</option>
              <option value="admin">Admin</option>
              <option value="barber">Barber</option>
              <option value="customer">Customer</option>
            </select>
            <button class="btn btn-primary" onclick="openAddUserModal()">+ Add User</button>
          </div>
        </div>

        <!-- Users List -->
        <div class="user-list">
          <div class="user-row header">
            <div>Name & Email</div>
            <div>Username</div>
            <div>Role</div>
            <div>Status</div>
            <div>Actions</div>
          </div>
          <div id="usersList"></div>
        </div>

        <!-- Pagination -->
        <div id="paginationContainer"></div>
      </div>
    </div>
  </div>

  <!-- Add/Edit User Modal -->
  <div id="userModal" class="modal">
    <div class="modal-content">
      <div class="modal-header" id="modalTitle">Add New User</div>
      
      <form id="userForm" onsubmit="handleUserSubmit(event)">
        <div class="form-group">
          <label for="username">Username *</label>
          <input type="text" id="username" name="username" required>
        </div>

        <div class="form-group">
          <label for="email">Email *</label>
          <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
          <label for="password">Password *</label>
          <input type="password" id="password" name="password" required>
          <small style="color: var(--text-muted); display: block; margin-top: 4px;">Minimum 6 characters</small>
        </div>

        <div class="form-group">
          <label for="fullName">Full Name</label>
          <input type="text" id="fullName" name="full_name">
        </div>

        <div class="form-group">
          <label for="phone">Phone</label>
          <input type="tel" id="phone" name="phone">
        </div>

        <div class="form-group">
          <label for="role">Role *</label>
          <select id="role" name="role" required>
            <option value="customer">Customer</option>
            <option value="barber">Barber</option>
            <option value="admin">Admin</option>
          </select>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-submit">Save User</button>
          <button type="button" class="btn-cancel" onclick="closeUserModal()">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <script src="js/admin.js"></script>
  <script>
    let currentPage = 1;
    let editingUserId = null;

    // Load users on page load
    document.addEventListener('DOMContentLoaded', () => {
      loadUsers();
      
      // Search and filter listeners
      document.getElementById('searchInput').addEventListener('input', () => {
        currentPage = 1;
        loadUsers();
      });
      
      document.getElementById('roleFilter').addEventListener('change', () => {
        currentPage = 1;
        loadUsers();
      });
    });

    function loadUsers() {
      const search = document.getElementById('searchInput').value;
      const role = document.getElementById('roleFilter').value;
      
      const params = new URLSearchParams({
        action: 'get-users',
        page: currentPage,
        search: search,
        role: role
      });

      fetch('../api/users.php?' + params.toString())
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            renderUsersList(data.data);
            renderPagination(data.pagination);
          }
        })
        .catch(err => console.error('Error:', err));
    }

    function renderUsersList(users) {
      const container = document.getElementById('usersList');
      
      if (users.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">👤</div><p>No users found</p></div>';
        return;
      }

      container.innerHTML = users.map(user => `
        <div class="user-row">
          <div>
            <div class="user-name">${escapeHtml(user.full_name || user.username)}</div>
            <div class="user-email">${escapeHtml(user.email)}</div>
          </div>
          <div>${escapeHtml(user.username)}</div>
          <div>
            <span class="role-badge ${user.role}">${user.role}</span>
          </div>
          <div>
            <span class="status-badge ${user.is_active ? 'active' : 'inactive'}">
              ${user.is_active ? 'Active' : 'Inactive'}
            </span>
          </div>
          <div class="user-actions">
            <button class="btn-icon" title="Edit" onclick="editUser(${user.id})">✎</button>
            <button class="btn-icon delete" title="Delete" onclick="deleteUserConfirm(${user.id})">✕</button>
          </div>
        </div>
      `).join('');
    }

    function renderPagination(pagination) {
      const container = document.getElementById('paginationContainer');
      
      if (pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
      }

      let buttons = [];
      for (let i = 1; i <= pagination.total_pages; i++) {
        buttons.push(`
          <button ${i === pagination.current_page ? 'class="active"' : ''} 
                  onclick="goToPage(${i})">
            ${i}
          </button>
        `);
      }

      container.innerHTML = '<div class="pagination">' + buttons.join('') + '</div>';
    }

    function goToPage(page) {
      currentPage = page;
      loadUsers();
    }

    function openAddUserModal() {
      editingUserId = null;
      document.getElementById('modalTitle').textContent = 'Add New User';
      document.getElementById('userForm').reset();
      document.getElementById('password').required = true;
      document.getElementById('userModal').classList.add('active');
    }

    function closeUserModal() {
      document.getElementById('userModal').classList.remove('active');
      editingUserId = null;
    }

    function editUser(userId) {
      editingUserId = userId;
      document.getElementById('modalTitle').textContent = 'Edit User';
      
      fetch(`../api/users.php?action=get-user&id=${userId}`)
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            const user = data.data;
            document.getElementById('username').value = user.username;
            document.getElementById('email').value = user.email;
            document.getElementById('fullName').value = user.full_name || '';
            document.getElementById('phone').value = user.phone || '';
            document.getElementById('role').value = user.role;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('userModal').classList.add('active');
          }
        });
    }

    function handleUserSubmit(event) {
      event.preventDefault();
      
      const formData = {
        username: document.getElementById('username').value,
        email: document.getElementById('email').value,
        full_name: document.getElementById('fullName').value,
        phone: document.getElementById('phone').value,
        role: document.getElementById('role').value
      };

      if (document.getElementById('password').value) {
        formData.password = document.getElementById('password').value;
      }

      if (editingUserId) {
        formData.action = 'update-user';
        formData.id = editingUserId;
      } else {
        formData.action = 'create-user';
        if (!document.getElementById('password').value) {
          showToast('Password is required for new users.', 'error');
          return;
        }
      }

      fetch('../api/users.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
      })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            showToast(data.message, 'success');
            closeUserModal();
            loadUsers();
          } else {
            showToast(data.message, 'error');
          }
        });
    }

    function deleteUserConfirm(userId) {
      if (confirm('Are you sure you want to delete this user?')) {
        fetch('../api/users.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'delete-user',
            id: userId
          })
        })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              showToast(data.message, 'success');
              loadUsers();
            } else {
              showToast(data.message, 'error');
            }
          });
      }
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
  </script>
</body>
</html>

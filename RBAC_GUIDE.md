# SharpCuts Role-Based Access Control (RBAC) Guide

## Overview

SharpCuts now has a unified user management system with **role-based access control**. A single `users` table manages all users (customers, barbers, and admins) with role-based permissions for different features.

---

## Database Structure

### Users Table Schema

```sql
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) UNIQUE NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(100),
  phone VARCHAR(20),
  role ENUM('customer', 'barber', 'admin') DEFAULT 'customer',
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

---

## User Roles

### 1. **Customer** (Default)
- Browse services
- View barber profiles
- Book appointments
- View own appointments
- Update profile

**Features:**
- Homepage access
- Booking system
- Dashboard (view own appointments)

---

### 2. **Barber**
- Manage own availability
- View appointments
- Manage appointment status
- View customer information
- Access barber profile

**Features:**
- Barber dashboard
- Appointment management
- Availability settings
- Customer communication

---

### 3. **Admin**
- Full access to dashboard
- Manage all users (create, read, update, delete)
- Assign/change user roles
- Manage barbers profile data
- Manage services
- View all appointments
- System configuration

**Features:**
- Admin dashboard
- User management (👥 Users page)
- Barber management
- Service management
- Appointment overview
- Analytics and reports

---

## Setup Instructions

### 1. Run Database Migration

Copy and paste the contents of `MIGRATION_RBAC.sql` into phpMyAdmin or MySQL CLI:

```bash
mysql -u root -p sharpcuts_db < MIGRATION_RBAC.sql
```

Or via phpMyAdmin:
1. Open phpMyAdmin
2. Navigate to your database
3. Click "Import" tab
4. Select `MIGRATION_RBAC.sql`
5. Click Import

### 2. Login Credentials (After Migration)

#### Admin Account
```
Email: admin@sharpcuts.local
Password: Admin@123
Username: admin_sharpcuts
```

#### Sample Barber Accounts
```
Email: marcus@sharpcuts.local     | Password: Barber@123 | Name: Marcus Johnson
Email: david@sharpcuts.local      | Password: Barber@123 | Name: David Smith
Email: alex@sharpcuts.local       | Password: Barber@123 | Name: Alex Rodriguez
Email: james@sharpcuts.local      | Password: Barber@123 | Name: James Brown
```

---

## Admin User Management

### Accessing User Management

1. Login as admin
2. Go to Admin Dashboard
3. Click "👥 Users" in sidebar
4. You'll see the user management interface

### User Management Features

#### View Users
- See all users with their role and status
- Search by name, email, or username
- Filter by role (Admin, Barber, Customer)
- Paginated list view

#### Create New User
1. Click "+ Add User"
2. Fill in:
   - Username (unique)
   - Email (unique)
   - Password (min 6 characters)
   - Full Name
   - Phone (optional)
   - Role (Admin, Barber, or Customer)
3. Click "Save User"

#### Edit User
1. Click the ✎ (edit) icon
2. Update fields as needed
3. Change password (optional) or leave blank to keep current
4. Change role if needed
5. Click "Save User"

#### Delete User
1. Click the ✕ (delete) icon
2. Confirm deletion
3. User will be permanently deleted

---

## Using RBAC Functions in Code

### Check if User is Logged In

```php
<?php
require_once 'functions.php';

if (isLoggedIn()) {
    echo "User is logged in";
} else {
    echo "User is not logged in";
    header('Location: login.php');
}
?>
```

### Check User Role

```php
<?php
// Check specific role
if (isAdmin()) {
    echo "Admin access granted";
}

if (isBarber()) {
    echo "Barber access granted";
}

if (isCustomer()) {
    echo "Customer access granted";
}

// Check multiple roles
if (hasAnyRole(['admin', 'barber'])) {
    echo "Admin or Barber";
}

// Check any role
if (hasRole('admin')) {
    echo "Exactly admin";
}
?>
```

### Require Specific Role (Redirect if Not Authorized)

```php
<?php
require_once 'functions.php';

// Require admin
requireRole('admin');
// or
requireRole('admin', '../login.php'); // Custom redirect

// Require admin or barber
requireAnyRole(['admin', 'barber']);
?>
```

### Get Current User Info

```php
<?php
$user = getCurrentUser();

echo "Username: " . $user['username'];
echo "Email: " . $user['email'];
echo "Role: " . $user['role'];
echo "Full Name: " . $user['full_name'];
?>
```

### Permission-Based Access

```php
<?php
// Can this user manage users?
if (canManageUsers()) {
    // Add user management interface
}

// Can this user manage appointments?
if (canManageAppointments()) {
    // Add appointment management
}

// Can this user view customer data?
if (canViewCustomerData()) {
    // Show customer info
}
?>
```

---

## API Endpoints

All user management operations go through `/api/users.php` (admin-only):

### Get Users
```
GET /api/users.php?action=get-users&page=1&search=john&role=barber
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "username": "john_doe",
      "email": "john@example.com",
      "full_name": "John Doe",
      "phone": "09123456789",
      "role": "barber",
      "is_active": true,
      "created_at": "2024-04-05 10:00:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_records": 45,
    "per_page": 10
  }
}
```

### Get Single User
```
GET /api/users.php?action=get-user&id=5
```

### Create User
```
POST /api/users.php
Content-Type: application/json

{
  "action": "create-user",
  "username": "newuser",
  "email": "new@example.com",
  "password": "SecurePass123",
  "full_name": "New User",
  "phone": "09123456789",
  "role": "barber"
}
```

### Update User
```
POST /api/users.php
Content-Type: application/json

{
  "action": "update-user",
  "id": 5,
  "full_name": "Updated Name",
  "phone": "09987654321",
  "role": "admin",
  "is_active": true,
  "new_password": "NewPassword123"  // Optional
}
```

### Delete User
```
POST /api/users.php
Content-Type: application/json

{
  "action": "delete-user",
  "id": 5
}
```

---

## Security Considerations

✅ **What's Protected:**
- All admin operations require `isAdmin()` check
- User can't delete own account
- User can't delete other admins (except first admin)
- Passwords are hashed with bcrypt (PASSWORD_BCRYPT)
- Prepared statements prevent SQL injection
- Session-based authentication

⚠️ **Important:**
1. Always verify `isAdmin()` before sensitive operations
2. Never expose password hashes to frontend
3. Use HTTPS in production
4. Regularly audit user permissions
5. Keep session timeout reasonable

---

## File Structure

```
SharpCuts/
├── functions.php              # Core RBAC functions
├── config.php                 # Database config
├── api/
│   ├── auth.php              # Authentication endpoints
│   └── users.php             # User management API
├── admin/
│   ├── users.php             # User management UI
│   ├── dashboard.php         # Admin dashboard
│   ├── appointments.php
│   ├── barbers.php
│   └── services.php
├── MIGRATION_RBAC.sql        # Database migration with seed data
└── RBAC_GUIDE.md             # This file
```

---

## Feature Access Matrix

| Feature | Customer | Barber | Admin |
|---------|----------|--------|-------|
| Browse Services | ✅ | ✅ | ✅ |
| View Barbers | ✅ | ✅ | ✅ |
| Book Appointment | ✅ | ❌ | ✅ |
| View Own Appointments | ✅ | ✅ | ✅ |
| Manage Appointments | ❌ | ✅ | ✅ |
| Manage Availability | ❌ | ✅ | ✅ |
| View All Users | ❌ | ❌ | ✅ |
| Create User | ❌ | ❌ | ✅ |
| Edit User | ❌ | ❌ | ✅ |
| Delete User | ❌ | ❌ | ✅ |
| Assign Roles | ❌ | ❌ | ✅ |
| View Analytics | ❌ | ❌ | ✅ |
| Manage Services | ❌ | ❌ | ✅ |
| Manage Barbers | ❌ | ❌ | ✅ |

---

## Best Practices

### 1. Always Check Authorization
```php
// ✅ Good
if (!isAdmin()) {
    http_response_code(403);
    exit('Unauthorized');
}

// ❌ Bad
// Assume user already checked on frontend
```

### 2. Use Prepared Statements
```php
// ✅ Good
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

// ❌ Bad
$result = $conn->query("SELECT * FROM users WHERE id = $id");
```

### 3. Hash Passwords Properly
```php
// ✅ Good
$hash = password_hash($password, PASSWORD_BCRYPT);

// ❌ Bad
$hash = md5($password);
```

### 4. Implement Audit Logging
```php
// Log role changes
$action = "Changed user role from barber to admin";
// Save to audit_log table
```

---

## Troubleshooting

### Issue: User can't login after migration
**Solution:** Clear browser cookies and session. Restart PHP session.

### Issue: Role changes not taking effect
**Solution:** Logout and login again. Role is stored in $_SESSION on login.

### Issue: Getting "Unauthorized" error on admin pages
**Solution:** Verify user role in database: `SELECT role FROM users WHERE id = ?`

### Issue: Can't create new users
**Solution:** Check if logged-in user is admin with `isAdmin()` check in API.

---

## Next Steps

1. ✅ Run the migration SQL
2. ✅ Login with admin account
3. ✅ Visit Admin → Users page
4. ✅ Create or edit users with different roles
5. ✅ Test feature access with different roles
6. ✅ Review and customize role permissions as needed

---

## Support

For issues or questions about RBAC implementation, check:
- `/api/users.php` - User management API
- `/admin/users.php` - User management UI
- `functions.php` - RBAC helper functions

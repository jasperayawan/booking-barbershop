# SharpCuts RBAC - Quick Reference

## 🔐 Default Login Credentials

### Admin Account
```
Email: admin@sharpcuts.local
Password: Admin@123
```

### Barber Accounts (Same Password for All)
```
marcus@sharpcuts.local  | Password: Barber@123
david@sharpcuts.local   | Password: Barber@123
alex@sharpcuts.local    | Password: Barber@123
james@sharpcuts.local   | Password: Barber@123
```

---

## 🛠️ Core RBAC Functions

### Authentication Check
```php
isLoggedIn()              // True if user logged in
getCurrentUser()          // Returns user array
```

### Role Checks
```php
isAdmin()                 // True if role = 'admin'
isBarber()                // True if role = 'barber'
isCustomer()              // True if role = 'customer'
hasRole('admin')          // Check specific role
hasAnyRole(['admin', 'barber'])  // Check multiple roles
```

### Authorization (Blocks Access)
```php
requireRole('admin')                // Redirect if not admin
requireAnyRole(['admin', 'barber']) // Redirect if not admin/barber
```

### Permission Checks
```php
canManageUsers()          // Only admin
canManageAppointments()   // Admin or barber
canViewCustomerData()     // Admin or barber
```

---

## 📊 User Roles & Permissions

| Permission | Customer | Barber | Admin |
|-----------|----------|--------|-------|
| Browse Services | ✅ | ✅ | ✅ |
| Book Appointment | ✅ | ❌ | ✅ |
| Manage Appointments | ❌ | ✅ | ✅ |
| View Users | ❌ | ❌ | ✅ |
| Create/Edit/Delete Users | ❌ | ❌ | ✅ |
| Assign Roles | ❌ | ❌ | ✅ |

---

## 📝 How to Create a User (Admin Only)

### Via Admin Dashboard
1. Login as admin
2. Go to "👥 Users" in sidebar
3. Click "+ Add User"
4. Fill form (username, email, password, role)
5. Click "Save User"

### Via API
```json
POST /api/users.php
{
  "action": "create-user",
  "username": "john_doe",
  "email": "john@example.com",
  "password": "SecurePass123",
  "full_name": "John Doe",
  "role": "barber"
}
```

---

## 🔄 Change User Role

1. Admin Dashboard → Users
2. Click ✎ (edit icon)
3. Change "Role" dropdown
4. Save

---

## 🛡️ Security Features

✅ Passwords hashed with bcrypt  
✅ Prepared statements (no SQL injection)  
✅ Session-based auth  
✅ Role-based access control  
✅ Can't delete own account  
✅ Can't modify other admins  

---

## 📁 Key Files

```
MIGRATION_RBAC.sql      # Database setup (run first!)
functions.php           # RBAC functions
api/users.php          # User management API
admin/users.php        # User management UI
RBAC_GUIDE.md          # Full documentation
```

---

## ⚡ Common Tasks

### Protect a Page (Admin Only)
```php
<?php
require_once 'functions.php';
requireRole('admin');
// Page content here
?>
```

### Show Content Based on Role
```php
<?php
if (isAdmin()) {
    echo "Admin Panel";
} elseif (isBarber()) {
    echo "Barber Dashboard";
} else {
    echo "Customer Page";
}
?>
```

### Create Admin User Programmatically
```php
<?php
registerUser('newadmin', 'admin@local.com', 'password', 'Admin Name');
// Then change role in database to 'admin'
?>
```

---

## 🚀 First Steps

1. **Run Migration:** Copy MIGRATION_RBAC.sql to phpMyAdmin
2. **Login:** Use admin credentials above
3. **Visit Users Page:** Admin Dashboard → Users
4. **Create Users:** Add barbers, customers, admins
5. **Test Roles:** Login as different roles to verify access

---

## 📞 Database Verification

Check users in database:
```sql
SELECT id, username, email, role, is_active FROM users ORDER BY created_at DESC;
```

Verify admin exists:
```sql
SELECT * FROM users WHERE role = 'admin' LIMIT 1;
```

---

## ⚠️ Important Notes

- **Change default passwords** after setup
- **Backup database** before changes
- **Use HTTPS in production**
- **Never expose password hashes**
- **Log all admin actions** for audit trail
- **Regular security reviews**

---

## 🆘 Troubleshooting

| Problem | Solution |
|---------|----------|
| Can't login | Check email/password, verify user is active in DB |
| Admin role not working | Logout & login again (role stored in session) |
| User not found | Check database search functionality |
| Role not changing | Refresh page, clear browser cache |
| API returning 403 | User must be admin to access user management |

---

**Last Updated:** April 5, 2026  
**Version:** 1.0 RBAC Implementation

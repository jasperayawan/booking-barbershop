# SharpCuts - Complete Setup & Deployment Guide

## 📋 Table of Contents
1. [Prerequisites](#prerequisites)
2. [Installation Steps](#installation-steps)
3. [Database Setup](#database-setup)
4. [Testing & Verification](#testing--verification)
5. [Troubleshooting](#troubleshooting)
6. [Security Improvements Made](#security-improvements-made)
7. [Project Architecture](#project-architecture)

---

## Prerequisites

### Required Software
- **XAMPP** (Apache + MySQL + PHP)
- **PHP 7.4+** with MySQLi extension
- **MySQL 5.7+** or **MariaDB 10.3+**
- **Modern Web Browser** (Chrome, Firefox, Safari, Edge)

### Recommended Setup
- PHP 8.0+ for better performance
- MySQL 8.0 for advanced features
- VS Code or similar editor for customization

---

## Installation Steps

### Step 1: Verify XAMPP Installation

1. Open **XAMPP Control Panel**
2. Start **Apache** and **MySQL** services
3. Verify they have status "Running"

```
✓ Apache [Running]
✓ MySQL   [Running]
```

### Step 2: Project Files

Ensure SharpCuts is in the correct location:
```
C:\xampp\htdocs\SharpCuts\
```

Project structure should have:
```
SharpCuts/
├── config.php                    # Database configuration
├── functions.php                 # Core PHP functions
├── setup_database.php            # NEW: Database initialization script
├── index.php                     # Homepage
├── login.php                     # Login page
├── register.php                  # Registration page
├── book.php                      # Booking form
├── dashboard.php                 # Customer dashboard
├── contact.php                   # Contact page
├── ourBarbers.php               # Barbers listing
├── main.js                       # Frontend JavaScript
├── style.css                     # Main styles
├── api/
│   ├── auth.php                 # Authentication endpoints
│   ├── appointments.php         # Appointment management (SECURED)
│   ├── barbers.php             # Barber management (SECURED)
│   ├── services.php            # Service management (SECURED)
│   └── get-data.php            # Data retrieval
└── admin/
    ├── dashboard.php           # Admin dashboard
    ├── appointments.php        # Manage appointments
    ├── barbers.php            # Manage barbers
    ├── services.php           # Manage services
    ├── config.php             # Database config (redundant)
    └── styles.css             # Admin styles
```

---

## Database Setup

### Method 1: Automatic Setup (Recommended)

1. **Open Browser** and navigate to:
   ```
   http://localhost/SharpCuts/setup_database.php
   ```

2. **Click Run** or let the page auto-execute

3. **Expected Output:**
   ```
   ✓ Users table created
   ✓ Barbers table created
   ✓ Services table created
   ✓ Barber Availability table created
   ✓ Appointments table created
   ✓ Added barber: Marcus Johnson
   ✓ Added barber: David Smith
   ✓ Added barber: Alex Rodriguez
   ✓ Added barber: James Brown
   ✓ Added service: Classic Haircut
   ✓ Added service: Fade Cut
   ... (and more)
   ✓ Admin account created (email: admin@sharpcuts.com, password: admin123)
   ✓ Sample customer created (email: customer@example.com, password: password123)
   ```

4. **Test Accounts Created:**
   - **Admin**: `admin@sharpcuts.com` / `admin123`
   - **Customer**: `customer@example.com` / `password123`

### Method 2: Manual MySQL Setup

If automatic setup fails, run these SQL commands:

```sql
-- Create Database
CREATE DATABASE IF NOT EXISTS sharpcuts_db;
USE sharpcuts_db;

-- Create Users Table
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
);

-- Create Barbers Table
CREATE TABLE barbers (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  title VARCHAR(100),
  specialties TEXT,
  rating DECIMAL(3, 2) DEFAULT 5.0,
  experience_years INT DEFAULT 0,
  photo_url VARCHAR(255),
  bio TEXT,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Services Table
CREATE TABLE services (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  price DECIMAL(10, 2) NOT NULL,
  duration_minutes INT DEFAULT 30,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Barber Availability Table
CREATE TABLE barber_availability (
  id INT PRIMARY KEY AUTO_INCREMENT,
  barber_id INT NOT NULL,
  day_of_week VARCHAR(20) NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  is_available BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE CASCADE,
  UNIQUE KEY barber_day (barber_id, day_of_week)
);

-- Create Appointments Table
CREATE TABLE appointments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  customer_name VARCHAR(100) NOT NULL,
  customer_phone VARCHAR(20) NOT NULL,
  customer_email VARCHAR(100) NOT NULL,
  barber_id INT NOT NULL,
  service_id INT NOT NULL,
  appointment_date DATE NOT NULL,
  appointment_time TIME NOT NULL,
  status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE CASCADE,
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
  UNIQUE KEY barber_slot (barber_id, appointment_date, appointment_time)
);
```

---

## Testing & Verification

### Step 1: Test Home Page
1. Navigate to: `http://localhost/SharpCuts/`
2. Should see homepage with barber images
3. Verify navigation menu works

### Step 2: Test Registration
1. Click **Sign up** button
2. Register with test account:
   - Username: `testuser`
   - Email: `testuser@test.com`
   - Password: `Test@123`
3. Should see success message
4. Verify account created in admin panel

### Step 3: Test Login
1. Click **Log in** button
2. Use registered or sample credentials
3. Should redirect to dashboard
4. Verify customer name shows in header

### Step 4: Test Booking
1. Click **Book Now** (requires login)
2. Select:
   - Barber: Marcus Johnson
   - Service: Classic Haircut
   - Date: Next available date
   - Time: Any available slot
3. Click **Book Appointment**
4. Should see appointment in dashboard

### Step 5: Test Admin Panel
1. Navigate to: `http://localhost/SharpCuts/admin/dashboard.php`
2. Login with admin credentials:
   - Email: `admin@sharpcuts.com`
   - Password: `admin123`
3. Verify dashboard shows:
   - Total Appointments count
   - Pending Appointments count
   - Total Barbers
4. Test each section:
   - **Appointments** - View/Edit/Delete
   - **Barbers** - Add/Edit/Delete barbers
   - **Services** - Add/Edit/Delete services

### Sample Test Data

| Type | Name | Details |
|------|------|---------|
| **Barber 1** | Marcus Johnson | Master Barber, Mon-Sat 9AM-7PM |
| **Barber 2** | David Smith | Senior Barber, Tue-Sat 10AM-6PM |
| **Barber 3** | Alex Rodriguez | Specialist, Wed-Sun 11AM-8PM |
| **Barber 4** | James Brown | Certified, Mon-Sat 8AM-5PM |
| **Service 1** | Classic Haircut | ₱350, 30 min |
| **Service 2** | Fade Cut | ₱400, 35 min |
| **Service 3** | Beard Trim | ₱250, 25 min |

---

## Troubleshooting

### Issue: Database Connection Error

**Error Message:**
```
Database connection failed: Connection refused
```

**Solutions:**
1. Verify MySQL is running in XAMPP Control Panel
2. Check `config.php` has correct credentials:
   ```php
   $servername = "localhost";
   $username = "root";
   $password = "";
   $dbname = "sharpcuts_db";
   ```
3. Ensure database `sharpcuts_db` exists
4. Check MySQL port (default 3306) is not blocked

### Issue: Tables Don't Exist

**Error Message:**
```
Table 'sharpcuts_db.users' doesn't exist
```

**Solutions:**
1. Run `setup_database.php` again
2. Manually create tables using MySQL commands (see Database Setup)
3. Verify user permissions in MySQL

### Issue: Can't Login

**Possible Causes:**
- Wrong email/password
- User not active (`is_active = 0`)
- User account doesn't exist

**Solutions:**
1. Check credentials in database:
   ```sql
   SELECT * FROM users WHERE email='admin@sharpcuts.com';
   ```
2. Verify password hash exists
3. Check if `is_active = 1`

### Issue: Images Not Displaying

**Solutions:**
1. Verify `assets/` folder is not empty
2. Add barber photos to admin panel
3. Check photo URLs in database

### Issue: Appointment Booking Fails

**Error:** "Barber not available on this day"

**Solutions:**
1. Verify barber availability is set correctly
2. Check availability schedule in admin panel
3. Run `setup_database.php` to repopulate schedule

### Issue: XAMPP Won't Start

**Solutions:**
1. Check ports (Apache:80, MySQL:3306) not in use
2. Disable antivirus temporarily
3. Run as Administrator
4. Check XAMPP error logs

---

## Security Improvements Made

### ✅ SQL Injection Prevention
**Before (Vulnerable):**
```php
$query = "SELECT * FROM users WHERE email = '$email'"; // UNSAFE
```

**After (Secured):**
```php
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
```

### ✅ Password Security
- Uses `PASSWORD_BCRYPT` hashing
- Password is never stored as plain text
- Minimum 6 characters (can be increased)

### ✅ Session Management
- Proper session destruction on logout
- Session cookie cleared
- Session regeneration support

### ✅ Input Validation
- Email validation with `filter_var()`
- Integer casting for IDs
- Required field checks

### ✅ Database Foreign Keys
- Cascade delete for data integrity
- Unique constraints to prevent duplicates
- Proper relationships between tables

---

## Project Architecture

### Frontend Architecture
```
Public Pages (No Auth Required)
├── index.php           - Homepage
├── login.php          - Login form
├── register.php       - Registration form
├── services.php       - Services listing
├── ourBarbers.php     - Barber profiles
└── contact.php        - Contact form

Protected Pages (Auth Required)
├── book.php           - Booking interface
└── dashboard.php      - Customer dashboard

Admin Pages (Admin Only)
├── admin/dashboard.php    - Overview
├── admin/appointments.php - Manage bookings
├── admin/barbers.php      - Manage barbers
└── admin/services.php     - Manage services
```

### Backend Architecture
```
Database Layer
├── config.php         - Connection
└── setup_database.php - Initialization

Business Logic
└── functions.php      - Core functions
    ├── registerUser()
    ├── loginUser()
    ├── bookAppointment()
    └── getUserAppointments()

API Layer (Endpoints)
├── api/auth.php           - Authentication
├── api/appointments.php   - CRUD (SECURED)
├── api/barbers.php        - CRUD (SECURED)
├── api/services.php       - CRUD (SECURED)
└── api/get-data.php       - Data retrieval

Frontend Layer
├── main.js            - JavaScript logic
├── style.css          - Styling
└── HTML Pages         - User interface
```

### Data Flow

**Registration Flow:**
```
register.php (form) 
  → functions.php::registerUser() 
  → database: INSERT users
  → response to page
```

**Login Flow:**
```
login.php (form)
  → functions.php::loginUser()
  → database: SELECT + password_verify()
  → SET $_SESSION
  → redirect to dashboard
```

**Booking Flow:**
```
book.php (form)
  → functions.php::bookAppointment()
  → Check barber availability
  → Check for conflicts
  → database: INSERT appointments
  → dashboard.php
```

**Admin Appointment Management:**
```
admin/appointments.php
  → api/appointments.php
  → INSERT/UPDATE/DELETE
  → database
  → JSON response
  → page reload
```

---

## Additional Notes

### Password Requirements
Current minimum: 6 characters

**To increase security, update:**
```php
// In functions.php registerUser()
if (strlen($password) < 8) {  // Changed from 6 to 8
    return ['success' => false, 'message' => 'Password must be at least 8 characters'];
}
```

### Email Notifications (Future Enhancement)
Currently no emails are sent. To add:
```php
// Add after appointment creation
mail($customer_email, "Appointment Confirmation", $message_body);
```

### File Upload Support (Future Enhancement)
To add barber photos:
1. Create `assets/barbers/` folder
2. Implement file upload validation
3. Store path in `photo_url` field

### Backup & Maintenance
**Regular backups:**
```sql
-- Backup database
mysqldump -u root sharpcuts_db > backup.sql

-- Restore database
mysql -u root sharpcuts_db < backup.sql
```

---

## Support & Resources

- **PHP Documentation**: https://www.php.net/docs.php
- **MySQL Documentation**: https://dev.mysql.com/doc/
- **XAMPP**: https://www.apachefriends.org/
- **Security Best Practices**: https://owasp.org/

---

**Setup Version:** 1.0  
**Last Updated:** April 5, 2026  
**Status:** ✅ Production Ready

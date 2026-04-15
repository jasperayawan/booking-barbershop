# SharpCuts - Quick Reference Guide

## 🚀 Quick Start

### 1. Initial Setup (One-Time)
```bash
1. Start XAMPP (Apache + MySQL)
2. Navigate to http://localhost/SharpCuts/setup_database.php
3. Wait for "✅ Setup Completed Successfully!"
4. Delete setup_database.php file from server
5. Go to http://localhost/SharpCuts/
```

### 2. Default Credentials
| Role | Email | Password |
|------|-------|----------|
| Admin | admin@sharpcuts.com | admin123 |
| Customer | customer@example.com | password123 |

### 3. Key URLs
| Page | URL |
|------|-----|
| Home | http://localhost/SharpCuts/ |
| Register | http://localhost/SharpCuts/register.php |
| Login | http://localhost/SharpCuts/login.php |
| Booking | http://localhost/SharpCuts/book.php |
| Dashboard | http://localhost/SharpCuts/dashboard.php |
| Admin | http://localhost/SharpCuts/admin/dashboard.php |

---

## 📁 File Structure & Purpose

### Core Files
| File | Purpose |
|------|---------|
| `config.php` | Database connection configuration |
| `functions.php` | All backend logic (register, login, booking) |
| `setup_database.php` | Database initialization (run once, then delete) |
| `main.js` | Frontend JavaScript (forms, logout, carousel) |
| `style.css` | Global styling |

### Public Pages
| File | Purpose |
|------|---------|
| `index.php` | Landing page with hero section |
| `login.php` | User login form |
| `register.php` | User registration form |
| `book.php` | Appointment booking interface |
| `dashboard.php` | Customer's appointment history |
| `services.php` | Service catalog |
| `ourBarbers.php` | Barber profiles |
| `contact.php` | Contact form |

### API Endpoints
| File | Methods | Purpose |
|------|---------|---------|
| `api/auth.php` | POST | Register, Login, Logout, Check auth |
| `api/appointments.php` | POST | Create, Update, Delete appointments |
| `api/barbers.php` | GET, POST | Get barbers, Create, Update, Delete |
| `api/services.php` | POST | Create, Update, Delete services |
| `api/get-data.php` | GET | Fetch appointments, barbers, services, slots |

### Admin Pages
| File | Purpose |
|------|---------|
| `admin/dashboard.php` | Admin overview with statistics |
| `admin/appointments.php` | Appointment management interface |
| `admin/barbers.php` | Barber management interface |
| `admin/services.php` | Service management interface |
| `admin/config.php` | Redundant config (can be removed) |

---

## 🔐 Security & Best Practices

### ✅ SQL Injection Prevention
All database queries use **Prepared Statements**:
```php
// CORRECT (Secure)
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();

// WRONG (Vulnerable - Don't use!)
$query = "SELECT * FROM users WHERE email = '$email'";
$conn->query($query);
```

### ✅ Password Hashing
```php
// Storing password
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Verifying password
password_verify($input_password, $stored_hash);
```

### ✅ Session Security
```php
// Login
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];

// Logout
session_destroy();
setcookie(session_name(), '', time() - 42000, '/');
```

### ✅ Input Validation
```php
// Email validation
filter_var($email, FILTER_VALIDATE_EMAIL);

// Required fields
if (empty($username) || empty($email) || empty($password)) { ... }

// Integer IDs
$id = intval($_POST['id']);

// Date validation (implicit with DATE type)
```

---

## 🗃️ Database Schema

### Users Table
```sql
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,           -- User ID
  username VARCHAR(50) UNIQUE NOT NULL,        -- Login username
  email VARCHAR(100) UNIQUE NOT NULL,          -- Login email
  password_hash VARCHAR(255) NOT NULL,         -- Bcrypt hash
  full_name VARCHAR(100),                      -- Display name
  phone VARCHAR(20),                           -- Contact phone
  role ENUM('customer','barber','admin'),      -- User type
  is_active BOOLEAN DEFAULT TRUE,              -- Account status
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE
);
```

### Barbers Table
```sql
CREATE TABLE barbers (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,                  -- Barber name
  title VARCHAR(100),                          -- Job title
  specialties TEXT,                            -- Skills/services
  rating DECIMAL(3,2) DEFAULT 5.0,            -- Star rating (0-5)
  experience_years INT DEFAULT 0,              -- Years of experience
  photo_url VARCHAR(255),                      -- Profile photo
  bio TEXT,                                    -- Barber biography
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE
);
```

### Services Table
```sql
CREATE TABLE services (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,                  -- Service name
  description TEXT,                            -- Service details
  price DECIMAL(10,2) NOT NULL,               -- Service cost
  duration_minutes INT DEFAULT 30,             -- Time required
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE
);
```

### Barber Availability Table
```sql
CREATE TABLE barber_availability (
  id INT PRIMARY KEY AUTO_INCREMENT,
  barber_id INT NOT NULL,                      -- FK to barbers
  day_of_week VARCHAR(20) NOT NULL,           -- Monday-Sunday
  start_time TIME NOT NULL,                   -- Opening time
  end_time TIME NOT NULL,                     -- Closing time
  is_available BOOLEAN DEFAULT TRUE,          -- Working or off
  FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE CASCADE,
  UNIQUE KEY barber_day (barber_id, day_of_week)
);
```

### Appointments Table
```sql
CREATE TABLE appointments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  customer_name VARCHAR(100) NOT NULL,        -- Client name
  customer_phone VARCHAR(20) NOT NULL,        -- Contact number
  customer_email VARCHAR(100) NOT NULL,       -- Email address
  barber_id INT NOT NULL,                     -- FK to barbers
  service_id INT NOT NULL,                    -- FK to services
  appointment_date DATE NOT NULL,             -- Day of booking
  appointment_time TIME NOT NULL,             -- Time of booking
  status ENUM('pending','confirmed',          -- Appointment state
             'completed','cancelled'),
  notes TEXT,                                 -- Additional info
  FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE CASCADE,
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
  UNIQUE KEY barber_slot (barber_id, appointment_date, appointment_time)
);
```

---

## 💻 Common Code Patterns

### Register New User
```php
$result = registerUser($username, $email, $password, $full_name);
if ($result['success']) {
    // Redirect to login
    header('Location: login.php');
} else {
    echo $result['message'];  // Show error
}
```

### Login User
```php
$result = loginUser($email, $password);
if ($result['success']) {
    // Check role for redirect
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
} else {
    echo $result['message'];
}
```

### Check User Authentication
```php
if (isLoggedIn()) {
    // User is logged in, show content
} else {
    // Redirect to login
    header('Location: login.php');
    exit;
}
```

### Check Admin Permission
```php
if (!isAdmin()) {
    header('Location: login.php');
    exit;
}
// Admin-only code here
```

### Book Appointment
```php
$result = bookAppointment(
    $customer_name,
    $customer_phone,
    $customer_email,
    $barber_id,
    $service_id,
    $appointment_date,
    $appointment_time
);

if ($result['success']) {
    // Show appointment ID
    $appointment_id = $result['appointment_id'];
} else {
    // Show error message
    echo $result['message'];
}
```

### Get User Appointments
```php
$user_id = $_SESSION['user_id'];
$appointments = getUserAppointments($user_id);

foreach ($appointments as $apt) {
    echo $apt['appointment_date'] . " - " . $apt['barber_name'];
}
```

---

## 🐛 Debugging Tips

### Enable Error Display (Development Only)
```php
// Add at top of functions.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Check Database Connection
```php
require_once 'config.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";
```

### View Database Queries
```php
// MySQL query log
$result = $conn->query("SELECT * FROM appointments");
if (!$result) {
    echo "Query Error: " . $conn->error;
} else {
    echo "Query successful, " . $result->num_rows . " rows";
}
```

### Verify Session Data
```php
// In any protected page
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";
```

### Test API Endpoints
```javascript
// In browser console
fetch('/SharpCuts/api/get-data.php?action=get-barbers')
  .then(r => r.json())
  .then(data => console.log(data));
```

---

## 📋 Common Tasks

### Add a New Service
1. Go to `admin/services.php`
2. Click "+ Add Service"
3. Enter: Name, Description, Price, Duration
4. Click Create
5. Service appears in booking form

### Add a New Barber
1. Go to `admin/barbers.php`
2. Click "+ Add Barber"
3. Enter: Name, Title, Specialties, Rating, Experience
4. Set availability for each day
5. Click Create

### View Appointments
1. Go to `admin/appointments.php`
2. Filter by Status, Barber, Date
3. Click appointment to edit
4. Update status: pending → confirmed → completed

### Delete Old Appointments
1. Go to `admin/appointments.php`
2. Click delete icon (trash) next to appointment
3. Confirm deletion
4. Optional: Cancel instead of delete to keep records

### Reset User Password (Future Enhancement)
```php
// Manual reset in MySQL
UPDATE users SET password_hash = PASSWORD('new_password') WHERE id = 1;
```

---

## ⚠️ Important Notes

### Delete After Setup
```
DELETE this file after initial setup:
- setup_database.php (security risk to leave on server)
```

### Database Credentials
```php
Located in config.php
- Host: localhost
- User: root
- Password: (empty by default)
- Database: sharpcuts_db
```

### Port Numbers
```
Apache: 8080 (or 80)
MySQL: 3306
```

### Appointment Slots
- Default: 30-minute intervals
- Set barber hours in availability
- Conflicts automatically prevented

---

## 📞 Quick Commands

### Check MySQL via Command Line
```bash
mysql -u root -p
USE sharpcuts_db;
SHOW TABLES;
SELECT * FROM users;
SELECT * FROM appointments;
```

### Reset All Data
```bash
# Backup first!
mysqldump -u root sharpcuts_db > backup.sql

# Then drop and recreate
DROP DATABASE sharpcuts_db;
# Run setup_database.php again
```

---

**Version:** 1.0  
**Last Updated:** April 5, 2026  
**Status:** ✅ Ready for Use

<?php
/**
 * SharpCuts Database Setup Script
 * Run this once to initialize all database tables
 * Access via: http://localhost/SharpCuts/setup_database.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Track results
$results = [];
$errors = [];

function executeQuery($sql, $description) {
    global $conn, $results, $errors;
    
    if ($conn->query($sql)) {
        $results[] = "✓ " . $description;
        return true;
    } else {
        $errors[] = "✗ " . $description . ": " . $conn->error;
        return false;
    }
}

// Drop existing tables (optional - commented out for safety)
// $conn->query("DROP TABLE IF EXISTS barber_availability");
// $conn->query("DROP TABLE IF EXISTS appointments");
// $conn->query("DROP TABLE IF EXISTS services");
// $conn->query("DROP TABLE IF EXISTS barbers");
// $conn->query("DROP TABLE IF EXISTS users");

// 1. Users Table
$sql_users = "
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100),
  `phone` VARCHAR(20),
  `role` ENUM('customer', 'barber', 'admin') DEFAULT 'customer',
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
";
executeQuery($sql_users, "Users table created");

// 2. Barbers Table
$sql_barbers = "
CREATE TABLE IF NOT EXISTS `barbers` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `title` VARCHAR(100),
  `specialties` TEXT,
  `rating` DECIMAL(3, 2) DEFAULT 5.0,
  `experience_years` INT DEFAULT 0,
  `photo_url` VARCHAR(255),
  `bio` TEXT,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
";
executeQuery($sql_barbers, "Barbers table created");

// 3. Services Table
$sql_services = "
CREATE TABLE IF NOT EXISTS `services` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(10, 2) NOT NULL,
  `duration_minutes` INT DEFAULT 30,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
";
executeQuery($sql_services, "Services table created");

// 4. Barber Availability Table
$sql_availability = "
CREATE TABLE IF NOT EXISTS `barber_availability` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `barber_id` INT NOT NULL,
  `day_of_week` VARCHAR(20) NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `is_available` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`barber_id`) REFERENCES `barbers`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `barber_day` (`barber_id`, `day_of_week`)
)
";
executeQuery($sql_availability, "Barber Availability table created");

// 5. Appointments Table
$sql_appointments = "
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_phone` VARCHAR(20) NOT NULL,
  `customer_email` VARCHAR(100) NOT NULL,
  `barber_id` INT NOT NULL,
  `service_id` INT NOT NULL,
  `appointment_date` DATE NOT NULL,
  `appointment_time` TIME NOT NULL,
  `status` ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`barber_id`) REFERENCES `barbers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `barber_slot` (`barber_id`, `appointment_date`, `appointment_time`)
)
";
executeQuery($sql_appointments, "Appointments table created");

// Now insert sample data
echo "\n========== INSERTING SAMPLE DATA ==========\n\n";

// Insert sample barbers
$barbers_data = [
    ['Marcus Johnson', 'Master Barber', 'Classic cuts, beard grooming, fades', '4.9', 12],
    ['David Smith', 'Senior Barber', 'Precision cuts, line-ups, styling', '4.8', 8],
    ['Alex Rodriguez', 'Barber Specialist', 'Modern cuts, color work, treatments', '4.7', 5],
    ['James Brown', 'Certified Barber', 'Traditional styles, shaving, conditioning', '4.9', 15],
];

foreach ($barbers_data as $barber) {
    $sql = "INSERT INTO barbers (name, title, specialties, rating, experience_years) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdi", $barber[0], $barber[1], $barber[2], $barber[3], $barber[4]);
    
    if ($stmt->execute()) {
        $results[] = "✓ Added barber: " . $barber[0];
    } else {
        $errors[] = "✗ Failed to add barber " . $barber[0] . ": " . $conn->error;
    }
    $stmt->close();
}

// Insert sample services
$services_data = [
    ['Classic Haircut', 'Timeless men\'s haircut with attention to detail', 350, 30],
    ['Fade Cut', 'Modern fade haircut with clean lines', 400, 35],
    ['Beard Trim & Shape', 'Professional beard grooming and shaping', 250, 25],
    ['Straight Razor Shave', 'Traditional hot towel shave experience', 300, 25],
    ['Line-up Only', 'Clean up edges and detail lines', 150, 15],
    ['Hair Color', 'Professional hair coloring service', 500, 45],
    ['Beard Oil Treatment', 'Conditioning treatment for beard health', 200, 20],
];

foreach ($services_data as $service) {
    $sql = "INSERT INTO services (name, description, price, duration_minutes) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdi", $service[0], $service[1], $service[2], $service[3]);
    
    if ($stmt->execute()) {
        $results[] = "✓ Added service: " . $service[0];
    } else {
        $errors[] = "✗ Failed to add service " . $service[0] . ": " . $conn->error;
    }
    $stmt->close();
}

// Insert sample barber availability
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$availability_schedule = [
    1 => ['09:00', '19:00', [true, true, true, true, true, true, false]], // Marcus
    2 => ['10:00', '18:00', [false, true, true, true, true, true, false]], // David
    3 => ['11:00', '20:00', [false, false, true, true, true, true, true]], // Alex
    4 => ['08:00', '17:00', [true, true, true, true, true, true, false]], // James
];

foreach ($availability_schedule as $barber_id => $schedule) {
    $start_time = $schedule[0];
    $end_time = $schedule[1];
    $availability = $schedule[2];
    
    foreach ($days as $day_index => $day) {
        $is_available = $availability[$day_index] ? 1 : 0;
        
        $sql = "INSERT INTO barber_availability (barber_id, day_of_week, start_time, end_time, is_available) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE start_time=VALUES(start_time), end_time=VALUES(end_time), is_available=VALUES(is_available)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssi", $barber_id, $day, $start_time, $end_time, $is_available);
        
        if ($stmt->execute()) {
            // Silent on success
        } else {
            $errors[] = "✗ Failed to add availability for barber $barber_id on $day: " . $conn->error;
        }
        $stmt->close();
    }
    $results[] = "✓ Added availability schedule for barber ID: " . $barber_id;
}

// Insert sample admin user
$admin_email = 'admin@sharpcuts.com';
$admin_username = 'admin';
$admin_password = password_hash('admin123', PASSWORD_BCRYPT);

$sql = "INSERT INTO users (username, email, password_hash, full_name, role) 
        VALUES (?, ?, ?, ?, 'admin')
        ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash)";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $admin_username, $admin_email, $admin_password);

if ($stmt->execute()) {
    $results[] = "✓ Admin account created/updated (email: admin@sharpcuts.com, password: admin123)";
} else {
    $errors[] = "✗ Failed to create admin account: " . $conn->error;
}
$stmt->close();

// Insert sample customer user
$customer_email = 'customer@example.com';
$customer_username = 'johndoe';
$customer_password = password_hash('password123', PASSWORD_BCRYPT);
$customer_name = 'John Doe';

$sql = "INSERT INTO users (username, email, password_hash, full_name, role) 
        VALUES (?, ?, ?, ?, 'customer')
        ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash)";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $customer_username, $customer_email, $customer_password, $customer_name);

if ($stmt->execute()) {
    $results[] = "✓ Sample customer created (email: customer@example.com, password: password123)";
} else {
    $errors[] = "✗ Failed to create sample customer: " . $conn->error;
}
$stmt->close();

$conn->close();

// Display results
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SharpCuts Database Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #111;
            border-bottom: 3px solid #8A38F5;
            padding-bottom: 15px;
        }
        .results, .errors {
            margin-top: 20px;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            line-height: 1.8;
        }
        .results {
            background: #f0fdf4;
            border: 1px solid #86efac;
            color: #166534;
        }
        .errors {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-top: 20px;
            color: #333;
        }
        .account-info {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
        }
        .account-info p {
            margin: 8px 0;
            font-family: monospace;
        }
        .next-steps {
            background: #e0e7ff;
            border: 1px solid #c7d2fe;
            padding: 20px;
            border-radius: 4px;
            margin-top: 20px;
        }
        .next-steps ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .next-steps li {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛠️ SharpCuts Database Setup</h1>
        
        <div class="section-title">✅ Setup Completed Successfully!</div>
        
        <?php if (!empty($results)): ?>
        <div class="results">
            <?php foreach ($results as $result): ?>
                <div><?php echo htmlspecialchars($result); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="errors">
            <strong>⚠️ Errors Encountered:</strong><br>
            <?php foreach ($errors as $error): ?>
                <div><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="account-info">
            <strong>📝 Test Accounts Created:</strong>
            <p><strong>Admin Account:</strong><br>
            Email: admin@sharpcuts.com<br>
            Password: admin123</p>
            
            <p><strong>Customer Account:</strong><br>
            Email: customer@example.com<br>
            Password: password123</p>
        </div>

        <div class="next-steps">
            <strong>🚀 Next Steps:</strong>
            <ol>
                <li><a href="index.php">Go to Home Page</a> - Visit the main site</li>
                <li><a href="login.php">Try Login</a> - Test with admin or customer account</li>
                <li><a href="admin/dashboard.php">View Admin Dashboard</a> - Manage bookings</li>
                <li>Update sample data in database as needed</li>
            </ol>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px;">
            <p><strong>⚠️ Important:</strong> Delete this setup file (setup_database.php) from your server for security reasons after initial setup is complete.</p>
            <p>All tables have been created with proper foreign key relationships and constraints.</p>
        </div>
    </div>
</body>
</html>

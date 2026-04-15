<?php
require_once 'functions.php';

// Fetch services from database
$services = [];
$servicesResult = $conn->query("SELECT * FROM services ORDER BY name");
if ($servicesResult && $servicesResult->num_rows > 0) {
    while ($service = $servicesResult->fetch_assoc()) {
        $services[] = $service;
    }
}

// Color mapping for service icons (fallback palette)
$iconColors = [
    '#EBF4FF', '#F4EBFF', '#EBFFEC', '#FFF9EB', '#FFEBF6'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
     <!-- ===================== HEADER ===================== -->
  <header>
    <nav class="nav-container">
      <div class="logo">
        <a href="index.php">Sharp<span>Cuts</span></a>
      </div>

      <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="services.php" class="active">Services</a></li>
        <li><a href="ourBarbers.php">Our Barbers</a></li>
        <li><a href="contact.php">Contact</a></li>
      </ul>

      
       <div class="nav-cta">
        <?php if (isLoggedIn()): ?>
          <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 13px; color: var(--text-muted);">
              <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>
            </span>
            <?php if (isAdmin()): ?>
              <a href="admin/dashboard.php" class="btn-login" style="text-decoration: none; color: #000;">Admin</a>
            <?php endif; ?>
            <button class="btn-signup" onclick="logoutUser()" style="cursor: pointer;">Logout</button>
          </div>
        <?php else: ?>
          <button class="btn-login" onclick="window.location.href='login.php'">Log in</button>
          <button class="btn-signup" onclick="window.location.href='register.php'">Sign up</button>
        <?php endif; ?>
      </div>

      <button class="hamburger" id="hamburger" aria-label="Toggle menu">
        <span></span><span></span><span></span>
      </button>
    </nav>

    <!-- Mobile nav -->
    <nav class="mobile-nav" id="mobileNav">
      <a href="index.php">Home</a>
      <a href="about.php">About</a>
      <a href="services.php" class="active">Services</a>
      <a href="contact.php">Contact</a>
      <div class="mobile-nav-cta">
        <?php if (isLoggedIn()): ?>
          <button onclick="logoutUser()" class="btn-signup">Logout</button>
        <?php else: ?>
          <button class="btn-login" onclick="window.location.href='login.php'">Log in</button>
          <button class="btn-signup" onclick="window.location.href='register.php'">Sign up</button>
        <?php endif; ?>
      </div>
    </nav>
  </header>

     <section id="Services">
    <div class="section-header reveal">
      <span class="badge">Our Services</span>
      <h2>Premium Grooming Services</h2>
      <p>From classic cuts to modern styles, we offer a full range of premium grooming services tailored to your unique needs.</p>
    </div>

    <div class="services-grid">
      <?php if (count($services) > 0): ?>
        <?php foreach ($services as $index => $service): ?>
          <div class="service-card reveal">
            <div class="service-icon" style="background:<?php echo htmlspecialchars($iconColors[$index % count($iconColors)]); ?>;">
              <img src="assets/star.svg" alt="icon">
            </div>
            <div class="service-content">
              <div class="service-title-row">
                <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                <span class="service-price">₱<?php echo number_format($service['price'], 0); ?></span>
              </div>
              <p class="service-desc"><?php echo htmlspecialchars($service['description'] ?? ''); ?></p>
              <div class="service-meta">
                <span><?php echo $service['duration_minutes'] ?? 0; ?> min</span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="grid-column: 1 / -1; text-align: center; color: #767E8A;">No services available at the moment.</p>
      <?php endif; ?>
    </div>

    <div class="services-cta reveal">
      <button class="btn btn-primary" onclick="<?php echo isLoggedIn() ? "window.location.href='book.php'" : "window.location.href='register.php'"; ?>">Book a Service</button>
    </div>
  </section>

  
  <!-- ===================== FOOTER ===================== -->
  <footer>
    <div class="footer-inner">
      <div class="footer-top">

        <div class="footer-brand">
          <div class="logo">Sharp<span>Cuts</span></div>
          <p>Experience the art of grooming at SharpCuts — where tradition meets modern style for the distinguished gentleman.</p>
        </div>

        <div class="footer-col">
          <h4>Navigation</h4>
          <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="services.php">Services</a></li>
            <li><a href="ourBarbers.php">Our Barbers</a></li>
            <li><a href="contact.php">Contact</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h4>Services</h4>
          <ul>
            <li><a href="services.php">Classic Haircut</a></li>
            <li><a href="services.php">Beard Grooming</a></li>
            <li><a href="services.php">Hair Coloring</a></li>
            <li><a href="services.php">Hot Towel Shave</a></li>
            <li><a href="services.php">Hair Treatment</a></li>
          </ul>
        </div>

        <div class="footer-contact">
          <h4>Contact</h4>
          <div class="contact-item">
            <div class="contact-icon">📍</div>
            <span class="contact-detail">Pitogo, Zamboanga del Sur</span>
          </div>
          <div class="contact-item">
            <div class="contact-icon">📞</div>
            <span class="contact-detail">+63 963 322 1778</span>
          </div>
          <div class="contact-item">
            <div class="contact-icon">✉️</div>
            <span class="contact-detail">ejayawan22@gmail.com</span>
          </div>
          <div class="contact-item">
            <div class="contact-icon">🕐</div>
            <span class="contact-detail">Mon–Sat: 9 AM – 7 PM</span>
          </div>
        </div>

      </div>

      <div class="footer-bottom">
        &copy; 2025 SharpCuts. All rights reserved.
      </div>
    </div>
  </footer>

  <script src="main.js"></script>
  <script>
    function logoutUser() {
      if (confirm('Are you sure you want to logout?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="logout">';
        document.body.appendChild(form);
        form.submit();
      }
    }
  </script>
</body>
</html>

<?php
require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact | SharpCuts</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
      <header>
    <nav class="nav-container">
      <div class="logo">
        <a href="index.php">Sharp<span>Cuts</span></a>
      </div>

      <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="services.php">Services</a></li>
        <li><a href="ourBarbers.php">Our Barbers</a></li>
        <li><a href="contact.php" class="active">Contact</a></li>
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
      <a href="services.php">Services</a>
      <a href="contact.php" class="active">Contact</a>
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


     <!-- Location section -->
   
     <section id="Location">
    <div class="location-inner">
      <div class="section-header reveal">
        <span class="badge">Location</span>
        <h2>Visit Our Shop</h2>
        <p>Conveniently located in downtown Pitogo. Drop by anytime or book an appointment online.</p>
      </div>

      <div class="location-cards">

        <div class="location-card reveal">
          <div class="loc-icon">📍</div>
          <div class="loc-text">
            <p class="loc-label">Our Location</p>
            <p class="loc-value">Pitogo, Zamboanga del Sur, Philippines</p>
          </div>
        </div>

        <div class="location-card reveal" style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
          <div style="display:flex; align-items:center; gap:12px;">
            <div class="loc-icon green">📞</div>
            <div class="loc-text">
              <p class="loc-label">Phone</p>
              <p class="loc-value">+63 963 322 1778</p>
            </div>
          </div>
          <div style="display:flex; align-items:center; gap:12px;">
            <div class="loc-icon blue">✉️</div>
            <div class="loc-text">
              <p class="loc-label">Email</p>
              <p class="loc-value">ejayawan22@gmail.com</p>
            </div>
          </div>
        </div>

        <div class="location-card reveal" style="flex-direction:column; align-items:stretch; gap:16px;">
          <div style="display:flex; align-items:center; gap:12px;">
            <div class="loc-icon orange">🕐</div>
            <div class="loc-text">
              <p class="loc-label">Business Hours</p>
              <p class="loc-value">Open 6 days a week</p>
            </div>
          </div>
          <div class="hours-grid">
            <div class="hours-row"><span class="day">Monday</span><span class="time">9:00 AM – 7:00 PM</span></div>
            <div class="hours-row"><span class="day">Tuesday</span><span class="time">9:00 AM – 7:00 PM</span></div>
            <div class="hours-row"><span class="day">Wednesday</span><span class="time">9:00 AM – 7:00 PM</span></div>
            <div class="hours-row"><span class="day">Thursday</span><span class="time">9:00 AM – 7:00 PM</span></div>
            <div class="hours-row"><span class="day">Friday</span><span class="time">9:00 AM – 7:00 PM</span></div>
            <div class="hours-row"><span class="day">Saturday</span><span class="time">9:00 AM – 5:00 PM</span></div>
            <div class="hours-row closed"><span class="day">Sunday</span><span class="time">Closed</span></div>
          </div>
        </div>

      </div>
    </div>
  </section>

     <!-- Footer -->
    
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

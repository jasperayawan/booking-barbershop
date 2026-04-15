<?php
require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About | SharpCuts</title>
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
        <li><a href="about.php" class="active">About</a></li>
        <li><a href="services.php">Services</a></li>
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
      <a href="about.php" class="active">About</a>
      <a href="services.php">Services</a>
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

      <!-- about section -->
    <section style="background:#fff; padding:80px 16px;">
        <div style="max-width:1280px; margin: auto; display: flex; justify-content: center; align-items: center; flex-direction: column;">
            <h1 style="font-size:56px; margin:0; font-weight:700;">About <span style="color:#8A38F5;">SharpCuts</span></h1>
            <p style="max-width:760px; margin-top:20px; line-height:1.7; color:#3D3D3D;">SharpCuts is where precision meets style. We're more than just a haircut — we're about confidence, individuality, and walking out looking your absolute best.</p>
            <p style="max-width:760px; margin-top:14px; line-height:1.7; color:#3D3D3D;">Our barbers combine classic techniques with modern trends to deliver sharp fades, clean lines, and styles that fit your lifestyle.</p>
            <p style="max-width:760px; margin-top:14px; line-height:1.7; color:#3D3D3D;">Whether you want a fresh professional look or something bold and expressive, we make sure every cut is done right the first time. At SharpCuts, we believe a great haircut starts with listening.</p>
            <p style="max-width:760px; margin-top:14px; line-height:1.7; color:#3D3D3D;">That's why every appointment begins with a consultation, ensuring your style matches your face shape, hair type, and personal vibe.</p>
        </div>

        <div style="max-width:1100px; margin:60px auto 0; display:grid; grid-template-columns:1fr 1fr; gap:40px; align-items:center;">
            <div style="display:flex; flex-direction:column; gap:20px;">
                <h2 style="font-size:42px; margin:0;">Our Mission</h2>
                <p style="line-height:1.7; color:#3D3D3D;">Our mission at SharpCuts is to deliver high-quality haircuts that combine precision, style, and consistency in every visit. We are committed to creating a welcoming and professional environment where clients feel comfortable, listened to, and confident in their choices.</p>
            </div>
            <div style="text-align:center;"><span style="font-size:48px; color:#000;">✂️</span></div>

            <div style="text-align:center;"><span style="font-size:48px; color:#000;">✂️</span></div>
            <div style="display:flex; flex-direction:column; gap:20px;">
                <h2 style="font-size:42px; margin:0;">Our Vision</h2>
                <p style="line-height:1.7; color:#3D3D3D;">Our vision is to become the go-to destination for sharp, reliable, and stylish haircuts — where every client feels confident, heard, and proud of their look. We aim to set the standard for modern grooming through skill, consistency, and exceptional service.</p>
            </div>
        </div>
    </section>

    <!-- Why Choose SharpCuts -->
    <section style="background:#fff; padding:80px 16px;">
        <div style="max-width:1100px; margin:0 auto;">
            <h2 style="font-size:44px; margin:0 0 24px;">Why Choose <span style="color:#8A38F5;">SharpCuts</span>?</h2>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:30px; align-items:center;">
                <div>
                    <img src="assets/why-choose.jpg" alt="barber at work" style="width:100%; height:auto; border-radius:20px; object-fit:cover; box-shadow:0 14px 30px rgba(0,0,0,.12);">
                </div>
                <div style="display:flex; flex-direction:column; gap:14px;">
                    <div style="background:#F4EBFF; border-radius:12px; padding:12px 14px; display:flex; gap:10px; align-items:center;"><span style="color:#8A38F5; font-weight:700;">✓</span><span>Expert Barbers – Skilled professionals with an eye for detail</span></div>
                    <div style="background:#F4EBFF; border-radius:12px; padding:12px 14px; display:flex; gap:10px; align-items:center;"><span style="color:#8A38F5; font-weight:700;">✓</span><span>Premium Services – Haircuts, beard grooming, coloring & more</span></div>
                    <div style="background:#F4EBFF; border-radius:12px; padding:12px 14px; display:flex; gap:10px; align-items:center;"><span style="color:#8A38F5; font-weight:700;">✓</span><span>Clean Facility – Hygienic and welcoming environment</span></div>
                    <div style="background:#F4EBFF; border-radius:12px; padding:12px 14px; display:flex; gap:10px; align-items:center;"><span style="color:#8A38F5; font-weight:700;">✓</span><span>Online Booking – Easy appointment scheduling</span></div>
                    <div style="background:#F4EBFF; border-radius:12px; padding:12px 14px; display:flex; gap:10px; align-items:center;"><span style="color:#8A38F5; font-weight:700;">✓</span><span>Affordable Pricing – Premium quality, fair prices</span></div>
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

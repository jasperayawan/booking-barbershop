<?php
require_once 'functions.php';

// Fetch barbers by user role only from users table
$barbers = [];
$barbersResult = $conn->query("
  SELECT
    u.id AS id,
    u.username,
    u.full_name AS full_name,
    u.full_name AS name,
    u.barber_title AS title,
    u.specialties AS specialties,
    COALESCE(u.rating, 0) AS rating,
    COALESCE(u.experience_years, 0) AS experience_years,
    COALESCE(NULLIF(TRIM(u.photo_url), ''), 'assets/default-avatar.png') AS photo_url
  FROM users u
  WHERE u.role = 'barber'
  ORDER BY COALESCE(NULLIF(TRIM(u.full_name), ''), u.username)
");
if ($barbersResult && $barbersResult->num_rows > 0) {
    while ($barber = $barbersResult->fetch_assoc()) {
        $barbers[] = $barber;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Barbers | SharpCuts</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=1.0.1">
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
        <li><a href="ourBarbers.php" class="active">Our Barbers</a></li>
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
      <a href="about.php" >About</a>
      <a href="ourBarbers.php" class="active">Our Barbers</a>
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

      <!-- our barbers section -->
    <section style="background:#fff; padding:80px 16px;">
        <div style="max-width:1280px; margin: auto; display: flex; justify-content: center; align-items: center; flex-direction: column;">
            <h1 style="font-size:56px; margin:0; font-weight:700;">Our <span style="color:#8A38F5;">Barbers</span></h1>
            <p style="max-width:760px; margin-top:20px; line-height:1.7; color:#3D3D3D;">At SharpCuts, our barbers are the heart of everything we do. Each member of our team is highly trained, experienced, and passionate about delivering sharp, confident styles tailored to every client.</p>
            <p style="max-width:760px; margin-top:20px; line-height:1.7; color:#3D3D3D;">We believe a great haircut starts with trust. That's why our barbers take the time to understand your preferences, hair type, and lifestyle before every cut. From classic styles to modern trends, our team is committed to precision, consistency, and quality in every service.</p>
        </div>
    </section>


    <section style="background: #fff; padding: 80px 16px;">
        <div style="max-width: 1100px; margin: 0 auto;">
            <div style="text-align: left; margin-bottom: 40px;">
                <span style="display: inline-block; background: #F4EBFF; color: #8A38F5; padding: 6px 14px; border-radius: 16px; font-size: 13px; font-weight: 600; margin-bottom: 14px;">Our team</span>
                <h2 style="font-size: 42px; margin: 12px 0;">Meet Our Expert Barbers</h2>
                <p style="font-size: 18px; color: #4C4C4C; max-width: 730px;">Our team of skilled barbers brings years of experience and passion to every cut.</p>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 20px;">
                <?php if (count($barbers) > 0): ?>
                    <?php foreach ($barbers as $barber): ?>
                        <article style="background:#fff; border-radius:20px; overflow:hidden; box-shadow:0 12px 30px rgba(0,0,0,.08);">
                            <div style="position:relative;">
                                <img src="<?php echo !empty($barber['photo_url']) ? htmlspecialchars($barber['photo_url']) : 'assets/default-avatar.png'; ?>" alt="<?php echo htmlspecialchars($barber['name']); ?>" style="width:100%; height:220px; object-fit:cover;">
                                <span style="position:absolute; top:12px; right:12px; background:#E0FFE7; color:#2F9C45; padding:5px 10px; border-radius:999px; font-size:12px;">Available</span>
                            </div>
                            <div style="padding:16px;">
                                <h3 style="margin:0; font-size:18px;"><?php echo htmlspecialchars($barber['name']); ?></h3>
                                <p style="margin:4px 0 10px; font-size:14px; color:#8A38F5;"><?php echo htmlspecialchars($barber['title']); ?></p>
                                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                    <?php 
                                    $specialties = explode(',', $barber['specialties'] ?? '');
                                    foreach (array_slice($specialties, 0, 2) as $specialty): 
                                    ?>
                                        <span style="background:#F4EBFF; color:#8A38F5; border-radius:10px; padding:4px 10px; font-size:12px;">
                                            <?php echo htmlspecialchars(trim($specialty)); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:14px; font-size:14px;">
                                    <span style="font-weight:700; color:#000;">
                                        <span style="color:#F1B439;">★</span> <?php echo number_format($barber['rating'] ?? 4.5, 1); ?>
                                    </span>
                                    <span style="color:#6B6B6B;"><?php echo $barber['years_experience'] ?? 0; ?> years experience</span>
                                </div>
                                <button class="btn btn-primary" style="width: 100%; margin-top: 14px; justify-content: center;" onclick="<?php echo isLoggedIn() ? "window.location.href='book.php'" : "window.location.href='login.php'"; ?>">Book Now</button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column: 1 / -1; text-align: center; color: #767E8A;">No barbers available at the moment.</p>
                <?php endif; ?>
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

  <script src="main.js?v=1.0.1"></script>
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

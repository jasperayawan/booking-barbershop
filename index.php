<?php
require_once 'functions.php';

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'logout') {
        logoutUser();
        echo json_encode(['success' => true, 'message' => 'Logged out successfully', 'redirect' => 'index.php']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="SharpCuts – Premium barbershop in Pitogo, Zamboanga del Sur. Expert barbers, classic cuts, beard grooming & more. Book your appointment today.">
  <title>SharpCuts | Premium Barbershop</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css?v=1.0.1">
</head>
<body>

  <!-- ===================== HEADER ===================== -->
  <header>
    <nav class="nav-container">
      <div class="logo">
        <a href="index.php">Sharp<span>Cuts</span></a>
      </div>

      <ul class="nav-links">
        <li><a href="index.php" class="active">Home</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="services.php">Services</a></li>
        <li><a href="ourBarbers.php">Our Barbers</a></li>
        <li><a href="contact.php">Contact</a></li>
        <?php if (isLoggedIn()): ?>
          <li><a href="book.php">Book Now</a></li>
        <?php endif; ?>
      </ul>

      <div class="nav-cta">
        <?php if (isLoggedIn()): ?>
          <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 13px; color: var(--text-muted);">
              Welcome, <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></strong>
            </span>
            <?php if (isAdmin()): ?>
              <a href="admin/dashboard.php" class="btn-login" style="text-decoration: none; color: #000;">Admin</a>
            <?php endif; ?>
            <button class="btn-signup" onclick="logoutUser()" style="cursor: pointer;">Logout</button>
          </div>
        <?php else: ?>
          <button class="btn-login" onclick="window.location.href='login.php'">
            Log in
          </button>
          <button class="btn-signup" onclick="window.location.href='register.php'">
            Sign up
          </button>
        <?php endif; ?>
      </div>

      <button class="hamburger" id="hamburger" aria-label="Toggle menu">
        <span></span><span></span><span></span>
      </button>
    </nav>

    <!-- Mobile nav -->
    <nav class="mobile-nav" id="mobileNav">
      <a href="index.php" class="active">Home</a>
      <a href="about.php">About</a>
      <a href="services.php">Services</a>
      <a href="contact.php">Contact</a>
      <div class="mobile-nav-cta">
        <?php if (isLoggedIn()): ?>
          <button onclick="logoutUser()" class="btn-signup">Logout</button>
          <?php if (isAdmin()): ?>
            <a href="admin/dashboard.php" class="btn-login">Admin</a>
          <?php endif; ?>
        <?php else: ?>
          <button class="btn-login" onclick="window.location.href='login.php'">Log in</button>
          <button class="btn-signup" onclick="window.location.href='register.php'">Sign up</button>
        <?php endif; ?>
      </div>
    </nav>
  </header>

  <!-- ===================== HERO ===================== -->
  <section id="Hero">
    <div class="hero-inner">

      <div class="hero-left reveal">
        <h1>Sharp Cuts, Sharper Style</h1>
        <p class="hero-desc">Experience premium grooming at its finest. Our expert barbers deliver precision cuts, clean fades, and classic styles that make you look and feel confident.</p>
        <div class="hero-cta">
          <button class="btn btn-primary" onclick="<?php echo isLoggedIn() ? "window.location.href='book.php'" : "window.location.href='register.php'"; ?>">Book Appointment</button>
          <button class="btn btn-outline">Learn More</button>
        </div>
        <div class="hero-stats">
          <div class="stat-item">
            <div class="stat-num">500+<img src="assets/star.svg" alt=""></div>
            <div class="stat-label">Happy Customers</div>
          </div>
          <div class="stat-item">
            <div class="stat-num">10+<img src="assets/star.svg" alt=""></div>
            <div class="stat-label">Expert Barbers</div>
          </div>
        </div>
      </div>

      <div class="hero-right reveal">
        <img src="assets/hero-img.png" alt="Barber cutting hair" class="hero-img">
        <div class="floating-card top-left">
          <div class="fc-icon">
            <img src="assets/star.svg" alt="">
          </div>
          <div class="fc-text">
            <h4>Perfect Cut</h4>
            <p>Premium results</p>
          </div>
        </div>
        <div class="floating-card bottom-right">
          <div class="fc-icon">
            <img src="assets/star.svg" alt="">
          </div>
          <div class="fc-text">
            <h4>Expert Team</h4>
            <p>Highly trained</p>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ===================== SERVICES ===================== -->
  <section id="Services">
    <div class="section-header reveal">
      <span class="badge">Our Services</span>
      <h2>Premium Grooming Services</h2>
      <p>From classic cuts to modern styles, we offer a full range of premium grooming services tailored to your unique needs.</p>
    </div>

    <div class="services-grid">

      <div class="service-card reveal">
        <div class="service-icon" style="background:#EBF4FF;">
          <img src="assets/star.svg" alt="icon" style="filter:invert(28%) sepia(95%) saturate(1200%) hue-rotate(210deg);">
        </div>
        <div class="service-content">
          <div class="service-title-row">
            <h3>Classic Haircut</h3>
            <span class="service-price">₱500</span>
          </div>
          <p class="service-desc">Precision cuts tailored to your style — includes consultation, shampoo, and finish styling.</p>
          <div class="service-meta">
            <span>30 min</span>
          </div>
        </div>
      </div>

      <div class="service-card reveal">
        <div class="service-icon" style="background:#F4EBFF;">
          <img src="assets/star.svg" alt="icon" style="filter:invert(28%) sepia(95%) saturate(800%) hue-rotate(255deg);">
        </div>
        <div class="service-content">
          <div class="service-title-row">
            <h3>Beard Grooming</h3>
            <span class="service-price">₱400</span>
          </div>
          <p class="service-desc">Expert beard shaping, trimming, and conditioning to keep your beard looking sharp and healthy.</p>
          <div class="service-meta">
            <span>25 min</span>
          </div>
        </div>
      </div>

      <div class="service-card reveal">
        <div class="service-icon" style="background:#EBFFEC;">
          <img src="assets/star.svg" alt="icon" style="filter:invert(50%) sepia(80%) saturate(500%) hue-rotate(90deg);">
        </div>
        <div class="service-content">
          <div class="service-title-row">
            <h3>Hair Coloring</h3>
            <span class="service-price">₱800</span>
          </div>
          <p class="service-desc">Full color, highlights, or balayage — vibrant, long-lasting results with premium products.</p>
          <div class="service-meta">
            <span>60 min</span>
          </div>
        </div>
      </div>

      <div class="service-card reveal">
        <div class="service-icon" style="background:#FFF9EB;">
          <img src="assets/star.svg" alt="icon" style="filter:invert(45%) sepia(90%) saturate(800%) hue-rotate(15deg);">
        </div>
        <div class="service-content">
          <div class="service-title-row">
            <h3>Hot Towel Shave</h3>
            <span class="service-price">₱600</span>
          </div>
          <p class="service-desc">Traditional straight-razor shave with hot towel treatment — the ultimate classic experience.</p>
          <div class="service-meta">
            <span>40 min</span>
          </div>
        </div>
      </div>

      <div class="service-card reveal">
        <div class="service-icon" style="background:#FFEBF6;">
          <img src="assets/star.svg" alt="icon" style="filter:invert(30%) sepia(90%) saturate(900%) hue-rotate(300deg);">
        </div>
        <div class="service-content">
          <div class="service-title-row">
            <h3>Kids Haircut</h3>
            <span class="service-price">₱350</span>
          </div>
          <p class="service-desc">Gentle, fun, and friendly haircuts for children — patient barbers who make kids feel at ease.</p>
          <div class="service-meta">
            <span>25 min</span>
          </div>
        </div>
      </div>

      <div class="service-card reveal">
        <div class="service-icon" style="background:#F4EBFF;">
          <img src="assets/star.svg" alt="icon" style="filter:invert(28%) sepia(95%) saturate(800%) hue-rotate(255deg);">
        </div>
        <div class="service-content">
          <div class="service-title-row">
            <h3>Hair Treatment</h3>
            <span class="service-price">₱700</span>
          </div>
          <p class="service-desc">Deep conditioning treatments to restore shine, repair damage, and promote healthy hair growth.</p>
          <div class="service-meta">
            <span>45 min</span>
          </div>
        </div>
      </div>

    </div>

    <div class="services-cta reveal">
      <button class="btn btn-primary" onclick="<?php echo isLoggedIn() ? "window.location.href='services.php'" : "window.location.href='register.php'"; ?>">
        Book a Service
      </button>
    </div>
  </section>

  <!-- ===================== TEAM ===================== -->
  <section id="Team">
    <div class="section-header reveal">
      <span class="badge">Our Team</span>
      <h2>Meet Our Expert Barbers</h2>
      <p>Our team of skilled barbers brings years of experience and passion to every single cut.</p>
    </div>

    <div class="team-grid">

      <article class="barber-card reveal">
        <div class="barber-img-wrap">
          <img src="assets/person-testimony1.png" alt="Marcus Johnson">
          <span class="barber-availability available">Available</span>
        </div>
        <div class="barber-info">
          <h3>Marcus Johnson</h3>
          <p class="barber-title">The Fade King</p>
          <div class="barber-tags">
            <span class="barber-tag">Skin Fades</span>
            <span class="barber-tag">Drop Fades</span>
          </div>
          <div class="barber-footer">
            <div>
              <span class="barber-rating"><span class="star">★</span> 4.9</span>
              <span class="barber-exp">8 years experience</span>
            </div>
          </div>
          <button class="barber-book-btn" onclick="<?php echo isLoggedIn() ? "window.location.href='book.php'" : "window.location.href='login.php'"; ?>">Book Now</button>
        </div>
      </article>

      <article class="barber-card reveal">
        <div class="barber-img-wrap">
          <img src="assets/person-testimony1.png" alt="David Smith">
          <span class="barber-availability available">Available</span>
        </div>
        <div class="barber-info">
          <h3>David Smith</h3>
          <p class="barber-title">Beard Specialist</p>
          <div class="barber-tags">
            <span class="barber-tag">Beard Grooming</span>
            <span class="barber-tag">Shaping</span>
          </div>
          <div class="barber-footer">
            <div>
              <span class="barber-rating"><span class="star">★</span> 4.8</span>
              <span class="barber-exp">6 years experience</span>
            </div>
          </div>
          <button class="barber-book-btn" onclick="<?php echo isLoggedIn() ? "window.location.href='book.php'" : "window.location.href='login.php'"; ?>">Book Now</button>
        </div>
      </article>

      <article class="barber-card reveal">
        <div class="barber-img-wrap">
          <img src="assets/person-testimony1.png" alt="Alex Rodriguez">
          <span class="barber-availability available">Available</span>
        </div>
        <div class="barber-info">
          <h3>Alex Rodriguez</h3>
          <p class="barber-title">Color Master</p>
          <div class="barber-tags">
            <span class="barber-tag">Hair Coloring</span>
            <span class="barber-tag">Highlights</span>
          </div>
          <div class="barber-footer">
            <div>
              <span class="barber-rating"><span class="star">★</span> 4.7</span>
              <span class="barber-exp">5 years experience</span>
            </div>
          </div>
          <button class="barber-book-btn" onclick="<?php echo isLoggedIn() ? "window.location.href='book.php'" : "window.location.href='login.php'"; ?>">Book Now</button>
        </div>
      </article>

      <article class="barber-card reveal">
        <div class="barber-img-wrap">
          <img src="assets/person-testimony1.png" alt="James Brown">
          <span class="barber-availability available">Available</span>
        </div>
        <div class="barber-info">
          <h3>James Brown</h3>
          <p class="barber-title">Classic Master</p>
          <div class="barber-tags">
            <span class="barber-tag">Classic Cuts</span>
            <span class="barber-tag">Hot Shave</span>
          </div>
          <div class="barber-footer">
            <div>
              <span class="barber-rating"><span class="star">★</span> 4.9</span>
              <span class="barber-exp">10 years experience</span>
            </div>
          </div>
          <button class="barber-book-btn" onclick="<?php echo isLoggedIn() ? "window.location.href='contact.php'" : "window.location.href='login.php'"; ?>">Book Now</button>
        </div>
      </article>

    </div>
  </section>

  <!-- ===================== PRICING ===================== -->
  <section id="Pricing" style="background: var(--primary-bg);">
    <div class="section-header reveal">
      <span class="badge">Pricing</span>
      <h2>Our Pricing Plans</h2>
      <p>Transparent pricing with no hidden fees — choose the service that fits your style and budget.</p>
    </div>

    <div class="pricing-grid">

      <div class="pricing-card reveal">
        <div class="pricing-img-wrap">
          <img src="assets/pricing-1.png" alt="Classic Haircut">
          <span class="pricing-tag available">Available</span>
        </div>
        <h3>Classic Haircut</h3>
        <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px;">Perfect for everyday style</p>
        <div style="display: flex; align-items: baseline; gap: 4px; margin-bottom: 16px;">
          <span style="font-size: 32px; font-weight: 800; color: var(--primary);">₱500</span>
          <span style="font-size: 12px; color: var(--text-muted);">30 min</span>
        </div>
        <button class="btn btn-primary" style="width: 100%; justify-content: center;" onclick="<?php echo isLoggedIn() ? "window.location.href='services.php'" : "window.location.href='register.php'"; ?>">Get Started</button>
      </div>

      <div class="pricing-card reveal">
        <div class="pricing-img-wrap">
          <img src="assets/pricing-1.png" alt="Beard Grooming">
          <span class="pricing-tag new">New</span>
        </div>
        <h3>Beard Grooming</h3>
        <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px;">Keep your beard sharp</p>
        <div style="display: flex; align-items: baseline; gap: 4px; margin-bottom: 16px;">
          <span style="font-size: 32px; font-weight: 800; color: var(--primary);">₱400</span>
          <span style="font-size: 12px; color: var(--text-muted);">25 min</span>
        </div>
        <button class="btn btn-primary" style="width: 100%; justify-content: center;" onclick="<?php echo isLoggedIn() ? "window.location.href='services.php'" : "window.location.href='register.php'"; ?>">Get Started</button>
      </div>

      <div class="pricing-card reveal">
        <div class="pricing-img-wrap">
          <img src="assets/pricing-1.png" alt="Hair Coloring">
          <span class="pricing-tag bestseller">Bestseller</span>
        </div>
        <h3>Hair Coloring</h3>
        <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px;">Premium color service</p>
        <div style="display: flex; align-items: baseline; gap: 4px; margin-bottom: 16px;">
          <span style="font-size: 32px; font-weight: 800; color: var(--primary);">₱800</span>
          <span style="font-size: 12px; color: var(--text-muted);">60 min</span>
        </div>
        <button class="btn btn-primary" style="width: 100%; justify-content: center;" onclick="<?php echo isLoggedIn() ? "window.location.href='services.php'" : "window.location.href='register.php'"; ?>">Get Started</button>
      </div>

      <div class="pricing-card reveal">
        <div class="pricing-img-wrap">
          <img src="assets/pricing-1.png" alt="Hot Towel Shave">
          <span class="pricing-tag available">Available</span>
        </div>
        <h3>Hot Towel Shave</h3>
        <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px;">Classic luxury experience</p>
        <div style="display: flex; align-items: baseline; gap: 4px; margin-bottom: 16px;">
          <span style="font-size: 32px; font-weight: 800; color: var(--primary);">₱600</span>
          <span style="font-size: 12px; color: var(--text-muted);">40 min</span>
        </div>
        <button class="btn btn-primary" style="width: 100%; justify-content: center;" onclick="<?php echo isLoggedIn() ? "window.location.href='services.php'" : "window.location.href='register.php'"; ?>">Get Started</button>
      </div>

    </div>
  </section>

  <!-- ===================== CTA ===================== -->
  <section id="CTA">
    <div class="cta-inner reveal">
      <span class="cta-badge">✂ Limited Time — 20% Off Your First Visit</span>
      <h2>Ready to Look Your Best?</h2>
      <p class="cta-desc">Book your appointment and experience the SharpCuts difference. Your perfect look is just one click away.</p>
      <div class="cta-buttons">
        <button class="btn btn-primary" onclick="<?php echo isLoggedIn() ? "window.location.href='book.php'" : "window.location.href='register.php'"; ?>">Book Now</button>
        <button class="btn btn-outline-white" onclick="document.getElementById('FAQ').scrollIntoView({behavior: 'smooth'});">Learn More</button>
      </div>
      <div class="cta-stats">
        <div style="text-align: center;">
          <div style="font-size: 32px; font-weight: 800; color: var(--white);">500+</div>
          <div style="font-size: 13px; color: rgba(255,255,255,0.8);">Happy Customers</div>
        </div>
        <div style="text-align: center;">
          <div style="font-size: 32px; font-weight: 800; color: var(--white);">4.9★</div>
          <div style="font-size: 13px; color: rgba(255,255,255,0.8);">Avg Rating</div>
        </div>
        <div style="text-align: center;">
          <div style="font-size: 32px; font-weight: 800; color: var(--white);">10+</div>
          <div style="font-size: 13px; color: rgba(255,255,255,0.8);">Expert Barbers</div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== FAQ ===================== -->
  <section id="FAQ">
    <div class="section-header reveal">
      <span class="badge">FAQ</span>
      <h2>Frequently Asked Questions</h2>
      <p>Everything you need to know before your visit. Can't find an answer? Contact us directly.</p>
    </div>

    <div class="faq-list">

      <div class="faq-item reveal">
        <button class="faq-question">
          <span>How do I book an appointment?</span>
          <span class="faq-toggle">+</span>
        </button>
        <div class="faq-answer" style="display: none;">
          <p>Simply click "Book Now" on our website, select your preferred barber and service, pick a date and time that works for you, and we'll send you a confirmation. It's quick and easy!</p>
        </div>
      </div>

      <div class="faq-item reveal">
        <button class="faq-question">
          <span>Can I walk in without an appointment?</span>
          <span class="faq-toggle">+</span>
        </button>
        <div class="faq-answer" style="display: none;">
          <p>Walk-ins are welcome! However, we recommend booking ahead to avoid long wait times and guarantee your preferred barber is available.</p>
        </div>
      </div>

      <div class="faq-item open reveal">
        <button class="faq-question">
          <span>What's your cancellation policy?</span>
          <span class="faq-toggle">−</span>
        </button>
        <div class="faq-answer" style="display: block;">
          <p>We understand plans change. You can cancel or reschedule up to 24 hours before your appointment. Cancellations within 24 hours may incur a charge.</p>
        </div>
      </div>

      <div class="faq-item reveal">
        <button class="faq-question">
          <span>Do you offer discounts for first-time customers?</span>
          <span class="faq-toggle">+</span>
        </button>
        <div class="faq-answer" style="display: none;">
          <p>Yes! First-time customers enjoy 20% off their first appointment. Sign up, book your first service, and the discount will be applied automatically.</p>
        </div>
      </div>

      <div class="faq-item reveal">
        <button class="faq-question">
          <span>Which barber is best for my hair type?</span>
          <span class="faq-toggle">+</span>
        </button>
        <div class="faq-answer" style="display: none;">
          <p>Check out our "Our Barbers" page to learn about each team member's specialties. During booking, you can select by skill. You can also call us for personalized recommendations!</p>
        </div>
      </div>

    </div>
  </section>

  <!-- ===================== LOCATION ===================== -->
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

  <!-- ===================== SCRIPTS ===================== -->

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

<?php
require_once 'functions.php';

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate all fields
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } else {
        $result = registerUser($username, $email, $password, '');
        
        if ($result['success']) {
            $success = $result['message'];
            // Redirect to login after 2 seconds
            header('refresh:2;url=login.php');
        } else {
            $error = $result['message'];
        }
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Create your SharpCuts account and start booking premium grooming appointments.">
  <title>Register | SharpCuts</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; }

    body {
      margin: 0;
      min-height: 100vh;
      font-family: 'DM Sans', Arial, sans-serif;
      background: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 16px;
    }

    .auth-card {
      width: 100%;
      max-width: 480px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0;
    }

    .auth-logo {
      font-size: 26px;
      font-weight: 800;
      color: #111827;
      text-decoration: none;
      margin-bottom: 32px;
      letter-spacing: -0.3px;
    }
    .auth-logo span { color: #8A38F5; }

    .auth-title {
      font-size: 28px;
      font-weight: 800;
      color: #111827;
      text-align: center;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin: 0 0 10px;
    }

    .auth-subtitle {
      font-size: 12px;
      font-weight: 500;
      color: #9ca3af;
      text-align: center;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      line-height: 1.6;
      margin: 0 0 32px;
      max-width: 320px;
    }

    .auth-form {
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 7px;
    }

    .form-group label {
      font-size: 14px;
      font-weight: 600;
      color: #111827;
    }

    .form-group input {
      width: 100%;
      padding: 14px 16px;
      background: #f5f5f5;
      border: 1.5px solid transparent;
      border-radius: 10px;
      font-size: 15px;
      font-family: 'DM Sans', Arial, sans-serif;
      color: #111827;
      outline: none;
      transition: border-color 0.2s ease, background 0.2s ease;
    }

    .form-group input::placeholder {
      color: #b0b8c1;
      font-size: 14px;
    }

    .form-group input:focus {
      border-color: #8A38F5;
      background: #ffffff;
      box-shadow: 0 0 0 4px rgba(138,56,245,0.08);
    }

    .input-wrap {
      position: relative;
    }

    .input-wrap input {
      padding-right: 48px;
    }

    .toggle-pw {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      padding: 0;
      color: #9ca3af;
      font-size: 18px;
      line-height: 1;
      display: flex;
      align-items: center;
    }
    .toggle-pw:hover { color: #8A38F5; }

    .btn-auth {
      width: 100%;
      padding: 15px;
      background: #8A38F5;
      color: #ffffff;
      border: none;
      border-radius: 10px;
      font-size: 16px;
      font-weight: 700;
      font-family: 'DM Sans', Arial, sans-serif;
      cursor: pointer;
      margin-top: 4px;
      transition: background 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
      letter-spacing: 0.2px;
    }
    .btn-auth:hover {
      background: #7D3BED;
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(138,56,245,0.35);
    }
    .btn-auth:active { transform: translateY(0); }

    .auth-footer {
      margin-top: 22px;
      font-size: 13px;
      color: #9ca3af;
      text-align: center;
    }
    .auth-footer a {
      color: #8A38F5;
      font-weight: 600;
      text-decoration: none;
    }
    .auth-footer a:hover { text-decoration: underline; }

    .form-group.error input {
      border-color: #ef4444;
      background: #fff5f5;
    }
    .error-msg {
      font-size: 12px;
      color: #ef4444;
      display: none;
    }
    .form-group.error .error-msg { display: block; }

    .auth-error {
      width: 100%;
      background: #fff5f5;
      border: 1px solid #fecaca;
      border-radius: 10px;
      padding: 12px 16px;
      font-size: 13px;
      color: #ef4444;
      text-align: center;
      display: none;
      margin-bottom: 4px;
    }
    .auth-error.show { display: block; }

    .auth-success {
      width: 100%;
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      border-radius: 10px;
      padding: 12px 16px;
      font-size: 13px;
      color: #15803d;
      text-align: center;
      display: none;
      margin-bottom: 4px;
    }
    .auth-success.show { display: block; }
  </style>
</head>
<body>

  <div class="auth-card">

    <a href="index.php" class="auth-logo">Sharp<span>Cuts</span></a>

    <h1 class="auth-title">Create Your Account</h1>
    <p class="auth-subtitle">Get started by setting up your profile in minutes</p>

    <?php if ($error): ?>
      <div class="auth-error show"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="auth-success show"><?php echo htmlspecialchars($success); ?> Redirecting to login...</div>
    <?php endif; ?>

    <form class="auth-form" method="POST" id="registerForm" novalidate>

      <div class="form-group" id="group-username">
        <label for="username">Username</label>
        <input
          type="text"
          id="username"
          name="username"
          placeholder="Choose a username"
          required
          autocomplete="username"
        >
        <span class="error-msg">Please enter a valid username (min. 3 characters).</span>
      </div>

      <div class="form-group" id="group-email">
        <label for="email">Email</label>
        <input
          type="email"
          id="email"
          name="email"
          placeholder="you@example.com"
          required
          autocomplete="email"
        >
        <span class="error-msg">Please enter a valid email address.</span>
      </div>

      <div class="form-group" id="group-password">
        <label for="password">Password</label>
        <div class="input-wrap">
          <input
            type="password"
            id="password"
            name="password"
            placeholder="••••••••"
            required
            autocomplete="new-password"
          >
          <button type="button" class="toggle-pw" id="togglePassword">👁</button>
        </div>
        <span class="error-msg">Please enter a password (min. 6 characters).</span>
      </div>

      <button type="submit" class="btn-auth">Sign up</button>

    </form>

    <p class="auth-footer">
      Already have an account? <a href="login.php">Log In</a>
    </p>

  </div>

  <script>
    // Password visibility toggle
    document.getElementById('togglePassword').addEventListener('click', () => {
      const input = document.getElementById('password');
      const btn = document.getElementById('togglePassword');
      const isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      btn.textContent = isHidden ? '🙈' : '👁';
    });

    // Form validation on submit
    document.getElementById('registerForm').addEventListener('submit', (e) => {
      const username = document.getElementById('username');
      const email = document.getElementById('email');
      const password = document.getElementById('password');
      
      let valid = true;

      const uGroup = document.getElementById('group-username');
      if (username.value.trim().length < 3) {
        uGroup.classList.add('error');
        valid = false;
      } else {
        uGroup.classList.remove('error');
      }

      const eGroup = document.getElementById('group-email');
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email.value.trim())) {
        eGroup.classList.add('error');
        valid = false;
      } else {
        eGroup.classList.remove('error');
      }

      const pGroup = document.getElementById('group-password');
      if (password.value.length < 6) {
        pGroup.classList.add('error');
        valid = false;
      } else {
        pGroup.classList.remove('error');
      }

      if (!valid) {
        e.preventDefault();
      }
    });

    // Clear error on input
    ['username','email','password'].forEach(id => {
      document.getElementById(id).addEventListener('input', () => {
        document.getElementById('group-' + id).classList.remove('error');
      });
    });
  </script>

</body>
</html>

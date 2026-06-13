<?php
session_start();
require_once 'api/config.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $errors[] = "Username and password are required.";
        } else {
            $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: admin.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                $errors[] = "Invalid username/email or password.";
            }
        }
    }

    elseif ($action === 'register') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
            $errors[] = "All fields are required.";
        } elseif ($password !== $confirm) {
            $errors[] = "Passwords do not match.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $errors[] = "Username or email already taken.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'customer')");
                if ($stmt->execute([$username, $email, $hash])) {
                    $success = "Registration successful! You can now log in.";
                } else {
                    $errors[] = "Registration failed. Try again.";
                }
            }
        }
    }

    // Forgot Password - ONLY for customers
    elseif ($action === 'forgot') {
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required.";
        } else {
            $stmt = $pdo->prepare("SELECT id, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if ($user['role'] === 'admin') {
                    $errors[] = "Admin accounts cannot reset password through this form. Contact support.";
                } else {
                    // Only customers can reset
                    $token = bin2hex(random_bytes(32));
                    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                    $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?");
                    if ($stmt->execute([$token, $expiry, $email])) {
                        $reset_link = "http://localhost/nepalidelights/reset_password.php?token=$token";

                        $success = "Password reset link sent to your email.<br><br>";
                        $success .= "<strong> (click instantly):</strong><br>";
                        $success .= "<a href='$reset_link' style='color:#ed8936; font-weight:bold; word-break:break-all;'>reset link</a>";
                        $success .= "<br><br>(Token expires in 1 hour)";
                    } else {
                        $errors[] = "Failed to generate reset link. Try again.";
                    }
                }
            } else {
                $errors[] = "No account found with that email.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login / Register - Nepali Delights</title>
  <link rel="stylesheet" href="style.css">

  <style>
    :root {
      --bg:        #1a202c;
      --card-bg:   #2d3748;
      --text:      #e2e8f0;
      --accent:    #ed8936;
      --accent-h:  #f6ad55;
      --error:     #e53e3e;
      --border:    #4a5568;
    }

    * { margin:0; padding:0; box-sizing:border-box; }

    body {
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
      background-image: 
        linear-gradient(rgba(26,32,44,0.92), rgba(26,32,44,0.92)),
        url('https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&q=80&w=2000');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
    }

    .login-wrapper { max-width: 420px; width: 100%; }
    .login-card {
      background: var(--card-bg);
      border-radius: 16px;
      padding: 2.5rem 2rem;
      box-shadow: 0 20px 50px rgba(0,0,0,0.6);
      border: 1px solid rgba(237,137,54,0.12);
      backdrop-filter: blur(6px);
    }

    .logo-area { text-align: center; margin-bottom: 1.8rem; }
    .logo { font-size: 2.4rem; font-weight: 800; color: var(--accent); letter-spacing: -1px; }
    .tagline { font-size: 0.95rem; color: #a0aec0; opacity: 0.9; }

    .tabs { display: flex; margin-bottom: 1.8rem; border-bottom: 1px solid var(--border); }
    .tab {
      flex: 1;
      padding: 12px;
      text-align: center;
      font-weight: 600;
      cursor: pointer;
      color: #a0aec0;
      transition: all 0.2s;
    }
    .tab.active {
      color: var(--accent);
      border-bottom: 3px solid var(--accent);
    }

    h1 { color: var(--accent); text-align: center; margin-bottom: 1.5rem; font-size: 1.8rem; }

    .form-group { margin-bottom: 1.4rem; }
    label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #cbd5e0; font-size: 0.95rem; }
    input {
      width: 100%;
      padding: 0.85rem 1rem;
      border: 1px solid var(--border);
      border-radius: 8px;
      background: #1e2533;
      color: white;
      font-size: 1rem;
      transition: all 0.2s;
    }
    input:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(237,137,54,0.18);
    }

    .error-message { color: var(--error); font-size: 0.84rem; margin-top: 0.4rem; display: none; }

    .btn-submit {
      width: 100%;
      padding: 1rem;
      background: var(--accent);
      color: #1a202c;
      border: none;
      border-radius: 8px;
      font-size: 1.05rem;
      font-weight: 700;
      cursor: pointer;
      margin-top: 1.2rem;
    }
    .btn-submit:hover { background: var(--accent-h); }

    .toggle-link { text-align: center; margin-top: 1.5rem; color: #a0aec0; }
    .toggle-link a { color: var(--accent); text-decoration: none; font-weight: 500; }
    .toggle-link a:hover { text-decoration: underline; }

    .back-to-site { text-align: center; margin-top: 2rem; font-size: 0.95rem; }
    .back-to-site a { color: #a0aec0; text-decoration: none; }
    .back-to-site a:hover { color: var(--accent); }

    .back {
      text-align: center;
      margin-top: 2rem;
      font-size: 0.95rem;
      color: #a0aec0;
    }
    .back a {
      color: var(--accent);
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s;
    }
    .back a:hover {
      color: var(--accent-h);
      text-decoration: underline;
    }
  </style>
</head>
<body>

<div class="login-wrapper">
  <div class="login-card">

    <div class="logo-area">
      <div class="logo">Nepali Delights</div>
      <div class="tagline">Authentic Himalayan Flavors</div>
    </div>

    <div class="tabs">
      <div class="tab active" data-tab="login">Login</div>
      <div class="tab" data-tab="register">Register</div>
      <div class="tab" data-tab="forgot">Forgot Password</div>
    </div>

    <?php if ($success): ?>
      <div class="success" style="color:#48bb78; text-align:center; margin:1rem 0; font-weight:bold;">
        <?= $success ?>
      </div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div style="color:#e53e3e; text-align:center; margin-bottom:1rem;">
        <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
      </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form id="loginForm" method="POST" style="display:block;">
      <input type="hidden" name="action" value="login">
      <h1>Login</h1>
      <div class="form-group">
        <label>Username / Email</label>
        <input type="text" name="username" required placeholder="admin / your@email.com">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn-submit">Sign In</button>
    </form>

    <!-- Register Form -->
    <form id="registerForm" method="POST" style="display:none;">
      <input type="hidden" name="action" value="register">
      <h1>Register</h1>
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" required placeholder="Choose username">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required placeholder="your@email.com">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required placeholder="••••••••">
      </div>
      <div class="form-group">
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn-submit">Create Account</button>
    </form>

    <!-- Forgot Password Form -->
    <form id="forgotForm" method="POST" style="display:none;">
      <input type="hidden" name="action" value="forgot">
      <h1>Forgot Password</h1>
      <p style="text-align:center; color:#a0aec0; margin-bottom:1.5rem;">
        Enter your email to receive a password reset link.
      </p>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required placeholder="your@email.com">
      </div>
      <button type="submit" class="btn-submit">Send Reset Link</button>
    </form>

    <div class="toggle-link">
      <span id="toggleText">Don't have an account? <a href="#" id="toggleLink">Register here</a></span>
    </div>

    <div class="back">
      <a href="index.php">← Back to Home</a>
    </div>

  </div>
</div>

<script>
  document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');

      document.getElementById('loginForm').style.display    = tab.dataset.tab === 'login' ? 'block' : 'none';
      document.getElementById('registerForm').style.display = tab.dataset.tab === 'register' ? 'block' : 'none';
      document.getElementById('forgotForm').style.display   = tab.dataset.tab === 'forgot' ? 'block' : 'none';

      document.getElementById('toggleText').innerHTML = 
        tab.dataset.tab === 'login' 
          ? 'Don\'t have an account? <a href="#" id="toggleLink">Register here</a>'
          : tab.dataset.tab === 'register'
            ? 'Already have an account? <a href="#" id="toggleLink">Login here</a>'
            : 'Back to Login? <a href="#" id="toggleLink">Login here</a>';
    });
  });

  document.addEventListener('click', e => {
    if (e.target.id === 'toggleLink') {
      e.preventDefault();
      const nextTab = document.querySelector('.tab:not(.active)');
      if (nextTab) nextTab.click();
    }
  });
</script>

</body>
</html>
<?php
// Very first line — start session here
session_start();

// Include config (no session in config anymore)
require_once 'api/config.php';

// Rest of your PHP logic for login/register...
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
            // Check if username or email exists
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
    /* Your existing login styles – I kept them almost identical */
    :root {
      --bg:        #1a202c;
      --card-bg:   #2d3748;
      --text:      #e2e8f0;
      --accent:    #ed8936;
      --accent-h:  #f6ad55;
      --error:     #e53e3e;
      --border:    #4a5568;
    }

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
      background-attachment: fixed;
    }

    .login-wrapper { max-width: 420px; width: 100%; }
    .login-card {
      background: var(--card-bg);
      border-radius: 16px;
      padding: 2.5rem 2rem;
      box-shadow: 0 20px 50px rgba(0,0,0,0.6);
      border: 1px solid rgba(237,137,54,0.12);
    }

    .logo { font-size: 2.4rem; font-weight: 800; color: var(--accent); text-align: center; }
    .tagline { text-align: center; color: #a0aec0; margin-bottom: 1.5rem; }

    .tabs { display: flex; margin-bottom: 1.5rem; }
    .tab {
      flex: 1;
      padding: 12px;
      text-align: center;
      cursor: pointer;
      font-weight: 600;
      color: #a0aec0;
      border-bottom: 3px solid transparent;
    }
    .tab.active {
      color: var(--accent);
      border-bottom-color: var(--accent);
    }

    h1 { color: var(--accent); text-align: center; margin-bottom: 1.5rem; }

    .form-group { margin-bottom: 1.4rem; }
    label { display: block; margin-bottom: 0.5rem; color: #cbd5e0; }
    input {
      width: 100%;
      padding: 0.9rem;
      border: 1px solid var(--border);
      border-radius: 8px;
      background: #1e2533;
      color: white;
    }
    input:focus {
      border-color: var(--accent);
      outline: none;
      box-shadow: 0 0 0 3px rgba(237,137,54,0.2);
    }

    .error { color: var(--error); font-size: 0.9rem; margin-top: 0.3rem; display: none; }

    .btn-submit {
      width: 100%;
      padding: 1rem;
      background: var(--accent);
      color: #1a202c;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
    }
    .btn-submit:hover { background: var(--accent-h); }

    .toggle-link { text-align: center; margin-top: 1.5rem; }
    .toggle-link a { color: var(--accent); text-decoration: none; }

    .back { text-align: center; margin-top: 2rem; }
    .back a { color: #a0aec0; }
    .back a:hover { color: var(--accent); }

    .success { color: #48bb78; text-align: center; margin: 1rem 0; font-weight: bold; }
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
    </div>

    <?php if (!empty($success)): ?>
      <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
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

    <div class="toggle-link">
      <span id="toggleText">Don't have an account? <a href="#" id="toggleLink">Register here</a></span>
    </div>

    <div class="back">
      <a href="index.php">← Back to Home</a>
    </div>

  </div>
</div>

<script>
  // Simple tab toggle (no PHP reload needed for UI switch)
  document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');

      document.getElementById('loginForm').style.display = tab.dataset.tab === 'login' ? 'block' : 'none';
      document.getElementById('registerForm').style.display = tab.dataset.tab === 'register' ? 'block' : 'none';

      document.getElementById('toggleText').innerHTML = 
        tab.dataset.tab === 'login' 
          ? 'Don\'t have an account? <a href="#" id="toggleLink">Register here</a>'
          : 'Already have an account? <a href="#" id="toggleLink">Login here</a>';
    });
  });

  // Toggle link click
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
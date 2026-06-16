<?php
session_start();
require_once 'api/config.php';

$errors = [];
$success = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $errors[] = "Invalid or missing reset token.";
} else {
    // Debug: show what we're searching for
    $stmt = $pdo->prepare("SELECT id, username, reset_token, reset_expiry FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $errors[] = "No user found with this token.";
    } elseif (!$user['reset_expiry'] || strtotime($user['reset_expiry']) < time()) {
        $errors[] = "Token expired or expiry not set. Expiry in DB: " . ($user['reset_expiry'] ?? 'NULL');
    } else {
        // Token valid
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['confirm_password'] ?? '';

            if (empty($password) || strlen($password) < 6) {
                $errors[] = "Password must be at least 6 characters.";
            } elseif ($password !== $confirm) {
                $errors[] = "Passwords do not match.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
                if ($stmt->execute([$hash, $user['id']])) {
                    $success = "Password reset successful! You can now log in.";
                } else {
                    $errors[] = "Failed to update password.";
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
  <title>Reset Password - Nepali Delights</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { background:#1a202c; color:#e2e8f0; padding:40px; font-family:Arial,sans-serif; }
    .container { max-width:500px; margin:0 auto; background:#2d3748; padding:40px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.5); }
    h1 { color:#ed8936; text-align:center; }
    .success { color:#48bb78; font-weight:bold; text-align:center; margin:20px 0; }
    .error { color:#e53e3e; text-align:center; margin:20px 0; }
    form { margin-top:20px; }
    label { display:block; margin:10px 0 5px; }
    input { width:100%; padding:10px; border-radius:6px; border:1px solid #4a5568; background:#1e2533; color:white; }
    button { width:100%; padding:12px; background:#ed8936; color:#1a202c; border:none; border-radius:8px; font-weight:bold; margin-top:15px; cursor:pointer; }
    button:hover { background:#f6ad55; }
    .back { text-align:center; margin-top:30px; }
    .back a { color:#ed8936; text-decoration:none; }
  </style>
</head>
<body>

<div class="container">
  <h1>Reset Password</h1>

  <?php if ($success): ?>
    <div class="success"><?= htmlspecialchars($success) ?></div>
    <div class="back"><a href="login.php">→ Go to Login</a></div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="error">
      <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
    </div>
  <?php endif; ?>

  <?php if (!$success && !$errors && $user): ?>
    <p style="text-align:center;">Hello <?= htmlspecialchars($user['username']) ?>,</p>
    <p style="text-align:center;">Token valid until: <?= htmlspecialchars($user['reset_expiry']) ?></p>
    <form method="POST">
      <label>New Password</label>
      <input type="password" name="password" required minlength="6">

      <label>Confirm Password</label>
      <input type="password" name="confirm_password" required minlength="6">

      <button type="submit">Reset Password</button>
    </form>
  <?php endif; ?>

  <div class="back">
    <a href="login.php">← Back to Login</a>
  </div>
</div>

</body>
</html>
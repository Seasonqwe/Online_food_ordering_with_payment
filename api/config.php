<?php
// api/config.php

$host = 'localhost';
$dbname = 'nepalidelights';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

// ────────────────────────────────────────────────
// eSewa Sandbox/Test Configuration (added for payment system)
// These are public test values - no merchant registration needed
// ────────────────────────────────────────────────
define('ESEWA_MODE', 'test');
define('ESEWA_PRODUCT_CODE', 'EPAYTEST');
define('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q');

// Use the most reliable sandbox URL in 2026
define('ESEWA_BASE_URL', 'https://rc-epay.esewa.com.np');  // preferred
// Alternative: define('ESEWA_BASE_URL', 'https://rc.esewa.com.np');

define('ESEWA_FORM_URL', ESEWA_BASE_URL . '/api/epay/main/v2/form');
define('ESEWA_STATUS_URL', ESEWA_BASE_URL . '/api/epay/transaction/status/');

// IMPORTANT: Your success & failure URLs - change 'nepalidelights' if your folder name is different
define('ESEWA_SUCCESS_URL', 'http://localhost/nepalidelights/api/esewa_success.php');
define('ESEWA_FAILURE_URL', 'http://localhost/nepalidelights/api/esewa_failed.php'); // optional
?>
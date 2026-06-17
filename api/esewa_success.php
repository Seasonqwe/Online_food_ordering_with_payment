<?php
echo "<pre>GET parameters received from eSewa:\n";
print_r($_GET);
echo "</pre>";
exit;
// temporary - remove after test
// api/esewa_success.php

// Start session if needed (for user info, messages, etc.)
session_start();

require_once 'config.php';  // Your config with PDO, eSewa constants, etc.

// Get the base64-encoded 'data' parameter that eSewa sends on redirect
$encoded_data = $_GET['data'] ?? '';

if (empty($encoded_data)) {
    die("<h2>Error: No payment data received from eSewa</h2>
         <p><a href='/menu.php'>Back to Menu</a></p>");
}

// Decode the base64 string to JSON
$json_data = base64_decode($encoded_data);
$payment_data = json_decode($json_data, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($payment_data)) {
    die("<h2>Error: Invalid payment response from eSewa</h2>
         <p><a href='/menu.php'>Back to Menu</a></p>");
}

// Extract important fields
$status = strtoupper($payment_data['status'] ?? 'UNKNOWN');
$transaction_uuid  = $payment_data['transaction_uuid'] ?? null;
$total_amount      = $payment_data['total_amount'] ?? 0;
$product_code      = $payment_data['product_code'] ?? null;
$transaction_code  = $payment_data['transaction_code'] ?? null;  // eSewa transaction ID

// Get order_id passed in success_url (from esewa_initiate.php)
$order_id = $_GET['order_id'] ?? null;

if (!$order_id || !$transaction_uuid) {
    die("<h2>Error: Missing order or transaction info</h2>
         <p><a href='/menu.php'>Back to Menu</a></p>");
}

// Step 1: Verify the payment status from eSewa server (very important - prevents fake success)
$verify_params = http_build_query([
    'product_code'     => ESEWA_PRODUCT_CODE,
    'total_amount'     => $total_amount,
    'transaction_uuid' => $transaction_uuid
]);

$verify_url = ESEWA_STATUS_URL . '?' . $verify_params;  // From config: https://rc.esewa.com.np/api/epay/transaction/status/...

$ch = curl_init($verify_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local testing only; enable in production
$verify_response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$verify_result = json_decode($verify_response, true);

$is_verified = false;
if ($http_code === 200 && isset($verify_result['status']) && strtoupper($verify_result['status']) === 'COMPLETE') {
    $is_verified = true;
}

// Step 2: Handle based on verification
// ────────────────────────────────────────────────
// IMPROVED SUCCESS / FAILURE HANDLING
// ────────────────────────────────────────────────
if ($is_verified && strtoupper($status) === 'COMPLETE') {
    try {
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET 
                status         = 'paid',
                payment_method = 'esewa',
                payment_ref    = :ref,
                updated_at     = NOW()
            WHERE id = :order_id
              AND status = 'pending'          -- prevents updating already paid orders
        ");
        $stmt->execute([
            ':ref'      => $transaction_uuid,
            ':order_id' => $order_id
        ]);

        $updated_rows = $stmt->rowCount();

        if ($updated_rows > 0) {
            echo "<p style='color:green; font-weight:bold;'>Order successfully marked as PAID in database.</p>";
        } else {
            echo "<p style='color:orange;'>Order not updated (may already be paid or not found).</p>";
        }

        echo "<h2 style='color: green;'>Payment Successful! Thank you for your order.</h2>";
        echo "<p>Order ID: <strong>" . htmlspecialchars($order_id) . "</strong></p>";
        echo "<p>Transaction ID: <strong>" . htmlspecialchars($transaction_uuid) . "</strong></p>";
        echo "<p>Amount: <strong>Rs. " . number_format($total_amount, 2) . "</strong></p>";
        echo "<p><a href='/index.php'>Go to Home</a> | <a href='/menu.php'>Order More</a></p>";

        // Optional session message
        $_SESSION['payment_message'] = "Payment successful! Order #$order_id paid Rs. $total_amount.";

    } catch (PDOException $e) {
        error_log("DB update error in esewa_success: " . $e->getMessage());
        echo "<h2 style='color: orange;'>Payment received, but we couldn't update order status.</h2>";
        echo "<p>Please contact support with Order ID: " . htmlspecialchars($order_id) . "</p>";
    }
} else {
    echo "<h2 style='color: red;'>Payment Not Completed</h2>";
    echo "<p>Status: " . htmlspecialchars($status) . "</p>";
    if (isset($verify_result['status'])) {
        echo "<p>Verification: " . htmlspecialchars($verify_result['status']) . "</p>";
    }
    if (isset($verify_result['error_message'])) {
        echo "<p>Error details: " . htmlspecialchars($verify_result['error_message']) . "</p>";
    }
    echo "<p><a href='/menu.php'>Try Again</a> | <a href='/index.php'>Home</a></p>";
}

// Keep your existing log lines at the very end
error_log("eSewa success callback: " . print_r($payment_data, true));
?> {
    // Failed / pending / tampered
    echo "<h2 style='color: red;'>Payment Not Completed</h2>";
    echo "<p>Status: " . htmlspecialchars($status) . "</p>";
    if (isset($verify_result['status'])) {
        echo "<p>Verification: " . htmlspecialchars($verify_result['status']) . "</p>";
    }
    echo "<p><a href='/menu.php'>Try Again</a> | <a href='/index.php'>Home</a></p>";
}

// Optional: Log the full response for debugging
error_log("eSewa success callback: " . print_r($payment_data, true));
?>
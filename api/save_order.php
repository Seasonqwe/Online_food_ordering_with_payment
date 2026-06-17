
<?php
session_start();  // Ensure session is started here too
require_once 'config.php';

// ... rest of the file ...

// Allow CORS for local development (remove in production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST requests allowed']);
    exit;
}

// Check login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in', 'session' => $_SESSION]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Read raw JSON body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON', 'json_error' => json_last_error_msg(), 'raw' => $raw]);
    exit;
}

if (empty($data['cart']) || !is_array($data['cart']) || empty($data['total'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid cart/total data', 'received' => $data]);
    exit;
}

$total_amount = floatval($data['total']);
$order_details = json_encode($data['cart']);

try {
    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, total_amount, order_details, status)
        VALUES (?, ?, ?, 'pending')
    ");
    $stmt->execute([$user_id, $total_amount, $order_details]);

    echo json_encode([
        'success' => true,
        'order_id' => $pdo->lastInsertId(),
        'message' => 'Order saved'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}
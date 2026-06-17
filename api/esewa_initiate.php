<?php
// api/esewa_initiate.php

require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST allowed']);
    exit;
}

$raw = json_decode(file_get_contents('php://input'), true);

$amount = floatval($raw['amount'] ?? 0);
$order_id = $raw['order_id'] ?? 'ORD-' . time();
$product_name = $raw['product_name'] ?? 'Food Order from NepalDelights';

if ($amount <= 0 || empty($order_id)) {
    echo json_encode(['error' => 'Invalid amount or order ID']);
    exit;
}

// Unique transaction ID
$transaction_uuid = uniqid('esewa_', true);

// Amount as string with exactly 2 decimals (MUST for signature)
$amount_str = number_format($amount, 2, '.', '');

// Exact signature format
$signature_string = "total_amount={$amount_str},transaction_uuid={$transaction_uuid},product_code=" . ESEWA_PRODUCT_CODE;
$signature = base64_encode(hash_hmac('sha256', $signature_string, ESEWA_SECRET_KEY, true));

// Prepare form data (use string values where needed)
$data = [
    'amount'                 => $amount_str,
    'tax_amount'             => '0.00',
    'total_amount'           => $amount_str,
    'transaction_uuid'       => $transaction_uuid,
    'product_code'           => ESEWA_PRODUCT_CODE,
    'product_service_charge' => '0.00',
    'product_delivery_charge'=> '0.00',
    'success_url'            => ESEWA_SUCCESS_URL . '?order_id=' . urlencode($order_id),
    'failure_url'            => ESEWA_FAILURE_URL,
    'signed_field_names'     => 'total_amount,transaction_uuid,product_code',
    'signature'              => $signature,
];

echo json_encode([
    'success' => true,
    'form_url' => ESEWA_FORM_URL,
    'form_data' => $data
]);
?>
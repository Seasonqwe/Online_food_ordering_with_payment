<?php
require_once 'config.php';

try {
    $stmt = $pdo->query("SELECT id, name, price, image_url, category FROM menu_items ORDER BY name");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($items);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

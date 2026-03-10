<?php
require_once __DIR__ . '/../../php/config/database.php';
require_once __DIR__ . '/../../php/config/helpers.php';

header('Content-Type: application/json');

$data   = json_decode(file_get_contents('php://input'), true);
$cartId = (int)($data['cart_id'] ?? 0);

if (!$cartId) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
    exit;
}

// Remove item from cart
$stmt = $pdo->prepare("DELETE FROM cart WHERE id = ?");
$stmt->execute([$cartId]);

echo json_encode([
    'success'    => true,
    'cart_count' => getCartCount($pdo)
]);
<?php
require_once __DIR__ . '/../../php/config/database.php';
require_once __DIR__ . '/../../php/config/helpers.php';

header('Content-Type: application/json');

$data     = json_decode(file_get_contents('php://input'), true);
$cartId   = (int)($data['cart_id']  ?? 0);
$quantity = (int)($data['quantity'] ?? 0);

if (!$cartId) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
    exit;
}

if ($quantity <= 0) {
    // Remove item if quantity is 0 or less
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ?");
    $stmt->execute([$cartId]);
} else {
    // Update quantity
    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $stmt->execute([$quantity, $cartId]);
}

echo json_encode([
    'success'    => true,
    'cart_count' => getCartCount($pdo)
]);
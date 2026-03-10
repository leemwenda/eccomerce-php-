<?php
require_once __DIR__ . '/../../php/config/database.php';
require_once __DIR__ . '/../../php/config/helpers.php';

header('Content-Type: application/json');

$data      = json_decode(file_get_contents('php://input'), true);
$productId = (int)($data['product_id'] ?? 0);
$quantity  = (int)($data['quantity']   ?? 1);
$sessionId = getCartSessionId();

// Validate product
if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

// Check product exists and has stock
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND stock > 0");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not available or out of stock']);
    exit;
}

// Check if already in cart
$stmt = $pdo->prepare("SELECT * FROM cart WHERE session_id = ? AND product_id = ?");
$stmt->execute([$sessionId, $productId]);
$existing = $stmt->fetch();

if ($existing) {
    // Update quantity
    $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + ? WHERE id = ?");
    $stmt->execute([$quantity, $existing['id']]);
} else {
    // Insert new cart item
    $stmt = $pdo->prepare("INSERT INTO cart (session_id, product_id, quantity) VALUES (?, ?, ?)");
    $stmt->execute([$sessionId, $productId, $quantity]);
}

echo json_encode([
    'success'    => true,
    'cart_count' => getCartCount($pdo)
]);
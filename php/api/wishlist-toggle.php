<?php
require_once __DIR__ . '/../../php/config/database.php';
require_once __DIR__ . '/../../php/config/helpers.php';
header('Content-Type: application/json');

$data      = json_decode(file_get_contents('php://input'), true);
$productId = (int)($data['product_id'] ?? 0);
$sessionId = getCartSessionId();

if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

try {
    $check = $pdo->prepare("SELECT id FROM wishlist WHERE session_id = ? AND product_id = ?");
    $check->execute([$sessionId, $productId]);

    if ($check->fetch()) {
        $pdo->prepare("DELETE FROM wishlist WHERE session_id = ? AND product_id = ?")
            ->execute([$sessionId, $productId]);
        echo json_encode(['success' => true, 'added' => false, 'message' => 'Removed from wishlist']);
    } else {
        $pdo->prepare("INSERT INTO wishlist (session_id, product_id, created_at) VALUES (?, ?, NOW())")
            ->execute([$sessionId, $productId]);
        echo json_encode(['success' => true, 'added' => true, 'message' => 'Added to wishlist!']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error updating wishlist']);
}

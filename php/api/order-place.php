<?php
require_once __DIR__ . '/../../php/config/database.php';
require_once __DIR__ . '/../../php/config/helpers.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$cartItems = getCartItems($pdo);
if (empty($cartItems)) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

$subtotal = 0;
foreach ($cartItems as $item) {
    $price     = !empty($item['sale_price']) ? $item['sale_price'] : $item['price'];
    $subtotal += $price * $item['quantity'];
}
$shipping     = $subtotal >= 200 ? 0 : 15;
$total        = $subtotal + $shipping;
$orderNumber  = 'ORD-' . strtoupper(substr(uniqid(), -6)) . '-' . date('Ymd');
$sessionId    = getCartSessionId();
$userId       = $_SESSION['user_id'] ?? null;

try {
    $stmt = $pdo->prepare("
        INSERT INTO orders
            (user_id, order_number, first_name, last_name, full_name, email, phone,
             address, city, subtotal, shipping_cost, total, status, payment_method, payment_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, 'pending')
    ");
    $stmt->execute([
        $userId,
        $orderNumber,
        $data['first_name'],
        $data['last_name'],
        $data['first_name'] . ' ' . $data['last_name'],
        $data['email'],
        $data['phone'],
        $data['address'],
        $data['city'],
        $subtotal,
        $shipping,
        $total,
        $data['payment_method'] ?? 'cod',
    ]);
    $orderId = $pdo->lastInsertId();

    // Save order items
    $itemStmt = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($cartItems as $item) {
        $price = !empty($item['sale_price']) ? $item['sale_price'] : $item['price'];
        $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $price]);
    }

    // Clear cart
    $pdo->prepare("DELETE FROM cart WHERE session_id = ?")->execute([$sessionId]);

    echo json_encode(['success' => true, 'order_number' => $orderNumber]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}
<?php
require_once __DIR__ . '/../../php/config/database.php';
require_once __DIR__ . '/../../php/config/helpers.php';


define('PESAPAL_KEY',    'C8TZjcIAJdSZDxx1W6BmJllzJZAwY1Rs');
define('PESAPAL_SECRET', 'HCqgetjZyMcd4z+ms8zde8qYqYQ=');

$baseUrl     = 'https://pay.pesapal.com/v3';
$orderNumber = $_GET['OrderMerchantReference'] ?? '';
$trackingId  = $_GET['OrderTrackingId']        ?? '';

if (!$orderNumber || !$trackingId) exit;


$ch = curl_init($baseUrl . '/api/Auth/RequestToken');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'consumer_key'    => PESAPAL_KEY,
    'consumer_secret' => PESAPAL_SECRET
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$tokenRes = json_decode(curl_exec($ch), true);
curl_close($ch);

$token = $tokenRes['token'] ?? '';
if (!$token) exit;


$ch = curl_init($baseUrl . '/api/Transactions/GetTransactionStatus?orderTrackingId=' . $trackingId);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$statusRes = json_decode(curl_exec($ch), true);
curl_close($ch);

$paymentStatus = $statusRes['payment_status_description'] ?? '';


$status = 'pending';
if ($paymentStatus === 'Completed') $status = 'paid';
if ($paymentStatus === 'Failed')    $status = 'failed';


if ($status !== 'pending') {
    $stmt = $pdo->prepare("
        UPDATE orders
        SET payment_status = ?,
            status = ?
        WHERE order_number = ?
    ");
    $stmt->execute([
        $status,
        $status === 'paid' ? 'processing' : 'cancelled',
        $orderNumber
    ]);
}


header('Content-Type: application/json');
echo json_encode(['orderNotificationType' => 'IPNCHANGE', 'orderTrackingId' => $trackingId, 'orderMerchantReference' => $orderNumber, 'status' => 200]);
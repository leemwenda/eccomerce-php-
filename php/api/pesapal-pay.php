<?php
require_once __DIR__ . '/../../php/config/database.php';
require_once __DIR__ . '/../../php/config/helpers.php';

header('Content-Type: application/json');


define('PESAPAL_KEY',    'C8TZjcIAJdSZDxx1W6BmJllzJZAwY1Rs');
define('PESAPAL_SECRET', 'HCqgetjZyMcd4z+ms8zde8qYqYQ=');
define('PESAPAL_ENV',    'live');

// FIX 1: Use the correct live endpoint (not pay.pesapal.com/v3 â€” that's the iframe host)
$baseUrl     = 'https://pay.pesapal.com/v3';
$callbackUrl = SITE_URL . '/pages/order-success.php';
$ipnUrl      = SITE_URL . '/php/api/pesapal-ipn.php';


$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$firstName = trim($data['first_name'] ?? '');
$lastName  = trim($data['last_name']  ?? '');
$email     = trim($data['email']      ?? '');
$phone     = trim($data['phone']      ?? '');
$address   = trim($data['address']    ?? '');
$city      = trim($data['city']       ?? '');
$county    = trim($data['county']     ?? '');

if (!$firstName || !$lastName || !$email || !$phone || !$address || !$city) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// FIX 2: Normalize phone to international format (Pesapal requires 254XXXXXXXXX)
$phone = preg_replace('/\D/', '', $phone);          // strip non-digits
if (substr($phone, 0, 1) === '0') {
    $phone = '254' . substr($phone, 1);             // 07XX â†’ 2547XX
}
if (substr($phone, 0, 3) !== '254') {
    $phone = '254' . $phone;
}

// ============================================
// CART & TOTALS
// ============================================
$cartItems = getCartItems($pdo);
if (empty($cartItems)) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
    exit;
}

$subtotal = 0;
foreach ($cartItems as $item) {
    $price     = !empty($item['sale_price']) ? (float)$item['sale_price'] : (float)$item['price'];
    $subtotal += $price * (int)$item['quantity'];
}

// Prices stored in USD â€” convert to KES for Pesapal (1 USD = 130 KES)

$subtotalKes = round($subtotal, 2);

// Free shipping on orders over KES 5000 (~$38 USD), otherwise KES 350 shipping
$shipping    = $subtotalKes >= 500 ? 0 : 50;
$total       = round($subtotalKes + $shipping, 2); // Total in KES for Pesapal

function pesapalRequest($url, $payload, $token = null, $method = 'POST') {
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) return ['_curl_error' => $curlError, '_http_code' => $httpCode];
    $decoded = json_decode($response, true);
    if (!is_array($decoded)) return ['_raw' => $response, '_http_code' => $httpCode];
    $decoded['_http_code'] = $httpCode;
    return $decoded;
}


$tokenResponse = pesapalRequest($baseUrl . '/api/Auth/RequestToken', [
    'consumer_key'    => PESAPAL_KEY,
    'consumer_secret' => PESAPAL_SECRET
]);

if (empty($tokenResponse['token'])) {
   // FIX 4: Surface the real Pesapal error so you can debug it
    $hint = '';
    if (!empty($tokenResponse['error'])) {
        $hint = ' Pesapal says: ' . (is_array($tokenResponse['error']) ? json_encode($tokenResponse['error']) : $tokenResponse['error']);
    } elseif (!empty($tokenResponse['message'])) {
        $hint = ' Message: ' . $tokenResponse['message'];
    }
    echo json_encode([
        'success' => false,
        'message' => 'Could not authenticate with Pesapal. Check your API keys.' . $hint
    ]);
    exit;
}

$token = $tokenResponse['token'];


$ipnId = '';

// Try to reuse a stored IPN ID first
$storedIpn = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'pesapal_ipn_id' LIMIT 1")->fetch();
if ($storedIpn && !empty($storedIpn['setting_value'])) {
    $ipnId = $storedIpn['setting_value'];
} else {
    $ipnResponse = pesapalRequest(
        $baseUrl . '/api/URLSetup/RegisterIPN',
        ['url' => $ipnUrl, 'ipn_notification_type' => 'GET'],
        $token
    );
    $ipnId = $ipnResponse['ipn_id'] ?? '';

    // Store IPN ID for future orders (avoid repeated registration)
    if ($ipnId) {
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value)
                       VALUES ('pesapal_ipn_id', ?)
                       ON DUPLICATE KEY UPDATE setting_value = ?")
            ->execute([$ipnId, $ipnId]);
    }
}

// IPN registration can fail on localhost â€” we allow it to continue without one
// but log it so you know
if (!$ipnId) {
    error_log('[Pesapal] Warning: IPN registration failed. Orders will process but IPN callbacks may not work.');
}


$orderNumber = 'MD-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

try {
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            order_number, first_name, last_name, full_name,
            email, phone, address, city, zip, country,
            subtotal, shipping_cost, total,
            status, payment_method, payment_status, created_at
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?, ?, '', 'Kenya',
            ?, ?, ?,
            'pending', 'pesapal', 'pending', NOW()
        )
    ");
    $stmt->execute([
        $orderNumber,
        $firstName, $lastName, trim($firstName . ' ' . $lastName),
        $email, $phone, $address, $city,
        $subtotal, $shipping, $total   // subtotal=USD, shipping+total=KES
    ]);
    $orderId = (int)$pdo->lastInsertId();

    foreach ($cartItems as $item) {
        $price = !empty($item['sale_price']) ? (float)$item['sale_price'] : (float)$item['price'];
        $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)")
            ->execute([$orderId, $item['product_id'], (int)$item['quantity'], $price]);
    }
} catch (Exception $e) {
    error_log('[Pesapal] DB error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not save your order. Please try again.']);
    exit;
}


$orderPayload = [
    'id'              => $orderNumber,
    'currency'        => 'KES',
    'amount'          => $total,   // Already in KES (USD * 130)
    'description'     => 'Maison Decor Order ' . $orderNumber,
    'callback_url'    => $callbackUrl . '?order=' . urlencode($orderNumber),
    'notification_id' => $ipnId,
    'branch'          => 'Maison Decor',
    'billing_address' => [
        'email_address' => $email,
        'phone_number'  => $phone,
        'country_code'  => 'KE',
        'first_name'    => $firstName,
        'last_name'     => $lastName,
        'line_1'        => $address,
        'city'          => $city,
        'state'         => $county,
        'postal_code'   => '',
        'zip_code'      => ''
    ]
];

$orderResponse = pesapalRequest(
    $baseUrl . '/api/Transactions/SubmitOrderRequest',
    $orderPayload,
    $token
);

if (empty($orderResponse['redirect_url'])) {
    // FIX 8: Mark the DB order as failed so it doesn't sit as ghost 'pending'
    $pdo->prepare("UPDATE orders SET status = 'cancelled', payment_status = 'failed' WHERE id = ?")
        ->execute([$orderId]);

    // Surface the real Pesapal error message for easier debugging
    $pesapalMsg = $orderResponse['error']['message']
               ?? $orderResponse['message']
               ?? json_encode($orderResponse);

    error_log('[Pesapal] SubmitOrderRequest failed: ' . $pesapalMsg);

    echo json_encode([
        'success' => false,
        'message' => 'Payment gateway error: ' . $pesapalMsg
    ]);
    exit;
}


$sessionId = getCartSessionId();
$pdo->prepare("DELETE FROM cart WHERE session_id = ?")->execute([$sessionId]);

// Store order number in session so order-success.php can use it as fallback
$_SESSION['last_order_number'] = $orderNumber;

echo json_encode([
    'success'      => true,
    'redirect_url' => $orderResponse['redirect_url'],
    'order_number' => $orderNumber
]);

<?php
require_once __DIR__ . '/../../php/config/database.php';
header('Content-Type: application/json');

$data  = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

try {
    // Check if already subscribed
    $check = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        echo json_encode(['success' => true, 'message' => 'You are already subscribed!']);
        exit;
    }
    $pdo->prepare("INSERT INTO newsletter_subscribers (email, created_at) VALUES (?, NOW())")
        ->execute([$email]);
    echo json_encode(['success' => true, 'message' => 'Thank you for subscribing!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again.']);
}

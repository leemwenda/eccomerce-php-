<?php
// Clean output to prevent XSS
function clean($str) {
    return htmlspecialchars(strip_tags($str ?? ''), ENT_QUOTES, 'UTF-8');
}

// Format price
function formatPrice($price) {
    return '$' . number_format((float)$price, 2);
}

// Get all categories
function getCategories($pdo) {
    return $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
}

// Get products with optional filters
function getProducts($pdo, $opts = []) {
    $where  = ['1=1'];
    $params = [];

    if (!empty($opts['category_id'])) {
        $where[]  = 'p.category_id = ?';
        $params[] = $opts['category_id'];
    }
    if (!empty($opts['featured'])) {
        $where[] = 'p.featured = 1';
    }
    if (!empty($opts['search'])) {
        $where[]  = 'p.name LIKE ?';
        $params[] = '%' . $opts['search'] . '%';
    }

    $order = 'p.created_at DESC';
    if (!empty($opts['sort'])) {
        if ($opts['sort'] === 'price_asc')  $order = 'p.price ASC';
        if ($opts['sort'] === 'price_desc') $order = 'p.price DESC';
        if ($opts['sort'] === 'name_asc')   $order = 'p.name ASC';
    }

    $limit = !empty($opts['limit']) ? 'LIMIT ' . (int)$opts['limit'] : '';

    $sql  = "SELECT p.*, c.name AS category_name 
             FROM products p 
             LEFT JOIN categories c ON p.category_id = c.id 
             WHERE " . implode(' AND ', $where) . " 
             ORDER BY $order $limit";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Cart session ID
function getCartSessionId() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['cart_id'])) {
        $_SESSION['cart_id'] = uniqid('cart_', true);
    }
    return $_SESSION['cart_id'];
}

// Get cart item count
function getCartCount($pdo) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE session_id = ?");
    $stmt->execute([getCartSessionId()]);
    return (int)$stmt->fetchColumn();
}

// Get all cart items
function getCartItems($pdo) {
    $stmt = $pdo->prepare("
        SELECT c.*, p.name, p.price, p.sale_price, p.image, p.stock
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.session_id = ?
    ");
    $stmt->execute([getCartSessionId()]);
    return $stmt->fetchAll();
}

// Check if logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/pages/login.php');
        exit;
    }
}

// Redirect if not admin
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}function generateSlug($string) {
    // Convert to lowercase and remove extra spaces
    $slug = strtolower(trim($string));
    // Replace non-alphanumeric characters with hyphens
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    // Remove duplicate hyphens
    $slug = preg_replace('/-+/', '-', $slug);
    // Trim leading/trailing hyphens
    $slug = trim($slug, '-');
    return $slug;
}
<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Load config if not already loaded
if (!isset($pdo)) {
    require_once __DIR__ . '/../../php/config/database.php';
}
if (!function_exists('clean')) {
    require_once __DIR__ . '/../../php/config/helpers.php';
}

$cartCount = getCartCount($pdo);
$pageTitle = $pageTitle ?? 'Maison Decor';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo clean($pageTitle); ?> | Maison Decor</title>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">

    <!-- Apply saved theme immediately to prevent flash -->
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</head>
<body>

<!-- ===== HEADER ===== -->
<header class="header">
    <nav class="nav container">

        <!-- Logo -->
        <a href="<?php echo SITE_URL; ?>/index.php" class="nav-logo">
            Maison Decor
        </a>

        <!-- Navigation Menu -->
        <ul class="nav-menu">
            <li><a href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
            <li><a href="<?php echo SITE_URL; ?>/pages/shop.php">Shop</a></li>
        </ul>

        <!-- Right Side Actions -->
        <div class="nav-actions">

            <!-- Currency Selector -->
            <select id="currencySelect"
                    class="currency-select"
                    onchange="convertPrices(this.value)">
                <option value="USD">$ USD</option>
                <option value="KES">KES</option>
                <option value="EUR">€ EUR</option>
                <option value="GBP">£ GBP</option>
                <option value="TZS">TZS</option>
                <option value="UGX">UGX</option>
                <option value="ZAR">ZAR</option>
                <option value="NGN">NGN</option>
                <option value="GHS">GHS</option>
                <option value="CAD">CAD</option>
                <option value="AUD">AUD</option>
                <option value="INR">INR</option>
                <option value="AED">AED</option>
                <option value="CNY">CNY</option>
            </select>

            <!-- Dark Mode Toggle -->
            <button class="nav-icon" onclick="toggleTheme()" title="Toggle Dark Mode">
                <i class="fa-solid fa-moon" id="themeIcon"></i>
            </button>

            <!-- Search -->
            <a href="<?php echo SITE_URL; ?>/pages/shop.php" class="nav-icon" title="Search">
                <i class="fa-solid fa-magnifying-glass"></i>
            </a>

            <!-- Admin Dashboard Link (only for admins) -->
            <?php if (isAdmin()): ?>
                <a href="<?php echo SITE_URL; ?>/admin/index.php" class="nav-icon" title="Admin Panel">
                    <i class="fa-solid fa-gauge-high"></i>
                </a>
            <?php endif; ?>

            <!-- Cart -->
            <a href="<?php echo SITE_URL; ?>/pages/cart.php" class="nav-icon" title="Cart">
                <i class="fa-solid fa-bag-shopping"></i>
                <span class="cart-badge" id="cartBadge"
                      style="display:<?php echo $cartCount > 0 ? 'flex' : 'none'; ?>">
                    <?php echo $cartCount; ?>
                </span>
            </a>

            <!-- Logout -->
            <a href="<?php echo SITE_URL; ?>/pages/logout.php" class="nav-icon" title="Logout">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>

        </div>
    </nav>
</header>

<!-- Page content starts here -->
<main>
<?php
session_start();
require_once '../php/config/database.php';
require_once '../php/config/helpers.php';

// Only admins can access this page
requireAdmin();

// Fetch stats
$totalProducts  = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders    = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$totalRevenue   = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status = 'paid'")->fetchColumn();
$lowStock       = $pdo->query("SELECT COUNT(*) FROM products WHERE stock < 5")->fetchColumn();
$pendingOrders  = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$recentOrders   = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 6")->fetchAll();
$recentProducts = $pdo->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
</head>
<body>

<div class="admin-layout">

    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <i class="fa-solid fa-store"></i>
            Maison Decor
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php" class="active"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fa-solid fa-box"></i> Products</a></li>
            <li><a href="orders.php"><i class="fa-solid fa-cart-shopping"></i> Orders</a></li>
            <li><a href="customers.php"><i class="fa-solid fa-users"></i> Customers</a></li>
            <li><a href="categories.php"><i class="fa-solid fa-tags"></i> Categories</a></li>
            <li><a href="import.php"><i class="fa-solid fa-file-import"></i> CSV Import</a></li>
            <li><a href="delivery-settings.php"><i class="fa-solid fa-truck"></i> Delivery</a></li>
        </ul>
        <hr class="sidebar-divider">
        <ul class="sidebar-menu">
            <li><a href="../index.php" target="_blank"><i class="fa-solid fa-globe"></i> View Website</a></li>
            <li>
                <a href="#" onclick="toggleTheme(); return false;">
                    <i class="fa-solid fa-moon" id="themeIcon"></i> Dark Mode
                </a>
            </li>
            <li><a href="logout.php" style="color: #FCA5A5;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="admin-main">

        <!-- Top Bar -->
        <div class="admin-topbar">
            <div>
                <h1>Dashboard</h1>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.2rem;">
                    <i class="fa-solid fa-calendar" style="margin-right: 0.35rem;"></i>
                    <?php echo date('l, F j, Y'); ?>
                </p>
            </div>
            <div class="admin-user">
                <div class="admin-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div>
                    <div style="font-weight: 600; font-size: 0.9rem;">
                        <?php echo clean($_SESSION['user_name'] ?? 'Admin'); ?>
                    </div>
                    <div style="color: var(--text-muted); font-size: 0.75rem;">Administrator</div>
                </div>
            </div>
        </div>

        <!-- ===== LOW STOCK ALERT ===== -->
        <?php if ($lowStock > 0): ?>
        <div class="alert alert-warning" style="margin-bottom: 1.5rem;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>
                <strong><?php echo $lowStock; ?> products</strong> are running low on stock.
            </span>
            <a href="products.php" style="margin-left: auto; color: #92400E; font-weight: 600; text-decoration: underline;">
                View Products
            </a>
        </div>
        <?php endif; ?>

        <?php if ($pendingOrders > 0): ?>
        <div class="alert alert-warning" style="margin-bottom: 1.5rem;">
            <i class="fa-solid fa-clock"></i>
            <span>
                <strong><?php echo $pendingOrders; ?> orders</strong> are waiting to be processed.
            </span>
            <a href="orders.php" style="margin-left: auto; color: #92400E; font-weight: 600; text-decoration: underline;">
                View Orders
            </a>
        </div>
        <?php endif; ?>

        <!-- ===== STAT CARDS ===== -->
        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fa-solid fa-box"></i>
                </div>
                <div>
                    <div class="stat-value"><?php echo number_format($totalProducts); ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
                <div>
                    <div class="stat-value"><?php echo number_format($totalOrders); ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <div class="stat-value"><?php echo number_format($totalCustomers); ?></div>
                    <div class="stat-label">Customers</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon amber">
                    <i class="fa-solid fa-dollar-sign"></i>
                </div>
                <div>
                    <div class="stat-value">$<?php echo number_format($totalRevenue, 0); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
        </div>

        <!-- ===== QUICK ACTIONS ===== -->
        <div class="quick-actions">
            <a href="product-add.php" class="quick-btn">
                <i class="fa-solid fa-plus"></i>
                Add Product
            </a>
            <a href="categories.php" class="quick-btn">
                <i class="fa-solid fa-tags"></i>
                Add Category
            </a>
            <a href="orders.php" class="quick-btn">
                <i class="fa-solid fa-list-check"></i>
                Manage Orders
            </a>
            <a href="import.php" class="quick-btn">
                <i class="fa-solid fa-file-import"></i>
                Import CSV
            </a>
        </div>

        <!-- ===== RECENT ORDERS + RECENT PRODUCTS ===== -->
        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 1.5rem;">

            <!-- Recent Orders -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>
                        <i class="fa-solid fa-clock-rotate-left" style="color: var(--primary); margin-right: 0.5rem;"></i>
                        Recent Orders
                    </h3>
                    <a href="orders.php" style="font-size: 0.85rem; color: var(--primary); font-weight: 600;">
                        View All <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentOrders)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                    <i class="fa-solid fa-inbox" style="font-size: 2rem; display: block; margin-bottom: 0.75rem; color: var(--border);"></i>
                                    No orders yet
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>
                                        <a href="orders.php?id=<?php echo $order['id']; ?>"
                                           style="font-weight: 700; color: var(--primary); font-size: 0.85rem;">
                                            #<?php echo clean($order['order_number'] ?? $order['id']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; font-size: 0.88rem;">
                                            <?php echo clean($order['full_name'] ?? ($order['first_name'] . ' ' . $order['last_name'])); ?>
                                        </div>
                                        <div style="color: var(--text-muted); font-size: 0.78rem;">
                                            <?php echo clean($order['email'] ?? ''); ?>
                                        </div>
                                    </td>
                                    <td style="font-weight: 700;">
                                        $<?php echo number_format($order['total'], 2); ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo clean($order['payment_status'] ?? 'pending'); ?>">
                                            <?php echo ucfirst($order['payment_status'] ?? 'pending'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo clean($order['status'] ?? 'pending'); ?>">
                                            <?php echo ucfirst($order['status'] ?? 'pending'); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Products -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>
                        <i class="fa-solid fa-box" style="color: var(--primary); margin-right: 0.5rem;"></i>
                        Recent Products
                    </h3>
                    <a href="products.php" style="font-size: 0.85rem; color: var(--primary); font-weight: 600;">
                        View All <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
                <div>
                    <?php if (empty($recentProducts)): ?>
                        <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                            <i class="fa-solid fa-box-open" style="font-size: 2rem; display: block; margin-bottom: 0.75rem; color: var(--border);"></i>
                            No products yet
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentProducts as $i => $p): ?>
                            <div style="display: flex; align-items: center; gap: 0.85rem; padding: 0.85rem 1.25rem; <?php echo $i > 0 ? 'border-top: 1px solid var(--border);' : ''; ?>">
                                <img src="<?php echo !empty($p['image']) ? clean($p['image']) : 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=100&q=80'; ?>"
                                     style="width: 46px; height: 46px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border); flex-shrink: 0;">
                                <div style="flex: 1; min-width: 0;">
                                    <p style="font-weight: 600; font-size: 0.88rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo clean($p['name']); ?>
                                    </p>
                                    <p style="color: var(--text-muted); font-size: 0.78rem;">
                                        <?php echo clean($p['category_name'] ?? 'No category'); ?>
                                    </p>
                                </div>
                                <div style="text-align: right; flex-shrink: 0;">
                                    <p style="font-weight: 700; font-size: 0.9rem; color: var(--primary);">
                                        $<?php echo number_format($p['sale_price'] ?? $p['price'], 2); ?>
                                    </p>
                                    <p style="font-size: 0.75rem; color: <?php echo $p['stock'] < 5 ? 'var(--danger)' : 'var(--text-muted)'; ?>;">
                                        <?php echo $p['stock']; ?> in stock
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </main>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script src="../assets/js/main.js"></script>
</body>
</html>
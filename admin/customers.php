<?php
session_start();
require_once '../php/config/database.php';
require_once '../php/config/helpers.php';
requireAdmin();

$search = trim($_GET['search'] ?? '');

$where  = ["role = 'customer'"];
$params = [];

if ($search) {
    $where[]  = '(name LIKE ? OR email LIKE ?)';
    $params[] = '%'.$search.'%';
    $params[] = '%'.$search.'%';
}

$stmt = $pdo->prepare("
    SELECT u.*,
           COUNT(o.id) AS total_orders,
           COALESCE(SUM(o.total), 0) AS total_spent
    FROM users u
    LEFT JOIN orders o ON o.email = u.email
    WHERE " . implode(' AND ', $where) . "
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers | Admin</title>
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

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <i class="fa-solid fa-store"></i>
            Maison Decor
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fa-solid fa-box"></i> Products</a></li>
            <li><a href="orders.php"><i class="fa-solid fa-cart-shopping"></i> Orders</a></li>
            <li><a href="customers.php" class="active"><i class="fa-solid fa-users"></i> Customers</a></li>
            <li><a href="categories.php"><i class="fa-solid fa-tags"></i> Categories</a></li>
            <li><a href="import.php"><i class="fa-solid fa-file-import"></i> CSV Import</a></li>
            <li><a href="delivery-settings.php"><i class="fa-solid fa-truck"></i> Delivery</a></li>
        </ul>
        <hr class="sidebar-divider">
        <ul class="sidebar-menu">
            <li><a href="../index.php" target="_blank"><i class="fa-solid fa-globe"></i> View Website</a></li>
            <li><a href="#" onclick="toggleTheme(); return false;"><i class="fa-solid fa-moon" id="themeIcon"></i> Dark Mode</a></li>
            <li><a href="logout.php" style="color:#FCA5A5;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- MAIN -->
    <main class="admin-main">

        <!-- Top Bar -->
        <div class="admin-topbar">
            <div>
                <h1>Customers</h1>
                <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.2rem;">
                    All registered customers
                </p>
            </div>
            <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius); padding:0.65rem 1.25rem; font-size:0.9rem; font-weight:600;">
                <i class="fa-solid fa-users" style="color:var(--primary); margin-right:0.5rem;"></i>
                <?php echo count($customers); ?> Customers
            </div>
        </div>

        <!-- Search -->
        <div class="admin-card" style="margin-bottom:1.5rem;">
            <div style="padding:1rem 1.5rem; display:flex; gap:1rem; align-items:center;">
                <div style="position:relative; flex:1;">
                    <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:1rem; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:0.85rem;"></i>
                    <input type="text"
                           id="searchInput"
                           placeholder="Search by name or email..."
                           value="<?php echo clean($search); ?>"
                           style="width:100%; padding:0.7rem 1rem 0.7rem 2.5rem; border:1.5px solid var(--border); border-radius:var(--radius); font-size:0.9rem; background:var(--bg); color:var(--text); font-family:var(--font-body);"
                           onkeypress="if(event.key==='Enter') window.location.href='customers.php?search='+encodeURIComponent(this.value)">
                </div>
                <button onclick="window.location.href='customers.php?search='+encodeURIComponent(document.getElementById('searchInput').value)"
                        class="btn btn-primary">
                    <i class="fa-solid fa-magnifying-glass"></i> Search
                </button>
                <?php if ($search): ?>
                    <a href="customers.php" class="btn btn-outline">
                        <i class="fa-solid fa-xmark"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="admin-card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width:48px;">#</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Joined</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:4rem; color:var(--text-muted);">
                                <i class="fa-solid fa-users" style="font-size:2.5rem; display:block; margin-bottom:1rem; color:var(--border);"></i>
                                <?php echo $search ? 'No customers match your search.' : 'No customers yet.'; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $i => $customer): ?>
                            <tr>
                                <!-- Number -->
                                <td style="color:var(--text-muted); font-size:0.85rem;">
                                    <?php echo $i + 1; ?>
                                </td>
                                <!-- Name + Avatar -->
                                <td>
                                    <div style="display:flex; align-items:center; gap:0.85rem;">
                                        <div style="width:38px; height:38px; border-radius:50%; background:var(--primary); color:white; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.9rem; flex-shrink:0;">
                                            <?php echo strtoupper(substr($customer['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:600; font-size:0.9rem;">
                                                <?php echo clean($customer['name']); ?>
                                            </div>
                                            <div style="color:var(--text-muted); font-size:0.75rem;">
                                                ID #<?php echo $customer['id']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <!-- Email -->
                                <td>
                                    <a href="mailto:<?php echo clean($customer['email']); ?>"
                                       style="color:var(--primary); font-size:0.88rem; display:flex; align-items:center; gap:0.4rem;">
                                        <i class="fa-solid fa-envelope" style="font-size:0.8rem;"></i>
                                        <?php echo clean($customer['email']); ?>
                                    </a>
                                </td>
                                <!-- Date Joined -->
                                <td style="color:var(--text-muted); font-size:0.85rem; white-space:nowrap;">
                                    <i class="fa-solid fa-calendar" style="margin-right:0.35rem; font-size:0.8rem;"></i>
                                    <?php echo date('M j, Y', strtotime($customer['created_at'])); ?>
                                </td>
                                <!-- Orders -->
                                <td>
                                    <span style="font-weight:700; font-size:0.95rem;">
                                        <?php echo $customer['total_orders']; ?>
                                    </span>
                                    <span style="color:var(--text-muted); font-size:0.78rem; margin-left:0.25rem;">
                                        order<?php echo $customer['total_orders'] != 1 ? 's' : ''; ?>
                                    </span>
                                </td>
                                <!-- Total Spent -->
                                <td>
                                    <span style="font-weight:700; color:var(--primary);">
                                        $<?php echo number_format($customer['total_spent'], 2); ?>
                                    </span>
                                </td>
                                <!-- Status -->
                                <td>
                                    <?php if ($customer['total_orders'] > 0): ?>
                                        <span class="badge badge-paid">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-customer">No Orders</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<div class="toast" id="toast"></div>
<script src="../assets/js/main.js"></script>
</body>
</html>
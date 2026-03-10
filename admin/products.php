<?php
session_start();
require_once '../php/config/database.php';
require_once '../php/config/helpers.php';
requireAdmin();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    header('Location: products.php?msg=deleted');
    exit;
}

// Search and filter
$search     = trim($_GET['search']   ?? '');
$categoryId = (int)($_GET['category'] ?? 0);
$categories = getCategories($pdo);

$where  = ['1=1'];
$params = [];
if ($search) {
    $where[]  = 'p.name LIKE ?';
    $params[] = '%' . $search . '%';
}
if ($categoryId) {
    $where[]  = 'p.category_id = ?';
    $params[] = $categoryId;
}

$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY p.created_at DESC
");
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | Admin</title>
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
            <li><a href="index.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
            <li><a href="products.php" class="active"><i class="fa-solid fa-box"></i> Products</a></li>
            <li><a href="orders.php"><i class="fa-solid fa-cart-shopping"></i> Orders</a></li>
            <li><a href="customers.php"><i class="fa-solid fa-users"></i> Customers</a></li>
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

    <!-- ===== MAIN ===== -->
    <main class="admin-main">

        <!-- Top Bar -->
        <div class="admin-topbar">
            <div>
                <h1>Products</h1>
                <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.2rem;">
                    Manage your product catalogue
                </p>
            </div>
            <a href="product-add.php" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i>
                Add New Product
            </a>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_GET['msg'])): ?>
            <?php
            $msgs = [
                'deleted' => ['type' => 'success', 'icon' => 'fa-trash',       'text' => 'Product deleted successfully'],
                'added'   => ['type' => 'success', 'icon' => 'fa-circle-check','text' => 'Product added successfully'],
                'updated' => ['type' => 'success', 'icon' => 'fa-circle-check','text' => 'Product updated successfully'],
            ];
            $msg = $msgs[$_GET['msg']] ?? null;
            ?>
            <?php if ($msg): ?>
                <div class="alert alert-<?php echo $msg['type']; ?>">
                    <i class="fa-solid <?php echo $msg['icon']; ?>"></i>
                    <?php echo $msg['text']; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Filters -->
        <div class="admin-card" style="margin-bottom:1.5rem;">
            <div style="padding:1.25rem 1.5rem; display:flex; gap:1rem; align-items:center; flex-wrap:wrap;">
                <!-- Search -->
                <div style="position:relative; flex:1; min-width:200px;">
                    <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:1rem; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:0.85rem;"></i>
                    <input type="text"
                           id="searchInput"
                           placeholder="Search products..."
                           value="<?php echo clean($search); ?>"
                           style="width:100%; padding:0.7rem 1rem 0.7rem 2.5rem; border:1.5px solid var(--border); border-radius:var(--radius); font-size:0.9rem; background:var(--bg); color:var(--text); font-family:var(--font-body);"
                           onkeypress="if(event.key==='Enter') applyFilters()">
                </div>
                <!-- Category Filter -->
                <select id="categoryFilter"
                        onchange="applyFilters()"
                        style="padding:0.7rem 1rem; border:1.5px solid var(--border); border-radius:var(--radius); font-size:0.9rem; background:var(--bg); color:var(--text); font-family:var(--font-body); min-width:180px;">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $categoryId == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo clean($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <!-- Search Button -->
                <button onclick="applyFilters()" class="btn btn-primary">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Search
                </button>
                <?php if ($search || $categoryId): ?>
                    <a href="products.php" class="btn btn-outline">
                        <i class="fa-solid fa-xmark"></i>
                        Clear
                    </a>
                <?php endif; ?>
                <!-- Count -->
                <span style="color:var(--text-muted); font-size:0.85rem; margin-left:auto;">
                    <?php echo count($products); ?> products
                </span>
            </div>
        </div>

        <!-- Products Table -->
        <div class="admin-card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width:60px;">Image</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Featured</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:4rem; color:var(--text-muted);">
                                <i class="fa-solid fa-box-open" style="font-size:2.5rem; display:block; margin-bottom:1rem; color:var(--border);"></i>
                                <?php if ($search || $categoryId): ?>
                                    No products match your search.
                                    <a href="products.php" style="color:var(--primary); display:block; margin-top:0.5rem;">Clear filters</a>
                                <?php else: ?>
                                    No products yet.
                                    <a href="product-add.php" style="color:var(--primary); display:block; margin-top:0.5rem;">Add your first product</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <!-- Image -->
                                <td>
                                    <img src="<?php echo !empty($p['image']) ? clean($p['image']) : 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=100&q=80'; ?>"
                                         style="width:52px; height:52px; object-fit:cover; border-radius:var(--radius-sm); border:1px solid var(--border);">
                                </td>
                                <!-- Name -->
                                <td>
                                    <div style="font-weight:600; font-size:0.92rem;">
                                        <?php echo clean($p['name']); ?>
                                    </div>
                                    <?php if (!empty($p['tag'])): ?>
                                        <span class="badge badge-<?php echo $p['tag'] === 'Sale' ? 'failed' : 'paid'; ?>" style="margin-top:0.25rem; font-size:0.68rem;">
                                            <?php echo clean($p['tag']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <!-- Category -->
                                <td style="color:var(--text-muted); font-size:0.88rem;">
                                    <?php echo clean($p['category_name'] ?? 'No category'); ?>
                                </td>
                                <!-- Price -->
                                <td>
                                    <?php if (!empty($p['sale_price'])): ?>
                                        <span style="font-weight:700; color:var(--danger);">
                                            $<?php echo number_format($p['sale_price'], 2); ?>
                                        </span>
                                        <span style="text-decoration:line-through; color:var(--text-muted); font-size:0.82rem; margin-left:0.35rem;">
                                            $<?php echo number_format($p['price'], 2); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="font-weight:700;">
                                            $<?php echo number_format($p['price'], 2); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <!-- Stock -->
                                <td>
                                    <?php if ($p['stock'] <= 0): ?>
                                        <span style="color:var(--danger); font-weight:600; font-size:0.85rem;">
                                            <i class="fa-solid fa-circle-xmark"></i> Out of stock
                                        </span>
                                    <?php elseif ($p['stock'] < 5): ?>
                                        <span style="color:#D97706; font-weight:600; font-size:0.85rem;">
                                            <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $p['stock']; ?> left
                                        </span>
                                    <?php else: ?>
                                        <span style="color:var(--accent); font-weight:600; font-size:0.85rem;">
                                            <i class="fa-solid fa-circle-check"></i> <?php echo $p['stock']; ?> in stock
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <!-- Featured -->
                                <td>
                                    <?php if ($p['featured']): ?>
                                        <span style="color:var(--primary); font-size:0.85rem;">
                                            <i class="fa-solid fa-star"></i> Yes
                                        </span>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:0.85rem;">
                                            <i class="fa-regular fa-star"></i> No
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <!-- Actions -->
                                <td>
                                    <div style="display:flex; gap:0.5rem;">
                                        <a href="product-edit.php?id=<?php echo $p['id']; ?>"
                                           class="btn btn-outline btn-sm">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </a>
                                        <a href="products.php?delete=<?php echo $p['id']; ?>"
                                           class="btn btn-sm"
                                           style="background:#FEE2E2; color:var(--danger); border:1px solid #FCA5A5;"
                                           onclick="return confirm('Are you sure you want to delete <?php echo clean($p['name']); ?>?')">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
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
<script>
function applyFilters() {
    const search   = document.getElementById('searchInput').value;
    const category = document.getElementById('categoryFilter').value;
    let url = 'products.php?';
    if (search)   url += 'search='   + encodeURIComponent(search) + '&';
    if (category) url += 'category=' + category;
    window.location.href = url;
}
</script>
</body>
</html>
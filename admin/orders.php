<?php
session_start();
require_once '../php/config/database.php';
require_once '../php/config/helpers.php';
requireAdmin();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId       = (int)$_POST['order_id'];
    $status        = $_POST['status']         ?? '';
    $paymentStatus = $_POST['payment_status'] ?? '';

    $stmt = $pdo->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?");
    $stmt->execute([$status, $paymentStatus, $orderId]);

    header('Location: orders.php?msg=updated');
    exit;
}

// Filter by status
$filterStatus = $_GET['status'] ?? '';
$search       = trim($_GET['search'] ?? '');

$where  = ['1=1'];
$params = [];

if ($filterStatus) {
    $where[]  = 'status = ?';
    $params[] = $filterStatus;
}
if ($search) {
    $where[]  = '(order_number LIKE ? OR full_name LIKE ? OR email LIKE ?)';
    $params[] = '%'.$search.'%';
    $params[] = '%'.$search.'%';
    $params[] = '%'.$search.'%';
}

$orders = $pdo->prepare("
    SELECT * FROM orders
    WHERE " . implode(' AND ', $where) . "
    ORDER BY created_at DESC
");
$orders->execute($params);
$orders = $orders->fetchAll();

// Count by status for tabs
$statusCounts = [];
$allStatuses  = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
foreach ($allStatuses as $s) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = ?");
    $stmt->execute([$s]);
    $statusCounts[$s] = $stmt->fetchColumn();
}
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | Admin</title>
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
            <li><a href="orders.php" class="active"><i class="fa-solid fa-cart-shopping"></i> Orders</a></li>
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

    <!-- MAIN -->
    <main class="admin-main">

        <!-- Top Bar -->
        <div class="admin-topbar">
            <div>
                <h1>Orders</h1>
                <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.2rem;">
                    Manage and update customer orders
                </p>
            </div>
        </div>

        <!-- Success Message -->
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                Order status updated successfully
            </div>
        <?php endif; ?>

        <!-- Status Filter Tabs -->
        <div style="display:flex; gap:0.5rem; margin-bottom:1.5rem; flex-wrap:wrap;">
            <a href="orders.php"
               style="padding:0.55rem 1.1rem; border-radius:var(--radius); font-size:0.85rem; font-weight:600; border:1.5px solid <?php echo !$filterStatus ? 'var(--primary)' : 'var(--border)'; ?>; background:<?php echo !$filterStatus ? 'var(--primary)' : 'var(--bg-card)'; ?>; color:<?php echo !$filterStatus ? 'white' : 'var(--text-muted)'; ?>;">
                <i class="fa-solid fa-list"></i> All
                <span style="background:rgba(255,255,255,0.25); padding:0.1rem 0.45rem; border-radius:2rem; font-size:0.75rem; margin-left:0.3rem;">
                    <?php echo $totalOrders; ?>
                </span>
            </a>
            <?php
            $tabIcons = [
                'pending'    => 'fa-clock',
                'processing' => 'fa-gear',
                'shipped'    => 'fa-truck',
                'delivered'  => 'fa-circle-check',
                'cancelled'  => 'fa-circle-xmark',
            ];
            foreach ($allStatuses as $s):
            ?>
                <a href="orders.php?status=<?php echo $s; ?>"
                   style="padding:0.55rem 1.1rem; border-radius:var(--radius); font-size:0.85rem; font-weight:600; border:1.5px solid <?php echo $filterStatus === $s ? 'var(--primary)' : 'var(--border)'; ?>; background:<?php echo $filterStatus === $s ? 'var(--primary)' : 'var(--bg-card)'; ?>; color:<?php echo $filterStatus === $s ? 'white' : 'var(--text-muted)'; ?>;">
                    <i class="fa-solid <?php echo $tabIcons[$s]; ?>"></i>
                    <?php echo ucfirst($s); ?>
                    <span style="background:rgba(255,255,255,0.25); padding:0.1rem 0.45rem; border-radius:2rem; font-size:0.75rem; margin-left:0.3rem;">
                        <?php echo $statusCounts[$s]; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Search -->
        <div class="admin-card" style="margin-bottom:1.5rem;">
            <div style="padding:1rem 1.5rem; display:flex; gap:1rem; align-items:center;">
                <div style="position:relative; flex:1;">
                    <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:1rem; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:0.85rem;"></i>
                    <input type="text"
                           id="searchInput"
                           placeholder="Search by order number, name or email..."
                           value="<?php echo clean($search); ?>"
                           style="width:100%; padding:0.7rem 1rem 0.7rem 2.5rem; border:1.5px solid var(--border); border-radius:var(--radius); font-size:0.9rem; background:var(--bg); color:var(--text); font-family:var(--font-body);"
                           onkeypress="if(event.key==='Enter') { window.location.href='orders.php?search='+encodeURIComponent(this.value)+'<?php echo $filterStatus ? '&status='.$filterStatus : ''; ?>'; }">
                </div>
                <button onclick="window.location.href='orders.php?search='+encodeURIComponent(document.getElementById('searchInput').value)+'<?php echo $filterStatus ? '&status='.$filterStatus : ''; ?>'"
                        class="btn btn-primary">
                    <i class="fa-solid fa-magnifying-glass"></i> Search
                </button>
                <?php if ($search): ?>
                    <a href="orders.php<?php echo $filterStatus ? '?status='.$filterStatus : ''; ?>" class="btn btn-outline">
                        <i class="fa-solid fa-xmark"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="admin-card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:4rem; color:var(--text-muted);">
                                <i class="fa-solid fa-inbox" style="font-size:2.5rem; display:block; margin-bottom:1rem; color:var(--border);"></i>
                                No orders found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <!-- Order Number -->
                                <td>
                                    <span style="font-weight:700; color:var(--primary); font-size:0.9rem;">
                                        #<?php echo clean($order['order_number'] ?? $order['id']); ?>
                                    </span>
                                </td>
                                <!-- Customer -->
                                <td>
                                    <div style="font-weight:600; font-size:0.88rem;">
                                        <?php echo clean($order['full_name'] ?? ($order['first_name'].' '.$order['last_name'])); ?>
                                    </div>
                                    <div style="color:var(--text-muted); font-size:0.78rem;">
                                        <?php echo clean($order['email'] ?? ''); ?>
                                    </div>
                                    <div style="color:var(--text-muted); font-size:0.78rem;">
                                        <?php echo clean($order['phone'] ?? ''); ?>
                                    </div>
                                </td>
                                <!-- Date -->
                                <td style="color:var(--text-muted); font-size:0.85rem; white-space:nowrap;">
                                    <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                    <div style="font-size:0.75rem;">
                                        <?php echo date('g:i A', strtotime($order['created_at'])); ?>
                                    </div>
                                </td>
                                <!-- Total -->
                                <td style="font-weight:700;">
                                    $<?php echo number_format($order['total'], 2); ?>
                                </td>
                                <!-- Payment Status -->
                                <td>
                                    <span class="badge badge-<?php echo clean($order['payment_status'] ?? 'pending'); ?>">
                                        <?php echo ucfirst($order['payment_status'] ?? 'pending'); ?>
                                    </span>
                                </td>
                                <!-- Order Status -->
                                <td>
                                    <span class="badge badge-<?php echo clean($order['status'] ?? 'pending'); ?>">
                                        <?php echo ucfirst($order['status'] ?? 'pending'); ?>
                                    </span>
                                </td>
                                <!-- Actions -->
                                <td>
                                    <button onclick="openUpdateModal(<?php echo $order['id']; ?>, '<?php echo clean($order['status']); ?>', '<?php echo clean($order['payment_status']); ?>')"
                                            class="btn btn-outline btn-sm">
                                        <i class="fa-solid fa-pen-to-square"></i> Update
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<!-- ===== UPDATE STATUS MODAL ===== -->
<div id="updateModal"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center;">
    <div style="background:var(--bg-card); border-radius:var(--radius-lg); padding:2rem; width:100%; max-width:440px; margin:1rem; box-shadow:var(--shadow-lg);">

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 style="font-family:var(--font-display); font-size:1.25rem; font-weight:600;">
                <i class="fa-solid fa-pen-to-square" style="color:var(--primary); margin-right:0.5rem;"></i>
                Update Order Status
            </h3>
            <button onclick="closeUpdateModal()"
                    style="background:none; border:none; font-size:1.25rem; color:var(--text-muted); cursor:pointer;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST">
            <input type="hidden" name="update_status" value="1">
            <input type="hidden" name="order_id" id="modalOrderId">

            <!-- Order Status -->
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label style="display:block; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); margin-bottom:0.5rem;">
                    Order Status
                </label>
                <select name="status" id="modalStatus" class="form-control">
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <!-- Payment Status -->
            <div class="form-group" style="margin-bottom:1.75rem;">
                <label style="display:block; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); margin-bottom:0.5rem;">
                    Payment Status
                </label>
                <select name="payment_status" id="modalPayment" class="form-control">
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="failed">Failed</option>
                </select>
            </div>

            <div style="display:flex; gap:0.75rem;">
                <button type="submit" class="btn btn-primary" style="flex:1; justify-content:center;">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
                <button type="button" onclick="closeUpdateModal()" class="btn btn-outline">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<div class="toast" id="toast"></div>
<script src="../assets/js/main.js"></script>
<script>
function openUpdateModal(orderId, status, paymentStatus) {
    document.getElementById('modalOrderId').value = orderId;
    document.getElementById('modalStatus').value  = status;
    document.getElementById('modalPayment').value = paymentStatus;
    document.getElementById('updateModal').style.display = 'flex';
}
function closeUpdateModal() {
    document.getElementById('updateModal').style.display = 'none';
}
// Close modal on background click
document.getElementById('updateModal').addEventListener('click', function(e) {
    if (e.target === this) closeUpdateModal();
});
</script>
</body>
</html>
<?php
$pageTitle = 'Order Confirmed';
require_once '../php/config/database.php';
require_once '../php/config/helpers.php';
require_once '../php/includes/header.php';

// Get order number from URL or session
$orderNumber = $_GET['order'] ?? $_SESSION['last_order_number'] ?? '';
$order       = null;
$orderItems  = [];

if ($orderNumber) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch();

    if ($order) {
        $itemStmt = $pdo->prepare("
            SELECT oi.*, p.name, p.image
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $itemStmt->execute([$order['id']]);
        $orderItems = $itemStmt->fetchAll();
    }
}
?>

<section class="section">
    <div class="container">
        <div style="max-width:660px; margin:0 auto; text-align:center;">

            <!-- Success Icon -->
            <div style="width:90px; height:90px; background:#D1FAE5; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem;">
                <i class="fa-solid fa-circle-check" style="font-size:2.75rem; color:#059669;"></i>
            </div>

            <h1 style="font-family:var(--font-display); font-size:2.25rem; font-weight:700; margin-bottom:0.75rem;">
                Order Confirmed!
            </h1>
            <p style="color:var(--text-muted); font-size:1rem; line-height:1.8; margin-bottom:2rem;">
                Thank you for shopping with Maison Decor. Your payment was received and your order is being processed.
            </p>

            <?php if ($order): ?>
            <!-- Order Summary Card -->
            <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-lg); padding:1.75rem; margin-bottom:1.5rem; text-align:left;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; flex-wrap:wrap; gap:0.5rem;">
                    <h3 style="font-family:var(--font-display); font-size:1.1rem; font-weight:600;">
                        <i class="fa-solid fa-receipt" style="color:var(--primary); margin-right:0.5rem;"></i>Order Summary
                    </h3>
                    <span style="background:var(--primary-light); color:var(--primary); font-size:0.8rem; font-weight:700; padding:0.3rem 0.9rem; border-radius:50px;">
                        #<?php echo clean($order['order_number']); ?>
                    </span>
                </div>

                <!-- Items -->
                <?php foreach ($orderItems as $item): ?>
                <div style="display:flex; gap:1rem; align-items:center; padding:0.75rem 0; border-bottom:1px solid var(--border);">
                    <img src="<?php echo clean($item['image'] ?: 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=80&q=60'); ?>"
                         style="width:52px; height:52px; object-fit:cover; border-radius:var(--radius-sm); flex-shrink:0;"
                         onerror="this.src='https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=80&q=60'">
                    <div style="flex:1; min-width:0;">
                        <p style="font-weight:600; font-size:0.9rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo clean($item['name']); ?></p>
                        <p style="color:var(--text-muted); font-size:0.8rem;">Qty: <?php echo $item['quantity']; ?></p>
                    </div>
                    <span style="font-weight:600; font-size:0.9rem; white-space:nowrap;" data-price="<?php echo $item['price']; ?>">
                        <?php echo formatPrice($item['price']); ?>
                    </span>
                </div>
                <?php endforeach; ?>

                <!-- Totals -->
                <div style="margin-top:1rem; display:flex; flex-direction:column; gap:0.45rem;">
                    <div style="display:flex; justify-content:space-between; font-size:0.9rem; color:var(--text-muted);">
                        <span>Subtotal</span>
                        <span data-price="<?php echo $order['subtotal']; ?>"><?php echo formatPrice($order['subtotal']); ?></span>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:0.9rem; color:var(--text-muted);">
                        <span>Shipping</span>
                        <span><?php echo $order['shipping_cost'] == 0 ? '<span style="color:#059669">Free</span>' : 'KES ' . number_format($order['shipping_cost'], 2); ?></span>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-weight:700; font-size:1rem; border-top:1px solid var(--border); padding-top:0.6rem; margin-top:0.25rem;">
                        <span>Total Paid</span>
                        <span style="color:var(--primary);">KES <?php echo number_format($order['total'], 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Delivery Info -->
            <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-lg); padding:1.5rem; margin-bottom:1.5rem; text-align:left;">
                <h3 style="font-size:0.95rem; font-weight:600; margin-bottom:1rem;">
                    <i class="fa-solid fa-location-dot" style="color:var(--primary); margin-right:0.5rem;"></i>Delivering To
                </h3>
                <p style="font-weight:600;"><?php echo clean($order['full_name']); ?></p>
                <p style="color:var(--text-muted); font-size:0.88rem;"><?php echo clean($order['address']); ?>, <?php echo clean($order['city']); ?></p>
                <p style="color:var(--text-muted); font-size:0.88rem;"><?php echo clean($order['phone']); ?> · <?php echo clean($order['email']); ?></p>
            </div>
            <?php endif; ?>

            <!-- Next Steps -->
            <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-lg); padding:1.5rem; margin-bottom:2rem; text-align:left;">
                <h3 style="font-size:0.95rem; font-weight:600; margin-bottom:1rem;">
                    <i class="fa-solid fa-clipboard-list" style="color:var(--primary); margin-right:0.5rem;"></i>What Happens Next
                </h3>
                <?php foreach ([
                    ['fa-envelope','Confirmation Email','You will receive an order receipt email shortly.'],
                    ['fa-box','Processing','Your items will be packed within 1-2 business days.'],
                    ['fa-truck','Delivery','Delivered to your address within 3-7 business days.'],
                ] as [$icon, $title, $desc]): ?>
                <div style="display:flex; gap:1rem; align-items:flex-start; margin-bottom:0.9rem;">
                    <div style="width:34px; height:34px; background:var(--primary-light); border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <i class="fa-solid <?php echo $icon; ?>" style="color:var(--primary); font-size:0.8rem;"></i>
                    </div>
                    <div>
                        <p style="font-weight:600; font-size:0.88rem;"><?php echo $title; ?></p>
                        <p style="color:var(--text-muted); font-size:0.82rem;"><?php echo $desc; ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Buttons -->
            <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
                <a href="../index.php" class="btn btn-primary">
                    <i class="fa-solid fa-house"></i> Back to Home
                </a>
                <a href="shop.php" class="btn btn-outline">
                    <i class="fa-solid fa-bag-shopping"></i> Continue Shopping
                </a>
            </div>

        </div>
    </div>
</section>

<?php require_once '../php/includes/footer.php'; ?>

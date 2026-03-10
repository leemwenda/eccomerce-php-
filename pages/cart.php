<?php
$pageTitle = 'Shopping Cart';
require_once '../php/config/database.php';
require_once '../php/config/helpers.php';
require_once '../php/includes/header.php';

$cartItems = getCartItems($pdo);
$subtotal  = 0;
foreach ($cartItems as $item) {
    $price     = !empty($item['sale_price']) ? $item['sale_price'] : $item['price'];
    $subtotal += $price * $item['quantity'];
}
$shipping = $subtotal >= 200 ? 0 : 15;
$total    = $subtotal + $shipping;
?>

<!-- ===== PAGE BANNER ===== -->
<section style="background: var(--bg-alt); border-bottom: 1px solid var(--border); padding: 2.5rem 0;">
    <div class="container">
        <h1 style="font-family: var(--font-display); font-size: 2.25rem; font-weight: 600; margin-bottom: 0.35rem;">
            Shopping Cart
        </h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem;">
            <a href="../index.php" style="color: var(--text-muted);">
                <i class="fa-solid fa-house"></i> Home
            </a>
            <i class="fa-solid fa-chevron-right" style="font-size: 0.6rem;"></i>
            <span>Cart</span>
        </p>
    </div>
</section>

<!-- ===== CART CONTENT ===== -->
<section class="section">
    <div class="container">

        <?php if (empty($cartItems)): ?>
        <!-- ===== EMPTY CART ===== -->
        <div style="text-align: center; padding: 5rem 2rem; max-width: 480px; margin: 0 auto;">
            <div style="width: 100px; height: 100px; background: var(--bg-alt); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                <i class="fa-solid fa-bag-shopping" style="font-size: 2.5rem; color: var(--border);"></i>
            </div>
            <h2 style="font-family: var(--font-display); font-size: 1.75rem; margin-bottom: 0.75rem;">
                Your cart is empty
            </h2>
            <p style="color: var(--text-muted); margin-bottom: 2rem; line-height: 1.7;">
                Looks like you have not added anything to your cart yet. Browse our collection and find something you love.
            </p>
            <a href="shop.php" class="btn btn-primary">
                <i class="fa-solid fa-arrow-left"></i>
                Continue Shopping
            </a>
        </div>

        <?php else: ?>
        <!-- ===== CART WITH ITEMS ===== -->
        <div style="display: grid; grid-template-columns: 1fr 380px; gap: 2.5rem; align-items: start;">

            <!-- ===== LEFT - CART ITEMS ===== -->
            <div>
                <!-- Cart Header -->
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 40px; gap: 1rem; padding: 0.75rem 1.25rem; background: var(--bg-alt); border: 1px solid var(--border); border-radius: var(--radius) var(--radius) 0 0; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted); font-weight: 600;">
                    <span>Product</span>
                    <span style="text-align: center;">Price</span>
                    <span style="text-align: center;">Quantity</span>
                    <span style="text-align: right;">Total</span>
                    <span></span>
                </div>

                <!-- Cart Items -->
                <div style="border: 1px solid var(--border); border-top: none; border-radius: 0 0 var(--radius) var(--radius); overflow: hidden; background: var(--bg-card);">
                    <?php foreach ($cartItems as $index => $item):
                        $price     = !empty($item['sale_price']) ? $item['sale_price'] : $item['price'];
                        $itemTotal = $price * $item['quantity'];
                    ?>
                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 40px; gap: 1rem; padding: 1.25rem; align-items: center; <?php echo $index > 0 ? 'border-top: 1px solid var(--border);' : ''; ?>">

                        <!-- Product Info -->
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <a href="product.php?slug=<?php echo clean($item['slug'] ?? ''); ?>">
                                <img src="<?php echo !empty($item['image']) ? clean($item['image']) : 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=200&q=80'; ?>"
                                     alt="<?php echo clean($item['name']); ?>"
                                     style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--radius); border: 1px solid var(--border); flex-shrink: 0;">
                            </a>
                            <div>
                                <a href="product.php?slug=<?php echo clean($item['slug'] ?? ''); ?>"
                                   style="font-weight: 600; font-size: 0.95rem; color: var(--text); display: block; margin-bottom: 0.25rem; line-height: 1.3;">
                                    <?php echo clean($item['name']); ?>
                                </a>
                                <?php if ($item['stock'] < 5): ?>
                                    <span style="font-size: 0.75rem; color: #D97706;">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        Only <?php echo $item['stock']; ?> left
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Price -->
                        <div style="text-align: center;">
                            <span style="font-weight: 600; color: var(--primary);" data-price="<?php echo $price; ?>">
                                <?php echo formatPrice($price); ?>
                            </span>
                            <?php if (!empty($item['sale_price'])): ?>
                                <span style="display: block; font-size: 0.78rem; text-decoration: line-through; color: var(--text-muted);" data-price="<?php echo $item['price']; ?>">
                                    <?php echo formatPrice($item['price']); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Quantity -->
                        <div style="display: flex; align-items: center; justify-content: center;">
                            <div style="display: flex; align-items: center; border: 1.5px solid var(--border); border-radius: var(--radius); overflow: hidden; background: var(--bg);">
                                <button onclick="updateCartItem(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)"
                                        style="width: 32px; height: 36px; background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s;"
                                        onmouseover="this.style.background='var(--bg-alt)'"
                                        onmouseout="this.style.background='none'">
                                    <i class="fa-solid fa-minus"></i>
                                </button>
                                <span style="width: 36px; text-align: center; font-weight: 700; font-size: 0.95rem; border-left: 1px solid var(--border); border-right: 1px solid var(--border); line-height: 36px;">
                                    <?php echo $item['quantity']; ?>
                                </span>
                                <button onclick="updateCartItem(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)"
                                        style="width: 32px; height: 36px; background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s;"
                                        onmouseover="this.style.background='var(--bg-alt)'"
                                        onmouseout="this.style.background='none'">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Item Total -->
                        <div style="text-align: right; font-weight: 700; font-size: 1rem;" data-price="<?php echo $itemTotal; ?>">
                            <?php echo formatPrice($itemTotal); ?>
                        </div>

                        <!-- Remove -->
                        <div style="text-align: center;">
                            <button onclick="removeCartItem(<?php echo $item['id']; ?>)"
                                    style="width: 32px; height: 32px; background: none; border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; font-size: 0.8rem;"
                                    onmouseover="this.style.borderColor='var(--danger)'; this.style.color='var(--danger)'; this.style.background='#FEE2E2';"
                                    onmouseout="this.style.borderColor='var(--border)'; this.style.color='var(--text-muted)'; this.style.background='none';"
                                    title="Remove item">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Continue Shopping -->
                <div style="margin-top: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                    <a href="shop.php" style="display: inline-flex; align-items: center; gap: 0.5rem; color: var(--text-muted); font-size: 0.9rem; font-weight: 500; transition: color 0.2s;"
                       onmouseover="this.style.color='var(--primary)'"
                       onmouseout="this.style.color='var(--text-muted)'">
                        <i class="fa-solid fa-arrow-left"></i>
                        Continue Shopping
                    </a>
                </div>
            </div>

            <!-- ===== RIGHT - ORDER SUMMARY ===== -->
            <div style="position: sticky; top: 90px;">
                <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden;">

                    <!-- Summary Header -->
                    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); background: var(--bg-alt);">
                        <h3 style="font-family: var(--font-display); font-size: 1.15rem; font-weight: 600;">
                            <i class="fa-solid fa-receipt" style="color: var(--primary); margin-right: 0.5rem;"></i>
                            Order Summary
                        </h3>
                    </div>

                    <!-- Summary Body -->
                    <div style="padding: 1.5rem;">

                        <!-- Subtotal -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; font-size: 0.95rem;">
                            <span style="color: var(--text-muted);">
                                Subtotal (<?php echo count($cartItems); ?> items)
                            </span>
                            <span style="font-weight: 600;" data-price="<?php echo $subtotal; ?>">
                                <?php echo formatPrice($subtotal); ?>
                            </span>
                        </div>

                        <!-- Shipping -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; font-size: 0.95rem;">
                            <span style="color: var(--text-muted);">Shipping</span>
                            <?php if ($shipping === 0): ?>
                                <span style="color: var(--accent); font-weight: 600;">
                                    <i class="fa-solid fa-circle-check"></i> Free
                                </span>
                            <?php else: ?>
                                <span style="font-weight: 600;" data-price="<?php echo $shipping; ?>">
                                    <?php echo formatPrice($shipping); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Free shipping progress -->
                        <?php if ($shipping > 0): ?>
                            <?php $remaining = 200 - $subtotal; ?>
                            <div style="background: var(--bg-alt); border: 1px solid var(--border); border-radius: var(--radius); padding: 0.85rem; margin-bottom: 0.85rem;">
                                <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                                    <i class="fa-solid fa-truck" style="color: var(--primary);"></i>
                                    Add <strong style="color: var(--primary);" data-price="<?php echo $remaining; ?>"><?php echo formatPrice($remaining); ?></strong> more for free shipping
                                </p>
                                <div style="background: var(--border); border-radius: 2rem; height: 6px; overflow: hidden;">
                                    <div style="background: var(--primary); height: 100%; width: <?php echo min(100, ($subtotal / 200) * 100); ?>%; border-radius: 2rem; transition: width 0.3s;"></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Divider -->
                        <hr style="border: none; border-top: 1px solid var(--border); margin: 1rem 0;">

                        <!-- Total -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <span style="font-size: 1.1rem; font-weight: 700;">Total</span>
                            <span style="font-size: 1.35rem; font-weight: 700; color: var(--primary);" data-price="<?php echo $total; ?>">
                                <?php echo formatPrice($total); ?>
                            </span>
                        </div>

                        <!-- Checkout Button -->
                        <a href="checkout.php" class="btn btn-primary" style="width: 100%; justify-content: center; font-size: 1rem; padding: 1rem;">
                            <i class="fa-solid fa-lock"></i>
                            Proceed to Checkout
                        </a>

                        <!-- Payment icons -->
                        <div style="margin-top: 1.25rem; text-align: center;">
                            <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.65rem;">
                                <i class="fa-solid fa-shield-halved" style="color: var(--accent);"></i>
                                Secure & Encrypted Checkout
                            </p>
                            <div style="display: flex; justify-content: center; align-items: center; gap: 0.75rem; font-size: 1.5rem; color: var(--text-muted);">
                                <i class="fa-brands fa-cc-visa" title="Visa"></i>
                                <i class="fa-brands fa-cc-mastercard" title="Mastercard"></i>
                                <i class="fa-solid fa-mobile-screen" title="M-Pesa" style="font-size: 1.1rem;"></i>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once '../php/includes/footer.php'; ?>
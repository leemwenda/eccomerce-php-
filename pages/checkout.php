<?php
$pageTitle = 'Checkout';
require_once '../php/config/database.php';
require_once '../php/config/helpers.php';
require_once '../php/includes/header.php';

// Redirect to cart if cart is empty
$cartItems = getCartItems($pdo);
if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

$subtotal = 0;
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
            Checkout
        </h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem;">
            <a href="../index.php" style="color: var(--text-muted);">
                <i class="fa-solid fa-house"></i> Home
            </a>
            <i class="fa-solid fa-chevron-right" style="font-size: 0.6rem;"></i>
            <a href="cart.php" style="color: var(--text-muted);">Cart</a>
            <i class="fa-solid fa-chevron-right" style="font-size: 0.6rem;"></i>
            <span>Checkout</span>
        </p>
    </div>
</section>

<!-- ===== CHECKOUT STEPS ===== -->
<div style="background: var(--bg-card); border-bottom: 1px solid var(--border); padding: 1rem 0;">
    <div class="container">
        <div style="display: flex; align-items: center; justify-content: center; gap: 0; max-width: 500px; margin: 0 auto;">
            <!-- Step 1 -->
            <div style="display: flex; align-items: center; gap: 0.6rem;">
                <div style="width: 30px; height: 30px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700;">
                    <i class="fa-solid fa-check"></i>
                </div>
                <span style="font-size: 0.85rem; font-weight: 600; color: var(--primary);">Cart</span>
            </div>
            <div style="flex: 1; height: 2px; background: var(--primary); margin: 0 0.75rem;"></div>
            <!-- Step 2 -->
            <div style="display: flex; align-items: center; gap: 0.6rem;">
                <div style="width: 30px; height: 30px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700;">2</div>
                <span style="font-size: 0.85rem; font-weight: 600; color: var(--primary);">Checkout</span>
            </div>
            <div style="flex: 1; height: 2px; background: var(--border); margin: 0 0.75rem;"></div>
            <!-- Step 3 -->
            <div style="display: flex; align-items: center; gap: 0.6rem;">
                <div style="width: 30px; height: 30px; border-radius: 50%; background: var(--border); color: var(--text-muted); display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700;">3</div>
                <span style="font-size: 0.85rem; color: var(--text-muted);">Payment</span>
            </div>
        </div>
    </div>
</div>

<!-- ===== CHECKOUT CONTENT ===== -->
<section class="section">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 400px; gap: 2.5rem; align-items: start;">

            <!-- ===== LEFT - FORMS ===== -->
            <div>

                <!-- ===== DELIVERY DETAILS ===== -->
                <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 1.5rem;">
                    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); background: var(--bg-alt);">
                        <h3 style="font-family: var(--font-display); font-size: 1.1rem; font-weight: 600; display: flex; align-items: center; gap: 0.65rem;">
                            <i class="fa-solid fa-location-dot" style="color: var(--primary);"></i>
                            Delivery Details
                        </h3>
                    </div>
                    <div style="padding: 1.5rem;">
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label class="form-label" style="display: block; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 0.5rem;">First Name *</label>
                                <div style="position: relative;">
                                    <i class="fa-solid fa-user" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
                                    <input type="text" id="first_name" placeholder="John" required
                                           style="width: 100%; padding: 0.85rem 1rem 0.85rem 2.75rem; border: 1.5px solid var(--border); border-radius: var(--radius); font-size: 0.95rem; background: var(--bg); color: var(--text); font-family: var(--font-body); transition: border-color 0.2s;"
                                           onfocus="this.style.borderColor='var(--primary)'"
                                           onblur="this.style.borderColor='var(--border)'">
                                </div>
                            </div>
                            <div class="form-group">
                                <label style="display: block; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 0.5rem;">Last Name *</label>
                                <div style="position: relative;">
                                    <i class="fa-solid fa-user" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
                                    <input type="text" id="last_name" placeholder="Doe" required
                                           style="width: 100%; padding: 0.85rem 1rem 0.85rem 2.75rem; border: 1.5px solid var(--border); border-radius: var(--radius); font-size: 0.95rem; background: var(--bg); color: var(--text); font-family: var(--font-body); transition: border-color 0.2s;"
                                           onfocus="this.style.borderColor='var(--primary)'"
                                           onblur="this.style.borderColor='var(--border)'">
                                </div>
                            </div>
                        </div>
                        <div class="form-grid-2" style="margin-top: 1rem;">
                            <div class="form-group">
                                <label style="display: block; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 0.5rem;">Email Address *</label>
                                <div style="position: relative;">
                                    <i class="fa-solid fa-envelope" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
                                    <input type="email" id="email" placeholder="john@example.com" required
                                           style="width: 100%; padding: 0.85rem 1rem 0.85rem 2.75rem; border: 1.5px solid var(--border); border-radius: var(--radius); font-size: 0.95rem; background: var(--bg); color: var(--text); font-family: var(--font-body); transition: border-color 0.2s;"
                                           onfocus="this.style.borderColor='var(--primary)'"
                                           onblur="this.style.borderColor='var(--border)'">
                                </div>
                            </div>
                            <div class="form-group">
                                <label style="display: block; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 0.5rem;">Phone Number *</label>
                                <div style="position: relative;">
                                    <i class="fa-solid fa-phone" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
                                    <input type="tel" id="phone" placeholder="0712 345 678" required
                                           style="width: 100%; padding: 0.85rem 1rem 0.85rem 2.75rem; border: 1.5px solid var(--border); border-radius: var(--radius); font-size: 0.95rem; background: var(--bg); color: var(--text); font-family: var(--font-body); transition: border-color 0.2s;"
                                           onfocus="this.style.borderColor='var(--primary)'"
                                           onblur="this.style.borderColor='var(--border)'">
                                </div>
                            </div>
                        </div>
                        <div style="margin-top: 1rem;">
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 0.5rem;">Street Address *</label>
                            <div style="position: relative;">
                                <i class="fa-solid fa-map-pin" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
                                <input type="text" id="address" placeholder="Street, Building, House No." required
                                       style="width: 100%; padding: 0.85rem 1rem 0.85rem 2.75rem; border: 1.5px solid var(--border); border-radius: var(--radius); font-size: 0.95rem; background: var(--bg); color: var(--text); font-family: var(--font-body); transition: border-color 0.2s;"
                                       onfocus="this.style.borderColor='var(--primary)'"
                                       onblur="this.style.borderColor='var(--border)'">
                            </div>
                        </div>
                        <div class="form-grid-2" style="margin-top: 1rem;">
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 0.5rem;">City *</label>
                                <div style="position: relative;">
                                    <i class="fa-solid fa-city" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
                                    <input type="text" id="city" placeholder="Nairobi" required
                                           style="width: 100%; padding: 0.85rem 1rem 0.85rem 2.75rem; border: 1.5px solid var(--border); border-radius: var(--radius); font-size: 0.95rem; background: var(--bg); color: var(--text); font-family: var(--font-body); transition: border-color 0.2s;"
                                           onfocus="this.style.borderColor='var(--primary)'"
                                           onblur="this.style.borderColor='var(--border)'">
                                </div>
                            </div>
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-muted); margin-bottom: 0.5rem;">County / Region</label>
                                <div style="position: relative;">
                                    <i class="fa-solid fa-map" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.85rem;"></i>
                                    <input type="text" id="county" placeholder="Nairobi County"
                                           style="width: 100%; padding: 0.85rem 1rem 0.85rem 2.75rem; border: 1.5px solid var(--border); border-radius: var(--radius); font-size: 0.95rem; background: var(--bg); color: var(--text); font-family: var(--font-body); transition: border-color 0.2s;"
                                           onfocus="this.style.borderColor='var(--primary)'"
                                           onblur="this.style.borderColor='var(--border)'">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ===== PAYMENT METHOD ===== -->
                <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden;">
                    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); background: var(--bg-alt);">
                        <h3 style="font-family: var(--font-display); font-size: 1.1rem; font-weight: 600; display: flex; align-items: center; gap: 0.65rem;">
                            <i class="fa-solid fa-credit-card" style="color: var(--primary);"></i>
                            Payment Method
                        </h3>
                    </div>
                    <div style="padding: 1.5rem;">
                        <!-- Pesapal Option -->
                        <div style="border: 2px solid var(--primary); border-radius: var(--radius); padding: 1.25rem; display: flex; align-items: center; gap: 1rem; background: var(--primary-light);">
                            <div style="width: 20px; height: 20px; border-radius: 50%; background: var(--primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fa-solid fa-check" style="color: white; font-size: 0.65rem;"></i>
                            </div>
                            <div style="flex: 1;">
                                <p style="font-weight: 700; font-size: 0.95rem; margin-bottom: 0.2rem;">Pay via Pesapal</p>
                                <p style="font-size: 0.82rem; color: var(--text-muted);">M-Pesa, Visa, Mastercard and more — all secured by Pesapal</p>
                            </div>
                            <div style="display: flex; gap: 0.5rem; font-size: 1.4rem; color: var(--text-muted);">
                                <i class="fa-solid fa-mobile-screen" title="M-Pesa" style="font-size: 1rem;"></i>
                                <i class="fa-brands fa-cc-visa" title="Visa"></i>
                                <i class="fa-brands fa-cc-mastercard" title="Mastercard"></i>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ===== RIGHT - ORDER SUMMARY ===== -->
            <div style="position: sticky; top: 90px;">
                <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden;">

                    <!-- Header -->
                    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); background: var(--bg-alt);">
                        <h3 style="font-family: var(--font-display); font-size: 1.1rem; font-weight: 600;">
                            <i class="fa-solid fa-basket-shopping" style="color: var(--primary); margin-right: 0.5rem;"></i>
                            Your Order (<?php echo count($cartItems); ?> items)
                        </h3>
                    </div>

                    <!-- Items -->
                    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); max-height: 260px; overflow-y: auto;">
                        <?php foreach ($cartItems as $item):
                            $price     = !empty($item['sale_price']) ? $item['sale_price'] : $item['price'];
                            $itemTotal = $price * $item['quantity'];
                        ?>
                            <div style="display: flex; gap: 0.85rem; align-items: center; <?php echo $item !== reset($cartItems) ? 'margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border);' : ''; ?>">
                                <img src="<?php echo !empty($item['image']) ? clean($item['image']) : 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=200&q=80'; ?>"
                                     alt="<?php echo clean($item['name']); ?>"
                                     style="width: 56px; height: 56px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border); flex-shrink: 0;">
                                <div style="flex: 1; min-width: 0;">
                                    <p style="font-size: 0.88rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo clean($item['name']); ?>
                                    </p>
                                    <p style="font-size: 0.78rem; color: var(--text-muted);">Qty: <?php echo $item['quantity']; ?></p>
                                </div>
                                <span style="font-weight: 700; font-size: 0.9rem; flex-shrink: 0;" data-price="<?php echo $itemTotal; ?>">
                                    <?php echo formatPrice($itemTotal); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Totals -->
                    <div style="padding: 1.25rem 1.5rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; font-size: 0.9rem;">
                            <span style="color: var(--text-muted);">Subtotal</span>
                            <span style="font-weight: 600;" data-price="<?php echo $subtotal; ?>"><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 0.9rem;">
                            <span style="color: var(--text-muted);">Shipping</span>
                            <?php if ($shipping === 0): ?>
                                <span style="color: var(--accent); font-weight: 600;">Free</span>
                            <?php else: ?>
                                <span style="font-weight: 600;" data-price="<?php echo $shipping; ?>"><?php echo formatPrice($shipping); ?></span>
                            <?php endif; ?>
                        </div>
                        <hr style="border: none; border-top: 2px solid var(--border); margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <span style="font-size: 1.1rem; font-weight: 700;">Total</span>
                            <span style="font-size: 1.4rem; font-weight: 700; color: var(--primary);" data-price="<?php echo $total; ?>"><?php echo formatPrice($total); ?></span>
                        </div>

                        <!-- Place Order Button -->
                        <button onclick="placeOrder()" class="btn btn-primary" id="placeOrderBtn"
                                style="width: 100%; justify-content: center; font-size: 1rem; padding: 1rem;">
                            <i class="fa-solid fa-lock"></i>
                            Place Order & Pay
                        </button>

                        <p style="text-align: center; font-size: 0.75rem; color: var(--text-muted); margin-top: 0.85rem; display: flex; align-items: center; justify-content: center; gap: 0.4rem;">
                            <i class="fa-solid fa-shield-halved" style="color: var(--accent);"></i>
                            Your payment is secured by Pesapal
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
function placeOrder() {
    // Validate all required fields
    const fields = {
        'First Name':  document.getElementById('first_name').value.trim(),
        'Last Name':   document.getElementById('last_name').value.trim(),
        'Email':       document.getElementById('email').value.trim(),
        'Phone':       document.getElementById('phone').value.trim(),
        'Address':     document.getElementById('address').value.trim(),
        'City':        document.getElementById('city').value.trim(),
    };

    for (const [name, value] of Object.entries(fields)) {
        if (!value) {
            alert('Please fill in your ' + name);
            return;
        }
    }

    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(fields['Email'])) {
        alert('Please enter a valid email address');
        return;
    }

    // Show loading state
    const btn = document.getElementById('placeOrderBtn');
    btn.disabled   = true;
    btn.innerHTML  = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';

    // Send to Pesapal API
    fetch('../php/api/pesapal-pay.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            first_name: fields['First Name'],
            last_name:  fields['Last Name'],
            email:      fields['Email'],
            phone:      fields['Phone'],
            address:    fields['Address'],
            city:       fields['City'],
            county:     document.getElementById('county').value.trim(),
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.redirect_url) {
            window.location.href = data.redirect_url;
        } else {
            alert('Payment error: ' + (data.message || 'Please try again'));
            btn.disabled  = false;
            btn.innerHTML = '<i class="fa-solid fa-lock"></i> Place Order & Pay';
        }
    })
    .catch(() => {
        alert('Network error. Please check your connection and try again.');
        btn.disabled  = false;
        btn.innerHTML = '<i class="fa-solid fa-lock"></i> Place Order & Pay';
    });
}
</script>

<?php require_once '../php/includes/footer.php'; ?>
<?php
session_start();
require_once '../php/config/database.php';
require_once '../php/config/helpers.php';
requireAdmin();

$success = '';
$error   = '';

// Ensure settings table has delivery rows
$defaults = [
    'delivery_fee_usd'          => '15',      // shown in cart/checkout (USD)
    'free_shipping_threshold_usd' => '100',   // free shipping above this USD amount
    'delivery_fee_kes'          => '350',      // sent to Pesapal (KES)
    'free_shipping_threshold_kes' => '5000',  // free shipping above this KES amount
];
foreach ($defaults as $key => $val) {
    $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)")
        ->execute([$key, $val]);
}

// Save changes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feeUsd       = (float)($_POST['delivery_fee_usd']            ?? 15);
    $thresholdUsd = (float)($_POST['free_shipping_threshold_usd'] ?? 100);
    // Auto-calculate KES from USD (rate = 130)
    $feeKes       = round($feeUsd * 130, 2);
    $thresholdKes = round($thresholdUsd * 130, 2);

    $updates = [
        'delivery_fee_usd'            => $feeUsd,
        'free_shipping_threshold_usd' => $thresholdUsd,
        'delivery_fee_kes'            => $feeKes,
        'free_shipping_threshold_kes' => $thresholdKes,
    ];
    foreach ($updates as $key => $val) {
        $pdo->prepare("UPDATE settings SET setting_value=? WHERE setting_key=?")
            ->execute([$val, $key]);
    }
    $success = "Delivery settings saved! Fee: \${$feeUsd} USD (KES {$feeKes}). Free shipping over: \${$thresholdUsd} USD (KES {$thresholdKes}).";
}

// Load current values
$settings = [];
$rows = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'delivery%' OR setting_key LIKE 'free_shipping%'")->fetchAll();
foreach ($rows as $row) $settings[$row['setting_key']] = $row['setting_value'];

$feeUsd       = $settings['delivery_fee_usd']            ?? 1;
$thresholdUsd = $settings['free_shipping_threshold_usd'] ?? 1;
$feeKes       = $settings['delivery_fee_kes']            ?? 15;
$thresholdKes = $settings['free_shipping_threshold_kes'] ?? 10;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delivery Settings — Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<script>const savedTheme=localStorage.getItem('theme')||'light';document.documentElement.setAttribute('data-theme',savedTheme);</script>
</head>
<body>
<div class="admin-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-logo"><i class="fa-solid fa-store"></i> Maison Decor</div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fa-solid fa-box"></i> Products</a></li>
            <li><a href="orders.php"><i class="fa-solid fa-cart-shopping"></i> Orders</a></li>
            <li><a href="customers.php"><i class="fa-solid fa-users"></i> Customers</a></li>
            <li><a href="categories.php"><i class="fa-solid fa-tags"></i> Categories</a></li>
            <li><a href="import.php"><i class="fa-solid fa-file-import"></i> CSV Import</a></li>
            <li><a href="delivery-settings.php" class="active"><i class="fa-solid fa-truck"></i> Delivery</a></li>
        </ul>
        <hr class="sidebar-divider">
        <ul class="sidebar-menu">
            <li><a href="../index.php" target="_blank"><i class="fa-solid fa-globe"></i> View Website</a></li>
            <li><a href="#" onclick="toggleTheme();return false;"><i class="fa-solid fa-moon" id="themeIcon"></i> Dark Mode</a></li>
            <li><a href="logout.php" style="color:#FCA5A5;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1><i class="fa-solid fa-truck" style="color:var(--primary);margin-right:0.5rem;"></i>Delivery Settings</h1>
                <p style="color:var(--text-muted);font-size:0.85rem;margin-top:0.2rem;">Set your delivery fee and free shipping threshold</p>
            </div>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo clean($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo clean($error); ?></div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;max-width:860px;">

            <!-- FORM -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3><i class="fa-solid fa-pen-to-square" style="color:var(--primary);margin-right:0.5rem;"></i>Edit Charges</h3>
                </div>
                <div style="padding:1.75rem;">
                    <form method="POST">

                        <div style="margin-bottom:1.5rem;">
                            <label style="display:block;font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin-bottom:0.5rem;">
                                Delivery Fee (USD $)
                            </label>
                            <div style="position:relative;">
                                <span style="position:absolute;left:0.9rem;top:50%;transform:translateY(-50%);color:var(--text-muted);font-weight:600;">$</span>
                                <input type="number" name="delivery_fee_usd" step="0.01" min="0" required
                                       value="<?php echo $feeUsd; ?>"
                                       class="form-control" style="padding-left:2rem;"
                                       oninput="updatePreview()">
                            </div>
                            <p style="font-size:0.78rem;color:var(--text-muted);margin-top:0.35rem;">
                                This is shown to customers in the cart. KES equivalent is calculated automatically (×130).
                            </p>
                        </div>

                        <div style="margin-bottom:1.75rem;">
                            <label style="display:block;font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin-bottom:0.5rem;">
                                Free Shipping Threshold (USD $)
                            </label>
                            <div style="position:relative;">
                                <span style="position:absolute;left:0.9rem;top:50%;transform:translateY(-50%);color:var(--text-muted);font-weight:600;">$</span>
                                <input type="number" name="free_shipping_threshold_usd" step="0.01" min="0" required
                                       value="<?php echo $thresholdUsd; ?>"
                                       class="form-control" style="padding-left:2rem;"
                                       oninput="updatePreview()">
                            </div>
                            <p style="font-size:0.78rem;color:var(--text-muted);margin-top:0.35rem;">
                                Orders above this amount get free shipping. Set to 0 to always charge delivery.
                            </p>
                        </div>

                        <!-- Live Preview -->
                        <div id="preview" style="background:var(--bg-alt);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;margin-bottom:1.5rem;">
                            <p style="font-size:0.78rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin-bottom:0.6rem;">Preview</p>
                            <div style="display:flex;justify-content:space-between;font-size:0.88rem;margin-bottom:0.3rem;">
                                <span>Delivery fee charged:</span>
                                <strong id="prev-fee">$<?php echo $feeUsd; ?> (KES <?php echo $feeKes; ?>)</strong>
                            </div>
                            <div style="display:flex;justify-content:space-between;font-size:0.88rem;">
                                <span>Free shipping above:</span>
                                <strong id="prev-threshold">$<?php echo $thresholdUsd; ?> (KES <?php echo $thresholdKes; ?>)</strong>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:0.9rem;">
                            <i class="fa-solid fa-floppy-disk"></i> Save Settings
                        </button>
                    </form>
                </div>
            </div>

            <!-- INFO CARD -->
            <div>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3><i class="fa-solid fa-circle-info" style="color:var(--primary);margin-right:0.5rem;"></i>How It Works</h3>
                    </div>
                    <div style="padding:1.5rem;display:flex;flex-direction:column;gap:1.1rem;">
                        <?php foreach ([
                            ['fa-cart-shopping','Cart & Checkout','The delivery fee in USD is shown to customers on the cart and checkout pages.'],
                            ['fa-mobile-screen','Pesapal Payment','The KES equivalent (USD × 130) is sent to Pesapal as the actual charge.'],
                            ['fa-truck','Free Shipping','When a cart subtotal exceeds the threshold you set, delivery is automatically free.'],
                            ['fa-rotate','Live Sync','Changes take effect immediately — no code editing needed.'],
                        ] as [$icon, $title, $desc]): ?>
                        <div style="display:flex;gap:0.9rem;align-items:flex-start;">
                            <div style="width:36px;height:36px;background:var(--primary-light);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fa-solid <?php echo $icon; ?>" style="color:var(--primary);font-size:0.85rem;"></i>
                            </div>
                            <div>
                                <p style="font-weight:600;font-size:0.88rem;margin-bottom:0.2rem;"><?php echo $title; ?></p>
                                <p style="color:var(--text-muted);font-size:0.82rem;line-height:1.6;"><?php echo $desc; ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Current Values Display -->
                <div class="admin-card" style="margin-top:1.5rem;">
                    <div class="admin-card-header">
                        <h3><i class="fa-solid fa-gauge" style="color:var(--primary);margin-right:0.5rem;"></i>Current Live Values</h3>
                    </div>
                    <div style="padding:1.5rem;">
                        <table style="width:100%;font-size:0.88rem;border-collapse:collapse;">
                            <tr style="border-bottom:1px solid var(--border);">
                                <td style="padding:0.6rem 0;color:var(--text-muted);">Delivery fee</td>
                                <td style="padding:0.6rem 0;text-align:right;font-weight:700;">$<?php echo $feeUsd; ?> <span style="color:var(--text-muted);font-weight:400;">/ KES <?php echo number_format($feeKes,2); ?></span></td>
                            </tr>
                            <tr style="border-bottom:1px solid var(--border);">
                                <td style="padding:0.6rem 0;color:var(--text-muted);">Free shipping above</td>
                                <td style="padding:0.6rem 0;text-align:right;font-weight:700;">$<?php echo $thresholdUsd; ?> <span style="color:var(--text-muted);font-weight:400;">/ KES <?php echo number_format($thresholdKes,2); ?></span></td>
                            </tr>
                            <tr>
                                <td style="padding:0.6rem 0;color:var(--text-muted);">Exchange rate used</td>
                                <td style="padding:0.6rem 0;text-align:right;font-weight:700;">1 USD = 130 KES</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<div class="toast" id="toast"></div>
<script src="../assets/js/main.js"></script>
<script>
function updatePreview() {
    const fee = parseFloat(document.querySelector('[name=delivery_fee_usd]').value) || 0;
    const threshold = parseFloat(document.querySelector('[name=free_shipping_threshold_usd]').value) || 0;
    const rate = 130;
    document.getElementById('prev-fee').textContent = '$' + fee.toFixed(2) + ' (KES ' + (fee * rate).toFixed(2) + ')';
    document.getElementById('prev-threshold').textContent = '$' + threshold.toFixed(2) + ' (KES ' + (threshold * rate).toFixed(2) + ')';
}
</script>
</body>
</html>

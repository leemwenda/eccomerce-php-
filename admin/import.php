<?php
session_start();
require_once '../php/config/database.php';
require_once '../php/config/helpers.php';
requireAdmin();

$results = [];
$error   = '';

// Get categories for reference
$categories = getCategories($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['csv_file']['name'])) {
    $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));

    if ($ext !== 'csv') {
        $error = 'Please upload a valid CSV file.';
    } else {
        $file    = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $headers = fgetcsv($file); // Skip header row
        $row     = 0;
        $success = 0;
        $failed  = 0;

        while (($data = fgetcsv($file)) !== false) {
            $row++;
            if (count($data) < 4) {
                $results[] = ['row' => $row, 'status' => 'error', 'msg' => 'Not enough columns — skipped'];
                $failed++;
                continue;
            }

            $name        = trim($data[0] ?? '');
            $categoryId  = (int)($data[1] ?? 0);
            $price       = (float)($data[2] ?? 0);
            $stock       = (int)($data[3] ?? 0);
            $description = trim($data[4] ?? '');
            $image       = trim($data[5] ?? '');
            $featured    = (int)($data[6] ?? 0);
            $tag         = trim($data[7] ?? '');
            $salePrice   = !empty($data[8]) ? (float)$data[8] : null;

            if (!$name || !$price) {
                $results[] = ['row' => $row, 'status' => 'error', 'msg' => "Row $row: Name and price are required — skipped"];
                $failed++;
                continue;
            }

            // Generate unique slug
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
            $slug = trim($slug, '-');

            $check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug = ?");
            $check->execute([$slug]);
            if ($check->fetchColumn() > 0) {
                $slug = $slug . '-' . time() . '-' . $row;
            }

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO products (name, slug, category_id, price, sale_price, stock, description, image, featured, tag)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $name,
                    $slug,
                    $categoryId ?: null,
                    $price,
                    $salePrice,
                    $stock,
                    $description,
                    $image,
                    $featured,
                    $tag
                ]);
                $results[] = ['row' => $row, 'status' => 'success', 'msg' => "\"$name\" imported successfully"];
                $success++;
            } catch (Exception $e) {
                $results[] = ['row' => $row, 'status' => 'error', 'msg' => "Row $row: " . $e->getMessage()];
                $failed++;
            }
        }

        fclose($file);
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV Import | Admin</title>
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
            <li><a href="customers.php"><i class="fa-solid fa-users"></i> Customers</a></li>
            <li><a href="categories.php"><i class="fa-solid fa-tags"></i> Categories</a></li>
            <li><a href="import.php" class="active"><i class="fa-solid fa-file-import"></i> CSV Import</a></li>
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

        <div class="admin-topbar">
            <div>
                <h1>CSV Import</h1>
                <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.2rem;">
                    Bulk import products from a CSV file
                </p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo clean($error); ?>
            </div>
        <?php endif; ?>

        <!-- Import Results -->
        <?php if (!empty($results)): ?>
            <?php
            $successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));
            $failedCount  = count(array_filter($results, fn($r) => $r['status'] === 'error'));
            ?>
            <div style="background:var(--bg-card); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; margin-bottom:1.5rem;">
                <div style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--border); background:var(--bg-alt); display:flex; align-items:center; justify-content:space-between;">
                    <h3 style="font-family:var(--font-display); font-size:1.1rem; font-weight:600;">
                        <i class="fa-solid fa-clipboard-list" style="color:var(--primary); margin-right:0.5rem;"></i>
                        Import Results
                    </h3>
                    <div style="display:flex; gap:1rem;">
                        <span style="color:var(--accent); font-weight:700; font-size:0.9rem;">
                            <i class="fa-solid fa-circle-check"></i> <?php echo $successCount; ?> imported
                        </span>
                        <?php if ($failedCount > 0): ?>
                            <span style="color:var(--danger); font-weight:700; font-size:0.9rem;">
                                <i class="fa-solid fa-circle-xmark"></i> <?php echo $failedCount; ?> failed
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="max-height:320px; overflow-y:auto; padding:1rem 1.5rem;">
                    <?php foreach ($results as $result): ?>
                        <div style="display:flex; align-items:flex-start; gap:0.75rem; padding:0.6rem 0; border-bottom:1px solid var(--border); font-size:0.875rem;">
                            <?php if ($result['status'] === 'success'): ?>
                                <i class="fa-solid fa-circle-check" style="color:var(--accent); margin-top:0.1rem; flex-shrink:0;"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-circle-xmark" style="color:var(--danger); margin-top:0.1rem; flex-shrink:0;"></i>
                            <?php endif; ?>
                            <span style="color:<?php echo $result['status'] === 'success' ? 'var(--text)' : 'var(--danger)'; ?>;">
                                <?php echo clean($result['msg']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($successCount > 0): ?>
                    <div style="padding:1rem 1.5rem; border-top:1px solid var(--border);">
                        <a href="products.php" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-box"></i>
                            View All Products
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:1fr 360px; gap:1.5rem; align-items:start;">

            <!-- Upload Form -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>
                        <i class="fa-solid fa-file-arrow-up" style="color:var(--primary); margin-right:0.5rem;"></i>
                        Upload CSV File
                    </h3>
                </div>
                <div style="padding:1.5rem;">
                    <form method="POST" enctype="multipart/form-data">

                        <!-- Drop zone -->
                        <div id="dropZone"
                             style="border:2px dashed var(--border); border-radius:var(--radius-lg); padding:3rem 2rem; text-align:center; cursor:pointer; transition:all 0.2s; margin-bottom:1.5rem; background:var(--bg);"
                             onclick="document.getElementById('csvFile').click()"
                             ondragover="event.preventDefault(); this.style.borderColor='var(--primary)'; this.style.background='var(--primary-light)';"
                             ondragleave="this.style.borderColor='var(--border)'; this.style.background='var(--bg)';"
                             ondrop="handleDrop(event)">
                            <i class="fa-solid fa-file-csv" style="font-size:3rem; color:var(--primary); display:block; margin-bottom:1rem;"></i>
                            <p style="font-weight:600; margin-bottom:0.35rem;">Click to choose CSV file</p>
                            <p style="color:var(--text-muted); font-size:0.85rem;">or drag and drop it here</p>
                            <p id="fileName" style="color:var(--primary); font-weight:600; margin-top:0.75rem; font-size:0.9rem;"></p>
                        </div>

                        <input type="file"
                               name="csv_file"
                               id="csvFile"
                               accept=".csv"
                               style="display:none;"
                               onchange="document.getElementById('fileName').textContent = this.files[0].name;">

                        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:1rem; font-size:1rem;">
                            <i class="fa-solid fa-file-import"></i>
                            Import Products
                        </button>

                    </form>
                </div>
            </div>

            <!-- Instructions -->
            <div>

                <!-- CSV Format -->
                <div class="admin-card" style="margin-bottom:1.5rem;">
                    <div class="admin-card-header">
                        <h3>
                            <i class="fa-solid fa-circle-info" style="color:var(--primary); margin-right:0.5rem;"></i>
                            CSV Format
                        </h3>
                    </div>
                    <div style="padding:1.25rem 1.5rem;">
                        <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1rem; line-height:1.6;">
                            Your CSV file must have these columns in this exact order:
                        </p>
                        <div style="display:flex; flex-direction:column; gap:0.5rem;">
                            <?php
                            $columns = [
                                ['col' => 'Column 1', 'name' => 'name',        'req' => true,  'desc' => 'Product name'],
                                ['col' => 'Column 2', 'name' => 'category_id', 'req' => false, 'desc' => 'Category ID number'],
                                ['col' => 'Column 3', 'name' => 'price',       'req' => true,  'desc' => 'Price e.g. 29.99'],
                                ['col' => 'Column 4', 'name' => 'stock',       'req' => true,  'desc' => 'Stock quantity'],
                                ['col' => 'Column 5', 'name' => 'description', 'req' => false, 'desc' => 'Product description'],
                                ['col' => 'Column 6', 'name' => 'image',       'req' => false, 'desc' => 'Image URL'],
                                ['col' => 'Column 7', 'name' => 'featured',    'req' => false, 'desc' => '1 = yes, 0 = no'],
                                ['col' => 'Column 8', 'name' => 'tag',         'req' => false, 'desc' => 'New, Sale or Popular'],
                                ['col' => 'Column 9', 'name' => 'sale_price',  'req' => false, 'desc' => 'Sale price e.g. 19.99'],
                            ];
                            foreach ($columns as $c):
                            ?>
                                <div style="display:flex; align-items:flex-start; gap:0.75rem; padding:0.5rem; background:var(--bg-alt); border-radius:var(--radius-sm);">
                                    <span style="font-size:0.72rem; font-weight:700; color:var(--text-muted); min-width:60px; padding-top:0.1rem;">
                                        <?php echo $c['col']; ?>
                                    </span>
                                    <div>
                                        <span style="font-weight:600; font-size:0.82rem; font-family:monospace; color:var(--primary);">
                                            <?php echo $c['name']; ?>
                                        </span>
                                        <?php if ($c['req']): ?>
                                            <span style="font-size:0.68rem; color:var(--danger); font-weight:700; margin-left:0.3rem;">*required</span>
                                        <?php endif; ?>
                                        <div style="font-size:0.78rem; color:var(--text-muted);"><?php echo $c['desc']; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Category IDs -->
                <div class="admin-card" style="margin-bottom:1.5rem;">
                    <div class="admin-card-header">
                        <h3>
                            <i class="fa-solid fa-tags" style="color:var(--primary); margin-right:0.5rem;"></i>
                            Category ID Reference
                        </h3>
                    </div>
                    <div style="padding:1.25rem 1.5rem;">
                        <?php if (empty($categories)): ?>
                            <p style="color:var(--text-muted); font-size:0.85rem;">
                                No categories yet.
                                <a href="categories.php" style="color:var(--primary);">Add categories first</a>
                            </p>
                        <?php else: ?>
                            <div style="display:flex; flex-direction:column; gap:0.4rem;">
                                <?php foreach ($categories as $cat): ?>
                                    <div style="display:flex; justify-content:space-between; align-items:center; padding:0.5rem 0.75rem; background:var(--bg-alt); border-radius:var(--radius-sm);">
                                        <span style="font-size:0.88rem; font-weight:500;">
                                            <?php echo clean($cat['name']); ?>
                                        </span>
                                        <span style="font-family:monospace; font-weight:700; font-size:0.88rem; color:var(--primary); background:var(--primary-light); padding:0.15rem 0.6rem; border-radius:var(--radius-sm);">
                                            ID: <?php echo $cat['id']; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sample CSV -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3>
                            <i class="fa-solid fa-file-lines" style="color:var(--primary); margin-right:0.5rem;"></i>
                            Sample CSV
                        </h3>
                    </div>
                    <div style="padding:1.25rem 1.5rem;">
                        <p style="font-size:0.82rem; color:var(--text-muted); margin-bottom:0.75rem;">
                            Copy this into a .csv file to get started:
                        </p>
                        <div style="background:var(--bg-alt); border:1px solid var(--border); border-radius:var(--radius); padding:0.85rem; font-family:monospace; font-size:0.72rem; line-height:1.8; overflow-x:auto; white-space:nowrap; color:var(--text);">
                            name,category_id,price,stock,description,image,featured,tag,sale_price<br>
                            Bamboo Chair,1,149.99,10,Handcrafted bamboo chair,,1,New,<br>
                            Linen Cushion,2,29.99,50,Soft linen cushion,,0,Sale,19.99
                        </div>
                        <button onclick="copySample()"
                                class="btn btn-outline btn-sm"
                                style="margin-top:0.85rem; width:100%; justify-content:center;">
                            <i class="fa-solid fa-copy"></i>
                            Copy Sample
                        </button>
                    </div>
                </div>

            </div>
        </div>

    </main>
</div>

<div class="toast" id="toast"></div>
<script src="../assets/js/main.js"></script>
<script>
function handleDrop(event) {
    event.preventDefault();
    const file = event.dataTransfer.files[0];
    if (file && file.name.endsWith('.csv')) {
        const input = document.getElementById('csvFile');
        const dt    = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('dropZone').style.borderColor = 'var(--primary)';
    }
}

function copySample() {
    const text = `name,category_id,price,stock,description,image,featured,tag,sale_price\nBamboo Chair,1,149.99,10,Handcrafted bamboo chair,,1,New,\nLinen Cushion,2,29.99,50,Soft linen cushion,,0,Sale,19.99`;
    navigator.clipboard.writeText(text).then(() => {
        showToast('Sample CSV copied to clipboard!', 'success');
    });
}
</script>
</body>
</html>
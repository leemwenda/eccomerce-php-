<?php
session_start();
require_once '../php/config/database.php';
require_once '../php/config/helpers.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: products.php');
    exit;
}

// Fetch product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

$error      = '';
$categories = getCategories($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']        ?? '');
    $categoryId  = (int)($_POST['category_id'] ?? 0);
    $price       = (float)($_POST['price']     ?? 0);
    $salePrice   = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
    $stock       = (int)($_POST['stock']       ?? 0);
    $description = trim($_POST['description'] ?? '');
    $featured    = isset($_POST['featured']) ? 1 : 0;
    $tag         = trim($_POST['tag']         ?? '');
    $imageUrl    = trim($_POST['image_url']   ?? '');

    if (!$name)  { $error = 'Product name is required'; }
    elseif (!$price) { $error = 'Price is required'; }
    else {
        // Keep existing image unless new one provided
        $image = $imageUrl ?: $product['image'];

        // Handle file upload
        if (!empty($_FILES['image_file']['name'])) {
            $uploadDir = '../assets/images/products/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $ext     = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowed)) {
                $error = 'Image must be JPG, PNG or WebP';
            } else {
                $filename = 'product_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $filename)) {
                    $image = SITE_URL . '/assets/images/products/' . $filename;
                }
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare("
                UPDATE products
                SET name=?, category_id=?, price=?, sale_price=?, stock=?,
                    description=?, image=?, featured=?, tag=?
                WHERE id=?
            ");
            $stmt->execute([
                $name,
                $categoryId ?: null,
                $price,
                $salePrice,
                $stock,
                $description,
                $image,
                $featured,
                $tag,
                $id
            ]);

            header('Location: products.php?msg=updated');
            exit;
        }
    }
}

// Use POST data if validation failed, otherwise use DB data
$data = $_SERVER['REQUEST_METHOD'] === 'POST' ? array_merge($product, $_POST) : $product;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product | Admin</title>
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

    <!-- MAIN -->
    <main class="admin-main">

        <!-- Top Bar -->
        <div class="admin-topbar">
            <div>
                <h1>Edit Product</h1>
                <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.2rem;">
                    <a href="products.php" style="color:var(--text-muted);">
                        <i class="fa-solid fa-arrow-left"></i> Back to Products
                    </a>
                </p>
            </div>
            <a href="products.php?delete=<?php echo $id; ?>"
               class="btn btn-danger"
               onclick="return confirm('Delete this product? This cannot be undone.')">
                <i class="fa-solid fa-trash"></i>
                Delete Product
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo clean($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div style="display:grid; grid-template-columns:1fr 340px; gap:1.5rem; align-items:start;">

                <!-- LEFT COLUMN -->
                <div>

                    <!-- Basic Info -->
                    <div class="admin-card" style="margin-bottom:1.5rem;">
                        <div class="admin-card-header">
                            <h3><i class="fa-solid fa-circle-info" style="color:var(--primary); margin-right:0.5rem;"></i> Basic Information</h3>
                        </div>
                        <div style="padding:1.5rem;">

                            <div class="form-group">
                                <label style="display:block; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); margin-bottom:0.5rem;">
                                    Product Name *
                                </label>
                                <input type="text"
                                       name="name"
                                       required
                                       value="<?php echo clean($data['name']); ?>"
                                       class="form-control">
                            </div>

                            <div class="form-group">
                                <label style="display:block; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); margin-bottom:0.5rem;">
                                    Description
                                </label>
                                <textarea name="description"
                                          rows="5"
                                          class="form-control"
                                          style="resize:vertical;"><?php echo clean($data['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-grid-2">
                                <div class="form-group">
                                    <label style="display:block; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); margin-bottom:0.5rem;">
                                        Price ($) *
                                    </label>
                                    <div style="position:relative;">
                                        <span style="position:absolute; left:1rem; top:50%; transform:translateY(-50%); color:var(--text-muted); font-weight:600;">$</span>
                                        <input type="number"
                                               name="price"
                                               required
                                               min="0"
                                               step="0.01"
                                               value="<?php echo clean($data['price']); ?>"
                                               class="form-control"
                                               style="padding-left:2rem;">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label style="display:block; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); margin-bottom:0.5rem;">
                                        Sale Price ($) <span style="font-weight:400; text-transform:none;">(optional)</span>
                                    </label>
                                    <div style="position:relative;">
                                        <span style="position:absolute; left:1rem; top:50%; transform:translateY(-50%); color:var(--text-muted); font-weight:600;">$</span>
                                        <input type="number"
                                               name="sale_price"
                                               min="0"
                                               step="0.01"
                                               value="<?php echo clean($data['sale_price'] ?? ''); ?>"
                                               class="form-control"
                                               style="padding-left:2rem;">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Image -->
                    <div class="admin-card" style="margin-bottom:1.5rem;">
                        <div class="admin-card-header">
                            <h3><i class="fa-solid fa-image" style="color:var(--primary); margin-right:0.5rem;"></i> Product Image</h3>
                        </div>
                        <div style="padding:1.5rem;">

                            <!-- Current Image -->
                            <?php if (!empty($product['image'])): ?>
                                <div style="margin-bottom:1.25rem;">
                                    <p style="font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); margin-bottom:0.5rem;">Current Image</p>
                                    <img src="<?php echo clean($product['image']); ?>"
                                         style="width:100%; max-height:220px; object-fit:cover; border-radius:var(--radius); border:1px solid var(--border);">
                                </div>
                            <?php endif; ?>

                            <!-- Tab buttons -->
                            <div style="display:flex; gap:0.5rem; margin-bottom:1.25rem;">
                                <button type="button" id="tabUpload"
                                        onclick="switchImageTab('upload')"
                                        style="padding:0.55rem 1.25rem; border-radius:var(--radius-sm); border:1.5px solid var(--primary); background:var(--primary); color:white; font-size:0.85rem; font-weight:600; cursor:pointer;">
                                    <i class="fa-solid fa-upload"></i> Upload New
                                </button>
                                <button type="button" id="tabUrl"
                                        onclick="switchImageTab('url')"
                                        style="padding:0.55rem 1.25rem; border-radius:var(--radius-sm); border:1.5px solid var(--border); background:transparent; color:var(--text-muted); font-size:0.85rem; font-weight:600; cursor:pointer;">
                                    <i class="fa-solid fa-link"></i> Image URL
                                </button>
                            </div>

                            <!-- Upload -->
                            <div id="uploadSection">
                                <input type="file"
                                       name="image_file"
                                       id="imageFile"
                                       accept="image/*"
                                       onchange="previewImage(this, 'imgPreview')"
                                       style="width:100%; padding:0.8rem; border:2px dashed var(--border); border-radius:var(--radius); background:var(--bg); color:var(--text); font-family:var(--font-body); cursor:pointer;">
                                <p style="color:var(--text-muted); font-size:0.78rem; margin-top:0.5rem;">
                                    Leave empty to keep current image
                                </p>
                            </div>

                            <!-- URL -->
                            <div id="urlSection" style="display:none;">
                                <input type="text"
                                       name="image_url"
                                       id="imageUrl"
                                       placeholder="https://example.com/image.jpg"
                                       value="<?php echo clean($product['image'] ?? ''); ?>"
                                       class="form-control"
                                       oninput="previewImageUrl(this.value, 'imgPreview')">
                            </div>

                            <!-- New Preview -->
                            <img id="imgPreview"
                                 style="width:100%; max-height:200px; object-fit:cover; border-radius:var(--radius); border:1px solid var(--border); margin-top:1rem; display:none;">

                        </div>
                    </div>

                </div>

                <!-- RIGHT COLUMN -->
                <div>

                    <!-- Organise -->
                    <div class="admin-card" style="margin-bottom:1.5rem;">
                        <div class="admin-card-header">
                            <h3><i class="fa-solid fa-folder" style="color:var(--primary); margin-right:0.5rem;"></i> Organise</h3>
                        </div>
                        <div style="padding:1.5rem;">

                            <div class="form-group">
                                <label style="display:block; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); margin-bottom:0.5rem;">
                                    Category
                                </label>
                                <select name="category_id" class="form-control">
                                    <option value="">-- No Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"
                                            <?php echo $data['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo clean($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label style="display:block; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); margin-bottom:0.5rem;">
                                    Stock Quantity *
                                </label>
                                <input type="number"
                                       name="stock"
                                       min="0"
                                       required
                                       value="<?php echo clean($data['stock']); ?>"
                                       class="form-control">
                            </div>

                            <div class="form-group">
                                <label style="display:block; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); margin-bottom:0.5rem;">
                                    Product Tag
                                </label>
                                <select name="tag" class="form-control">
                                    <option value="">None</option>
                                    <option value="New"     <?php echo ($data['tag'] ?? '') === 'New'     ? 'selected' : ''; ?>>New</option>
                                    <option value="Sale"    <?php echo ($data['tag'] ?? '') === 'Sale'    ? 'selected' : ''; ?>>Sale</option>
                                    <option value="Popular" <?php echo ($data['tag'] ?? '') === 'Popular' ? 'selected' : ''; ?>>Popular</option>
                                </select>
                            </div>

                            <div style="display:flex; align-items:center; gap:0.75rem; padding:1rem; background:var(--bg-alt); border-radius:var(--radius); border:1px solid var(--border);">
                                <input type="checkbox"
                                       name="featured"
                                       id="featured"
                                       value="1"
                                       <?php echo $data['featured'] ? 'checked' : ''; ?>
                                       style="width:18px; height:18px; accent-color:var(--primary); cursor:pointer;">
                                <label for="featured" style="cursor:pointer; font-weight:600; font-size:0.9rem;">
                                    <i class="fa-solid fa-star" style="color:var(--primary); margin-right:0.35rem;"></i>
                                    Show on Homepage
                                </label>
                            </div>

                        </div>
                    </div>

                    <!-- Product Info -->
                    <div class="admin-card" style="margin-bottom:1.5rem;">
                        <div style="padding:1.25rem 1.5rem;">
                            <p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:0.35rem;">
                                <i class="fa-solid fa-clock" style="margin-right:0.4rem;"></i>
                                Added: <?php echo date('M j, Y', strtotime($product['created_at'])); ?>
                            </p>
                            <p style="font-size:0.8rem; color:var(--text-muted);">
                                <i class="fa-solid fa-link" style="margin-right:0.4rem;"></i>
                                Slug: <?php echo clean($product['slug']); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:1rem; font-size:1rem;">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Save Changes
                    </button>
                    <a href="products.php" class="btn btn-outline" style="width:100%; justify-content:center; margin-top:0.75rem;">
                        <i class="fa-solid fa-xmark"></i>
                        Cancel
                    </a>

                </div>
            </div>
        </form>

    </main>
</div>

<div class="toast" id="toast"></div>
<script src="../assets/js/main.js"></script>
<script>
function switchImageTab(tab) {
    const uploadSection = document.getElementById('uploadSection');
    const urlSection    = document.getElementById('urlSection');
    const tabUpload     = document.getElementById('tabUpload');
    const tabUrl        = document.getElementById('tabUrl');

    if (tab === 'upload') {
        uploadSection.style.display = 'block';
        urlSection.style.display    = 'none';
        tabUpload.style.background  = 'var(--primary)';
        tabUpload.style.color       = 'white';
        tabUpload.style.borderColor = 'var(--primary)';
        tabUrl.style.background     = 'transparent';
        tabUrl.style.color          = 'var(--text-muted)';
        tabUrl.style.borderColor    = 'var(--border)';
    } else {
        uploadSection.style.display = 'none';
        urlSection.style.display    = 'block';
        tabUrl.style.background     = 'var(--primary)';
        tabUrl.style.color          = 'white';
        tabUrl.style.borderColor    = 'var(--primary)';
        tabUpload.style.background  = 'transparent';
        tabUpload.style.color       = 'var(--text-muted)';
        tabUpload.style.borderColor = 'var(--border)';
    }
}
</script>
</body>
</html>
<?php
session_start();
require_once '../php/config/database.php';
require_once '../php/config/helpers.php';
requireAdmin();

$error   = '';
$success = '';

// --- CSRF Token ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- Handle Delete ---
if (isset($_GET['delete']) && isset($_GET['token'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
        die('Invalid CSRF token.');
    }
    $id = (int)$_GET['delete'];
    $count = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $count->execute([$id]);
    if ($count->fetchColumn() > 0) {
        $error = 'Cannot delete this category because it has products. Remove the products first.';
    } else {
        // Also delete local image file if stored locally
        $imgRow = $pdo->prepare("SELECT image FROM categories WHERE id = ?");
        $imgRow->execute([$id]);
        $imgPath = $imgRow->fetchColumn();
        if ($imgPath && strpos($imgPath, '/assets/images/categories/') !== false) {
            $localPath = '../assets/images/categories/' . basename($imgPath);
            if (file_exists($localPath)) @unlink($localPath);
        }
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        header('Location: categories.php?msg=deleted');
        exit;
    }
}

// --- Handle Add / Edit (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token.');
    }

    $catId       = (int)($_POST['cat_id']     ?? 0);
    $name        = trim($_POST['name']         ?? '');
    $description = trim($_POST['description'] ?? '');
    $imageUrl    = trim($_POST['image_url']   ?? '');
    $imageTab    = $_POST['image_tab'] ?? 'upload'; // which tab was active

    if (!$name) {
        $error = 'Category name is required.';
    } else {
        // Generate slug
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-');

        // --- Determine final image value ---
        $image = '';

        // Priority 1: file upload (if a file was actually chosen)
        if ($imageTab === 'upload' && !empty($_FILES['image_file']['name'])) {
            $uploadDir = '../assets/images/categories/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext     = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowed)) {
                $error = 'Image must be JPG, PNG or WebP.';
            } else {
                $filename = 'cat_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $filename)) {
                    $image = SITE_URL . '/assets/images/categories/' . $filename;
                } else {
                    $error = 'Failed to upload image. Check folder permissions.';
                }
            }
        }

        // Priority 2: URL tab with a URL entered
        if (!$image && $imageTab === 'url' && $imageUrl) {
            if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $error = 'Please enter a valid image URL.';
            } else {
                $image = $imageUrl;
            }
        }

        // Priority 3: editing — keep the existing image if nothing new was provided
        if (!$image && $catId) {
            $existing = $pdo->prepare("SELECT image FROM categories WHERE id = ?");
            $existing->execute([$catId]);
            $image = $existing->fetchColumn() ?: '';
        }

        if (!$error) {
            if ($catId) {
                // --- UPDATE ---
                $stmt = $pdo->prepare("UPDATE categories SET name=?, slug=?, description=?, image=? WHERE id=?");
                $stmt->execute([$name, $slug, $description, $image, $catId]);
                header('Location: categories.php?msg=updated');
            } else {
                // --- INSERT: ensure slug uniqueness ---
                $check = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE slug = ?");
                $check->execute([$slug]);
                if ($check->fetchColumn() > 0) $slug .= '-' . time();

                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, image) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $description, $image]);
                header('Location: categories.php?msg=added');
            }
            exit;
        }
    }
}

// --- Fetch all categories with product count ---
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id
    ORDER BY c.name ASC
")->fetchAll();

// --- Fetch category for edit ---
$editCat = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editCat = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories | Admin</title>
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
            <li><a href="categories.php" class="active"><i class="fa-solid fa-tags"></i> Categories</a></li>
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

        <div class="admin-topbar">
            <div>
                <h1><?php echo $editCat ? 'Edit Category' : 'Categories'; ?></h1>
                <p style="color:var(--text-muted); font-size:0.85rem; margin-top:0.2rem;">
                    Manage your product categories
                </p>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($_GET['msg'])): ?>
            <?php $msgs = ['added'=>'Category added successfully.','updated'=>'Category updated successfully.','deleted'=>'Category deleted successfully.']; ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <?php echo $msgs[$_GET['msg']] ?? ''; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo clean($error); ?>
            </div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns:1fr 380px; gap:1.5rem; align-items:start;">

            <!-- ===================== CATEGORIES TABLE ===================== -->
            <div class="admin-card" style="overflow:hidden;">
                <div class="admin-card-header">
                    <h3>
                        <i class="fa-solid fa-tags" style="color:var(--primary); margin-right:0.5rem;"></i>
                        All Categories (<?php echo count($categories); ?>)
                    </h3>
                </div>
                <div style="overflow-x:auto;">
                <table class="admin-table" style="min-width:580px;">
                    <thead>
                        <tr>
                            <th style="width:70px;">Image</th>
                            <th>Category Name</th>
                            <th style="width:90px; text-align:center;">Products</th>
                            <th style="width:180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="4" style="text-align:center; padding:4rem; color:var(--text-muted);">
                                    <i class="fa-solid fa-tags" style="font-size:2.5rem; display:block; margin-bottom:1rem; color:var(--border);"></i>
                                    No categories yet. Add your first one.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <!-- Image -->
                                    <td>
                                        <?php if (!empty($cat['image'])): ?>
                                            <img src="<?php echo clean($cat['image']); ?>"
                                                 style="width:56px; height:56px; object-fit:cover; border-radius:var(--radius-sm); border:1px solid var(--border);"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div style="width:56px; height:56px; background:var(--bg-alt); border-radius:var(--radius-sm); border:1px solid var(--border); display:none; align-items:center; justify-content:center;">
                                                <i class="fa-solid fa-image" style="color:var(--border); font-size:1.25rem;"></i>
                                            </div>
                                        <?php else: ?>
                                            <div style="width:56px; height:56px; background:var(--bg-alt); border-radius:var(--radius-sm); border:1px solid var(--border); display:flex; align-items:center; justify-content:center;">
                                                <i class="fa-solid fa-image" style="color:var(--border); font-size:1.25rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Name + Slug + Description -->
                                    <td>
                                        <div style="font-weight:600; font-size:0.92rem;">
                                            <?php echo clean($cat['name']); ?>
                                        </div>
                                        <div style="color:var(--text-muted); font-size:0.75rem; margin-top:0.15rem;">
                                            slug: <?php echo clean($cat['slug']); ?>
                                        </div>
                                        <?php if (!empty($cat['description'])): ?>
                                            <div style="color:var(--text-muted); font-size:0.78rem; margin-top:0.2rem; max-width:260px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                                                 title="<?php echo clean($cat['description']); ?>">
                                                <?php echo clean($cat['description']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Product Count -->
                                    <td style="text-align:center;">
                                        <span style="font-weight:700; font-size:0.95rem;">
                                            <?php echo $cat['product_count']; ?>
                                        </span>
                                        <div style="color:var(--text-muted); font-size:0.75rem;">
                                            product<?php echo $cat['product_count'] != 1 ? 's' : ''; ?>
                                        </div>
                                    </td>

                                    <!-- Actions -->
                                    <td>
                                        <div style="display:flex; gap:0.5rem;">
                                            <!-- Edit -->
                                            <a href="categories.php?edit=<?php echo $cat['id']; ?>"
                                               class="btn btn-outline btn-sm"
                                               title="Edit category">
                                                <i class="fa-solid fa-pen-to-square"></i> Edit
                                            </a>

                                            <!-- Delete (only if no products) -->
                                            <?php if ($cat['product_count'] == 0): ?>
                                                <a href="categories.php?delete=<?php echo $cat['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>"
                                                   class="btn btn-sm"
                                                   style="background:#FEE2E2; color:var(--danger); border:1px solid #FCA5A5;"
                                                   title="Delete category"
                                                   onclick="return confirm('Are you sure you want to delete \'<?php echo addslashes(clean($cat['name'])); ?>\'? This cannot be undone.')">
                                                    <i class="fa-solid fa-trash"></i> Delete
                                                </a>
                                            <?php else: ?>
                                                <button disabled
                                                        title="Remove all products from this category before deleting"
                                                        class="btn btn-sm"
                                                        style="background:var(--bg-alt); color:var(--text-muted); border:1px solid var(--border); cursor:not-allowed;">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div><!-- end overflow wrapper -->
            </div>

            <!-- ===================== ADD / EDIT FORM ===================== -->
            <div>
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3>
                            <i class="fa-solid <?php echo $editCat ? 'fa-pen-to-square' : 'fa-plus'; ?>" style="color:var(--primary); margin-right:0.5rem;"></i>
                            <?php echo $editCat ? 'Edit Category' : 'Add New Category'; ?>
                        </h3>
                        <?php if ($editCat): ?>
                            <a href="categories.php" style="font-size:0.85rem; color:var(--text-muted);">
                                <i class="fa-solid fa-xmark"></i> Cancel
                            </a>
                        <?php endif; ?>
                    </div>

                    <div style="padding:1.5rem;">
                        <form method="POST" enctype="multipart/form-data" id="catForm">
                            <!-- Security -->
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="cat_id"     value="<?php echo $editCat ? $editCat['id'] : 0; ?>">
                            <!-- Track which image tab was active -->
                            <input type="hidden" name="image_tab"  id="imageTabInput" value="upload">

                            <!-- Name -->
                            <div class="form-group" style="margin-bottom:1.25rem;">
                                <label style="display:block; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); margin-bottom:0.5rem;">
                                    Category Name *
                                </label>
                                <input type="text"
                                       name="name"
                                       required
                                       placeholder="e.g. Living Room"
                                       value="<?php echo clean($editCat['name'] ?? ($_POST['name'] ?? '')); ?>"
                                       class="form-control">
                            </div>

                            <!-- Description -->
                            <div class="form-group" style="margin-bottom:1.25rem;">
                                <label style="display:block; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); margin-bottom:0.5rem;">
                                    Description
                                </label>
                                <textarea name="description"
                                          rows="3"
                                          placeholder="Short description of this category..."
                                          class="form-control"
                                          style="resize:vertical;"><?php echo clean($editCat['description'] ?? ($_POST['description'] ?? '')); ?></textarea>
                            </div>

                            <!-- Image -->
                            <div class="form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-muted); margin-bottom:0.5rem;">
                                    Category Image
                                </label>

                                <!-- Tab Buttons -->
                                <div style="display:flex; gap:0.5rem; margin-bottom:0.85rem;">
                                    <button type="button" id="tabUpload" onclick="switchImageTab('upload')"
                                            style="padding:0.45rem 1rem; border-radius:var(--radius-sm); border:1.5px solid var(--primary); background:var(--primary); color:white; font-size:0.82rem; font-weight:600; cursor:pointer;">
                                        <i class="fa-solid fa-upload"></i> Upload File
                                    </button>
                                    <button type="button" id="tabUrl" onclick="switchImageTab('url')"
                                            style="padding:0.45rem 1rem; border-radius:var(--radius-sm); border:1.5px solid var(--border); background:transparent; color:var(--text-muted); font-size:0.82rem; font-weight:600; cursor:pointer;">
                                        <i class="fa-solid fa-link"></i> Image URL
                                    </button>
                                </div>

                                <!-- Upload Section -->
                                <div id="uploadSection">
                                    <input type="file"
                                           name="image_file"
                                           id="imageFileInput"
                                           accept="image/jpeg,image/png,image/webp"
                                           onchange="handleFileSelect(this)"
                                           style="width:100%; padding:0.7rem; border:2px dashed var(--border); border-radius:var(--radius); background:var(--bg); color:var(--text); font-family:var(--font-body); cursor:pointer; box-sizing:border-box;">
                                    <p style="font-size:0.75rem; color:var(--text-muted); margin-top:0.35rem;">
                                        Accepted: JPG, PNG, WebP
                                    </p>
                                </div>

                                <!-- URL Section -->
                                <div id="urlSection" style="display:none;">
                                    <input type="text"
                                           name="image_url"
                                           id="imageUrlInput"
                                           placeholder="https://example.com/image.jpg"
                                           value="<?php
                                               // Pre-fill URL field when editing (if image is a URL, not a local upload)
                                               if ($editCat && !empty($editCat['image']) && filter_var($editCat['image'], FILTER_VALIDATE_URL)) {
                                                   echo clean($editCat['image']);
                                               } elseif (isset($_POST['image_url'])) {
                                                   echo clean($_POST['image_url']);
                                               }
                                           ?>"
                                           class="form-control"
                                           oninput="previewFromUrl(this.value)">
                                </div>

                                <!-- Image Preview -->
                                <?php
                                $previewSrc = '';
                                if (!empty($editCat['image'])) $previewSrc = clean($editCat['image']);
                                ?>
                                <div id="previewWrap" style="margin-top:0.75rem; <?php echo $previewSrc ? '' : 'display:none;'; ?>">
                                    <img id="imgPreview"
                                         src="<?php echo $previewSrc; ?>"
                                         alt="Preview"
                                         style="width:100%; height:150px; object-fit:cover; border-radius:var(--radius); border:1px solid var(--border);"
                                         onerror="document.getElementById('previewWrap').style.display='none';">
                                    <button type="button" onclick="clearImagePreview()"
                                            style="margin-top:0.4rem; font-size:0.78rem; color:var(--danger); background:none; border:none; cursor:pointer; padding:0;">
                                        <i class="fa-solid fa-xmark"></i> Remove image
                                    </button>
                                </div>
                            </div>

                            <!-- Submit -->
                            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:0.9rem;">
                                <i class="fa-solid <?php echo $editCat ? 'fa-floppy-disk' : 'fa-plus'; ?>"></i>
                                <?php echo $editCat ? 'Save Changes' : 'Add Category'; ?>
                            </button>

                        </form>
                    </div>
                </div>
            </div>

        </div><!-- end grid -->
    </main>
</div>

<div class="toast" id="toast"></div>
<script src="../assets/js/main.js"></script>
<script>
// ── Image Tab Switcher ──────────────────────────────────────────────
function switchImageTab(tab) {
    const uploadSection = document.getElementById('uploadSection');
    const urlSection    = document.getElementById('urlSection');
    const tabUpload     = document.getElementById('tabUpload');
    const tabUrl        = document.getElementById('tabUrl');
    document.getElementById('imageTabInput').value = tab;

    if (tab === 'upload') {
        uploadSection.style.display = 'block';
        urlSection.style.display    = 'none';
        tabUpload.style.cssText     = 'padding:.45rem 1rem; border-radius:var(--radius-sm); border:1.5px solid var(--primary); background:var(--primary); color:white; font-size:.82rem; font-weight:600; cursor:pointer;';
        tabUrl.style.cssText        = 'padding:.45rem 1rem; border-radius:var(--radius-sm); border:1.5px solid var(--border); background:transparent; color:var(--text-muted); font-size:.82rem; font-weight:600; cursor:pointer;';
    } else {
        uploadSection.style.display = 'none';
        urlSection.style.display    = 'block';
        tabUrl.style.cssText        = 'padding:.45rem 1rem; border-radius:var(--radius-sm); border:1.5px solid var(--primary); background:var(--primary); color:white; font-size:.82rem; font-weight:600; cursor:pointer;';
        tabUpload.style.cssText     = 'padding:.45rem 1rem; border-radius:var(--radius-sm); border:1.5px solid var(--border); background:transparent; color:var(--text-muted); font-size:.82rem; font-weight:600; cursor:pointer;';
    }
}

// ── Preview from file input ─────────────────────────────────────────
function handleFileSelect(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) { showPreview(e.target.result); };
        reader.readAsDataURL(input.files[0]);
    }
}

// ── Preview from URL input ──────────────────────────────────────────
function previewFromUrl(url) {
    if (url.trim()) {
        showPreview(url.trim());
    } else {
        document.getElementById('previewWrap').style.display = 'none';
    }
}

// ── Show preview helper ─────────────────────────────────────────────
function showPreview(src) {
    const wrap = document.getElementById('previewWrap');
    const img  = document.getElementById('imgPreview');
    img.src    = src;
    wrap.style.display = 'block';
}

// ── Clear image (remove button) ─────────────────────────────────────
function clearImagePreview() {
    document.getElementById('previewWrap').style.display = 'none';
    document.getElementById('imgPreview').src            = '';
    document.getElementById('imageFileInput').value      = '';
    document.getElementById('imageUrlInput').value       = '';
}

// ── On page load: if editing and image is a URL, switch to URL tab ──
<?php if ($editCat && !empty($editCat['image']) && filter_var($editCat['image'], FILTER_VALIDATE_URL)): ?>
switchImageTab('url');
<?php endif; ?>
</script>
</body>
</html>
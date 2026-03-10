<?php

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../admin/login.php');
    exit;
}
require_once '../php/config/database.php';
require_once '../php/config/helpers.php';

$adminName = $_SESSION['user_name'] ?? 'Admin';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle ?? 'Admin'; ?> — Maison Décor Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --sidebar-w: 240px;
    --topbar-h: 60px;
    --primary: #8B6442;
    --primary-dark: #6B4C2A;
    --accent: #D4A76A;
    --dark: #1C1007;
    --gray: #6B7280;
    --gray-light: #E5E7EB;
    --bg: #F9F6F1;
    --white: #ffffff;
    --danger: #C94545;
    --success: #065F46;
    --warning: #856404;
    --radius: 8px;
    --radius-lg: 12px;
    --shadow: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.06);
    --shadow-md: 0 4px 16px rgba(0,0,0,0.1);
}
body { font-family: 'Inter', system-ui, sans-serif; background: var(--bg); color: var(--dark); display: flex; min-height: 100vh; font-size: 14px; }
a { text-decoration: none; color: inherit; }

/* ── SIDEBAR ── */
.sidebar {
    width: var(--sidebar-w);
    background: var(--dark);
    min-height: 100vh;
    position: fixed;
    top: 0; left: 0; bottom: 0;
    display: flex;
    flex-direction: column;
    z-index: 100;
    overflow-y: auto;
}
.sidebar__logo {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.sidebar__logo-icon {
    width: 36px; height: 36px;
    background: var(--primary);
    border-radius: var(--radius);
    display: flex; align-items: center; justify-content: center;
    color: white;
    font-size: 1rem;
    flex-shrink: 0;
}
.sidebar__logo-text {
    font-size: 0.9rem;
    font-weight: 700;
    color: white;
    line-height: 1.2;
}
.sidebar__logo-text span {
    display: block;
    font-size: 0.7rem;
    font-weight: 400;
    color: rgba(255,255,255,0.45);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.sidebar__nav { padding: 1rem 0; flex: 1; }
.nav-label {
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.3);
    padding: 0.75rem 1.5rem 0.4rem;
}
.nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.65rem 1.5rem;
    color: rgba(255,255,255,0.6);
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.15s;
    border-left: 3px solid transparent;
    cursor: pointer;
}
.nav-item i { width: 18px; text-align: center; font-size: 0.95rem; }
.nav-item:hover { color: white; background: rgba(255,255,255,0.06); }
.nav-item.active {
    color: white;
    background: rgba(139,100,66,0.25);
    border-left-color: var(--accent);
}

.sidebar__footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid rgba(255,255,255,0.08);
}
.admin-user {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}
.admin-avatar {
    width: 34px; height: 34px;
    background: var(--primary);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: white;
    font-size: 0.8rem;
    font-weight: 700;
    flex-shrink: 0;
}
.admin-name { font-size: 0.82rem; color: rgba(255,255,255,0.8); font-weight: 600; }
.admin-role { font-size: 0.7rem; color: rgba(255,255,255,0.35); }
.sidebar-logout {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.55rem 0.75rem;
    border-radius: var(--radius);
    color: rgba(255,255,255,0.45);
    font-size: 0.8rem;
    transition: all 0.15s;
    width: 100%;
    background: none;
    border: none;
    cursor: pointer;
}
.sidebar-logout:hover { background: rgba(201,69,69,0.15); color: #F87171; }

/* ── MAIN ── */
.main-wrap {
    margin-left: var(--sidebar-w);
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* ── TOPBAR ── */
.topbar {
    height: var(--topbar-h);
    background: var(--white);
    border-bottom: 1px solid var(--gray-light);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 1.5rem;
    position: sticky;
    top: 0;
    z-index: 50;
    gap: 1rem;
}
.topbar__title {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--dark);
}
.topbar__right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.topbar-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: var(--radius);
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
    border: none;
    text-decoration: none;
}
.topbar-btn-primary {
    background: var(--primary);
    color: white;
}
.topbar-btn-primary:hover { background: var(--primary-dark); color: white; }
.topbar-btn-ghost {
    background: none;
    color: var(--gray);
    border: 1px solid var(--gray-light);
}
.topbar-btn-ghost:hover { background: var(--bg); color: var(--dark); }

/* ── PAGE CONTENT ── */
.page-content { padding: 1.5rem; flex: 1; }

/* ── CARDS ── */
.card {
    background: var(--white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow);
    overflow: hidden;
}
.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--gray-light);
    gap: 1rem;
    flex-wrap: wrap;
}
.card-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.card-title i { color: var(--primary); }
.card-body { padding: 1.25rem; }

/* ── STAT CARDS ── */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.stat-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    padding: 1.25rem;
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    gap: 1rem;
}
.stat-icon {
    width: 48px; height: 48px;
    border-radius: var(--radius);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}
.stat-icon--primary  { background: rgba(139,100,66,0.12);  color: var(--primary); }
.stat-icon--blue     { background: rgba(59,130,246,0.12);  color: #3B82F6; }
.stat-icon--green    { background: rgba(16,185,129,0.12);  color: #10B981; }
.stat-icon--orange   { background: rgba(245,158,11,0.12);  color: #F59E0B; }
.stat-icon--red      { background: rgba(201,69,69,0.12);   color: var(--danger); }
.stat-num   { font-size: 1.6rem; font-weight: 700; color: var(--dark); line-height: 1; }
.stat-label { font-size: 0.78rem; color: var(--gray); margin-top: 0.2rem; }

/* ── TABLES ── */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
thead tr { border-bottom: 2px solid var(--gray-light); }
th {
    padding: 0.65rem 1rem;
    text-align: left;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: var(--gray);
    white-space: nowrap;
}
td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--gray-light);
    font-size: 0.875rem;
    color: var(--dark);
    vertical-align: middle;
}
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover { background: #FAFAF9; }

/* ── BADGES ── */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.25rem 0.65rem;
    border-radius: 2rem;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
}
.badge-pending    { background: #FFF3CD; color: #856404; }
.badge-processing { background: #DBEAFE; color: #1D4ED8; }
.badge-shipped    { background: #E0F2FE; color: #0369A1; }
.badge-delivered  { background: #D1FAE5; color: #065F46; }
.badge-cancelled  { background: #FEE2E2; color: #991B1B; }
.badge-paid       { background: #D1FAE5; color: #065F46; }
.badge-unpaid     { background: #FEE2E2; color: #991B1B; }
.badge-admin      { background: rgba(139,100,66,0.12); color: var(--primary); }
.badge-customer   { background: var(--bg); color: var(--gray); }

/* ── FORMS ── */
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }
.form-group { margin-bottom: 1rem; }
.form-group:last-child { margin-bottom: 0; }
label {
    display: block;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--gray);
    margin-bottom: 0.4rem;
}
input[type="text"], input[type="email"], input[type="number"],
input[type="password"], select, textarea {
    width: 100%;
    padding: 0.6rem 0.85rem;
    border: 1.5px solid var(--gray-light);
    border-radius: var(--radius);
    font-size: 0.875rem;
    font-family: inherit;
    color: var(--dark);
    background: var(--white);
    transition: border-color 0.2s, box-shadow 0.2s;
}
input:focus, select:focus, textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(139,100,66,0.1);
}
textarea { resize: vertical; min-height: 90px; }
.form-hint { font-size: 0.75rem; color: var(--gray); margin-top: 0.3rem; }

/* ── BUTTONS ── */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.55rem 1.1rem;
    border-radius: var(--radius);
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.15s;
    text-decoration: none;
    white-space: nowrap;
}
.btn-primary   { background: var(--primary); color: white; }
.btn-primary:hover { background: var(--primary-dark); color: white; }
.btn-danger    { background: var(--danger); color: white; }
.btn-danger:hover { opacity: 0.88; }
.btn-ghost     { background: none; border: 1px solid var(--gray-light); color: var(--gray); }
.btn-ghost:hover { background: var(--bg); color: var(--dark); }
.btn-success   { background: #10B981; color: white; }
.btn-success:hover { opacity: 0.88; }
.btn-sm { padding: 0.35rem 0.75rem; font-size: 0.78rem; }

/* ── ALERTS ── */
.alert {
    padding: 0.85rem 1rem;
    border-radius: var(--radius);
    font-size: 0.85rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
}
.alert-success { background: #D1FAE5; color: #065F46; }
.alert-error   { background: #FEE2E2; color: #991B1B; }
.alert-warning { background: #FFF3CD; color: #856404; }
.alert i { flex-shrink: 0; margin-top: 0.1rem; }

/* ── PRODUCT THUMB ── */
.product-thumb {
    width: 44px; height: 44px;
    border-radius: var(--radius);
    object-fit: cover;
    background: var(--bg);
    border: 1px solid var(--gray-light);
}
.product-thumb-placeholder {
    width: 44px; height: 44px;
    border-radius: var(--radius);
    background: var(--bg);
    border: 1px solid var(--gray-light);
    display: flex; align-items: center; justify-content: center;
    color: var(--gray);
    font-size: 1rem;
    opacity: 0.5;
}

/* ── SEARCH / FILTER BAR ── */
.filter-bar {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    align-items: center;
}
.search-input-wrap {
    position: relative;
    flex: 1;
    min-width: 200px;
}
.search-input-wrap i {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray);
    font-size: 0.85rem;
}
.search-input-wrap input {
    padding-left: 2.25rem;
}

/* ── PAGINATION ── */
.pagination {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    justify-content: center;
    padding: 1rem;
}
.page-btn {
    width: 34px; height: 34px;
    display: flex; align-items: center; justify-content: center;
    border-radius: var(--radius);
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid var(--gray-light);
    background: var(--white);
    color: var(--dark);
    transition: all 0.15s;
    text-decoration: none;
}
.page-btn:hover { background: var(--bg); }
.page-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
.page-btn.disabled { opacity: 0.4; pointer-events: none; }

/* ── TOAST ── */
#adminToast {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    background: var(--dark);
    color: white;
    padding: 0.85rem 1.25rem;
    border-radius: var(--radius);
    font-size: 0.875rem;
    font-weight: 500;
    box-shadow: var(--shadow-md);
    z-index: 9999;
    opacity: 0;
    transform: translateY(12px);
    transition: all 0.3s;
    pointer-events: none;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    max-width: 320px;
}
#adminToast.show { opacity: 1; transform: translateY(0); }

/* ── RESPONSIVE ── */
@media (max-width: 900px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .sidebar { transform: translateX(-100%); }
    .main-wrap { margin-left: 0; }
    .stats-grid { grid-template-columns: 1fr 1fr; }
    .form-grid, .form-grid-3 { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar__logo">
        <div class="sidebar__logo-icon"><i class="fa-solid fa-couch"></i></div>
        <div class="sidebar__logo-text">
            Maison Décor
            <span>Admin Panel</span>
        </div>
    </div>

    <nav class="sidebar__nav">
        <div class="nav-label">Main</div>
        <a href="index.php" class="nav-item <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-line"></i> Dashboard
        </a>

        <div class="nav-label">Catalogue</div>
        <a href="products.php" class="nav-item <?php echo $currentPage === 'products' ? 'active' : ''; ?>">
            <i class="fa-solid fa-box-open"></i> Products
        </a>
        <a href="categories.php" class="nav-item <?php echo $currentPage === 'categories' ? 'active' : ''; ?>">
            <i class="fa-solid fa-tags"></i> Categories
        </a>
        <a href="import.php" class="nav-item <?php echo $currentPage === 'import' ? 'active' : ''; ?>">
            <i class="fa-solid fa-file-import"></i> CSV Import
        </a>

        <div class="nav-label">Store</div>
        <a href="orders.php" class="nav-item <?php echo $currentPage === 'orders' ? 'active' : ''; ?>">
            <i class="fa-solid fa-receipt"></i> Orders
        </a>
        <a href="customers.php" class="nav-item <?php echo $currentPage === 'customers' ? 'active' : ''; ?>">
            <i class="fa-solid fa-users"></i> Customers
        </a>

        <div class="nav-label">Site</div>
        <a href="../index.php" class="nav-item" target="_blank">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> View Store
        </a>
    </nav>

    <div class="sidebar__footer">
        <div class="admin-user">
            <div class="admin-avatar"><?php echo strtoupper(substr($adminName, 0, 1)); ?></div>
            <div>
                <div class="admin-name"><?php echo clean($adminName); ?></div>
                <div class="admin-role">Administrator</div>
            </div>
        </div>
        <a href="logout.php" class="sidebar-logout">
            <i class="fa-solid fa-right-from-bracket"></i> Sign Out
        </a>
    </div>
</aside>

<!-- MAIN -->
<div class="main-wrap">
<div id="adminToast"></div>
<script>
function adminToast(msg, type) {
    const t = document.getElementById('adminToast');
    t.innerHTML = (type === 'success' ? '<i class="fa-solid fa-circle-check" style="color:#10B981;"></i>' : '<i class="fa-solid fa-circle-xmark" style="color:#F87171;"></i>') + msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3200);
}
</script>
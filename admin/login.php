<?php
session_start();
require_once '../php/config/database.php';
require_once '../php/config/helpers.php';

// Already logged in as admin
if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin') {
    header('Location: index.php'); exit;
}
// Logged in as customer - redirect to shop
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: index.php'); exit;
        } else {
            $error = 'Invalid admin credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin Login | Maison Decor</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<script>const t=localStorage.getItem('theme')||'light';document.documentElement.setAttribute('data-theme',t);</script>
<style>
:root{--bg:#FAF7F2;--bg-card:#fff;--text:#1C1410;--text-muted:#7A6A5A;--primary:#A67C52;--primary-dark:#8B6340;--border:#E0D5C8;--danger:#C94545;--radius:10px;}
[data-theme=dark]{--bg:#141210;--bg-card:#1E1A16;--text:#F0EAE0;--text-muted:#9A8A78;--primary:#C49A6C;--border:#2E2620;--danger:#E05555;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:Inter,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem;}
.card{background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:2.5rem;width:100%;max-width:420px;box-shadow:0 8px 32px rgba(0,0,0,0.1);}
.logo{font-family:'Playfair Display',serif;font-size:1.75rem;font-weight:700;color:var(--primary);text-align:center;margin-bottom:0.25rem;}
.subtitle{text-align:center;color:var(--text-muted);font-size:0.85rem;margin-bottom:2rem;}
label{display:block;font-size:0.78rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);margin-bottom:0.5rem;}
.inp-wrap{position:relative;margin-bottom:1.25rem;}
.inp-wrap i{position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:0.9rem;}
input{width:100%;padding:0.85rem 1rem 0.85rem 2.75rem;border:1.5px solid var(--border);border-radius:var(--radius);font-size:0.95rem;background:var(--bg-card);color:var(--text);font-family:inherit;}
input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(166,124,82,0.12);}
.btn{width:100%;padding:1rem;background:var(--primary);color:white;border:none;border-radius:var(--radius);font-size:1rem;font-weight:600;cursor:pointer;font-family:inherit;transition:all 0.25s;display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-top:0.5rem;}
.btn:hover{background:var(--primary-dark);}
.alert{padding:0.85rem 1rem;background:#FEE2E2;color:#991B1B;border:1px solid #FCA5A5;border-radius:var(--radius);margin-bottom:1.5rem;font-size:0.88rem;display:flex;align-items:center;gap:0.65rem;}
.back-link{display:block;text-align:center;margin-top:1.5rem;color:var(--text-muted);font-size:0.85rem;}
.back-link:hover{color:var(--primary);}
</style>
</head>
<body>
<div class="card">
    <div class="logo">Maison Decor</div>
    <div class="subtitle"><i class="fa-solid fa-shield-halved"></i> Admin Panel Access</div>
    <?php if ($error): ?>
        <div class="alert"><i class="fa-solid fa-circle-exclamation"></i><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="inp-wrap">
            <label>Admin Email</label>
            <div style="position:relative;">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" required placeholder="admin@homedecor.com" value="<?php echo htmlspecialchars($_POST['email']??''); ?>">
            </div>
        </div>
        <div class="inp-wrap">
            <label>Password</label>
            <div style="position:relative;">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="password" required placeholder="Your admin password" id="pass">
                <button type="button" onclick="const i=document.getElementById('pass');i.type=i.type==='password'?'text':'password';"
                        style="position:absolute;right:1rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="btn"><i class="fa-solid fa-right-to-bracket"></i> Login to Admin</button>
    </form>
    <a href="../index.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Store</a>
</div>
</body>
</html>

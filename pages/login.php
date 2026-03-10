<?php
session_start();
require_once '../php/config/database.php';
require_once '../php/config/helpers.php';

// Already logged in - redirect based on role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: ../admin/index.php');
    } else {
        header('Location: ../index.php');
    }
    exit;
}

$error   = '';
$success = '';
$tab     = $_GET['tab'] ?? 'login';

// ===== HANDLE LOGIN =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'login') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $tab      = 'login';

    if (!$email || !$password) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: ../admin/index.php');
            } else {
                header('Location: ../index.php');
            }
            exit;
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}

// ===== HANDLE REGISTER =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'register') {
    $name     = trim($_POST['name']             ?? '');
    $email    = trim($_POST['email']            ?? '');
    $password = trim($_POST['password']         ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');
    $tab      = 'register';

    if (!$name || !$email || !$password || !$confirm) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'This email is already registered. Please login instead.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt   = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'customer')");
            $stmt->execute([$name, $email, $hashed]);

            $_SESSION['user_id']   = $pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            $_SESSION['user_role'] = 'customer';

            header('Location: ../index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Maison Decor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    </script>
    <style>
        /* Variables */
        :root {
            --bg:           #FAF7F2;
            --bg-card:      #FFFFFF;
            --text:         #1C1410;
            --text-muted:   #7A6A5A;
            --primary:      #A67C52;
            --primary-dark: #8B6340;
            --border:       #E0D5C8;
            --danger:       #C94545;
            --accent:       #6B9E78;
            --font-display: 'Playfair Display', serif;
            --font-body:    'Inter', sans-serif;
            --radius:       10px;
            --transition:   0.25s ease;
        }
        [data-theme="dark"] {
            --bg:        #141210;
            --bg-card:   #1E1A16;
            --text:      #F0EAE0;
            --text-muted:#9A8A78;
            --primary:   #C49A6C;
            --border:    #2E2620;
            --danger:    #E05555;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: var(--font-body);
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            transition: background var(--transition), color var(--transition);
        }

        /* ===== LEFT PANEL ===== */
        .left-panel {
            background: linear-gradient(160deg, #1C1410 0%, #5C3D1E 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 4rem 3rem;
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        .left-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('https://images.unsplash.com/photo-1616486338812-3dadae4b4ace?w=800&q=80') center/cover;
            opacity: 0.12;
        }
        .left-content { position: relative; z-index: 1; color: white; }
        .left-logo {
            font-family: var(--font-display);
            font-size: 2.75rem;
            font-weight: 700;
            color: #C49A6C;
            margin-bottom: 0.5rem;
            letter-spacing: 0.02em;
        }
        .left-tagline {
            font-size: 0.85rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.6);
            margin-bottom: 3rem;
        }
        .left-features { list-style: none; text-align: left; width: 100%; max-width: 280px; }
        .left-features li {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.85rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            font-size: 0.9rem;
            color: rgba(255,255,255,0.8);
        }
        .left-features li:last-child { border-bottom: none; }
        .left-features i { color: #C49A6C; width: 18px; text-align: center; }

        /* ===== RIGHT PANEL ===== */
        .right-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            background: var(--bg);
        }
        .form-box { width: 100%; max-width: 420px; }

        /* Theme toggle on login page */
        .login-theme-toggle {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 1rem;
            transition: all var(--transition);
            z-index: 10;
        }
        .login-theme-toggle:hover { color: var(--primary); border-color: var(--primary); }

        /* Tabs */
        .form-tabs {
            display: flex;
            border-bottom: 2px solid var(--border);
            margin-bottom: 2rem;
        }
        .form-tab {
            flex: 1;
            padding: 0.9rem;
            text-align: center;
            background: none;
            border: none;
            font-family: var(--font-body);
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all var(--transition);
        }
        .form-tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        /* Form sections */
        .form-section { display: none; }
        .form-section.active { display: block; }

        /* Welcome text */
        .welcome-title {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.35rem;
            color: var(--text);
        }
        .welcome-sub {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }

        /* Input groups */
        .input-group { margin-bottom: 1.25rem; }
        .input-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }
        .input-wrap { position: relative; }
        .input-wrap i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .input-wrap input {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.75rem;
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
            font-size: 0.95rem;
            font-family: var(--font-body);
            background: var(--bg-card);
            color: var(--text);
            transition: border-color var(--transition);
        }
        .input-wrap input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(166,124,82,0.12);
        }
        .toggle-pass {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 0.9rem;
        }
        .toggle-pass:hover { color: var(--primary); }

        /* Submit button */
        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 600;
            font-family: var(--font-body);
            cursor: pointer;
            letter-spacing: 0.04em;
            transition: all var(--transition);
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .submit-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(166,124,82,0.3);
        }

        /* Error / success */
        .alert {
            padding: 0.85rem 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            gap: 0.65rem;
        }
        .alert-error   { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
        .alert-success { background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }

        /* Admin note */
        .admin-note {
            margin-top: 1.5rem;
            padding: 0.85rem 1rem;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 0.82rem;
            color: var(--text-muted);
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body { grid-template-columns: 1fr; }
            .left-panel { display: none; }
        }
    </style>
</head>
<body>

    <!-- Left Branding Panel -->
    <div class="left-panel">
        <div class="left-content">
            <div class="left-logo">Maison Decor</div>
            <div class="left-tagline">Curated Home Decor</div>
            <ul class="left-features">
                <li><i class="fa-solid fa-house"></i> 10,000+ Premium Products</li>
                <li><i class="fa-solid fa-globe"></i> Shop in Your Currency</li>
                <li><i class="fa-solid fa-mobile-screen"></i> Pay with M-Pesa or Card</li>
                <li><i class="fa-solid fa-truck"></i> Free Shipping Over $200</li>
                <li><i class="fa-solid fa-rotate-left"></i> 30-Day Easy Returns</li>
                <li><i class="fa-solid fa-leaf"></i> Sustainably Sourced</li>
            </ul>
        </div>
    </div>

    <!-- Right Form Panel -->
    <div class="right-panel">
        <div class="form-box">

            <div class="welcome-title">Welcome Back</div>
            <div class="welcome-sub">Login or create an account to continue shopping</div>

            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?php echo clean($error); ?>
                </div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="form-tabs">
                <button class="form-tab <?php echo $tab === 'login' ? 'active' : ''; ?>"
                        onclick="switchFormTab('login')">
                    <i class="fa-solid fa-right-to-bracket"></i> Login
                </button>
                <button class="form-tab <?php echo $tab === 'register' ? 'active' : ''; ?>"
                        onclick="switchFormTab('register')">
                    <i class="fa-solid fa-user-plus"></i> Register
                </button>
            </div>

            <!-- LOGIN FORM -->
            <div class="form-section <?php echo $tab === 'login' ? 'active' : ''; ?>" id="loginSection">
                <form method="POST">
                    <input type="hidden" name="action" value="login">

                    <div class="input-group">
                        <label>Email Address</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-envelope"></i>
                            <input type="email"
                                   name="email"
                                   required
                                   placeholder="you@example.com"
                                   value="<?php echo clean($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Password</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password"
                                   name="password"
                                   required
                                   placeholder="Your password"
                                   id="loginPass">
                            <button type="button" class="toggle-pass" onclick="togglePass('loginPass', this)">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        Login to My Account
                    </button>
                </form>

               
            </div>

            <!-- REGISTER FORM -->
            <div class="form-section <?php echo $tab === 'register' ? 'active' : ''; ?>" id="registerSection">
                <form method="POST">
                    <input type="hidden" name="action" value="register">

                    <div class="input-group">
                        <label>Full Name</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-user"></i>
                            <input type="text"
                                   name="name"
                                   required
                                   placeholder="John Doe"
                                   value="<?php echo clean($_POST['name'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Email Address</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-envelope"></i>
                            <input type="email"
                                   name="email"
                                   required
                                   placeholder="you@example.com"
                                   value="<?php echo clean($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Password</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password"
                                   name="password"
                                   required
                                   placeholder="Minimum 6 characters"
                                   id="regPass">
                            <button type="button" class="toggle-pass" onclick="togglePass('regPass', this)">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Confirm Password</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password"
                                   name="confirm_password"
                                   required
                                   placeholder="Repeat your password"
                                   id="confirmPass">
                            <button type="button" class="toggle-pass" onclick="togglePass('confirmPass', this)">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fa-solid fa-user-plus"></i>
                        Create My Account
                    </button>
                </form>
            </div>

        </div>
    </div>

    <!-- Theme Toggle Button -->
    <button class="login-theme-toggle" onclick="toggleTheme()" title="Toggle Dark Mode">
        <i class="fa-solid fa-moon" id="themeIcon"></i>
    </button>

<script>
    // Apply saved theme
    function initTheme() {
        const saved = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', saved);
        updateThemeIcon(saved);
    }
    function toggleTheme() {
        const current = document.documentElement.getAttribute('data-theme');
        const next    = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        updateThemeIcon(next);
    }
    function updateThemeIcon(theme) {
        const icon = document.getElementById('themeIcon');
        if (!icon) return;
        icon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    }

    // Switch between login and register tabs
    function switchFormTab(tab) {
        document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.form-tab').forEach(t => t.classList.remove('active'));
        document.getElementById(tab + 'Section').classList.add('active');
        event.currentTarget.classList.add('active');
    }

    // Toggle password visibility
    function togglePass(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon  = btn.querySelector('i');
        if (input.type === 'password') {
            input.type    = 'text';
            icon.className = 'fa-solid fa-eye-slash';
        } else {
            input.type    = 'password';
            icon.className = 'fa-solid fa-eye';
        }
    }

    initTheme();
</script>
</body>
</html>
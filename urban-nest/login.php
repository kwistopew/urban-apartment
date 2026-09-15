<?php
session_start();
require_once __DIR__ . '/includes/db.php';
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . (($_SESSION['role'] ?? '') === 'admin' ? 'admin/dashboard.php' : 'tenant/dashboard.php'));
    exit;
}
$error = '';
$registered = isset($_GET['registered']);
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        $stmt = $conn->prepare('SELECT id, username, email, password, role FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $stmt = $conn->prepare('SELECT full_name FROM tenants WHERE user_id = ? LIMIT 1');
            $stmt->bind_param('i', $user['id']);
            $stmt->execute();
            $tenant = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $_SESSION['full_name'] = $tenant['full_name'] ?? ($user['role'] === 'admin' ? 'Administrator' : $user['username']);
            header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'tenant/dashboard.php'));
            exit;
        }
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Login - Apartment Rental</title><link rel="stylesheet" href="css/style.css?v=20260914-login2"><style>
.auth-language-bar{position:fixed!important;top:22px!important;left:28px!important;right:28px!important;z-index:99999!important;display:flex!important;justify-content:space-between!important;align-items:center!important;pointer-events:none!important}
.auth-language-bar>*{pointer-events:auto!important}
.auth-home-link{display:inline-flex!important;align-items:center!important;gap:7px!important;padding:10px 15px!important;min-height:40px!important;border-radius:12px!important;background:rgba(255,255,255,.94)!important;color:#263a63!important;text-decoration:none!important;border:1px solid rgba(214,222,235,.95)!important;box-shadow:0 8px 24px rgba(15,23,42,.12)!important;backdrop-filter:blur(12px)!important;-webkit-backdrop-filter:blur(12px)!important;font-size:11px!important;font-weight:800!important;line-height:1!important;transition:transform .22s ease,box-shadow .22s ease,background .22s ease!important}
.auth-home-link:hover{transform:translateX(-3px)!important;background:#fff!important;box-shadow:0 12px 28px rgba(15,23,42,.16)!important}
.auth-home-link:active{transform:translateX(-1px) scale(.98)!important}
.auth-language-bar .language-toggle{display:inline-flex!important;align-items:center!important;justify-content:center!important;min-height:40px!important;padding:9px 13px!important;border:1px solid rgba(214,222,235,.95)!important;border-radius:12px!important;background:rgba(255,255,255,.94)!important;color:#536177!important;box-shadow:0 8px 24px rgba(15,23,42,.12)!important;backdrop-filter:blur(12px)!important;-webkit-backdrop-filter:blur(12px)!important;cursor:pointer!important}
.auth-language-bar .language-toggle:hover{transform:translateY(-2px)!important;background:#fff!important;box-shadow:0 12px 28px rgba(15,23,42,.16)!important}
@media(max-width:1180px){.auth-language-bar{top:16px!important;left:20px!important;right:20px!important}.auth-home-link,.auth-language-bar .language-toggle{min-height:38px!important}}
@media(max-width:560px){.auth-language-bar{top:10px!important;left:12px!important;right:12px!important}.auth-home-link{padding:9px 11px!important;font-size:10px!important}.auth-language-bar .language-toggle{padding:8px 10px!important;font-size:10px!important}}
</style></head>
<body class="auth-page">
<div class="auth-language-bar"><div class="auth-top-actions"><a href="index.php" class="auth-home-link" data-i18n="back_home">← Back to Urban Nest</a><button class="language-toggle" id="languageToggle" type="button" aria-label="Change language"><span class="lang-active">EN</span><span> / </span><span>TL</span></button></div></div>
<div class="auth-layout">
    <section class="auth-visual"><div class="auth-slideshow" aria-hidden="true"><span class="auth-slide auth-slide-a"></span><span class="auth-slide auth-slide-b"></span></div><div class="auth-logo"><img src="assets/urban-nest-logo.png" alt="Urban Nest logo"></div><div class="auth-photo-label">URBAN NEST RESIDENCES</div><div class="auth-visual-copy"><span class="kicker">SMART • SIMPLE • ORGANIZED</span><h1 data-i18n="login_visual_title">Manage rentals without the paperwork.</h1><p data-i18n="login_visual_text">Track apartments, rental requests, tenants, and monthly payments in one clean workspace.</p></div></section>
    <section class="auth-card-wrap"><div class="auth-card">
        <div class="mobile-brand"><div class="auth-logo compact"><img src="assets/urban-nest-logo.png" alt="Urban Nest logo"></div><div><strong>Apartment Rental</strong><span>Management System</span></div></div>
        <div class="auth-heading"><span class="kicker" data-i18n="login_kicker">WELCOME BACK</span><h2 data-i18n="login_title">Sign in to your account</h2><p data-i18n="login_text">Enter your account details to continue.</p></div>
        <?php if ($registered): ?><div class="alert success">Account created successfully. You can sign in now.</div><?php endif; ?><?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post" class="form-grid single" data-validate>
            <div class="form-group"><label for="username" data-i18n="login_username">Username</label><div class="input-wrap"><span class="input-icon">@</span><input id="username" name="username" type="text" value="<?= htmlspecialchars($username) ?>" placeholder="Enter username" data-i18n-placeholder="username_placeholder" required maxlength="50" autocomplete="username"></div></div>
            <div class="form-group"><div class="label-row"><label for="password" data-i18n="login_password">Password</label><a class="auth-transition-link" data-auth-transition="left" href="forgot_password.php" data-i18n="forgot_password">Forgot password?</a></div><div class="input-wrap"><span class="input-icon">●</span><input id="password" name="password" type="password" placeholder="Enter password" data-i18n-placeholder="password_placeholder" required autocomplete="current-password"><button class="password-toggle" type="button" data-toggle-password="password">Show</button></div></div>
            <button class="btn primary full btn-large" type="submit"><span data-i18n="sign_in">Sign In</span> <span>→</span></button>
        </form>
        <div class="auth-divider"><span data-i18n="new_here">New here?</span></div>
        <a class="btn secondary full btn-large" href="register.php"><span data-i18n="create_account">Create a Tenant Account</span></a>
    </div></section>
</div><script src="js/script.js"></script></body></html>

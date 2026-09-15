<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$flash = get_flash();
$role = $_SESSION['role'] ?? '';
$displayName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$current = basename($_SERVER['PHP_SELF']);
$scriptDir = basename(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$isSubdir = in_array($scriptDir, ['admin', 'tenant'], true);
$root = $isSubdir ? '../' : '';
$accountUrl = $root . 'account.php';
$unreadMessages = 0;
if ($role === 'admin') {
    $stmtUnread = $conn->prepare("SELECT COUNT(*) c FROM messages WHERE sender_role='tenant' AND is_read=0");
    if ($stmtUnread) { $stmtUnread->execute(); $unreadMessages = (int)$stmtUnread->get_result()->fetch_assoc()['c']; $stmtUnread->close(); }
} elseif ($role === 'tenant' && !empty($_SESSION['user_id'])) {
    $stmtUnread = $conn->prepare("SELECT COUNT(*) c FROM messages m JOIN tenants t ON t.id=m.tenant_id WHERE t.user_id=? AND m.sender_role='admin' AND m.is_read=0");
    if ($stmtUnread) { $stmtUnread->bind_param('i', $_SESSION['user_id']); $stmtUnread->execute(); $unreadMessages = (int)$stmtUnread->get_result()->fetch_assoc()['c']; $stmtUnread->close(); }
}
function nav_icon(string $name): string {
    $icons = [
        'dashboard' => '⌂', 'apartments' => '▦', 'tenants' => '♟', 'requests' => '↗',
        'payments' => '₱', 'rental' => '⌂', 'leave' => '↪', 'messages' => '✉', 'account' => '⚙', 'logout' => '↪'
    ];
    return $icons[$name] ?? '•';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#172033">
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('urbanNestTheme');
                if (saved === 'dark') document.documentElement.dataset.theme = 'dark';
            } catch (e) {}
        }());
    </script>
    <title><?= e($pageTitle ?? 'Apartment Rental System') ?></title>
    <link rel="stylesheet" href="<?= e($root . 'css/style.css') ?>">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="brand-mark logo-brand"><img src="<?= e($root . 'assets/urban-nest-logo.png') ?>" alt="Urban Nest logo"></div>
            <div class="brand-copy"><strong>Urban Nest</strong><span>Apartment Rental Management</span></div>
        </div>

        <div class="sidebar-section-label">MENU</div>
        <nav class="nav-menu">
            <?php if ($role === 'admin'): ?>
                <a class="nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>" href="<?= e($root . 'admin/dashboard.php') ?>"><span class="nav-icon"><?= nav_icon('dashboard') ?></span>Dashboard</a>
                <a class="nav-link <?= $current === 'apartments.php' ? 'active' : '' ?>" href="<?= e($root . 'admin/apartments.php') ?>"><span class="nav-icon"><?= nav_icon('apartments') ?></span>Apartments</a>
                <a class="nav-link <?= $current === 'tenants.php' ? 'active' : '' ?>" href="<?= e($root . 'admin/tenants.php') ?>"><span class="nav-icon"><?= nav_icon('tenants') ?></span>Tenants</a>
                <a class="nav-link <?= $current === 'requests.php' ? 'active' : '' ?>" href="<?= e($root . 'admin/requests.php') ?>"><span class="nav-icon"><?= nav_icon('requests') ?></span>Rental Requests</a>
                <a class="nav-link <?= $current === 'leave_requests.php' ? 'active' : '' ?>" href="<?= e($root . 'admin/leave_requests.php') ?>"><span class="nav-icon"><?= nav_icon('leave') ?></span>Leave Requests</a>
                <a class="nav-link <?= $current === 'payments.php' ? 'active' : '' ?>" href="<?= e($root . 'admin/payments.php') ?>"><span class="nav-icon"><?= nav_icon('payments') ?></span>Payments</a>
                <a class="nav-link <?= $current === 'messages.php' ? 'active' : '' ?>" href="<?= e($root . 'admin/messages.php') ?>"><span class="nav-icon"><?= nav_icon('messages') ?></span>Customer Support<?php if ($unreadMessages): ?><span class="nav-badge"><?= $unreadMessages > 99 ? '99+' : $unreadMessages ?></span><?php endif; ?></a>
            <?php elseif ($role === 'tenant'): ?>
                <a class="nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>" href="<?= e($root . 'tenant/dashboard.php') ?>"><span class="nav-icon"><?= nav_icon('dashboard') ?></span>Dashboard</a>
                <a class="nav-link <?= $current === 'apartments.php' ? 'active' : '' ?>" href="<?= e($root . 'tenant/apartments.php') ?>"><span class="nav-icon"><?= nav_icon('apartments') ?></span>Available Apartments</a>
                <a class="nav-link <?= $current === 'rental.php' ? 'active' : '' ?>" href="<?= e($root . 'tenant/rental.php') ?>"><span class="nav-icon"><?= nav_icon('rental') ?></span>My Rental</a>
                <a class="nav-link <?= $current === 'payments.php' ? 'active' : '' ?>" href="<?= e($root . 'tenant/payments.php') ?>"><span class="nav-icon"><?= nav_icon('payments') ?></span>Payments</a>
                <a class="nav-link <?= $current === 'messages.php' ? 'active' : '' ?>" href="<?= e($root . 'tenant/messages.php') ?>"><span class="nav-icon"><?= nav_icon('messages') ?></span>Customer Support<?php if ($unreadMessages): ?><span class="nav-badge"><?= $unreadMessages > 99 ? '99+' : $unreadMessages ?></span><?php endif; ?></a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-user">
            <div class="avatar"><?= e(strtoupper(substr($displayName, 0, 1))) ?></div>
            <div class="user-mini"><strong><?= e($displayName) ?></strong><span><?= e(ucfirst($role)) ?><?= $email ? ' • ' . e($email) : '' ?></span></div>
        </div>
    </aside>

    <div class="main-area">
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle" type="button" aria-label="Open navigation">☰</button>
            <div class="topbar-heading">
                <div class="eyebrow">APARTMENT RENTAL SYSTEM</div>
                <h1><?= e($pageTitle ?? 'Apartment Rental System') ?></h1>
                <p><?= e($pageSubtitle ?? 'Manage your apartment rental activities.') ?></p>
            </div>
            <div class="topbar-actions">
                <button class="theme-toggle" id="themeToggle" type="button" aria-label="Switch theme" title="Switch theme">
                    <span class="theme-icon" aria-hidden="true">☾</span>
                    <span class="theme-label">Dark mode</span>
                </button>
                <div class="account-menu">
                    <button class="account-chip" id="accountMenuToggle" type="button" aria-expanded="false" aria-haspopup="menu" aria-controls="accountDropdown">
                        <div class="avatar small"><?= e(strtoupper(substr($displayName, 0, 1))) ?></div>
                        <div><span>Signed in as</span><strong><?= e($displayName) ?></strong></div>
                        <span class="chevron">⌄</span>
                    </button>
                    <div class="account-dropdown" id="accountDropdown" role="menu" aria-hidden="true">
                        <div class="account-dropdown-profile">
                            <div class="avatar"><?= e(strtoupper(substr($displayName, 0, 1))) ?></div>
                            <div>
                                <strong><?= e($displayName) ?></strong>
                                <span><?= e(ucfirst($role)) ?><?= $email ? ' • ' . e($email) : '' ?></span>
                            </div>
                        </div>
                        <div class="account-dropdown-divider"></div>
                        <a class="account-dropdown-item" href="<?= e($accountUrl) ?>" role="menuitem">
                            <span class="dropdown-icon">⚙</span>
                            <span><strong>Settings</strong><small>Account preferences</small></span>
                        </a>
                        <a class="account-dropdown-item signout" href="<?= e($root . 'logout.php') ?>" role="menuitem">
                            <span class="dropdown-icon">↪</span>
                            <span><strong>Sign out</strong><small>Log out of Urban Nest</small></span>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <main class="page-content">
            <?php if ($flash): ?><div class="alert <?= e($flash['type']) ?> auto-dismiss"><?= e($flash['message']) ?></div><?php endif; ?>

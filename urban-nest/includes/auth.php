<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        $path = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
        $login = (strpos($path, '/admin/') !== false || strpos($path, '/tenant/') !== false) ? '../login.php' : 'login.php';
        header('Location: ' . $login);
        exit;
    }
}

function require_role(string $role): void {
    require_login();
    if (($_SESSION['role'] ?? '') !== $role) {
        $path = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
        $prefix = (strpos($path, '/admin/') !== false || strpos($path, '/tenant/') !== false) ? '../' : '';
        $destination = $prefix . (($_SESSION['role'] ?? '') === 'admin' ? 'admin/dashboard.php' : 'tenant/dashboard.php');
        header('Location: ' . $destination);
        exit;
    }
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money(float $value): string {
    return '₱' . number_format($value, 2);
}

function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid security token. Please go back and try again.');
    }
}

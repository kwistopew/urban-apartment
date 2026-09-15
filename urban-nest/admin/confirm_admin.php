<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$action = $_GET['action'] ?? $_POST['action'] ?? 'confirm';
$submit = $_SERVER['REQUEST_METHOD'] === 'POST';

function page_message(string $title, string $message, string $kind = 'info', ?string $actionHtml = null): void {
    $accent = $kind === 'success' ? '#16805b' : ($kind === 'danger' ? '#c23434' : '#4056d8');
    echo '<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>
<style>
*{box-sizing:border-box}
body{margin:0;font-family:Inter,Arial,sans-serif;background:#f4f7fb;color:#182033;min-height:100vh;display:grid;place-items:center;padding:24px}
.card{width:min(640px,100%);background:#fff;border:1px solid #e4e9f2;border-radius:22px;box-shadow:0 20px 50px rgba(23,32,51,.10);padding:34px}
.brand{font-size:12px;letter-spacing:2px;font-weight:800;color:#63708a}
h1{margin:10px 0 12px;font-size:28px}
p{color:#65708a;line-height:1.65}
.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:24px}
button,a.btn{border:0;text-decoration:none;cursor:pointer;font:inherit;font-weight:700;padding:12px 18px;border-radius:12px;display:inline-block}
.primary{background:#4056d8;color:#fff}
.secondary{background:#eef1f6;color:#26334d}
.alert{border-radius:14px;padding:13px 15px;margin:18px 0;background:#f4f7ff;color:#4056d8}
.success{background:#ebfaf4;color:#16805b}.danger{background:#fff0f0;color:#c23434}
.small{font-size:13px}
</style>
</head>
<body>
<div class="card">
<div class="brand">APARTMENT RENTAL SYSTEM</div>
<h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>
<p>' . $message . '</p>
' . ($actionHtml ?: '') . '
</div>
</body>
</html>';
}

if ($id <= 0 || $token === '' || !in_array($action, ['confirm','ignore'], true)) {
    http_response_code(400);
    page_message('Invalid confirmation link', 'This email link is incomplete or invalid.', 'danger');
    exit;
}

$stmt = $conn->prepare("SELECT apv.*, u.role AS target_role, t.full_name, u.username
                        FROM admin_promotion_verifications apv
                        JOIN users u ON u.id=apv.target_user_id
                        JOIN tenants t ON t.user_id=u.id
                        WHERE apv.id=? AND apv.verified_at IS NULL AND apv.expires_at >= NOW()
                        LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$verification = $stmt->get_result()->fetch_assoc();
$stmt->close();

$tokenHash = hash('sha256', $token);
if (!$verification || !hash_equals((string)$verification['code_hash'], $tokenHash)) {
    http_response_code(410);
    page_message('Link expired or invalid', 'This admin promotion link is invalid, expired, or has already been used. Return to Tenant Management and send a new confirmation email.', 'danger',
        '<div class="actions"><a class="btn secondary" href="tenants.php">Back to Tenant Management</a></div>');
    exit;
}

if ($submit) {
    if (!hash_equals((string)$verification['code_hash'], $tokenHash)) {
        http_response_code(410);
        page_message('Link expired or invalid', 'This confirmation link is no longer valid.', 'danger');
        exit;
    }

    if ($action === 'ignore') {
        $stmt = $conn->prepare('UPDATE admin_promotion_verifications SET verified_at=NOW() WHERE id=? AND verified_at IS NULL');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        page_message('Request ignored', 'The admin promotion request was cancelled. No account was changed.', 'info',
            '<div class="actions"><a class="btn primary" href="../login.php">Back to Login</a></div>');
        exit;
    }

    if ($verification['target_role'] !== 'tenant') {
        $stmt = $conn->prepare('UPDATE admin_promotion_verifications SET verified_at=NOW() WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        page_message('Already completed', 'This account is no longer a tenant, so no promotion was made.');
        exit;
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT role FROM users WHERE id=? FOR UPDATE");
        $stmt->bind_param('i', $verification['target_user_id']);
        $stmt->execute();
        $latest = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$latest || $latest['role'] !== 'tenant') {
            throw new RuntimeException('The account is no longer a tenant.');
        }

        $stmt = $conn->prepare("UPDATE users SET role='admin' WHERE id=? AND role='tenant'");
        $stmt->bind_param('i', $verification['target_user_id']);
        $stmt->execute();
        $changed = $stmt->affected_rows;
        $stmt->close();

        if ($changed !== 1) {
            throw new RuntimeException('The account could not be promoted.');
        }

        $stmt = $conn->prepare('UPDATE admin_promotion_verifications SET verified_at=NOW() WHERE id=? AND verified_at IS NULL');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        page_message('Admin promotion confirmed',
            '<strong>' . htmlspecialchars($verification['full_name'], ENT_QUOTES, 'UTF-8') . '</strong> is now an administrator.', 'success',
            '<div class="actions"><a class="btn primary" href="../login.php">Back to Login</a></div>');
    } catch (Throwable $e) {
        $conn->rollback();
        http_response_code(500);
        page_message('Promotion failed', htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'), 'danger');
    }
    exit;
}

$title = $action === 'ignore' ? 'Ignore admin promotion?' : 'Confirm admin promotion';
$message = $action === 'ignore'
    ? 'This will cancel the pending admin promotion request for <strong>' . htmlspecialchars($verification['full_name'], ENT_QUOTES, 'UTF-8') . '</strong>.'
    : 'You are confirming that <strong>' . htmlspecialchars($verification['full_name'], ENT_QUOTES, 'UTF-8') . '</strong> should be promoted to an administrator.';

$form = '<form method="post" class="actions">'
      . '<input type="hidden" name="id" value="' . $id . '">'
      . '<input type="hidden" name="token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">'
      . '<input type="hidden" name="action" value="' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '">'
      . ($action === 'ignore'
            ? '<button class="secondary" type="submit">Yes, Ignore Request</button>'
            : '<button class="primary" type="submit">Confirm Admin Promotion</button>')
      . '<a class="btn secondary" href="../login.php">Cancel</a>'
      . '</form>';

page_message($title, $message . '<div class="alert small">This page is an extra safety step because email providers may automatically open links to scan them for security.</div>', 'info', $form);

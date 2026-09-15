<?php
require_once '../includes/auth.php';
require_once '../includes/mailer.php';
require_role('admin');

$message = '';
$error = '';
$admin = [];
$userId = (int)($_SESSION['user_id'] ?? 0);
$stmt = $conn->prepare('SELECT username,email FROM users WHERE id=? AND role=\'admin\' LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $smtpError = '';
    $html = '<div style="font-family:Arial,sans-serif;padding:24px"><h2>Apartment Rental SMTP Test</h2><p>Your SMTP configuration is working.</p><p>This is a test email sent from your XAMPP project.</p></div>';
    $ok = send_smtp_email(
        $admin['email'] ?? '',
        $admin['username'] ?? 'Administrator',
        'Apartment Rental - SMTP Test Email',
        $html,
        "Apartment Rental SMTP Test\n\nYour SMTP configuration is working.\nThis is a test email sent from your XAMPP project.",
        $smtpError
    );
    if ($ok) {
        $message = 'Test email sent successfully to ' . ($admin['email'] ?? 'your admin email') . '. Check the inbox and spam folder.';
    } else {
        $error = $smtpError ?: 'Unknown SMTP error.';
    }
}

require '../includes/header.php';
?>
<div class="page-heading"><div><span class="kicker">SYSTEM TOOLS</span><h1>SMTP Email Test</h1><p>Use this page to verify your Gmail SMTP configuration.</p></div></div>
<section class="panel narrow-panel">
    <h2>Send a test email</h2>
    <p class="muted">Current admin email: <strong><?= e($admin['email'] ?? 'Not configured') ?></strong></p>
    <?php if ($message): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><strong>SMTP test failed:</strong><br><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <button class="btn primary" type="submit">Send Test Email</button>
        <a class="btn secondary" href="tenants.php">Back to Tenants</a>
    </form>
</section>
<?php require '../includes/footer.php'; ?>

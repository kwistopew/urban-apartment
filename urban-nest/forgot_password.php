<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/password_reset.php';

$message = '';
$error = '';
$emailSent = false;
$smtpError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare('SELECT id, username, email FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $message = 'If an account exists for that email, a password reset email has been sent.';

        if ($user) {
            try {
                $token = create_password_reset($conn, (int)$user['id']);
                $resetUrl = rtrim(APP_URL, '/') . '/reset_password.php?token=' . urlencode($token);
                $displayName = $user['username'];

                $safeName = e($displayName);
                $safeUrl = e($resetUrl);
                $htmlBody = <<<HTML
<!doctype html>
<html lang="en">
<head><meta charset="UTF-8"><title>Password Reset</title></head>
<body style="margin:0;background:#f4f7fb;font-family:Arial,sans-serif;color:#172033;">
<div style="max-width:620px;margin:40px auto;background:#ffffff;border:1px solid #e5eaf2;border-radius:18px;overflow:hidden;">
  <div style="padding:28px 32px;background:#111a2e;color:#ffffff;">
    <div style="font-size:14px;letter-spacing:1px;font-weight:700;opacity:.8;">APARTMENT RENTAL SYSTEM</div>
    <h1 style="margin:10px 0 0;font-size:28px;">Password Reset Request</h1>
  </div>
  <div style="padding:32px;">
    <p style="font-size:16px;">Hello <strong>{$safeName}</strong>,</p>
    <p style="font-size:15px;line-height:1.7;">We received a request to reset your Apartment Rental Management System password.</p>
    <p style="margin:28px 0;"><a href="{$safeUrl}" style="display:inline-block;padding:14px 22px;background:#435cf5;color:#ffffff;text-decoration:none;border-radius:10px;font-weight:700;">Reset My Password</a></p>
    <p style="font-size:14px;line-height:1.7;color:#667085;">This reset link expires in 1 hour and can be used once. If you did not request a password reset, you can ignore this email.</p>
    <p style="font-size:13px;line-height:1.7;color:#98a2b3;word-break:break-all;">{$safeUrl}</p>
  </div>
</div>
</body>
</html>
HTML;

                $textBody = "Hello {$displayName},\n\nWe received a request to reset your Apartment Rental Management System password.\n\nOpen this link to reset your password:\n{$resetUrl}\n\nThis link expires in 1 hour. If you did not request this, ignore this email.";

                $emailSent = send_smtp_email(
                    $user['email'],
                    $displayName,
                    'Password Reset - Apartment Rental Management System',
                    $htmlBody,
                    $textBody,
                    $smtpError
                );
            } catch (Throwable $e) {
                $error = 'We could not create a password reset request. Please try again.';
                error_log('Apartment Rental password reset creation error: ' . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - Apartment Rental</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
<div class="auth-layout compact single-auth">
<section class="auth-visual"><div class="auth-slideshow" aria-hidden="true"><span class="auth-slide auth-slide-a"></span><span class="auth-slide auth-slide-b"></span></div>
<div class="auth-logo"><img src="assets/urban-nest-logo.png" alt="Urban Nest logo"></div>
<div class="auth-visual-copy">
<span class="kicker">PASSWORD RECOVERY</span>
<h1>Get back into your account.</h1>
<p>Enter your registered email and we'll send a secure password-reset link to your inbox.</p>
</div>
<div class="feature-list"><span>✓ Email delivery</span><span>✓ Secure random token</span><span>✓ One-hour expiry</span></div>
</section>
<section class="auth-card-wrap">
<div class="auth-card">
<a class="back-link auth-transition-link" data-auth-transition="right" href="login.php">← Back to login</a>
<div class="auth-heading"><span class="kicker">FORGOT PASSWORD</span><h2>Reset your password</h2><p>We'll email a link to your registered account.</p></div>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<?php if ($message): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>
<?php if ($message && !$emailSent && !$error): ?>
<div class="alert warning"><strong>Email could not be sent yet.</strong><br><?= e($smtpError ?: 'Check includes/mail_config.php and your Gmail App Password.') ?></div>
<?php endif; ?>
<form method="post" class="form-grid single" data-validate>
<div class="form-group"><label for="email">Email Address</label><input id="email" type="email" name="email" required maxlength="120" placeholder="you@email.com" autocomplete="email"></div>
<button class="btn primary full btn-large" type="submit">Send Reset Email <span>→</span></button>
</form>
<div class="auth-footer-link"><a class="auth-transition-link" data-auth-transition="right" href="login.php">Back to sign in</a></div>
</div>
</section>
</div>
<script src="js/script.js"></script>
</body>
</html>

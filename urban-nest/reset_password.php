<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/password_reset.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$success = '';
$validReset = null;

if ($token !== '') {
    $validReset = find_valid_password_reset($conn, $token);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validReset) {
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            if (consume_password_reset($conn, (int)$validReset['reset_id'], (int)$validReset['user_id'], $hash)) {
                $validReset = null;
                $success = 'Your password has been changed successfully. You can now sign in with your new password.';
            } else {
                $error = 'This reset link is no longer valid. Please request a new one.';
            }
        } catch (Throwable $e) {
            error_log('Apartment Rental password reset error: ' . $e->getMessage());
            $error = 'We could not update your password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - Apartment Rental</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
<div class="auth-layout compact single-auth">
<section class="auth-visual"><div class="auth-slideshow" aria-hidden="true"><span class="auth-slide auth-slide-a"></span><span class="auth-slide auth-slide-b"></span></div>
<div class="auth-logo"><img src="assets/urban-nest-logo.png" alt="Urban Nest logo"></div>
<div class="auth-visual-copy">
<span class="kicker">NEW PASSWORD</span>
<h1>Choose a stronger password.</h1>
<p>Use at least six characters. Your old password will no longer work after the reset.</p>
</div>
</section>
<section class="auth-card-wrap">
<div class="auth-card">
<a class="back-link" href="login.php">← Back to login</a>
<div class="auth-heading">
<span class="kicker">PASSWORD RESET</span>
<h2><?= $success ? 'Password updated' : ($validReset ? 'Set a new password' : 'Reset link expired') ?></h2>
<p><?= $success ? 'Your password has been changed successfully.' : ($validReset ? 'Choose a new password for ' . e($validReset['username']) . '.' : 'This reset link is invalid, expired, or has already been used. Request a new link from the Forgot Password page.') ?></p>
</div>

<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?>
<div class="alert success"><?= e($success) ?></div>
<a class="btn primary full btn-large" href="login.php">Return to Login <span>→</span></a>
<?php elseif ($validReset): ?>
<form method="post" class="form-grid single" data-validate>
<input type="hidden" name="token" value="<?= e($token) ?>">
<div class="form-group">
<label for="newPass">New Password</label>
<div class="input-wrap"><input id="newPass" name="password" type="password" minlength="6" required autocomplete="new-password"><button class="password-toggle" type="button" data-toggle-password="newPass">Show</button></div>
</div>
<div class="form-group">
<label for="newPass2">Confirm Password</label>
<div class="input-wrap"><input id="newPass2" name="confirm_password" type="password" minlength="6" required autocomplete="new-password"><button class="password-toggle" type="button" data-toggle-password="newPass2">Show</button></div>
</div>
<button class="btn primary full btn-large" type="submit">Change Password <span>→</span></button>
</form>
<?php else: ?>
<a class="btn secondary full btn-large" href="forgot_password.php">Request a New Reset Link <span>→</span></a>
<?php endif; ?>
<div class="auth-footer-link"><a href="login.php">Back to sign in</a></div>
</div>
</section>
</div>
<script src="js/script.js"></script>
</body>
</html>

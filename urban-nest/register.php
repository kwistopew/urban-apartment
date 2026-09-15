<?php
session_start();
require_once __DIR__ . '/includes/db.php';
if (!empty($_SESSION['user_id'])) { header('Location: ' . (($_SESSION['role'] ?? '') === 'admin' ? 'admin/dashboard.php' : 'tenant/dashboard.php')); exit; }
$error='';
$old=['full_name'=>'','email'=>'','contact_number'=>'','address'=>'','username'=>''];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    foreach ($old as $key => $_) $old[$key] = trim($_POST[$key] ?? '');
    $password=$_POST['password']??''; $confirm=$_POST['confirm_password']??'';
    if ($old['full_name']==='' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL) || $old['username']==='' || strlen($password)<6) $error='Please complete all required fields. Password must be at least 6 characters.';
    elseif ($password!==$confirm) $error='Passwords do not match.';
    else {
        $stmt=$conn->prepare('SELECT id FROM users WHERE username=? OR email=? LIMIT 1'); $stmt->bind_param('ss',$old['username'],$old['email']); $stmt->execute(); $existing=$stmt->get_result()->fetch_assoc(); $stmt->close();
        if ($existing) $error='That username or email is already registered.';
        else {
            $conn->begin_transaction();
            try {
                $hash=password_hash($password,PASSWORD_DEFAULT);
                $stmt=$conn->prepare("INSERT INTO users (username,email,password,role) VALUES (?,?,?,'tenant')"); $stmt->bind_param('sss',$old['username'],$old['email'],$hash); $stmt->execute(); $userId=$conn->insert_id; $stmt->close();
                $stmt=$conn->prepare('INSERT INTO tenants (user_id,full_name,contact_number,address) VALUES (?,?,?,?)'); $stmt->bind_param('isss',$userId,$old['full_name'],$old['contact_number'],$old['address']); $stmt->execute(); $stmt->close();
                $conn->commit();
                header('Location: login.php?registered=1'); exit;
            } catch (Throwable $e) { $conn->rollback(); $error='Unable to create account. Please check your information.'; }
        }
    }
}
$registered = isset($_GET['registered']);
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Create Account - Apartment Rental</title><link rel="stylesheet" href="css/style.css"></head>
<body class="auth-page"><div class="auth-layout compact"><section class="auth-visual"><div class="auth-slideshow" aria-hidden="true"><span class="auth-slide auth-slide-a"></span><span class="auth-slide auth-slide-b"></span></div><div class="auth-logo"><img src="assets/urban-nest-logo.png" alt="Urban Nest logo"></div><div class="auth-photo-label">URBAN NEST RESIDENCES</div><div class="auth-visual-copy"><span class="kicker">CREATE YOUR ACCOUNT</span><h1>Find your place and manage your rental.</h1><p>Register once, then browse apartments, submit requests, and track your payments.</p></div><div class="feature-list"><span>✓ Apartment requests</span><span>✓ Rental history</span><span>✓ Payment tracking</span></div></section>
<section class="auth-card-wrap"><div class="auth-card wide"><a class="back-link" href="login.php">← Back to login</a><div class="auth-heading"><span class="kicker">TENANT REGISTRATION</span><h2>Create your account</h2><p>Fill in the details below. You can update your email and password later in My Account.</p></div><?php if($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="form-grid two-col" data-validate><div class="form-group"><label>Full Name *</label><input name="full_name" value="<?= e($old['full_name']) ?>" required maxlength="100" placeholder="Juan Dela Cruz"></div><div class="form-group"><label>Email Address *</label><input name="email" type="email" value="<?= e($old['email']) ?>" required maxlength="120" placeholder="juan@email.com"></div><div class="form-group"><label>Username *</label><input name="username" value="<?= e($old['username']) ?>" required maxlength="50" placeholder="juan123"></div><div class="form-group"><label>Contact Number</label><input name="contact_number" value="<?= e($old['contact_number']) ?>" maxlength="30" placeholder="09xxxxxxxxx"></div><div class="form-group full-span"><label>Address</label><textarea name="address" rows="3" maxlength="255" placeholder="Your home address"><?= e($old['address']) ?></textarea></div><div class="form-group"><label>Password *</label><div class="input-wrap"><input id="regPassword" name="password" type="password" minlength="6" required placeholder="At least 6 characters"><button class="password-toggle" type="button" data-toggle-password="regPassword">Show</button></div></div><div class="form-group"><label>Confirm Password *</label><div class="input-wrap"><input id="regConfirm" name="confirm_password" type="password" minlength="6" required placeholder="Repeat your password"><button class="password-toggle" type="button" data-toggle-password="regConfirm">Show</button></div></div><div class="form-actions full-span"><button class="btn primary full btn-large" type="submit">Create Account <span>→</span></button></div></form></div></section></div><script src="js/script.js"></script></body></html>

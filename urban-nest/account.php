<?php
require_once 'includes/auth.php';
require_login();
$pageTitle='My Account';
$pageSubtitle='Update your profile information, email, and password.';
$userId=(int)$_SESSION['user_id'];
$error=''; $success='';
$stmt=$conn->prepare('SELECT id,username,email FROM users WHERE id=? LIMIT 1'); $stmt->bind_param('i',$userId); $stmt->execute(); $account=$stmt->get_result()->fetch_assoc(); $stmt->close();
$profile=null;
if($_SESSION['role']==='tenant'){ $stmt=$conn->prepare('SELECT full_name,contact_number,address FROM tenants WHERE user_id=? LIMIT 1'); $stmt->bind_param('i',$userId); $stmt->execute(); $profile=$stmt->get_result()->fetch_assoc(); $stmt->close(); }
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf(); $action=$_POST['action']??'';
    if($action==='profile'){
        $email=trim($_POST['email']??'');
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)) $error='Please enter a valid email address.';
        else {
            $stmt=$conn->prepare('SELECT id FROM users WHERE email=? AND id<>? LIMIT 1'); $stmt->bind_param('si',$email,$userId); $stmt->execute(); $exists=$stmt->get_result()->fetch_assoc(); $stmt->close();
            if($exists) $error='That email address is already in use.';
            else {
                if($_SESSION['role']==='tenant'){
                    $fullName=trim($_POST['full_name']??''); $contact=trim($_POST['contact_number']??''); $address=trim($_POST['address']??'');
                    if($fullName==='') $error='Full name is required.';
                    else { $stmt=$conn->prepare('UPDATE users SET email=? WHERE id=?'); $stmt->bind_param('si',$email,$userId); $stmt->execute(); $stmt->close(); $stmt=$conn->prepare('UPDATE tenants SET full_name=?,contact_number=?,address=? WHERE user_id=?'); $stmt->bind_param('sssi',$fullName,$contact,$address,$userId); $stmt->execute(); $stmt->close(); $_SESSION['email']=$email; $_SESSION['full_name']=$fullName; $success='Profile updated successfully.'; }
                } else { $stmt=$conn->prepare('UPDATE users SET email=? WHERE id=?'); $stmt->bind_param('si',$email,$userId); $stmt->execute(); $stmt->close(); $_SESSION['email']=$email; $success='Email address updated successfully.'; }
            }
        }
    } elseif($action==='password'){
        $currentPassword=$_POST['current_password']??''; $newPassword=$_POST['new_password']??''; $confirm=$_POST['confirm_password']??'';
        $stmt=$conn->prepare('SELECT password FROM users WHERE id=? LIMIT 1'); $stmt->bind_param('i',$userId); $stmt->execute(); $record=$stmt->get_result()->fetch_assoc(); $stmt->close();
        if(!$record || !password_verify($currentPassword,$record['password'])) $error='Your current password is incorrect.';
        elseif(strlen($newPassword)<6) $error='New password must be at least 6 characters.';
        elseif($newPassword!==$confirm) $error='New passwords do not match.';
        else { $hash=password_hash($newPassword,PASSWORD_DEFAULT); $stmt=$conn->prepare('UPDATE users SET password=? WHERE id=?'); $stmt->bind_param('si',$hash,$userId); $stmt->execute(); $stmt->close(); $success='Password changed successfully.'; }
    }
}
// Reload latest values after updates
$stmt=$conn->prepare('SELECT username,email FROM users WHERE id=? LIMIT 1'); $stmt->bind_param('i',$userId); $stmt->execute(); $account=$stmt->get_result()->fetch_assoc(); $stmt->close();
if($_SESSION['role']==='tenant'){ $stmt=$conn->prepare('SELECT full_name,contact_number,address FROM tenants WHERE user_id=? LIMIT 1'); $stmt->bind_param('i',$userId); $stmt->execute(); $profile=$stmt->get_result()->fetch_assoc(); $stmt->close(); }
require 'includes/header.php';
?>
<div class="account-layout">
<section class="profile-hero panel"><div class="profile-cover"></div><div class="profile-body"><div class="profile-avatar"><?= e(strtoupper(substr($profile['full_name'] ?? 'Administrator',0,1))) ?></div><div class="profile-main"><span class="kicker">ACCOUNT PROFILE</span><h2><?= e($profile['full_name'] ?? 'Administrator') ?></h2><p><?= e($account['username']) ?> <span>•</span> <?= e(ucfirst($_SESSION['role'])) ?></p></div><span class="badge <?= $_SESSION['role']==='admin'?'admin-badge':'active' ?>"><?= e(ucfirst($_SESSION['role'])) ?></span></div></section>
<?php if($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?><?php if($success): ?><div class="alert success auto-dismiss"><?= e($success) ?></div><?php endif; ?>
<div class="account-grid">
<section class="panel"><div class="panel-head"><div><h2>Profile Information</h2><p>Keep your account details current.</p></div></div><form method="post" class="form-grid two-col"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="profile"><?php if($_SESSION['role']==='tenant'): ?><div class="form-group full-span"><label>Full Name</label><input name="full_name" value="<?= e($profile['full_name'] ?? '') ?>" required maxlength="100"></div><div class="form-group"><label>Contact Number</label><input name="contact_number" value="<?= e($profile['contact_number'] ?? '') ?>" maxlength="30"></div><div class="form-group"><label>Username</label><input value="<?= e($account['username']) ?>" disabled></div><div class="form-group full-span"><label>Address</label><textarea name="address" rows="3" maxlength="255"><?= e($profile['address'] ?? '') ?></textarea></div><?php else: ?><div class="form-group"><label>Username</label><input value="<?= e($account['username']) ?>" disabled></div><?php endif; ?><div class="form-group <?= $_SESSION['role']==='tenant'?'full-span':'' ?>"><label>Email Address</label><input type="email" name="email" value="<?= e($account['email']) ?>" required maxlength="120"></div><div class="form-actions full-span"><button class="btn primary" type="submit">Save Profile Changes</button></div></form></section>
<section class="panel"><div class="panel-head"><div><h2>Change Password</h2><p>Use your current password to set a new one.</p></div></div><form method="post" class="form-grid single"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="password"><div class="form-group"><label>Current Password</label><div class="input-wrap"><input id="currentPassword" type="password" name="current_password" required><button class="password-toggle" type="button" data-toggle-password="currentPassword">Show</button></div></div><div class="form-group"><label>New Password</label><div class="input-wrap"><input id="accountNewPassword" type="password" name="new_password" minlength="6" required><button class="password-toggle" type="button" data-toggle-password="accountNewPassword">Show</button></div></div><div class="form-group"><label>Confirm New Password</label><div class="input-wrap"><input id="accountConfirmPassword" type="password" name="confirm_password" minlength="6" required><button class="password-toggle" type="button" data-toggle-password="accountConfirmPassword">Show</button></div></div><div class="form-actions"><button class="btn primary" type="submit">Change Password</button><a class="btn secondary" href="forgot_password.php">Forgot Password</a></div></form></section>
</div></div>
<?php require 'includes/footer.php'; ?>

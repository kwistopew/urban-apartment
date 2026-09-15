<?php
require_once '../includes/auth.php';
require_once '../includes/mailer.php';
require_role('admin');

$pageTitle = 'Tenant Management';
$pageSubtitle = 'Create, edit, and manage tenant profiles.';
$error = '';
$editing = null;
$pendingPromotion = null;

// admin_promotion_verifications is created by the Supabase SQL schema.


$userId = (int)$_SESSION['user_id'];

$stmt = $conn->prepare('SELECT username,email FROM users WHERE id=? AND role=\'admin\' LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$currentAdmin = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

$stmt = $conn->prepare("SELECT apv.id, apv.target_user_id, apv.expires_at, t.full_name, u.email, u.username
                       FROM admin_promotion_verifications apv
                       JOIN users u ON u.id=apv.target_user_id
                       JOIN tenants t ON t.user_id=u.id
                       WHERE apv.id=(
                           SELECT MAX(id) FROM admin_promotion_verifications
                           WHERE requested_by=? AND verified_at IS NULL AND expires_at>NOW()
                       ) LIMIT 1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$pendingPromotion = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare('SELECT t.*,u.username,u.email,u.role FROM tenants t JOIN users u ON u.id=t.user_id WHERE t.id=? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editing = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $tenantId = (int)($_POST['tenant_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact = trim($_POST['contact_number'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $username === '' || ($tenantId === 0 && strlen($password) < 6)) {
            $error = 'Please complete the required fields. New passwords must be at least 6 characters.';
        } else {
            $owner = null;
            if ($tenantId > 0) {
                $stmt = $conn->prepare('SELECT user_id FROM tenants WHERE id=? LIMIT 1');
                $stmt->bind_param('i', $tenantId);
                $stmt->execute();
                $owner = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!$owner) $error = 'Tenant not found.';
            }

            if (!$error) {
                $userIdToCheck = (int)($owner['user_id'] ?? 0);
                $stmt = $conn->prepare($owner
                    ? 'SELECT id FROM users WHERE (username=? OR email=?) AND id<>? LIMIT 1'
                    : 'SELECT id FROM users WHERE username=? OR email=? LIMIT 1');
                if ($owner) $stmt->bind_param('ssi', $username, $email, $userIdToCheck);
                else $stmt->bind_param('ss', $username, $email);
                $stmt->execute();
                $exists = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($exists) $error = 'Username or email is already in use.';
            }

            if (!$error) {
                $conn->begin_transaction();
                try {
                    if ($owner) {
                        $userIdToCheck = (int)$owner['user_id'];
                        if ($password !== '') {
                            $hash = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $conn->prepare('UPDATE users SET username=?,email=?,password=? WHERE id=?');
                            $stmt->bind_param('sssi', $username, $email, $hash, $userIdToCheck);
                        } else {
                            $stmt = $conn->prepare('UPDATE users SET username=?,email=? WHERE id=?');
                            $stmt->bind_param('ssi', $username, $email, $userIdToCheck);
                        }
                        $stmt->execute();
                        $stmt->close();

                        $stmt = $conn->prepare('UPDATE tenants SET full_name=?,contact_number=?,address=? WHERE id=?');
                        $stmt->bind_param('sssi', $fullName, $contact, $address, $tenantId);
                        $stmt->execute();
                        $stmt->close();
                        flash('success', 'Tenant updated successfully.');
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO users(username,email,password,role) VALUES(?,?,?,'tenant')");
                        $stmt->bind_param('sss', $username, $email, $hash);
                        $stmt->execute();
                        $newUserId = $conn->insert_id;
                        $stmt->close();

                        $stmt = $conn->prepare('INSERT INTO tenants(user_id,full_name,contact_number,address) VALUES(?,?,?,?)');
                        $stmt->bind_param('isss', $newUserId, $fullName, $contact, $address);
                        $stmt->execute();
                        $stmt->close();
                        flash('success', 'Tenant account created successfully.');
                    }
                    $conn->commit();
                } catch (Throwable $e) {
                    $conn->rollback();
                    $error = $e->getCode() === 1062 ? 'Username or email already exists.' : 'Unable to save tenant.';
                }
            }
        }
        if (!$error) redirect('tenants.php');
    }

    if ($action === 'delete') {
        $tenantId = (int)($_POST['tenant_id'] ?? 0);
        $stmt = $conn->prepare('SELECT user_id FROM tenants WHERE id=?');
        $stmt->bind_param('i', $tenantId);
        $stmt->execute();
        $tenant = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($tenant) {
            $stmt = $conn->prepare("SELECT COUNT(*) c FROM rentals WHERE tenant_id=? AND status='Active'");
            $stmt->bind_param('i', $tenantId);
            $stmt->execute();
            $active = (int)$stmt->get_result()->fetch_assoc()['c'];
            $stmt->close();
            if ($active > 0) {
                flash('error', 'Active tenants cannot be deleted. End the rental first.');
            } else {
                $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role='tenant'");
                $stmt->bind_param('i', $tenant['user_id']);
                $stmt->execute();
                $stmt->close();
                flash('success', 'Tenant deleted successfully.');
            }
        }
        redirect('tenants.php');
    }

    if ($action === 'request_admin_promotion') {
        $tenantId = (int)($_POST['tenant_id'] ?? 0);

        $stmt = $conn->prepare("SELECT t.id,t.full_name,t.user_id,u.username,u.email,u.role,
                               (SELECT COUNT(*) FROM rentals r WHERE r.tenant_id=t.id AND r.status='Active') active_rental_count
                               FROM tenants t JOIN users u ON u.id=t.user_id
                               WHERE t.id=? LIMIT 1");
        $stmt->bind_param('i', $tenantId);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$target) {
            flash('error', 'The selected tenant could not be found.');
            redirect('tenants.php');
        }
        if ((int)$target['user_id'] === $userId) {
            flash('error', 'You cannot promote your own account.');
            redirect('tenants.php');
        }
        if ($target['role'] !== 'tenant') {
            flash('error', 'This account is already an administrator.');
            redirect('tenants.php');
        }
        if ((int)$target['active_rental_count'] > 0) {
            flash('error', 'This tenant has an active rental. End the rental before promoting the account to admin.');
            redirect('tenants.php');
        }
        if (empty($currentAdmin['email']) || !filter_var($currentAdmin['email'], FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Your admin account does not have a valid email address. Update My Account first.');
            redirect('tenants.php');
        }

        $stmt = $conn->prepare('UPDATE admin_promotion_verifications SET verified_at=NOW() WHERE requested_by=? AND verified_at IS NULL');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();

        
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $stmt = $conn->prepare('INSERT INTO admin_promotion_verifications(requested_by,target_user_id,code_hash,expires_at) VALUES(?,?,?,DATE_ADD(NOW(), INTERVAL 10 MINUTE))');
        $stmt->bind_param('iis', $userId, $target['user_id'], $tokenHash);
        $stmt->execute();
        $verificationId = $conn->insert_id;
        $stmt->close();

        $stmt = $conn->prepare('SELECT expires_at FROM admin_promotion_verifications WHERE id=? LIMIT 1');
        $stmt->bind_param('i', $verificationId);
        $stmt->execute();
        $expiresAt = (string)($stmt->get_result()->fetch_assoc()['expires_at'] ?? date('Y-m-d H:i:s', time() + 600));
        $stmt->close();

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseAdminUrl = $scheme . '://' . $host . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $confirmUrl = $baseAdminUrl . '/confirm_admin.php?id=' . (int)$verificationId . '&token=' . rawurlencode($token) . '&action=confirm';
        $ignoreUrl = $baseAdminUrl . '/confirm_admin.php?id=' . (int)$verificationId . '&token=' . rawurlencode($token) . '&action=ignore';

        $smtpError = '';
        $subject = 'Apartment Rental - Confirm Admin Promotion';
        $safeAdminName = e($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Administrator');
        $safeTargetName = e($target['full_name']);
        $safeAdminEmail = e($currentAdmin['email']);
        $safeConfirmUrl = e($confirmUrl);
        $safeIgnoreUrl = e($ignoreUrl);
        $htmlBody = '<div style="font-family:Arial,sans-serif;background:#f5f7fb;padding:32px;color:#172033">'
            . '<div style="max-width:600px;margin:auto;background:#fff;border:1px solid #e5e9f2;border-radius:18px;padding:30px;box-shadow:0 8px 30px rgba(20,30,60,.08)">'
            . '<div style="font-size:12px;letter-spacing:2px;font-weight:700;color:#53658a">APARTMENT RENTAL SYSTEM</div>'
            . '<h2 style="margin:10px 0 8px">Confirm admin promotion</h2>'
            . '<p>Hello ' . $safeAdminName . ',</p>'
            . '<p>You requested to promote <strong>' . $safeTargetName . '</strong> to an administrator account.</p>'
            . '<p style="margin:22px 0 12px">Choose one action:</p>'
            . '<a href="' . $safeConfirmUrl . '" style="display:inline-block;background:#4056d8;color:#fff;text-decoration:none;padding:13px 20px;border-radius:10px;font-weight:700;margin-right:8px">Confirm Admin Promotion</a>'
            . '<a href="' . $safeIgnoreUrl . '" style="display:inline-block;background:#eef1f6;color:#28344d;text-decoration:none;padding:13px 20px;border-radius:10px;font-weight:700">Ignore / This Wasn\'t Me</a>'
            . '<p style="margin-top:24px;font-size:13px;color:#6b7890">This confirmation expires in 10 minutes and can only be used once.</p>'
            . '<p style="font-size:12px;color:#7c8799">If you did not make this request, click Ignore / This Wasn\'t Me. Do not forward this email.</p>'
            . '<p style="font-size:12px;color:#9aa3b2">Sent to ' . $safeAdminEmail . '</p>'
            . '</div></div>';
        $textBody = "Confirm admin promotion\n\nYou requested to promote {$target['full_name']} to an administrator.\n\nConfirm: {$confirmUrl}\nIgnore / This wasn't me: {$ignoreUrl}\n\nThis confirmation expires in 10 minutes and can only be used once.";

        if (!send_smtp_email($currentAdmin['email'], $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Administrator', $subject, $htmlBody, $textBody, $smtpError)) {
            $stmt = $conn->prepare('DELETE FROM admin_promotion_verifications WHERE id=?');
            $stmt->bind_param('i', $verificationId);
            $stmt->execute();
            $stmt->close();
            flash('error', 'The verification email could not be sent: ' . ($smtpError ?: 'check includes/mail_config.php.'));
            redirect('tenants.php');
        }

        $_SESSION['admin_promotion_pending'] = [
            'id' => (int)$verificationId,
            'target_user_id' => (int)$target['user_id'],
            'full_name' => $target['full_name'],
            'email' => $target['email'],
            'username' => $target['username'],
            'expires_at' => $expiresAt
        ];
        flash('success', 'Confirmation email sent. Open Gmail and click “Confirm Admin Promotion” to complete the promotion.');
        redirect('tenants.php');
    }

    if ($action === 'cancel_admin_promotion') {
        $verificationId = (int)($_SESSION['admin_promotion_pending']['id'] ?? 0);
        if ($verificationId > 0) {
            $stmt = $conn->prepare('UPDATE admin_promotion_verifications SET verified_at=NOW() WHERE id=? AND requested_by=?');
            $stmt->bind_param('ii', $verificationId, $userId);
            $stmt->execute();
            $stmt->close();
        }
        unset($_SESSION['admin_promotion_pending']);
        flash('success', 'Admin promotion request cancelled.');
        redirect('tenants.php');
    }
}

if (!empty($pendingPromotion)) {
    $_SESSION['admin_promotion_pending'] = [
        'id' => (int)$pendingPromotion['id'],
        'target_user_id' => (int)$pendingPromotion['target_user_id'],
        'full_name' => $pendingPromotion['full_name'],
        'email' => $pendingPromotion['email'],
        'username' => $pendingPromotion['username'],
        'expires_at' => $pendingPromotion['expires_at'],
    ];
}

$pendingPromotion = null;
$pendingData = $_SESSION['admin_promotion_pending'] ?? null;
$pendingId = (int)($pendingData['id'] ?? 0);
if ($pendingId > 0) {
    $stmt = $conn->prepare("SELECT apv.id, apv.target_user_id, apv.expires_at,
                                  t.full_name, u.email, u.username
                           FROM admin_promotion_verifications apv
                           JOIN users u ON u.id = apv.target_user_id
                           JOIN tenants t ON t.user_id = u.id
                           WHERE apv.id = ?
                             AND apv.requested_by = ?
                             AND apv.verified_at IS NULL
                             AND apv.expires_at > NOW()
                           LIMIT 1");
    $stmt->bind_param('ii', $pendingId, $userId);
    $stmt->execute();
    $pendingPromotion = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$pendingPromotion) {
        unset($_SESSION['admin_promotion_verification_id'], $_SESSION['admin_promotion_pending']);
    }
}

$tenants = $conn->query("SELECT t.*,u.username,u.email,u.role,
    (SELECT a.apartment_number FROM rentals r JOIN apartments a ON a.id=r.apartment_id WHERE r.tenant_id=t.id AND r.status='Active' LIMIT 1) apartment_number,
    (SELECT COUNT(*) FROM rentals r2 WHERE r2.tenant_id=t.id AND r2.status='Active') active_rental_count
    FROM tenants t JOIN users u ON u.id=t.user_id
    WHERE u.role='tenant'
    ORDER BY t.full_name");

require '../includes/header.php';
?>

<?php if ($pendingPromotion): ?>
<section class="admin-verification-panel panel">
    <div class="verification-icon">✉</div>
    <div class="verification-copy">
        <span class="kicker">EMAIL VERIFICATION IN PROGRESS</span>
        <h2>Confirmation email sent</h2>
        <p>Check <strong><?= e($currentAdmin['email']) ?></strong>. The email contains <strong>Confirm Admin Promotion</strong> and <strong>Ignore / This Wasn't Me</strong> buttons.</p><p>The link opens a secure confirmation page before any account change is made.</p>
        <p class="small-note">The link expires in 10 minutes and does not change the account just by being opened.</p>
    </div>
    <form method="post" class="verification-cancel" data-confirm="Cancel this admin promotion request?">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="cancel_admin_promotion">
        <button class="btn secondary" type="submit">Cancel Request</button>
    </form>
</section>
<?php endif; ?>

<div class="top-actions"><a class="btn secondary" href="test_email.php">Test Email Delivery</a></div>

<div class="section-grid two compact-left">
<section class="panel">
    <div class="panel-head">
        <div><span class="kicker"><?= $editing ? 'EDIT TENANT' : 'NEW TENANT' ?></span><h2><?= $editing ? 'Edit Tenant' : 'Add Tenant' ?></h2><p><?= $editing ? 'Update the profile and login details.' : 'Create a tenant login and profile.' ?></p></div>
        <?php if ($editing): ?><a class="btn secondary small" href="tenants.php">Cancel Edit</a><?php endif; ?>
    </div>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form-grid two-col">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="tenant_id" value="<?= (int)($editing['id'] ?? 0) ?>">
        <div class="form-group full-span"><label>Full Name *</label><input name="full_name" value="<?= e($editing['full_name'] ?? '') ?>" required maxlength="100"></div>
        <div class="form-group"><label>Email *</label><input name="email" type="email" value="<?= e($editing['email'] ?? '') ?>" required maxlength="120"></div>
        <div class="form-group"><label>Username *</label><input name="username" value="<?= e($editing['username'] ?? '') ?>" required maxlength="50"></div>
        <div class="form-group"><label><?= $editing ? 'New Password (optional)' : 'Password *' ?></label><input name="password" type="password" <?= $editing ? '' : 'required minlength="6"' ?> placeholder="<?= $editing ? 'Leave blank to keep current' : 'At least 6 characters' ?>"></div>
        <div class="form-group"><label>Contact Number</label><input name="contact_number" value="<?= e($editing['contact_number'] ?? '') ?>" maxlength="30"></div>
        <div class="form-group full-span"><label>Address</label><textarea name="address" rows="3" maxlength="255"><?= e($editing['address'] ?? '') ?></textarea></div>
        <div class="form-actions full-span"><button class="btn primary" type="submit"><?= $editing ? 'Save Changes' : 'Create Tenant' ?></button></div>
    </form>
</section>

<section class="panel">
    <div class="panel-head">
        <div><span class="kicker">DIRECTORY</span><h2>Tenant List</h2><p><?= $tenants->num_rows ?> registered tenant<?= $tenants->num_rows === 1 ? '' : 's' ?></p></div>
        <input class="search-input" id="tableSearch" type="search" placeholder="Search tenants...">
    </div>
    <div class="table-wrap">
        <table id="dataTable">
            <thead><tr><th>Tenant</th><th>Account</th><th>Contact</th><th>Apartment</th><th>Actions</th></tr></thead>
            <tbody>
            <?php while ($row = $tenants->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= e($row['full_name']) ?></strong><br><small><?= e($row['address'] ?: 'No address') ?></small></td>
                    <td><?= e($row['username']) ?><br><small><?= e($row['email']) ?></small></td>
                    <td><?= e($row['contact_number'] ?: '—') ?></td>
                    <td><?= e($row['apartment_number'] ?: '—') ?></td>
                    <td class="actions">
                        <a class="btn small secondary" href="tenants.php?edit=<?= (int)$row['id'] ?>">Edit</a>
                        <?php if ((int)$row['active_rental_count'] === 0): ?>
                            <?php if ($pendingPromotion && (int)$pendingPromotion['target_user_id'] === (int)$row['user_id']): ?>
                                <span class="btn small admin-promote waiting-code" aria-disabled="true">Waiting for Email</span>
                            <?php else: ?>
                                <form method="post" class="inline-form" data-confirm="Send an admin promotion confirmation email for <?= e($row['full_name']) ?>? You can approve or ignore it from Gmail.">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="request_admin_promotion">
                                    <input type="hidden" name="tenant_id" value="<?= (int)$row['id'] ?>">
                                    <button class="btn small admin-promote" type="submit">Add as Admin</button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="action-note">Active rental</span>
                        <?php endif; ?>
                        <form method="post" class="inline-form" data-confirm="Delete this tenant account?">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="tenant_id" value="<?= (int)$row['id'] ?>">
                            <button class="btn small danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php if ($tenants->num_rows === 0): ?><tr><td colspan="5" class="empty">No tenant accounts are available for promotion.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
</div>

<?php require '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_role('tenant');
$pageTitle='Customer Support';
$pageSubtitle='Chat privately with the apartment administrator for help with your rental.';

$stmt=$conn->prepare('SELECT id, full_name FROM tenants WHERE user_id=? LIMIT 1');
$stmt->bind_param('i',$_SESSION['user_id']); $stmt->execute(); $tenant=$stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$tenant) { flash('error','Tenant profile not found.'); redirect('../logout.php'); }
$tenantId=(int)$tenant['id'];

$admin=$conn->query("SELECT id, username FROM users WHERE role='admin' ORDER BY id ASC LIMIT 1")->fetch_assoc();
if (!$admin) { flash('error','No administrator account is available.'); redirect('dashboard.php'); }
$adminId=(int)$admin['id'];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $message=trim($_POST['message']??'');
    if ($message==='') {
        flash('error','Please enter a message.');
    } elseif (mb_strlen($message)>2000) {
        flash('error','Message is too long. Please keep it under 2,000 characters.');
    } else {
        $stmt=$conn->prepare("INSERT INTO messages (tenant_id,admin_id,sender_role,message,is_read) VALUES (?,?,?,?,0)");
        $role='tenant';
        $stmt->bind_param('iiss',$tenantId,$adminId,$role,$message);
        $stmt->execute(); $stmt->close();
        flash('success','Message sent to the administrator.');
    }
    redirect('messages.php');
}

$stmt=$conn->prepare('UPDATE messages SET is_read=1 WHERE tenant_id=? AND admin_id=? AND sender_role=\'admin\' AND is_read=0');
$stmt->bind_param('ii',$tenantId,$adminId); $stmt->execute(); $stmt->close();

$stmt=$conn->prepare('SELECT m.*, CASE WHEN m.sender_role=\'admin\' THEN u.username ELSE t.full_name END sender_name FROM messages m LEFT JOIN users u ON u.id=m.admin_id LEFT JOIN tenants t ON t.id=m.tenant_id WHERE m.tenant_id=? ORDER BY m.created_at ASC,m.id ASC');
$stmt->bind_param('i',$tenantId); $stmt->execute(); $messages=$stmt->get_result();
require '../includes/header.php';
?>
<section class="panel chat-panel">
    <div class="panel-head chat-header">
        <div>
            <span class="eyebrow">PRIVATE SUPPORT</span>
            <h2>Chat with Admin</h2>
            <p>Your messages are private and can only be seen by you and the administrator.</p>
        </div>
        <div class="chat-contact"><span class="chat-avatar">A</span><div><strong>Administrator</strong><small>Apartment Support</small></div></div>
    </div>
    <div class="chat-box" id="chatBox">
        <?php if ($messages->num_rows===0): ?>
            <div class="chat-empty"><div class="empty-icon">💬</div><h3>Start a conversation</h3><p>Ask about rent, your apartment, requests, payments, or anything related to your rental.</p></div>
        <?php else: while($row=$messages->fetch_assoc()): ?>
            <div class="chat-message <?= $row['sender_role']==='tenant' ? 'mine' : 'theirs' ?>">
                <div class="chat-bubble"><?= nl2br(e($row['message'])) ?></div>
                <small><?= e($row['sender_role']==='tenant' ? 'You' : 'Admin') ?> • <?= e(date('M d, Y g:i A',strtotime($row['created_at']))) ?></small>
            </div>
        <?php endwhile; endif; ?>
    </div>
    <form method="post" class="chat-compose" data-validate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <textarea name="message" rows="3" maxlength="2000" placeholder="Type your message to the administrator..." required></textarea>
        <button class="btn primary" type="submit">Send Message</button>
    </form>
</section>
<?php require '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_role('admin');
$pageTitle='Customer Support';
$pageSubtitle='Respond to private support conversations from tenants.';
$adminId=(int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $tenantId=(int)($_POST['tenant_id']??0);
    $message=trim($_POST['message']??'');
    if ($tenantId<=0 || $message==='') {
        flash('error','Please select a tenant and enter a message.');
    } elseif (mb_strlen($message)>2000) {
        flash('error','Message is too long. Please keep it under 2,000 characters.');
    } else {
        $check=$conn->prepare('SELECT id FROM tenants WHERE id=? LIMIT 1'); $check->bind_param('i',$tenantId); $check->execute(); $exists=$check->get_result()->fetch_assoc(); $check->close();
        if (!$exists) flash('error','Tenant not found.');
        else {
            $stmt=$conn->prepare("INSERT INTO messages (tenant_id,admin_id,sender_role,message,is_read) VALUES (?,?,?,?,0)");
            $role='admin'; $stmt->bind_param('iiss',$tenantId,$adminId,$role,$message); $stmt->execute(); $stmt->close();
            flash('success','Reply sent to the tenant.');
        }
    }
    $target=$tenantId>0 ? '?tenant_id='.$tenantId : '';
    redirect('messages.php'.$target);
}

$tenantRows=$conn->query("SELECT t.id,t.full_name,t.contact_number,a.apartment_number,MAX(m.created_at) last_message,SUM(CASE WHEN m.sender_role='tenant' AND m.is_read=0 THEN 1 ELSE 0 END) unread FROM tenants t LEFT JOIN rentals r ON r.tenant_id=t.id AND r.status='Active' LEFT JOIN apartments a ON a.id=r.apartment_id LEFT JOIN messages m ON m.tenant_id=t.id GROUP BY t.id,t.full_name,t.contact_number,a.apartment_number ORDER BY unread DESC,last_message DESC,t.full_name ASC");
$selectedId=(int)($_GET['tenant_id']??0);
if ($selectedId<=0 && $tenantRows->num_rows>0) { $first=$tenantRows->fetch_assoc(); $selectedId=(int)$first['id']; $tenantRows->data_seek(0); }

$selectedTenant=null;
if ($selectedId>0) {
    $stmt=$conn->prepare('SELECT t.id,t.full_name,t.contact_number,a.apartment_number FROM tenants t LEFT JOIN rentals r ON r.tenant_id=t.id AND r.status=\'Active\' LEFT JOIN apartments a ON a.id=r.apartment_id WHERE t.id=? LIMIT 1');
    $stmt->bind_param('i',$selectedId); $stmt->execute(); $selectedTenant=$stmt->get_result()->fetch_assoc(); $stmt->close();
    if ($selectedTenant) {
        $stmt=$conn->prepare("UPDATE messages SET is_read=1 WHERE tenant_id=? AND sender_role='tenant' AND is_read=0"); $stmt->bind_param('i',$selectedId); $stmt->execute(); $stmt->close();
    }
}
$chatMessages=null;
if ($selectedTenant) {
    $stmt=$conn->prepare('SELECT m.*,t.full_name FROM messages m JOIN tenants t ON t.id=m.tenant_id WHERE m.tenant_id=? ORDER BY m.created_at ASC,m.id ASC'); $stmt->bind_param('i',$selectedId); $stmt->execute(); $chatMessages=$stmt->get_result();
}
require '../includes/header.php';
?>
<div class="support-layout">
    <section class="panel support-inbox">
        <div class="panel-head"><div><h2>Tenant Conversations</h2><p>Only administrators can see all conversations.</p></div></div>
        <div class="conversation-list">
        <?php if ($tenantRows->num_rows===0): ?><div class="empty-state small"><h3>No tenants yet</h3><p>Tenant conversations will appear here.</p></div>
        <?php else: while($tenant=$tenantRows->fetch_assoc()): ?>
            <a class="conversation-item <?= $selectedId===(int)$tenant['id'] ? 'active' : '' ?>" href="messages.php?tenant_id=<?= (int)$tenant['id'] ?>">
                <span class="chat-avatar small-avatar"><?= e(strtoupper(substr($tenant['full_name'],0,1))) ?></span>
                <span class="conversation-copy"><strong><?= e($tenant['full_name']) ?></strong><small><?= e($tenant['apartment_number'] ? 'Apartment '.$tenant['apartment_number'] : 'No active rental') ?></small></span>
                <?php if ((int)$tenant['unread']>0): ?><span class="unread-count"><?= (int)$tenant['unread'] ?></span><?php endif; ?>
            </a>
        <?php endwhile; endif; ?>
        </div>
    </section>

    <section class="panel chat-panel admin-chat-panel">
    <?php if (!$selectedTenant): ?>
        <div class="chat-empty full"><div class="empty-icon">💬</div><h3>Select a tenant</h3><p>Choose a conversation from the left to reply.</p></div>
    <?php else: ?>
        <div class="panel-head chat-header"><div><span class="eyebrow">PRIVATE SUPPORT</span><h2><?= e($selectedTenant['full_name']) ?></h2><p><?= e($selectedTenant['apartment_number'] ? 'Apartment '.$selectedTenant['apartment_number'] : 'No active rental') ?><?= $selectedTenant['contact_number'] ? ' • '.e($selectedTenant['contact_number']) : '' ?></p></div><span class="badge approved">ADMIN</span></div>
        <div class="chat-box" id="chatBox">
            <?php if (!$chatMessages || $chatMessages->num_rows===0): ?><div class="chat-empty"><div class="empty-icon">💬</div><h3>No messages yet</h3><p>Send a message to start helping this tenant.</p></div>
            <?php else: while($row=$chatMessages->fetch_assoc()): ?>
                <div class="chat-message <?= $row['sender_role']==='admin' ? 'mine' : 'theirs' ?>">
                    <div class="chat-bubble"><?= nl2br(e($row['message'])) ?></div>
                    <small><?= e($row['sender_role']==='admin' ? 'You' : $row['full_name']) ?> • <?= e(date('M d, Y g:i A',strtotime($row['created_at']))) ?></small>
                </div>
            <?php endwhile; endif; ?>
        </div>
        <form method="post" class="chat-compose" data-validate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="tenant_id" value="<?= (int)$selectedTenant['id'] ?>">
            <textarea name="message" rows="3" maxlength="2000" placeholder="Reply to <?= e($selectedTenant['full_name']) ?>..." required></textarea>
            <button class="btn primary" type="submit">Send Reply</button>
        </form>
    <?php endif; ?>
    </section>
</div>
<?php require '../includes/footer.php'; ?>

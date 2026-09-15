<?php
require_once '../includes/auth.php';
require_once '../includes/mailer.php';
require_role('admin');
$pageTitle = 'Payment Management';
$pageSubtitle = 'View all rent payments and quickly identify overdue accounts.';
$filter = $_GET['status'] ?? 'All';
$allowed = ['All','Pending','Paid','Early Payment','Overdue'];
if (!in_array($filter, $allowed, true)) $filter = 'All';


if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='receive_cash') {
    verify_csrf();
    $paymentId=(int)($_POST['payment_id']??0);
    $stmt=$conn->prepare("SELECT p.*,t.full_name,u.email,a.apartment_number FROM payments p JOIN tenants t ON t.id=p.tenant_id JOIN users u ON u.id=t.user_id JOIN apartments a ON a.id=p.apartment_id WHERE p.id=? AND p.status='Pending' AND p.payment_method='Cash' LIMIT 1");
    $stmt->bind_param('i',$paymentId); $stmt->execute(); $payment=$stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$payment) { flash('error','Pending cash payment not found.'); redirect('payments.php'); }
    $paymentTs=strtotime($payment['payment_date']);
    $dueTs=strtotime($payment['due_date']);
    $status=$paymentTs<$dueTs?'Early Payment':($paymentTs===$dueTs?'Paid':'Overdue');
    $stmt=$conn->prepare('UPDATE payments SET status=?,received_at=NOW(),received_by=?,receipt_sent_at=NULL WHERE id=?'); $stmt->bind_param('sii',$status,$_SESSION['user_id'],$paymentId); $stmt->execute(); $stmt->close();
    $nextDue=next_billing_date($payment['due_date']);
    $advance=$conn->prepare("UPDATE rentals SET next_due_date=? WHERE id=? AND status='Active'");
    $advance->bind_param('si',$nextDue,$payment['rental_id']); $advance->execute(); $advance->close();
    $subject='Cash Rent Payment Receipt - Apartment '.$payment['apartment_number'];
    $html='<h2>Apartment Rental Cash Payment Receipt</h2><p>Hello '.e($payment['full_name']).',</p><p>The administrator has received your cash rent payment.</p><table cellpadding="6"><tr><td>Apartment</td><td><strong>'.e($payment['apartment_number']).'</strong></td></tr><tr><td>Payment Method</td><td>Cash</td></tr><tr><td>Payment Date</td><td>'.e(date('M d, Y',strtotime($payment['payment_date']))).'</td></tr><tr><td>Due Date</td><td>'.e(date('M d, Y',strtotime($payment['due_date']))).'</td></tr><tr><td>Rent</td><td>'.money((float)$payment['rent_amount']).'</td></tr><tr><td>Early Discount</td><td>'.money((float)$payment['early_discount']).'</td></tr><tr><td>Late Fee</td><td>'.money((float)$payment['late_fee']).'</td></tr><tr><td>Total</td><td><strong>'.money((float)$payment['total_amount']).'</strong></td></tr><tr><td>Status</td><td><strong>'.e($status).'</strong></td></tr></table><p>Thank you.</p>';
    $text='Cash rent payment receipt for Apartment '.$payment['apartment_number'].'\nPayment Date: '.$payment['payment_date'].'\nDue Date: '.$payment['due_date'].'\nTotal: '.money((float)$payment['total_amount']).'\nStatus: '.$status;
    $mailError=''; $sent=send_smtp_email($payment['email'],$payment['full_name'],$subject,$html,$text,$mailError);
    if ($sent) { $up=$conn->prepare('UPDATE payments SET receipt_sent_at=NOW() WHERE id=?'); $up->bind_param('i',$paymentId); $up->execute(); $up->close(); flash('success','Cash payment received and receipt emailed to the tenant.'); }
    else flash('success','Cash payment received, but the receipt email could not be sent. Check SMTP settings.');
    redirect('payments.php');
}

if ($filter === 'All') {
    $payments = $conn->query("SELECT p.*, t.full_name, a.apartment_number FROM payments p JOIN tenants t ON t.id=p.tenant_id JOIN apartments a ON a.id=p.apartment_id ORDER BY p.payment_date DESC, p.id DESC");
} else {
    $stmt = $conn->prepare('SELECT p.*, t.full_name, a.apartment_number FROM payments p JOIN tenants t ON t.id=p.tenant_id JOIN apartments a ON a.id=p.apartment_id WHERE p.status=? ORDER BY p.payment_date DESC, p.id DESC');
    $stmt->bind_param('s', $filter);
    $stmt->execute();
    $payments = $stmt->get_result();
}
$overdueTotal = $conn->query("SELECT COALESCE(SUM(total_amount),0) total FROM payments WHERE status='Overdue'")->fetch_assoc()['total'] ?? 0;
require '../includes/header.php';
?>
<div class="filter-bar">
    <a class="btn <?= $filter==='All' ? 'primary' : 'secondary' ?>" href="payments.php">All</a>
    <a class="btn <?= $filter==='Paid' ? 'primary' : 'secondary' ?>" href="payments.php?status=Paid">Paid</a>
    <a class="btn <?= $filter==='Early Payment' ? 'primary' : 'secondary' ?>" href="payments.php?status=Early%20Payment">Early Payment</a>
    <a class="btn <?= $filter==='Overdue' ? 'primary' : 'secondary' ?>" href="payments.php?status=Overdue">Overdue</a>
    <a class="btn <?= $filter==='Pending' ? 'primary' : 'secondary' ?>" href="payments.php?status=Pending">Pending Cash</a>
    <div class="filter-summary">Overdue Total: <strong><?= money((float)$overdueTotal) ?></strong></div>
</div>
<section class="panel">
<div class="panel-head"><div><h2>Payment Records</h2><p>Showing: <strong><?= e($filter) ?></strong></p></div><input class="search-input" id="tableSearch" type="search" placeholder="Search payments..."></div>
<div class="table-wrap"><table id="dataTable"><thead><tr><th>Tenant</th><th>Apartment</th><th>Method</th><th>Rent</th><th>Due Date</th><th>Payment Date</th><th>Discount</th><th>Late Fee</th><th>Total</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php while ($row = $payments->fetch_assoc()): ?><tr class="<?= $row['status']==='Overdue' ? 'overdue-row' : '' ?>"><td><?= e($row['full_name']) ?></td><td><?= e($row['apartment_number']) ?></td><td><?= e($row['payment_method'] ?? '—') ?></td><td><?= money((float)$row['rent_amount']) ?></td><td><?= e(date('M d, Y', strtotime($row['due_date']))) ?></td><td><?= e(date('M d, Y', strtotime($row['payment_date']))) ?></td><td><?= money((float)$row['early_discount']) ?></td><td><?= money((float)$row['late_fee']) ?></td><td><strong><?= money((float)$row['total_amount']) ?></strong></td><td><span class="badge <?= strtolower(str_replace(' ', '-', $row['status'])) ?>"><?= e($row['status']) ?></span></td><td><?php if (($row['status'] ?? '') === 'Pending' && ($row['payment_method'] ?? '') === 'Cash'): ?><form method="post" class="inline-form" data-confirm="Confirm that you received this cash payment?"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="receive_cash"><input type="hidden" name="payment_id" value="<?= (int)$row['id'] ?>"><button class="btn small success" type="submit">Receive Payment</button></form><?php else: ?><?php if (!empty($row['receipt_sent_at'])): ?><span class="action-note">Receipt emailed</span><?php else: ?>—<?php endif; ?><?php endif; ?></td></tr><?php endwhile; ?>
<?php if ($payments->num_rows===0): ?><tr><td colspan="11" class="empty">No payment records found.</td></tr><?php endif; ?>
</tbody></table></div>
</section>
<?php require '../includes/footer.php'; ?>

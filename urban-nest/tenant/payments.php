<?php
require_once '../includes/auth.php';
require_once '../includes/mailer.php';
require_role('tenant');
$pageTitle='Payments'; $pageSubtitle='Pay your rent and review your payment history.';
$stmt=$conn->prepare('SELECT id FROM tenants WHERE user_id=? LIMIT 1'); $stmt->bind_param('i',$_SESSION['user_id']); $stmt->execute(); $tenantId=(int)$stmt->get_result()->fetch_assoc()['id']; $stmt->close();
$stmt=$conn->prepare("SELECT r.*,a.apartment_number,a.type FROM rentals r JOIN apartments a ON a.id=r.apartment_id WHERE r.tenant_id=? AND r.status='Active' LIMIT 1"); $stmt->bind_param('i',$tenantId); $stmt->execute(); $rental=$stmt->get_result()->fetch_assoc(); $stmt->close();
$error=''; $calculated=null;
if ($_SERVER['REQUEST_METHOD']==='POST' && $rental) {
    verify_csrf();
    $paymentDate=$_POST['payment_date']??'';
    $paymentMethod=$_POST['payment_method']??'';
    $reference=trim($_POST['reference_number']??'');
    $dateObj=DateTime::createFromFormat('Y-m-d',$paymentDate);
    $dateErrors=DateTime::getLastErrors();
    $validDate=$dateObj && (!$dateErrors || ($dateErrors['warning_count']===0 && $dateErrors['error_count']===0));
    $allowedMethods=['Card','QR Payment','Cash'];
    if (!$validDate) $error='Please enter a valid payment date.';
    elseif (!in_array($paymentMethod,$allowedMethods,true)) $error='Please select a payment method.';
    else {
        $paymentDate=$dateObj->format('Y-m-d');
        $startDate=$rental['start_date'];
        $dueDate=$rental['next_due_date'] ?: next_billing_date($startDate);
        $rent=(float)$rental['monthly_rent'];
        $paymentTs=strtotime($paymentDate);
        $startTs=strtotime($startDate);
        $dueTs=strtotime($dueDate);
        if ($paymentTs < $startTs) {
            $error='Payment date cannot be earlier than your rental start date.';
        }
        $discount=$paymentTs < $dueTs ? 200 : 0;
        $lateFee=$paymentTs > $dueTs ? 500 : 0;
        $calculatedStatus=$paymentTs < $dueTs ? 'Early Payment':($paymentTs === $dueTs ? 'Paid':'Overdue');
        $status=$paymentMethod==='Cash'?'Pending':$calculatedStatus;
        $total=$rent-$discount+$lateFee;
        $stmt=$conn->prepare('SELECT COUNT(*) c FROM payments WHERE rental_id=? AND due_date=?');
        $stmt->bind_param('is',$rental['id'],$dueDate); $stmt->execute(); $exists=(int)$stmt->get_result()->fetch_assoc()['c']; $stmt->close();
        if ($error==='') {
            if ($exists>0) $error='A payment for this billing cycle has already been recorded.';
            elseif (($paymentMethod==='QR Payment' || $paymentMethod==='Card') && $reference==='') $error='Please complete the demo payment in the new payment tab before submitting.';
            else {
                $stmt=$conn->prepare('INSERT INTO payments (rental_id,tenant_id,apartment_id,rent_amount,due_date,payment_date,early_discount,late_fee,total_amount,status,payment_method,reference_number) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->bind_param('iiidssdddsss',$rental['id'],$tenantId,$rental['apartment_id'],$rent,$dueDate,$paymentDate,$discount,$lateFee,$total,$status,$paymentMethod,$reference);
                if ($stmt->execute()) {
                    $paymentId=$stmt->insert_id;
                    $stmt->close();
                    if ($status!=='Pending') {
                        $nextDue=next_billing_date($dueDate);
                        $advance=$conn->prepare("UPDATE rentals SET next_due_date=? WHERE id=? AND status='Active'");
                        $advance->bind_param('si',$nextDue,$rental['id']); $advance->execute(); $advance->close();
                        $emailStmt=$conn->prepare('SELECT u.email,t.full_name FROM tenants t JOIN users u ON u.id=t.user_id WHERE t.id=? LIMIT 1'); $emailStmt->bind_param('i',$tenantId); $emailStmt->execute(); $recipient=$emailStmt->get_result()->fetch_assoc(); $emailStmt->close();
                        $sent=false; $mailError='';
                        if ($recipient) {
                            $subject='Rent Payment Receipt - Apartment '.$rental['apartment_number'];
                            $html='<h2>Apartment Rental Payment Receipt</h2><p>Hello '.e($recipient['full_name']).',</p><p>Your rent payment has been recorded successfully.</p><table cellpadding="6"><tr><td>Apartment</td><td><strong>'.e($rental['apartment_number']).'</strong></td></tr><tr><td>Payment Method</td><td>'.e($paymentMethod).'</td></tr><tr><td>Payment Date</td><td>'.e(date('M d, Y',strtotime($paymentDate))).'</td></tr><tr><td>Due Date</td><td>'.e(date('M d, Y',strtotime($dueDate))).'</td></tr><tr><td>Rent</td><td>'.money($rent).'</td></tr><tr><td>Early Discount</td><td>'.money($discount).'</td></tr><tr><td>Late Fee</td><td>'.money($lateFee).'</td></tr><tr><td>Total</td><td><strong>'.money($total).'</strong></td></tr><tr><td>Status</td><td><strong>'.e($status).'</strong></td></tr></table><p>Thank you.</p>';
                            $text='Rent payment receipt for Apartment '.$rental['apartment_number'].'\nMethod: '.$paymentMethod.'\nPayment Date: '.$paymentDate.'\nDue Date: '.$dueDate.'\nTotal: '.money($total).'\nStatus: '.$status;
                            $sent=send_smtp_email($recipient['email'],$recipient['full_name'],$subject,$html,$text,$mailError);
                        }
                        if ($sent) { $up=$conn->prepare('UPDATE payments SET receipt_sent_at=NOW() WHERE id=?'); $up->bind_param('i',$paymentId); $up->execute(); $up->close(); flash('success','Payment recorded. Your email receipt has been sent.'); }
                        else flash('success','Payment recorded as '.$status.'. Receipt email could not be sent yet; check SMTP settings.');
                    } else {
                        flash('success','Cash payment submitted and is Pending. Please wait for the administrator to receive it.');
                    }
                    redirect('payments.php');
                }
                $error='Unable to record payment.';
                $stmt->close();
            }
        }
    }
}

$history=$conn->prepare('SELECT * FROM payments WHERE tenant_id=? ORDER BY payment_date DESC,id DESC'); $history->bind_param('i',$tenantId); $history->execute(); $historyResult=$history->get_result();
$todayDate=date('Y-m-d');
require '../includes/header.php';
?>
<div class="section-grid two">
<section class="panel">
<div class="panel-head"><div><h2>Make a Rent Payment</h2><p>Your first rent is due one month after your approved rental start date, then monthly on the same day.</p></div></div>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<?php if ($rental): ?>
<div class="rent-summary"><div><span>Apartment</span><strong><?= e($rental['apartment_number']) ?></strong></div><div><span>Monthly Rent</span><strong id="monthlyRentValue" data-rent="<?= e((string)$rental['monthly_rent']) ?>"><?= money((float)$rental['monthly_rent']) ?></strong></div><div><span>Next Due Date</span><strong><?= e(date('M d, Y', strtotime($rental['next_due_date'] ?: next_billing_date($rental['start_date'])))) ?></strong></div><div><span>Rule</span><strong>₱200 early discount / ₱500 late fee</strong></div></div>
<form method="post" class="form-grid" id="paymentForm" data-validate>
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="form-group full-span"><label for="payment_date">Payment Date</label><input id="payment_date" name="payment_date" type="date" value="<?= e($todayDate) ?>" data-due-date="<?= e($rental['next_due_date'] ?: next_billing_date($rental['start_date'])) ?>" required><small>Before the due date = Early Payment, on the due date = Paid, after the due date = Overdue.</small></div>
<div class="form-group full-span"><label>Payment Method</label><div class="payment-methods"><label class="payment-method"><input type="radio" name="payment_method" value="Card" required><span>💳<strong>Card</strong><small>Demo card payment</small></span></label><label class="payment-method"><input type="radio" name="payment_method" value="QR Payment"><span>▦<strong>QR Payment</strong><small>Demo QR payment</small></span></label><label class="payment-method"><input type="radio" name="payment_method" value="Cash"><span>💵<strong>Cash</strong><small>Admin must receive it</small></span></label></div></div>
<input id="reference_number" name="reference_number" type="hidden" value="">
<div id="paymentPreview" class="payment-preview full-span"></div>
<div class="form-actions full-span"><button class="btn primary pay-rent-btn" type="submit"><span>Pay Rent</span><span class="pay-rent-amount" id="payRentAmount"></span></button></div>
</form>
<?php else: ?><div class="empty-state small"><h3>No Active Rental</h3><p>You need an approved rental before you can make a payment.</p><a class="btn secondary" href="apartments.php">Find Apartment</a></div><?php endif; ?>
</section>
<section class="panel"><div class="panel-head"><div><h2>Payment History</h2><p>Your previous rent payments.</p></div></div><div class="table-wrap"><table><thead><tr><th>Payment Date</th><th>Method</th><th>Due Date</th><th>Total</th><th>Status</th></tr></thead><tbody><?php while($row=$historyResult->fetch_assoc()): ?><tr><td><?= e(date('M d, Y',strtotime($row['payment_date']))) ?></td><td><?= e($row['payment_method'] ?? '—') ?></td><td><?= e(date('M d, Y',strtotime($row['due_date']))) ?></td><td><?= money((float)$row['total_amount']) ?></td><td><span class="badge <?= strtolower(str_replace(' ', '-', $row['status'])) ?>"><?= e($row['status']) ?></span></td></tr><?php endwhile; ?><?php if($historyResult->num_rows===0): ?><tr><td colspan="5" class="empty">No payments recorded yet.</td></tr><?php endif; ?></tbody></table></div></section>
</div>


<?php require '../includes/footer.php'; ?>

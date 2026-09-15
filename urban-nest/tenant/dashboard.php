<?php
require_once '../includes/auth.php';
require_role('tenant');
$pageTitle = 'Tenant Dashboard';
$pageSubtitle = 'Your rental, payment status, and apartment overview.';
$tenantId = 0;
$stmt = $conn->prepare('SELECT id, full_name FROM tenants WHERE user_id=? LIMIT 1');
$stmt->bind_param('i', $_SESSION['user_id']); $stmt->execute(); $tenant = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$tenant) { flash('error','Tenant profile not found.'); redirect('../logout.php'); }
$tenantId = (int)$tenant['id'];
$_SESSION['full_name'] = $tenant['full_name'];

$stmt = $conn->prepare("SELECT r.*, a.apartment_number, a.type FROM rentals r JOIN apartments a ON a.id=r.apartment_id WHERE r.tenant_id=? AND r.status='Active' LIMIT 1");
$stmt->bind_param('i',$tenantId); $stmt->execute(); $rental=$stmt->get_result()->fetch_assoc(); $stmt->close();

$today = date('Y-m-d');
$dueDate = $rental['next_due_date'] ?? ($rental ? next_billing_date($rental['start_date']) : date('Y-m-d'));
if ($rental) {
    $todayTs=strtotime($today); $dueTs=strtotime($dueDate);
    $statusLabel = $todayTs > $dueTs ? 'Overdue' : ($todayTs === $dueTs ? 'Due Today' : 'Upcoming');
} else { $statusLabel='Upcoming'; }
$latestPayment = null;
if ($rental) {
    $stmt=$conn->prepare('SELECT * FROM payments WHERE rental_id=? ORDER BY payment_date DESC, id DESC LIMIT 1'); $stmt->bind_param('i',$rental['id']); $stmt->execute(); $latestPayment=$stmt->get_result()->fetch_assoc(); $stmt->close();
}
$stmt=$conn->prepare("SELECT COUNT(*) c FROM rental_requests WHERE tenant_id=? AND status='Pending'"); $stmt->bind_param('i',$tenantId); $stmt->execute(); $pendingRequest=(int)$stmt->get_result()->fetch_assoc()['c']; $stmt->close();
require '../includes/header.php';
?>
<div class="welcome-banner"><div><span class="eyebrow">TENANT PORTAL</span><h2>Welcome, <?= e($tenant['full_name']) ?>!</h2><p>Manage your apartment request, rental details, and rent payments in one place.</p></div><a class="btn primary" href="apartments.php">Find an Apartment</a></div>

<?php if (!$rental): ?>
<div class="empty-state panel"><div class="empty-icon">⌂</div><h2>No Active Rental</h2><p>You do not have an active apartment yet. Browse available units and submit one rental request.</p><a class="btn primary" href="apartments.php">View Available Apartments</a><?php if ($pendingRequest): ?><p class="small-note">You have <?= $pendingRequest ?> pending rental request.</p><?php endif; ?></div>
<?php else: ?>
<div class="stats-grid tenant-stats">
<div class="stat-card"><span>My Apartment</span><strong><?= e($rental['apartment_number']) ?></strong><small><?= e($rental['type']) ?></small></div>
<div class="stat-card"><span>Monthly Rent</span><strong><?= money((float)$rental['monthly_rent']) ?></strong><small>Per month</small></div>
<div class="stat-card"><span>Due Date</span><strong><?= e(date('M d',strtotime($dueDate))) ?></strong><small><?= e(date('Y',strtotime($dueDate))) ?></small></div>
<div class="stat-card <?= $statusLabel==='Overdue' ? 'danger' : '' ?>"><span>Payment Status</span><strong><?= e($latestPayment['status'] ?? $statusLabel) ?></strong><small><?= $latestPayment ? 'Latest payment' : 'No payment this month' ?></small></div>
</div>

<div class="panel support-shortcut"><div class="panel-head"><div><h2>Need Help?</h2><p>Contact the administrator privately about your apartment, rental, or payments.</p></div><a class="btn primary" href="messages.php">Open Customer Support</a></div></div>

<div class="section-grid two">
<section class="panel"><div class="panel-head"><div><h2>My Rental</h2><p>Current apartment information.</p></div><a class="btn secondary" href="rental.php">View Rental</a></div><div class="detail-card"><div><span>Apartment</span><strong><?= e($rental['apartment_number']) ?></strong></div><div><span>Type</span><strong><?= e($rental['type']) ?></strong></div><div><span>Start Date</span><strong><?= e(date('M d, Y',strtotime($rental['start_date']))) ?></strong></div><div><span>Monthly Rent</span><strong><?= money((float)$rental['monthly_rent']) ?></strong></div></div></section>
<section class="panel"><div class="panel-head"><div><h2>Rent Payment</h2><p>Pay before your monthly due date to receive the early-payment discount.</p></div><a class="btn primary" href="payments.php">Pay Rent</a></div><?php if ($latestPayment): ?><div class="payment-highlight"><span class="badge <?= strtolower(str_replace(' ', '-', $latestPayment['status'])) ?>"><?= e($latestPayment['status']) ?></span><strong><?= money((float)$latestPayment['total_amount']) ?></strong><small>Paid <?= e(date('M d, Y',strtotime($latestPayment['payment_date']))) ?></small></div><?php else: ?><div class="payment-highlight warning"><span class="badge overdue">No Payment</span><strong><?= money((float)$rental['monthly_rent']) ?></strong><small>Due <?= e(date('M d, Y',strtotime($dueDate))) ?></small></div><?php endif; ?></section>
</div>
<?php endif; ?>
<?php require '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_role('tenant');

$pageTitle = 'My Rental';
$pageSubtitle = 'View your current apartment and rental agreement details.';

$stmt = $conn->prepare('SELECT id, full_name FROM tenants WHERE user_id=? LIMIT 1');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$tenant = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$tenant) { flash('error', 'Tenant profile not found.'); redirect('../logout.php'); }

// Create the leave-request table automatically for existing databases.
// leave_requests is created by the Supabase SQL schema.


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'request_leave') {
        $stmt = $conn->prepare("SELECT r.id rental_id, r.apartment_id, a.apartment_number FROM rentals r JOIN apartments a ON a.id=r.apartment_id WHERE r.tenant_id=? AND r.status='Active' LIMIT 1");
        $stmt->bind_param('i', $tenant['id']);
        $stmt->execute();
        $activeRental = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$activeRental) {
            flash('error', 'You do not have an active rental to leave.');
        } else {
            $stmt = $conn->prepare("SELECT id FROM leave_requests WHERE tenant_id=? AND status='Pending' LIMIT 1");
            $stmt->bind_param('i', $tenant['id']);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($existing) {
                flash('error', 'You already have a pending leave request. Please wait for admin approval.');
            } else {
                $stmt = $conn->prepare("INSERT INTO leave_requests (tenant_id,rental_id,apartment_id,request_date,status) VALUES (?,?,?,?, 'Pending')");
                $requestDate = date('Y-m-d');
                $stmt->bind_param('iiis', $tenant['id'], $activeRental['rental_id'], $activeRental['apartment_id'], $requestDate);
                $stmt->execute();
                $stmt->close();
                flash('success', 'Leave request submitted. Please wait for admin approval before leaving the apartment.');
            }
        }
        redirect('rental.php');
    }
}

$stmt = $conn->prepare("SELECT r.*, a.apartment_number, a.type, a.status apartment_status FROM rentals r JOIN apartments a ON a.id=r.apartment_id WHERE r.tenant_id=? AND r.status='Active' LIMIT 1");
$stmt->bind_param('i', $tenant['id']);
$stmt->execute();
$rental = $stmt->get_result()->fetch_assoc();
$stmt->close();

$pendingRequest = null;
if ($rental) {
    $stmt = $conn->prepare("SELECT * FROM leave_requests WHERE tenant_id=? AND rental_id=? AND status='Pending' ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ii', $tenant['id'], $rental['id']);
    $stmt->execute();
    $pendingRequest = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

require '../includes/header.php';
?>

<?php if ($rental): ?>
<section class="panel hero-detail">
    <div>
        <span class="eyebrow">ACTIVE RENTAL</span>
        <h2>Apartment <?= e($rental['apartment_number']) ?></h2>
        <p>Your apartment is currently assigned to you.</p>
    </div>
    <span class="badge occupied">Occupied</span>
</section>

<div class="detail-grid">
    <div class="panel detail-box"><span>Apartment Number</span><strong><?= e($rental['apartment_number']) ?></strong></div>
    <div class="panel detail-box"><span>Apartment Type</span><strong><?= e($rental['type']) ?></strong></div>
    <div class="panel detail-box"><span>Monthly Rent</span><strong><?= money((float)$rental['monthly_rent']) ?></strong></div>
    <div class="panel detail-box"><span>Start Date</span><strong><?= e(date('M d, Y', strtotime($rental['start_date']))) ?></strong><small>Approved rental start</small></div>
    <div class="panel detail-box"><span>Next Rent Due</span><strong><?= e(date('M d, Y', strtotime($rental['next_due_date'] ?: next_billing_date($rental['start_date'])))) ?></strong><small>First due is one month after approval</small></div>
    <div class="panel detail-box"><span>End Date</span><strong><?= $rental['end_date'] ? e(date('M d, Y', strtotime($rental['end_date']))) : 'Ongoing' ?></strong></div>
    <div class="panel detail-box"><span>Rental Status</span><strong><?= e($rental['status']) ?></strong></div>
</div>

<section class="panel leave-panel">
    <div class="leave-copy">
        <span class="eyebrow">MOVE-OUT REQUEST</span>
        <?php if ($pendingRequest): ?>
            <h2>Leave Request Pending</h2>
            <p>Your request to leave Apartment <strong><?= e($rental['apartment_number']) ?></strong> has been sent to the administrator. Keep your rental active until it is approved.</p>
            <span class="badge pending">Waiting for Admin Approval</span>
        <?php else: ?>
            <h2>Want to leave this apartment?</h2>
            <p>Submit a leave request first. The apartment will remain assigned to you until an administrator approves the request.</p>
            <form method="post" class="inline-form" data-confirm="Submit a leave request for Apartment <?= e($rental['apartment_number']) ?>? You will need admin approval before the rental ends.">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="request_leave">
                <button class="btn danger" type="submit">Leave Apartment</button>
            </form>
        <?php endif; ?>
    </div>
</section>
<?php elseif ($pendingRequest): ?>
<div class="empty-state panel"><div class="empty-icon">⌛</div><h2>Request Pending</h2><p>Your leave/request status is pending.</p><span class="badge pending">Pending</span></div>
<?php else: ?>
<div class="empty-state panel"><div class="empty-icon">⌂</div><h2>No Rental Yet</h2><p>You do not have an active rental. Browse available apartments to submit a request.</p><a class="btn primary" href="apartments.php">Browse Apartments</a></div>
<?php endif; ?>

<?php require '../includes/footer.php'; ?>

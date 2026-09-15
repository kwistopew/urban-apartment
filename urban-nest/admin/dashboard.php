<?php
require_once '../includes/auth.php';
require_role('admin');
$pageTitle = 'Admin Dashboard';
$pageSubtitle = 'Overview of apartments, tenants, requests, and payments.';

$totalApartments = $conn->query('SELECT COUNT(*) total FROM apartments')->fetch_assoc()['total'] ?? 0;
$availableApartments = $conn->query("SELECT COUNT(*) total FROM apartments WHERE status = 'Available'")->fetch_assoc()['total'] ?? 0;
$occupiedApartments = $conn->query("SELECT COUNT(*) total FROM apartments WHERE status = 'Occupied'")->fetch_assoc()['total'] ?? 0;
$pendingRequests = $conn->query("SELECT COUNT(*) total FROM rental_requests WHERE status = 'Pending'")->fetch_assoc()['total'] ?? 0;
$paidPayments = $conn->query("SELECT COUNT(*) total FROM payments WHERE status IN ('Paid','Early Payment')")->fetch_assoc()['total'] ?? 0;
$overduePayments = $conn->query("SELECT COUNT(*) total FROM payments WHERE status = 'Overdue'")->fetch_assoc()['total'] ?? 0;
$overdueTotal = $conn->query("SELECT COALESCE(SUM(total_amount),0) total FROM payments WHERE status = 'Overdue'")->fetch_assoc()['total'] ?? 0;
$unreadMessages = $conn->query("SELECT COUNT(*) total FROM messages WHERE sender_role='tenant' AND is_read=0")->fetch_assoc()['total'] ?? 0;

$recentRequests = $conn->query("SELECT rr.*, t.full_name, a.apartment_number FROM rental_requests rr JOIN tenants t ON t.id = rr.tenant_id JOIN apartments a ON a.id = rr.apartment_id ORDER BY rr.request_date DESC, rr.id DESC LIMIT 5");
$recentPayments = $conn->query("SELECT p.*, t.full_name, a.apartment_number FROM payments p JOIN tenants t ON t.id = p.tenant_id JOIN apartments a ON a.id = p.apartment_id ORDER BY p.payment_date DESC, p.id DESC LIMIT 5");
require '../includes/header.php';
?>
<div class="stats-grid">
    <div class="stat-card"><span>Total Apartments</span><strong><?= (int)$totalApartments ?></strong><small>All units</small></div>
    <div class="stat-card"><span>Available</span><strong><?= (int)$availableApartments ?></strong><small>Ready to rent</small></div>
    <div class="stat-card"><span>Occupied</span><strong><?= (int)$occupiedApartments ?></strong><small>Currently rented</small></div>
    <div class="stat-card"><span>Pending Requests</span><strong><?= (int)$pendingRequests ?></strong><small>Need review</small></div>
    <div class="stat-card"><span>Paid Payments</span><strong><?= (int)$paidPayments ?></strong><small>On-time or early</small></div>
    <div class="stat-card danger"><span>Overdue Payments</span><strong><?= (int)$overduePayments ?></strong><small><?= money((float)$overdueTotal) ?> total</small></div>
    <div class="stat-card"><span>Unread Messages</span><strong><?= (int)$unreadMessages ?></strong><small>Tenant support</small></div>
</div>

<div class="section-grid two">
    <section class="panel">
        <div class="panel-head"><div><h2>Recent Rental Requests</h2><p>Latest requests submitted by tenants.</p></div><a class="btn secondary" href="requests.php">View All</a></div>
        <div class="table-wrap"><table><thead><tr><th>Tenant</th><th>Apartment</th><th>Date</th><th>Status</th></tr></thead><tbody>
        <?php while ($row = $recentRequests->fetch_assoc()): ?><tr><td><?= e($row['full_name']) ?></td><td><?= e($row['apartment_number']) ?></td><td><?= e(date('M d, Y', strtotime($row['request_date']))) ?></td><td><span class="badge <?= strtolower($row['status']) ?>"><?= e($row['status']) ?></span></td></tr><?php endwhile; ?>
        <?php if ($recentRequests->num_rows === 0): ?><tr><td colspan="4" class="empty">No rental requests yet.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>

    <section class="panel">
        <div class="panel-head"><div><h2>Recent Payments</h2><p>Latest payment records.</p></div><a class="btn secondary" href="payments.php">View All</a></div>
        <div class="table-wrap"><table><thead><tr><th>Tenant</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead><tbody>
        <?php while ($row = $recentPayments->fetch_assoc()): ?><tr><td><?= e($row['full_name']) ?></td><td><?= money((float)$row['total_amount']) ?></td><td><?= e(date('M d, Y', strtotime($row['payment_date']))) ?></td><td><span class="badge <?= strtolower(str_replace(' ', '-', $row['status'])) ?>"><?= e($row['status']) ?></span></td></tr><?php endwhile; ?>
        <?php if ($recentPayments->num_rows === 0): ?><tr><td colspan="4" class="empty">No payments yet.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</div>

<div class="panel warning-panel"><strong>⚠ Overdue Payment Reminder</strong><p>Payments made after the 5th of the month automatically receive a <?= money(500) ?> late fee. Current overdue total: <b><?= money((float)$overdueTotal) ?></b>.</p></div>
<?php require '../includes/footer.php'; ?>

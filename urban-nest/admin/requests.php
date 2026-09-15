<?php
require_once '../includes/auth.php';
require_role('admin');
$pageTitle = 'Rental Requests';
$pageSubtitle = 'Review and approve or reject apartment requests.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT rr.id, rr.tenant_id, rr.apartment_id, rr.status, a.status apartment_status, a.monthly_rent FROM rental_requests rr JOIN apartments a ON a.id=rr.apartment_id WHERE rr.id=? FOR UPDATE");
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$request || $request['status'] !== 'Pending') throw new RuntimeException('This request is no longer pending.');

        if ($action === 'approve') {
            $stmt = $conn->prepare("SELECT COUNT(*) c FROM rentals WHERE tenant_id=? AND status='Active'");
            $stmt->bind_param('i', $request['tenant_id']);
            $stmt->execute();
            $tenantActive = (int)$stmt->get_result()->fetch_assoc()['c'];
            $stmt->close();
            if ($tenantActive > 0) throw new RuntimeException('Tenant already has an active rental.');
            if ($request['apartment_status'] !== 'Available') throw new RuntimeException('Apartment is no longer available.');

            $stmt = $conn->prepare("UPDATE rental_requests SET status='Approved', approved_at=NOW() WHERE id=?");
            $stmt->bind_param('i', $requestId); $stmt->execute(); $stmt->close();
            $stmt = $conn->prepare("UPDATE apartments SET status='Occupied' WHERE id=?");
            $stmt->bind_param('i', $request['apartment_id']); $stmt->execute(); $stmt->close();
            $startDate = date('Y-m-d');
            $firstDueDate = next_billing_date($startDate);
            $stmt = $conn->prepare("INSERT INTO rentals (tenant_id, apartment_id, start_date, next_due_date, monthly_rent, status) VALUES (?, ?, ?, ?, ?, 'Active')");
            $stmt->bind_param('iissd', $request['tenant_id'], $request['apartment_id'], $startDate, $firstDueDate, $request['monthly_rent']);
            $stmt->execute(); $stmt->close();

            $stmt = $conn->prepare("UPDATE rental_requests SET status='Rejected' WHERE apartment_id=? AND id<>? AND status='Pending'");
            $stmt->bind_param('ii', $request['apartment_id'], $requestId); $stmt->execute(); $stmt->close();
            $conn->commit();
            flash('success', 'Rental request approved. Apartment is now occupied.');
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE rental_requests SET status='Rejected' WHERE id=?");
            $stmt->bind_param('i', $requestId); $stmt->execute(); $stmt->close();
            $conn->commit();
            flash('success', 'Rental request rejected.');
        } else {
            throw new RuntimeException('Invalid request action.');
        }
    } catch (Throwable $e) {
        $conn->rollback();
        flash('error', $e->getMessage());
    }
    redirect('requests.php');
}

$requests = $conn->query("SELECT rr.*, t.full_name, a.apartment_number, a.type, a.monthly_rent, r.start_date, r.next_due_date FROM rental_requests rr JOIN tenants t ON t.id=rr.tenant_id JOIN apartments a ON a.id=rr.apartment_id LEFT JOIN rentals r ON r.tenant_id=rr.tenant_id AND r.apartment_id=rr.apartment_id AND r.status='Active' ORDER BY CASE rr.status WHEN 'Pending' THEN 1 WHEN 'Approved' THEN 2 WHEN 'Rejected' THEN 3 ELSE 4 END, rr.request_date DESC, rr.id DESC");
require '../includes/header.php';
?>
<section class="panel">
<div class="panel-head"><div><h2>Rental Requests</h2><p>Pending requests are reviewed by the administrator before a rental becomes active.</p></div></div>
<div class="table-wrap"><table><thead><tr><th>Tenant</th><th>Apartment</th><th>Rent</th><th>Request Date</th><th>Start Date</th><th>First / Next Due</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php while ($row = $requests->fetch_assoc()): ?><tr><td><?= e($row['full_name']) ?></td><td><strong><?= e($row['apartment_number']) ?></strong><br><small><?= e($row['type']) ?></small></td><td><?= money((float)$row['monthly_rent']) ?></td><td><?= e(date('M d, Y', strtotime($row['request_date']))) ?></td><td><?= !empty($row['start_date']) ? e(date('M d, Y', strtotime($row['start_date']))) : '—' ?></td><td><?= !empty($row['next_due_date']) ? e(date('M d, Y', strtotime($row['next_due_date']))) : 'Set on approval' ?></td><td><span class="badge <?= strtolower($row['status']) ?>"><?= e($row['status']) ?></span></td><td><?php if ($row['status']==='Pending'): ?><div class="actions"><form method="post" class="inline-form" data-confirm="Approve this rental request?"><input type="hidden" name="request_id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="action" value="approve"><button class="btn small success" type="submit">Approve</button></form><form method="post" class="inline-form" data-confirm="Reject this rental request?"><input type="hidden" name="request_id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="action" value="reject"><button class="btn small danger" type="submit">Reject</button></form></div><?php else: ?>—<?php endif; ?></td></tr><?php endwhile; ?>
<?php if ($requests->num_rows===0): ?><tr><td colspan="8" class="empty">No rental requests found.</td></tr><?php endif; ?>
</tbody></table></div>
</section>
<?php require '../includes/footer.php'; ?>

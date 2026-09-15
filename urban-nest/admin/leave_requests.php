<?php
require_once '../includes/auth.php';
require_role('admin');

$pageTitle = 'Leave Requests';
$pageSubtitle = 'Review tenant requests to leave their current apartment.';

// leave_requests is created by the Supabase SQL schema.


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $requestId = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT lr.*, t.full_name, a.apartment_number, a.status apartment_status, r.status rental_status FROM leave_requests lr JOIN tenants t ON t.id=lr.tenant_id JOIN apartments a ON a.id=lr.apartment_id JOIN rentals r ON r.id=lr.rental_id WHERE lr.id=? FOR UPDATE");
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$request || $request['status'] !== 'Pending') throw new RuntimeException('This leave request is no longer pending.');

        if ($action === 'approve') {
            if ($request['rental_status'] !== 'Active') throw new RuntimeException('The rental is already inactive.');

            $endDate = date('Y-m-d');
            $stmt = $conn->prepare("UPDATE rentals SET status='Ended', end_date=? WHERE id=? AND status='Active'");
            $stmt->bind_param('si', $endDate, $request['rental_id']);
            $stmt->execute();
            if ($stmt->affected_rows !== 1) throw new RuntimeException('The rental could not be ended.');
            $stmt->close();

            $stmt = $conn->prepare("UPDATE apartments SET status='Available' WHERE id=?");
            $stmt->bind_param('i', $request['apartment_id']);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE leave_requests SET status='Approved', processed_at=NOW() WHERE id=? AND status='Pending'");
            $stmt->bind_param('i', $requestId);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            flash('success', 'Leave request approved. The rental has ended and the apartment is now available.');
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE leave_requests SET status='Rejected', processed_at=NOW() WHERE id=? AND status='Pending'");
            $stmt->bind_param('i', $requestId);
            $stmt->execute();
            $stmt->close();
            $conn->commit();
            flash('success', 'Leave request rejected. The tenant keeps the active rental.');
        } else {
            throw new RuntimeException('Invalid action.');
        }
    } catch (Throwable $e) {
        $conn->rollback();
        flash('error', $e->getMessage());
    }
    redirect('leave_requests.php');
}

$requests = $conn->query("SELECT lr.*, t.full_name, u.username, a.apartment_number, a.type, r.monthly_rent FROM leave_requests lr JOIN tenants t ON t.id=lr.tenant_id JOIN users u ON u.id=t.user_id JOIN apartments a ON a.id=lr.apartment_id JOIN rentals r ON r.id=lr.rental_id ORDER BY CASE lr.status WHEN 'Pending' THEN 1 WHEN 'Approved' THEN 2 WHEN 'Rejected' THEN 3 ELSE 4 END, lr.request_date DESC, lr.id DESC");
require '../includes/header.php';
?>
<section class="panel">
    <div class="panel-head"><div><h2>Leave Requests</h2><p>Approve a tenant's request to leave only after reviewing the request. Approval ends the rental and releases the apartment.</p></div></div>
    <div class="table-wrap"><table><thead><tr><th>Tenant</th><th>Apartment</th><th>Monthly Rent</th><th>Request Date</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php while ($row = $requests->fetch_assoc()): ?>
        <tr>
            <td><strong><?= e($row['full_name']) ?></strong><br><small><?= e($row['username']) ?></small></td>
            <td><strong><?= e($row['apartment_number']) ?></strong><br><small><?= e($row['type']) ?></small></td>
            <td><?= money((float)$row['monthly_rent']) ?></td>
            <td><?= e(date('M d, Y', strtotime($row['request_date']))) ?></td>
            <td><span class="badge <?= strtolower($row['status']) ?>"><?= e($row['status']) ?></span></td>
            <td>
                <?php if ($row['status'] === 'Pending'): ?>
                <div class="actions">
                    <form method="post" class="inline-form" data-confirm="Approve this leave request? The rental will end and the apartment will become available.">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="request_id" value="<?= (int)$row['id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <button class="btn small success" type="submit">Approve</button>
                    </form>
                    <form method="post" class="inline-form" data-confirm="Reject this leave request? The tenant will keep the active rental.">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="request_id" value="<?= (int)$row['id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <button class="btn small danger" type="submit">Reject</button>
                    </form>
                </div>
                <?php else: ?>—<?php endif; ?>
            </td>
        </tr>
    <?php endwhile; ?>
    <?php if ($requests->num_rows===0): ?><tr><td colspan="6" class="empty">No leave requests found.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php require '../includes/footer.php'; ?>

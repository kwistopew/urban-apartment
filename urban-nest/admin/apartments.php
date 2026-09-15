<?php
require_once '../includes/auth.php';
require_role('admin');
$pageTitle = 'Apartment Management';
$pageSubtitle = 'Add, edit, and monitor apartment units.';

$editing = null;
$error = '';

if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM apartments WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editing = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $number = trim($_POST['apartment_number'] ?? '');
        $type = $_POST['type'] ?? '';
        $rent = (float)($_POST['monthly_rent'] ?? 0);
        $status = $_POST['status'] ?? 'Available';
        $validTypes = ['Studio', '1 Bedroom', '2 Bedroom'];
        $validStatuses = ['Available', 'Reserved', 'Occupied'];
        $uploadDir = dirname(__DIR__) . '/uploads/apartments';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

        if ($number === '' || !in_array($type, $validTypes, true) || $rent <= 0 || !in_array($status, $validStatuses, true)) {
            $error = 'Please complete all apartment fields with valid values.';
        } else {
            $uploadedFiles = [];
            if (!empty($_FILES['apartment_images']['name'][0])) {
                $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
                foreach ($_FILES['apartment_images']['tmp_name'] as $i => $tmp) {
                    if ($_FILES['apartment_images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    if ($_FILES['apartment_images']['size'][$i] > 8 * 1024 * 1024) { $error = 'Each apartment image must be 8MB or smaller.'; break; }
                    $info = @getimagesize($tmp);
                    $mime = $info['mime'] ?? '';
                    if (!$info || !isset($allowed[$mime])) { $error = 'Only JPG, PNG, WEBP, and GIF images are allowed.'; break; }
                    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
                    if (move_uploaded_file($tmp, $uploadDir . '/' . $filename)) $uploadedFiles[] = $filename;
                }
            }
            if ($error === '') {
                if ($id > 0) {
                    $stmt = $conn->prepare('UPDATE apartments SET apartment_number = ?, type = ?, monthly_rent = ?, status = ? WHERE id = ?');
                    $stmt->bind_param('ssdsi', $number, $type, $rent, $status, $id);
                    if (!$stmt->execute()) $error = $stmt->errno === 1062 ? 'Apartment number already exists.' : 'Unable to update apartment.';
                    $stmt->close();
                    if ($error === '') {
                        if ($uploadedFiles) {
                            $img = $conn->prepare('INSERT INTO apartment_images (apartment_id, image_path) VALUES (?, ?)');
                            foreach ($uploadedFiles as $file) { $img->bind_param('is', $id, $file); $img->execute(); }
                            $img->close();
                        }
                        flash('success', 'Apartment updated successfully.');
                        redirect('apartments.php');
                    }
                } else {
                    $stmt = $conn->prepare('INSERT INTO apartments (apartment_number, type, monthly_rent, status) VALUES (?, ?, ?, ?)');
                    $stmt->bind_param('ssds', $number, $type, $rent, $status);
                    if ($stmt->execute()) {
                        $newId = $stmt->insert_id;
                        $stmt->close();
                        if ($uploadedFiles) {
                            $img = $conn->prepare('INSERT INTO apartment_images (apartment_id, image_path) VALUES (?, ?)');
                            foreach ($uploadedFiles as $file) { $img->bind_param('is', $newId, $file); $img->execute(); }
                            $img->close();
                        }
                        flash('success', 'Apartment added successfully.');
                        redirect('apartments.php');
                    }
                    $error = $stmt->errno === 1062 ? 'Apartment number already exists.' : 'Unable to add apartment.';
                    $stmt->close();
                }
            }
        }
    }
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('SELECT status FROM apartments WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$existing) {
            flash('error', 'Apartment not found.');
        } elseif ($existing['status'] === 'Occupied') {
            flash('error', 'Occupied apartments cannot be deleted.');
        } else {
            $stmt = $conn->prepare('DELETE FROM apartments WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            flash('success', 'Apartment deleted successfully.');
        }
        redirect('apartments.php');
    }
}

$apartments = $conn->query("SELECT a.*, (SELECT t.full_name FROM rentals r JOIN tenants t ON t.id=r.tenant_id WHERE r.apartment_id=a.id AND r.status='Active' LIMIT 1) tenant_name, (SELECT COUNT(*) FROM apartment_images ai WHERE ai.apartment_id=a.id) image_count FROM apartments a ORDER BY a.apartment_number");
require '../includes/header.php';
?>
<div class="section-grid two compact-left">
<section class="panel">
    <div class="panel-head"><div><h2><?= $editing ? 'Edit Apartment' : 'Add Apartment' ?></h2><p>Keep unit information up to date.</p></div></div>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form-grid" enctype="multipart/form-data" data-validate>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
        <div class="form-group"><label>Apartment Number</label><input name="apartment_number" value="<?= e($editing['apartment_number'] ?? '') ?>" placeholder="e.g. A107" required maxlength="20"></div>
        <div class="form-group"><label>Apartment Type</label><select name="type" required><option value="">Select type</option><?php foreach (['Studio','1 Bedroom','2 Bedroom'] as $type): ?><option <?= (($editing['type'] ?? '') === $type) ? 'selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Monthly Rent</label><input name="monthly_rent" type="number" min="1" step="0.01" value="<?= e((string)($editing['monthly_rent'] ?? '')) ?>" required></div>
        <div class="form-group"><label>Status</label><select name="status" required><?php foreach (['Available','Reserved','Occupied'] as $status): ?><option <?= (($editing['status'] ?? 'Available') === $status) ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></div>
        <div class="form-group full-span"><label>Apartment Photos</label><input name="apartment_images[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple><small>Upload one or more photos. Maximum 8MB per image.</small></div>
        <div class="form-actions"><button class="btn primary" type="submit">Save Apartment</button><?php if ($editing): ?><a class="btn secondary" href="apartments.php">Cancel</a><?php endif; ?></div>
    </form>
</section>
<section class="panel">
    <div class="panel-head"><div><h2>Apartment List</h2><p><?= $apartments->num_rows ?> total units</p></div><input class="search-input" id="tableSearch" type="search" placeholder="Search apartments..."></div>
    <div class="table-wrap"><table id="dataTable"><thead><tr><th>Number</th><th>Type</th><th>Rent</th><th>Status</th><th>Tenant</th><th>Photos</th><th>Actions</th></tr></thead><tbody>
    <?php while ($row = $apartments->fetch_assoc()): ?><tr><td><?= e($row['apartment_number']) ?></td><td><?= e($row['type']) ?></td><td><?= money((float)$row['monthly_rent']) ?></td><td><span class="badge <?= strtolower($row['status']) ?>"><?= e($row['status']) ?></span></td><td><?= e($row['tenant_name'] ?? '—') ?></td><td><?= (int)$row['image_count'] ?> photo(s)</td><td class="actions"><a class="btn small secondary" href="apartments.php?edit=<?= (int)$row['id'] ?>">Edit</a><form method="post" class="inline-form" data-confirm="Delete this apartment?"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="btn small danger" type="submit">Delete</button></form></td></tr><?php endwhile; ?>
    <?php if ($apartments->num_rows === 0): ?><tr><td colspan="7" class="empty">No apartments found.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
</div>
<?php require '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_role('tenant');
$pageTitle = 'Available Apartments';
$pageSubtitle = 'Browse units that are currently available for rent.';
$tenantStmt=$conn->prepare('SELECT id FROM tenants WHERE user_id=? LIMIT 1'); $tenantStmt->bind_param('i',$_SESSION['user_id']); $tenantStmt->execute(); $tenantId=(int)$tenantStmt->get_result()->fetch_assoc()['id']; $tenantStmt->close();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $apartmentId=(int)($_POST['apartment_id']??0);
    $stmt=$conn->prepare("SELECT id, status FROM apartments WHERE id=?"); $stmt->bind_param('i',$apartmentId); $stmt->execute(); $apartment=$stmt->get_result()->fetch_assoc(); $stmt->close();
    $stmt=$conn->prepare("SELECT COUNT(*) c FROM rentals WHERE tenant_id=? AND status='Active'"); $stmt->bind_param('i',$tenantId); $stmt->execute(); $hasRental=(int)$stmt->get_result()->fetch_assoc()['c']; $stmt->close();
    $stmt=$conn->prepare("SELECT COUNT(*) c FROM rental_requests WHERE tenant_id=? AND status='Pending'"); $stmt->bind_param('i',$tenantId); $stmt->execute(); $hasPending=(int)$stmt->get_result()->fetch_assoc()['c']; $stmt->close();
    if (!$apartment || $apartment['status']!=='Available') flash('error','This apartment is no longer available.');
    elseif ($hasRental>0) flash('error','You already have an active rental.');
    elseif ($hasPending>0) flash('error','You already have a pending rental request.');
    else { $stmt=$conn->prepare("INSERT INTO rental_requests (tenant_id, apartment_id, status) VALUES (?, ?, 'Pending')"); $stmt->bind_param('ii',$tenantId,$apartmentId); $stmt->execute(); $stmt->close(); flash('success','Rental request submitted. Please wait for admin approval.'); }
    redirect('apartments.php');
}

$apartments=$conn->query("SELECT a.*, (SELECT COUNT(*) FROM apartment_images ai WHERE ai.apartment_id=a.id) image_count FROM apartments a WHERE a.status='Available' ORDER BY a.apartment_number");
require '../includes/header.php';
?>
<div class="toolbar"><div><strong><?= $apartments->num_rows ?></strong> available apartments</div><input class="search-input" id="cardSearch" type="search" placeholder="Search by number or type..."></div>
<div class="apartment-grid" id="apartmentGrid">
<?php while ($row=$apartments->fetch_assoc()): ?>
<?php $imgStmt=$conn->prepare('SELECT image_path FROM apartment_images WHERE apartment_id=? ORDER BY id ASC'); $imgStmt->bind_param('i',$row['id']); $imgStmt->execute(); $imgRes=$imgStmt->get_result(); $images=[]; while($im=$imgRes->fetch_assoc()) $images[]=$im['image_path']; $imgStmt->close(); ?>
<article class="apartment-card" data-search="<?= e(strtolower($row['apartment_number'].' '.$row['type'])) ?>">
    <div class="apartment-photo" onclick="openApartmentGallery(<?= (int)$row['id'] ?>)">
        <?php if ($images): ?><img src="../uploads/apartments/<?= e($images[0]) ?>" alt="Apartment <?= e($row['apartment_number']) ?>"><span class="photo-count">📷 <?= count($images) ?></span>
        <?php else: ?><div class="no-photo">⌂<span>No photos uploaded</span></div><?php endif; ?>
    </div>
    <div class="apartment-top"><span class="badge available">Available</span><span class="unit-number"><?= e($row['apartment_number']) ?></span></div>
    <h2><?= e($row['type']) ?></h2><div class="rent-price"><?= money((float)$row['monthly_rent']) ?><span>/ month</span></div>
    <p>Ready for a new tenant. View the apartment photos, then submit a request and wait for administrator approval.</p>
    <div class="card-actions"><button class="btn secondary" type="button" onclick="openApartmentGallery(<?= (int)$row['id'] ?>)">View Photos</button><form method="post"><input type="hidden" name="apartment_id" value="<?= (int)$row['id'] ?>"><button class="btn primary" type="submit">Request Apartment</button></form></div>
    <script type="application/json" id="gallery-data-<?= (int)$row['id'] ?>"><?= json_encode($images, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?></script>
</article>
<?php endwhile; ?>
</div>
<?php if ($apartments->num_rows===0): ?><div class="empty-state panel"><div class="empty-icon">⌂</div><h2>No Available Apartments</h2><p>There are no units available right now.</p></div><?php endif; ?>
<div class="modal-backdrop apartment-gallery-modal" id="apartmentGalleryModal" hidden><div class="gallery-modal-card"><button class="modal-close" type="button" onclick="closeApartmentGallery()">×</button><button class="gallery-nav prev" type="button" onclick="galleryPrev()">‹</button><div class="gallery-main"><img id="galleryMainImage" src="" alt="Apartment photo"><div id="galleryEmpty" class="gallery-empty" hidden>No photos available.</div></div><button class="gallery-nav next" type="button" onclick="galleryNext()">›</button><div class="gallery-caption"><strong id="galleryTitle">Apartment Photos</strong><span id="galleryCounter"></span></div></div></div>
<?php require '../includes/footer.php'; ?>

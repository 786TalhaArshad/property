<?php
require_once '../includes/auth.php';
require_login();
require_permission('properties.view');
$title = 'Property Details';
$active = 'properties';
$canEdit = has_permission('properties.manage');

$id = (int)($_GET['id'] ?? 0);
$prop = db_get("SELECT p.*, pt.name AS type_name, pc.name AS category_name, pr.name AS project_name,
                b.name AS block_name, r.name AS road_name, s.name AS street_name,
                o.full_name AS owner_name, o.phone AS owner_phone, c.full_name AS customer_name
                FROM properties p
                LEFT JOIN property_types pt ON pt.id = p.property_type_id
                LEFT JOIN property_categories pc ON pc.id = p.property_category_id
                LEFT JOIN projects pr ON pr.id = p.project_id
                LEFT JOIN blocks b ON b.id = p.block_id
                LEFT JOIN roads r ON r.id = p.road_id
                LEFT JOIN streets s ON s.id = p.street_id
                LEFT JOIN owners o ON o.id = p.owner_id
                LEFT JOIN customers c ON c.id = p.customer_id
                WHERE p.id = ?", [$id]);
if (!$prop) {
    flash('danger', 'Property not found.');
    redirect('properties.php');
}

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'image_upload') {
        $title = trim($_POST['title'] ?? '');
        $img = upload_file('image_file', 'uploads/properties');
        if ($img === false) {
            flash('danger', 'Image upload failed.');
        } else {
            db_exec("INSERT INTO property_images (property_id, image_file, title, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$id, $img, $title]);
            flash('success', 'Image uploaded.');
        }
    } elseif ($action === 'image_delete') {
        db_exec("DELETE FROM property_images WHERE id = ? AND property_id = ?", [(int)($_POST['id'] ?? 0), $id]);
        flash('success', 'Image deleted.');
    } elseif ($action === 'doc_upload') {
        $title = trim($_POST['title'] ?? '');
        $doc = upload_file('doc_file', 'uploads/properties');
        if ($doc === false) {
            flash('danger', 'Document upload failed.');
        } else {
            db_exec("INSERT INTO property_documents (property_id, title, file_path, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$id, $title, $doc]);
            flash('success', 'Document uploaded.');
        }
    } elseif ($action === 'doc_delete') {
        db_exec("DELETE FROM property_documents WHERE id = ? AND property_id = ?", [(int)($_POST['id'] ?? 0), $id]);
        flash('success', 'Document deleted.');
    } elseif ($action === 'amenity_toggle') {
        $amenity_id = (int)($_POST['amenity_id'] ?? 0);
        $exists = db_get("SELECT id FROM property_amenities WHERE property_id = ? AND amenity_id = ?", [$id, $amenity_id]);
        if ($exists) {
            db_exec("DELETE FROM property_amenities WHERE id = ?", [$exists['id']]);
        } else {
            db_exec("INSERT INTO property_amenities (property_id, amenity_id, created_date, created_time, updated_date, updated_time) VALUES (?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$id, $amenity_id]);
        }
        flash('success', 'Amenity updated.');
    }
    redirect('property_view.php?id=' . $id);
}

$images = db_all("SELECT * FROM property_images WHERE property_id = ? ORDER BY id DESC", [$id]);
$docs = db_all("SELECT * FROM property_documents WHERE property_id = ? ORDER BY id DESC", [$id]);
$amenities = db_all("SELECT * FROM amenities ORDER BY name");
$hasAmenity = [];
foreach (db_all("SELECT amenity_id FROM property_amenities WHERE property_id = ?", [$id]) as $a) {
    $hasAmenity[$a['amenity_id']] = true;
}
$booking = db_get("SELECT b.*, c.full_name AS customer_name FROM bookings b JOIN customers c ON c.id = b.customer_id WHERE b.property_id = ? AND b.status <> 'cancelled' ORDER BY b.id DESC LIMIT 1", [$id]);
$rental = db_get("SELECT ra.*, t.full_name AS tenant_name FROM rental_agreements ra JOIN tenants t ON t.id = ra.tenant_id WHERE ra.property_id = ? ORDER BY ra.id DESC LIMIT 1", [$id]);

include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="properties.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= e($prop['property_no']) ?></h5>
    <?= status_badge($prop['status']) ?>
    <span class="ms-auto">
        <?php if ($canEdit): ?>
        <a href="property_form.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i> Edit</a>
        <?php endif; ?>
    </span>
</div>

<ul class="nav nav-pills mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tOverview">Overview</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tImages">Images (<?= count($images) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tAmenities">Amenities</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tDocs">Documents (<?= count($docs) ?>)</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tOverview">
        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-info-circle me-2"></i>Details</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4"><div class="text-muted small">Type</div><div class="fw-medium"><?= e($prop['type_name'] ?? '-') ?></div></div>
                            <div class="col-md-4"><div class="text-muted small">Category</div><div class="fw-medium"><?= e($prop['category_name'] ?? '-') ?></div></div>
                            <div class="col-md-4"><div class="text-muted small">Size</div><div class="fw-medium"><?= fmt_num($prop['size_value']) ?> <?= e($prop['size_unit']) ?></div></div>
                            <div class="col-md-4"><div class="text-muted small">Project</div><div class="fw-medium"><?= e($prop['project_name'] ?? '-') ?></div></div>
                            <div class="col-md-4"><div class="text-muted small">Block / Road / Street</div><div class="fw-medium"><?= e($prop['block_name'] ?? '-') ?> / <?= e($prop['road_name'] ?? '-') ?> / <?= e($prop['street_name'] ?? '-') ?></div></div>
                            <div class="col-md-4"><div class="text-muted small">File No</div><div class="fw-medium"><?= e($prop['file_no'] ?? '-') ?></div></div>
                            <div class="col-md-4"><div class="text-muted small">Plot / House / Unit No</div><div class="fw-medium"><?= e($prop['plot_no'] ?: $prop['house_no'] ?: $prop['apartment_no'] ?: $prop['shop_no'] ?: $prop['office_no'] ?: '-') ?></div></div>
                            <div class="col-md-4"><div class="text-muted small">Sale Price</div><div class="fw-medium"><?= fmt_money($prop['sale_price']) ?></div></div>
                            <div class="col-md-4"><div class="text-muted small">Rent / Month</div><div class="fw-medium"><?= fmt_money($prop['rent_amount']) ?></div></div>
                            <div class="col-md-4"><div class="text-muted small">Possession</div><div class="fw-medium"><?= ucfirst(str_replace('_', ' ', $prop['possession_status'])) ?> <?= $prop['possession_date'] ? '(' . fmt_date($prop['possession_date']) . ')' : '' ?></div></div>
                            <div class="col-md-8"><div class="text-muted small">Description</div><div class="fw-medium"><?= e($prop['description'] ?? '-') ?></div></div>
                        </div>
                        <hr>
                        <div class="d-flex gap-4">
                            <span><?= $prop['corner'] ? '<span class="badge bg-info">Corner</span>' : '' ?></span>
                            <span><?= $prop['main_boulevard'] ? '<span class="badge bg-info">Main Boulevard</span>' : '' ?></span>
                            <span><?= $prop['park_facing'] ? '<span class="badge bg-info">Park Facing</span>' : '' ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-person-badge me-2"></i>Owner</div>
                    <div class="card-body py-3">
                        <div class="fw-medium"><?= e($prop['owner_name'] ?? '-') ?></div>
                        <div class="small text-muted"><?= e($prop['owner_phone'] ?? '') ?></div>
                    </div>
                </div>
                <?php if ($booking): ?>
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-journal-check me-2"></i>Current Booking</div>
                    <div class="card-body py-3">
                        <div class="fw-medium"><?= e($booking['booking_no']) ?></div>
                        <div class="small text-muted"><?= e($booking['customer_name']) ?></div>
                        <div class="mt-2"><?= status_badge($booking['status']) ?></div>
                        <a href="booking_view.php?id=<?= $booking['id'] ?>" class="btn btn-sm btn-outline-primary mt-2">View Booking</a>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($rental): ?>
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-house-heart me-2"></i>Rental</div>
                    <div class="card-body py-3">
                        <div class="fw-medium"><?= e($rental['agreement_no']) ?></div>
                        <div class="small text-muted"><?= e($rental['tenant_name']) ?></div>
                        <div class="mt-2"><?= status_badge($rental['status']) ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tImages">
        <div class="card">
            <div class="card-body">
                <?php if ($canEdit): ?>
                <form method="post" enctype="multipart/form-data" class="row g-2 align-items-center mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="image_upload">
                    <div class="col-md-4"><input type="file" name="image_file" class="form-control" accept="image/*" required></div>
                    <div class="col-md-4"><input type="text" name="title" class="form-control" placeholder="Title (optional)"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-upload"></i> Upload</button></div>
                </form>
                <?php endif; ?>
                <div class="row g-3">
                    <?php foreach ($images as $img): ?>
                    <div class="col-md-3 col-sm-4">
                        <div class="card">
                            <img src="<?= BASE_URL ?>/assets/<?= e($img['image_file']) ?>" class="card-img-top" style="height:150px;object-fit:cover">
                            <div class="card-body py-2 d-flex align-items-center justify-content-between">
                                <span class="small text-truncate"><?= e($img['title'] ?? 'Image') ?></span>
                                <?php if ($canEdit): ?>
                                <form method="post" data-confirm="Delete this image?" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="image_delete">
                                    <input type="hidden" name="id" value="<?= $img['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (!$images): ?><div class="col-12"><div class="empty-state"><i class="bi bi-camera"></i><p>No images yet</p></div></div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tAmenities">
        <div class="card">
            <div class="card-body">
                <?php if ($canEdit): ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="amenity_toggle">
                    <div class="row g-3">
                        <?php foreach ($amenities as $a): ?>
                        <div class="col-md-3 col-sm-4">
                            <button type="submit" name="amenity_id" value="<?= $a['id'] ?>" class="btn w-100 <?= isset($hasAmenity[$a['id']]) ? 'btn-primary' : 'btn-outline-secondary' ?>" style="text-align:left">
                                <i class="bi <?= e($a['icon']) ?> me-2"></i><?= e($a['name']) ?>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </form>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($amenities as $a): ?>
                            <?php if (isset($hasAmenity[$a['id']])): ?><span class="badge bg-light text-dark border"><i class="bi <?= e($a['icon']) ?>"></i> <?= e($a['name']) ?></span><?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tDocs">
        <div class="card">
            <div class="card-body">
                <?php if ($canEdit): ?>
                <form method="post" enctype="multipart/form-data" class="row g-2 align-items-center mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="doc_upload">
                    <div class="col-md-4"><input type="file" name="doc_file" class="form-control" required></div>
                    <div class="col-md-4"><input type="text" name="title" class="form-control" placeholder="Title" required></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-upload"></i> Upload</button></div>
                </form>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>#</th><th>Title</th><th>File</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($docs as $i => $d): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= e($d['title']) ?></td>
                                <td><a href="<?= BASE_URL ?>/assets/<?= e($d['file_path']) ?>" target="_blank"><i class="bi bi-file-earmark-arrow-down"></i> Download</a></td>
                                <td class="text-end">
                                    <?php if ($canEdit): ?>
                                    <form method="post" class="d-inline" data-confirm="Delete this document?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="doc_delete">
                                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$docs): ?><tr><td colspan="4" class="text-center text-muted py-4">No documents yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_login();
require_permission('properties.view');
$title = 'Property Inventory';
$active = 'properties';
$canEdit = has_permission('properties.manage');

$status = $_GET['status'] ?? '';
$project_id = (int)($_GET['project_id'] ?? active_project_id());
$type_id = (int)($_GET['type_id'] ?? 0);
$search = trim($_GET['search'] ?? '');

$where = ['1=1'];
$params = [];
if ($status !== '') {
    $where[] = 'p.status = ?';
    $params[] = $status;
}
if ($project_id) {
    $where[] = 'p.project_id = ?';
    $params[] = $project_id;
}
if ($type_id) {
    $where[] = 'p.property_type_id = ?';
    $params[] = $type_id;
}
if ($search !== '') {
    $where[] = "(p.property_no LIKE ? OR p.plot_no LIKE ? OR p.house_no LIKE ? OR p.file_no LIKE ? OR p.shop_no LIKE ? OR p.apartment_no LIKE ?)";
    $s = "%$search%";
    array_push($params, $s, $s, $s, $s, $s, $s);
}

$sql = "SELECT p.*, pt.name AS type_name, pr.name AS project_name, b.name AS block_name,
               o.full_name AS owner_name, c.full_name AS customer_name,
               (SELECT i.image_file FROM property_images i WHERE i.property_id = p.id ORDER BY i.id LIMIT 1) AS thumb
        FROM properties p
        LEFT JOIN property_types pt ON pt.id = p.property_type_id
        LEFT JOIN projects pr ON pr.id = p.project_id
        LEFT JOIN blocks b ON b.id = p.block_id
        LEFT JOIN owners o ON o.id = p.owner_id
        LEFT JOIN customers c ON c.id = p.customer_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY p.id DESC";

$records = db_all($sql, $params);
$projects = db_all("SELECT * FROM projects ORDER BY name");
$types = db_all("SELECT * FROM property_types ORDER BY name");

include '../includes/header.php';
?>

<form method="get" class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:240px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" name="search" class="form-control" placeholder="Property / plot / file no..." value="<?= e($search) ?>">
    </div>
    <select name="status" class="form-select form-select-sm" style="max-width:160px">
        <option value="">All Status</option>
        <?php foreach (['available', 'booked', 'reserved', 'sold', 'transferred', 'rental', 'occupied', 'vacant'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="project_id" class="form-select form-select-sm" style="max-width:220px">
        <option value="">All Projects</option>
        <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
    </select>
    <select name="type_id" class="form-select form-select-sm" style="max-width:180px">
        <option value="">All Types</option>
        <?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>" <?= $type_id === (int)$t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-outline-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
    <a href="properties.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
    <?php if ($canEdit): ?>
    <a href="property_form.php" class="btn btn-primary ms-auto"><i class="bi bi-plus-lg me-1"></i>Add Property</a>
    <?php endif; ?>
</form>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr>
                    <th></th><th>Property</th><th>Location</th><th>Size</th><th>Price</th><th>Status</th><th>Owner</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($records as $r): ?>
                    <tr>
                        <td>
                            <?php if ($r['thumb']): ?>
                                <img src="<?= BASE_URL ?>/assets/<?= e($r['thumb']) ?>" class="img-thumb">
                            <?php else: ?>
                                <span class="avatar-sm"><i class="bi bi-house"></i></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-medium"><?= e($r['property_no']) ?></div>
                            <div class="small text-muted">
                                <?= e($r['plot_no'] ?: $r['house_no'] ?: $r['apartment_no'] ?: $r['shop_no'] ?: $r['office_no'] ?: '-') ?>
                            </div>
                        </td>
                        <td>
                            <?= e($r['project_name'] ?? '-') ?>
                            <?= $r['block_name'] ? '<div class="small text-muted">Block ' . e($r['block_name']) . '</div>' : '' ?>
                        </td>
                        <td class="text-nowrap"><?= fmt_num($r['size_value']) ?> <?= e($r['size_unit']) ?></td>
                        <td class="text-nowrap"><?= fmt_money($r['sale_price']) ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td class="small"><?= e($r['owner_name'] ?? '-') ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-info" href="property_view.php?id=<?= $r['id'] ?>" title="View"><i class="bi bi-eye"></i></a>
                            <?php if ($canEdit): ?>
                            <a class="btn btn-sm btn-outline-primary" href="property_form.php?id=<?= $r['id'] ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$records): ?>
                    <tr><td colspan="8"><div class="empty-state"><i class="bi bi-house-door"></i><p>No properties found</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

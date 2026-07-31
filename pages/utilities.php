<?php
require_once '../includes/auth.php';
require_login();
require_permission('utilities.view');
$title = 'Utilities';
$active = 'utilities';
$canEdit = has_permission('utilities.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $property_id = (int)$_POST['property_id'];
        $tenant_id = (int)($_POST['tenant_id'] ?? 0) ?: null;
        $utility_type = $_POST['utility_type'] ?? 'electricity';
        $meter_no = trim($_POST['meter_no'] ?? '');
        $connection_no = trim($_POST['connection_no'] ?? '');
        $consumer_no = trim($_POST['consumer_no'] ?? '');
        $rate = (float)($_POST['rate'] ?? 0);
        $status = $_POST['status'] ?? 'active';

        if ($property_id <= 0) {
            flash('danger', 'Please select a property.');
        } elseif ($id > 0) {
            db_exec("UPDATE utilities SET property_id=?, tenant_id=?, utility_type=?, meter_no=?, connection_no=?, consumer_no=?, rate=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$property_id, $tenant_id, $utility_type, $meter_no, $connection_no, $consumer_no, $rate, $status, $id]);
            flash('success', 'Utility updated successfully.');
        } else {
            db_exec("INSERT INTO utilities (property_id, tenant_id, utility_type, meter_no, connection_no, consumer_no, rate, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$property_id, $tenant_id, $utility_type, $meter_no, $connection_no, $consumer_no, $rate, $status]);
            flash('success', 'Utility added successfully.');
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM utilities WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Utility deleted successfully.');
    }
    redirect('utilities.php');
}

$records = db_all("SELECT u.*, p.property_no, t.full_name AS tenant_name
                   FROM utilities u
                   JOIN properties p ON p.id = u.property_id
                   LEFT JOIN tenants t ON t.id = u.tenant_id
                   ORDER BY p.property_no, u.utility_type");
$properties = db_all("SELECT * FROM properties ORDER BY property_no");
$tenants = db_all("SELECT * FROM tenants WHERE status = 1 ORDER BY full_name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search utilities...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Utility"><i class="bi bi-plus-lg me-1"></i>Add Utility</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Property</th><th>Tenant</th><th>Type</th><th>Meter / Connection</th><th>Consumer No</th><th>Rate</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><a href="property_view.php?id=<?= $r['property_id'] ?>"><?= e($r['property_no']) ?></a></td>
                        <td><?= e($r['tenant_name'] ?? '-') ?></td>
                        <td><span class="badge bg-light text-dark border text-capitalize"><?= e($r['utility_type']) ?></span></td>
                        <td class="small"><?= e($r['meter_no'] ?? '-') ?><?= $r['connection_no'] ? '<br>' . e($r['connection_no']) : '' ?></td>
                        <td class="small"><?= e($r['consumer_no'] ?? '-') ?></td>
                        <td><?= fmt_money($r['rate']) ?></td>
                        <td><?= $r['status'] === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-info" href="utility_view.php?id=<?= $r['id'] ?>"><i class="bi bi-eye"></i></a>
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'property_id' => $r['property_id'], 'tenant_id' => $r['tenant_id'], 'utility_type' => $r['utility_type'], 'meter_no' => $r['meter_no'], 'connection_no' => $r['connection_no'], 'consumer_no' => $r['consumer_no'], 'rate' => $r['rate'], 'status' => $r['status']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this utility? Readings and bills will be removed.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$records): ?>
                    <tr><td colspan="9"><div class="empty-state"><i class="bi bi-plug"></i><p>No utilities yet</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="recordModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header"><h5 class="modal-title">Add Utility</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Property</label>
                            <select name="property_id" class="form-select" required>
                                <option value="">Select Property</option>
                                <?php foreach ($properties as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['property_no']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tenant (optional)</label>
                            <select name="tenant_id" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($tenants as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['full_name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <select name="utility_type" class="form-select">
                                <?php foreach (['electricity', 'gas', 'water', 'internet', 'maintenance', 'generator', 'lift'] as $t): ?>
                                    <option value="<?= $t ?>"><?= ucfirst($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Meter No</label>
                            <input type="text" name="meter_no" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Connection No</label>
                            <input type="text" name="connection_no" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Consumer No</label>
                            <input type="text" name="consumer_no" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Rate</label>
                            <input type="number" step="0.01" name="rate" class="form-control" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

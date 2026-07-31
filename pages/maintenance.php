<?php
require_once '../includes/auth.php';
require_login();
require_permission('maintenance.view');
$title = 'Maintenance';
$active = 'maintenance';
$canEdit = has_permission('maintenance.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'complaint_save') {
        $id = (int)($_POST['id'] ?? 0);
        $complaint_no = trim($_POST['complaint_no'] ?? '');
        if ($complaint_no === '') {
            $complaint_no = next_number('MC', 'maintenance_complaints', 'complaint_no');
        }
        $property_id = (int)($_POST['property_id'] ?? 0) ?: null;
        $tenant_id = (int)($_POST['tenant_id'] ?? 0) ?: null;
        $category = $_POST['category'] ?? 'other';
        $description = trim($_POST['description'] ?? '');
        $priority = $_POST['priority'] ?? 'medium';
        $status = $_POST['status'] ?? 'open';
        $reported_by = trim($_POST['reported_by'] ?? '');
        $reported_date = $_POST['reported_date'] ?? date('Y-m-d');

        if ($description === '') {
            flash('danger', 'Description is required.');
        } elseif ($id > 0) {
            db_exec("UPDATE maintenance_complaints SET complaint_no=?, property_id=?, tenant_id=?, category=?, description=?, priority=?, status=?, reported_by=?, reported_date=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$complaint_no, $property_id, $tenant_id, $category, $description, $priority, $status, $reported_by, $reported_date, $id]);
            flash('success', 'Complaint updated successfully.');
        } else {
            db_exec("INSERT INTO maintenance_complaints (complaint_no, property_id, tenant_id, category, description, priority, status, reported_by, reported_date, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$complaint_no, $property_id, $tenant_id, $category, $description, $priority, $status, $reported_by, $reported_date]);
            flash('success', 'Complaint registered successfully.');
        }
    } elseif ($action === 'complaint_delete') {
        db_exec("DELETE FROM maintenance_complaints WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Complaint deleted successfully.');
    } elseif ($action === 'tech_save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $speciality = trim($_POST['speciality'] ?? '');
        $status = (int)($_POST['status'] ?? 1);
        if ($name === '') {
            flash('danger', 'Technician name is required.');
        } elseif ($id > 0) {
            db_exec("UPDATE technicians SET name=?, phone=?, speciality=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$name, $phone, $speciality, $status, $id]);
            flash('success', 'Technician updated successfully.');
        } else {
            db_exec("INSERT INTO technicians (name, phone, speciality, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$name, $phone, $speciality, $status]);
            flash('success', 'Technician added successfully.');
        }
    } elseif ($action === 'tech_delete') {
        db_exec("DELETE FROM technicians WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Technician deleted successfully.');
    }
    redirect('maintenance.php');
}

$status = $_GET['status'] ?? '';
$records = db_all("SELECT mc.*, p.property_no, t.full_name AS tenant_name,
                   (SELECT COALESCE(SUM(mt.cost),0) FROM maintenance_tasks mt WHERE mt.complaint_id = mc.id) AS total_cost
                   FROM maintenance_complaints mc
                   LEFT JOIN properties p ON p.id = mc.property_id
                   LEFT JOIN tenants t ON t.id = mc.tenant_id
                   WHERE (? = '' OR mc.status = ?)
                   ORDER BY mc.reported_date DESC", [$status, $status]);
$technicians = db_all("SELECT * FROM technicians ORDER BY name");
$properties = db_all("SELECT * FROM properties ORDER BY property_no");
$tenants = db_all("SELECT * FROM tenants ORDER BY full_name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search complaints...">
    </div>
    <select class="form-select form-select-sm" style="max-width:160px" onchange="location.href='?status='+this.value">
        <option value="">All Status</option>
        <?php foreach (['open', 'in_progress', 'completed', 'cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#complaintModal" data-add="Register Complaint"><i class="bi bi-plus-lg me-1"></i>Register Complaint</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Complaint</th><th>Property</th><th>Tenant</th><th>Category</th><th>Priority</th><th>Date</th><th>Cost</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><a class="fw-medium text-decoration-none" href="maintenance_view.php?id=<?= $r['id'] ?>"><?= e($r['complaint_no']) ?></a><div class="small text-muted"><?= mb_strimwidth(e($r['description']), 0, 60, '...') ?></div></td>
                        <td><?= e($r['property_no'] ?? '-') ?></td>
                        <td><?= e($r['tenant_name'] ?? '-') ?></td>
                        <td><span class="badge bg-light text-dark border"><?= ucfirst(e($r['category'])) ?></span></td>
                        <td><?= status_badge($r['priority']) ?></td>
                        <td><?= fmt_date($r['reported_date']) ?></td>
                        <td><?= fmt_money($r['total_cost']) ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-info" href="maintenance_view.php?id=<?= $r['id'] ?>"><i class="bi bi-eye"></i></a>
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#complaintModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'complaint_no' => $r['complaint_no'], 'property_id' => $r['property_id'], 'tenant_id' => $r['tenant_id'], 'category' => $r['category'], 'description' => $r['description'], 'priority' => $r['priority'], 'status' => $r['status'], 'reported_by' => $r['reported_by'], 'reported_date' => $r['reported_date']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this complaint? Tasks will be removed.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="complaint_delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$records): ?>
                    <tr><td colspan="10"><div class="empty-state"><i class="bi bi-tools"></i><p>No complaints yet</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-person-gear me-2"></i>Technicians</span>
        <?php if ($canEdit): ?><button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#techModal" data-add="Add Technician"><i class="bi bi-plus-lg"></i></button><?php endif; ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th style="width:50px">#</th><th>Name</th><th>Phone</th><th>Speciality</th><th>Status</th><?php if ($canEdit): ?><th class="text-end">Actions</th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($technicians as $i => $t): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-medium"><?= e($t['name']) ?></td>
                        <td><?= e($t['phone']) ?></td>
                        <td><?= e($t['speciality'] ?? '-') ?></td>
                        <td><?= $t['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                        <?php if ($canEdit): ?>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#techModal" data-edit='<?= h(json_encode(['id' => $t['id'], 'name' => $t['name'], 'phone' => $t['phone'], 'speciality' => $t['speciality'], 'status' => $t['status']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this technician?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="tech_delete">
                                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$technicians): ?><tr><td colspan="6" class="text-center text-muted py-3">No technicians added</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="complaintModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header"><h5 class="modal-title">Register Complaint</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="complaint_save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Complaint No</label>
                            <input type="text" name="complaint_no" class="form-control" placeholder="Auto if blank">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reported Date</label>
                            <input type="date" name="reported_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Property</label>
                            <select name="property_id" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($properties as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['property_no']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tenant</label>
                            <select name="tenant_id" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($tenants as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['full_name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <?php foreach (['electric', 'plumbing', 'painting', 'structural', 'cleaning', 'other'] as $c): ?>
                                    <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                <?php foreach (['low', 'medium', 'high', 'urgent'] as $pr): ?>
                                    <option value="<?= $pr ?>"><?= ucfirst($pr) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach (['open', 'in_progress', 'completed', 'cancelled'] as $s): ?>
                                    <option value="<?= $s ?>"><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reported By</label>
                            <input type="text" name="reported_by" class="form-control">
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

<div class="modal fade" id="techModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header"><h5 class="modal-title">Add Technician</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="tech_save">
                    <input type="hidden" name="id" id="techId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Speciality</label>
                            <input type="text" name="speciality" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
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

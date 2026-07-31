<?php
require_once '../includes/auth.php';
require_login();
require_permission('master.view');
$title = 'Branches';
$active = 'master';
$canEdit = has_permission('settings.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $city_id = (int)($_POST['city_id'] ?? 0) ?: null;
        $status = (int)($_POST['status'] ?? 1);
        if ($name === '') {
            flash('danger', 'Branch name is required.');
        } else {
            $dup = db_get("SELECT id FROM branches WHERE name = ? AND id <> ?", [$name, $id]);
            if ($dup) {
                flash('danger', 'A branch with this name already exists.');
            } elseif ($id > 0) {
                db_exec("UPDATE branches SET name=?, code=?, address=?, phone=?, email=?, city_id=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$name, $code, $address, $phone, $email, $city_id, $status, $id]);
                flash('success', 'Branch updated successfully.');
            } else {
                db_exec("INSERT INTO branches (name, code, address, phone, email, city_id, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$name, $code, $address, $phone, $email, $city_id, $status]);
                flash('success', 'Branch added successfully.');
            }
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM branches WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Branch deleted successfully.');
    }
    redirect('branches.php');
}

$records = db_all("SELECT b.*, c.name AS city_name FROM branches b LEFT JOIN cities c ON c.id = b.city_id ORDER BY b.name");
$cities = db_all("SELECT * FROM cities ORDER BY name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Branch"><i class="bi bi-plus-lg me-1"></i>Add Branch</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Name</th><th>Code</th><th>City</th><th>Phone</th><th>Email</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-medium"><?= e($r['name']) ?></td>
                        <td><?= e($r['code']) ?></td>
                        <td><?= e($r['city_name']) ?></td>
                        <td><?= e($r['phone']) ?></td>
                        <td><?= e($r['email']) ?></td>
                        <td><?= $r['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                        <td class="text-end">
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'name' => $r['name'], 'code' => $r['code'], 'city_id' => $r['city_id'], 'phone' => $r['phone'], 'email' => $r['email'], 'status' => $r['status']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this branch? This cannot be undone.">
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
                    <tr><td colspan="8"><div class="empty-state"><i class="bi bi-diagram-2"></i><p>No branches yet</p></div></td></tr>
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
                <div class="modal-header"><h5 class="modal-title">Add Branch</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Branch Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <select name="city_id" class="form-select">
                                <option value="">Select city</option>
                                <?php foreach ($cities as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"></textarea>
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

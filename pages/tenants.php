<?php
require_once '../includes/auth.php';
require_login();
require_permission('tenants.view');
$title = 'Tenants';
$active = 'tenants';
$canEdit = has_permission('tenants.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $tenant_no = trim($_POST['tenant_no'] ?? '');
        if ($tenant_no === '') {
            $tenant_no = next_number('TEN', 'tenants', 'tenant_no');
        }
        $full_name = trim($_POST['full_name'] ?? '');
        $cnic = trim($_POST['cnic'] ?? '');
        $police_verification = $_POST['police_verification'] ?? 'pending';
        $emergency_contact = trim($_POST['emergency_contact'] ?? '');
        $emergency_name = trim($_POST['emergency_name'] ?? '');
        $occupation = trim($_POST['occupation'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $status = (int)($_POST['status'] ?? 1);

        if ($full_name === '') {
            flash('danger', 'Tenant name is required.');
        } elseif ($id > 0) {
            db_exec("UPDATE tenants SET tenant_no=?, full_name=?, cnic=?, police_verification=?, emergency_contact=?, emergency_name=?, occupation=?, company=?, address=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$tenant_no, $full_name, $cnic, $police_verification, $emergency_contact, $emergency_name, $occupation, $company, $address, $status, $id]);
            flash('success', 'Tenant updated successfully.');
        } else {
            $photo = upload_file('photo', 'uploads/users', ['jpg', 'jpeg', 'png', 'webp']);
            if ($photo === false) {
                flash('danger', 'Photo upload failed. Allowed: JPG, PNG, WEBP.');
                redirect('tenants.php');
            }
            db_exec("INSERT INTO tenants (tenant_no, full_name, cnic, police_verification, emergency_contact, emergency_name, occupation, company, address, photo, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$tenant_no, $full_name, $cnic, $police_verification, $emergency_contact, $emergency_name, $occupation, $company, $address, $photo, $status]);
            flash('success', 'Tenant added successfully.');
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM tenants WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Tenant deleted successfully.');
    }
    redirect('tenants.php');
}

$records = db_all("SELECT t.*,
                   (SELECT COUNT(*) FROM rental_agreements ra WHERE ra.tenant_id = t.id AND ra.status IN ('active','renewed')) AS active_agreements
                   FROM tenants t ORDER BY t.full_name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search tenants...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Tenant"><i class="bi bi-plus-lg me-1"></i>Add Tenant</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Tenant</th><th>CNIC</th><th>Contact</th><th>Occupation</th><th>Police Verif.</th><th>Active Rents</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <a class="fw-medium text-decoration-none" href="tenant_view.php?id=<?= $r['id'] ?>"><?= e($r['full_name']) ?></a>
                            <div class="small text-muted"><?= e($r['tenant_no']) ?></div>
                        </td>
                        <td class="small"><?= e($r['cnic']) ?></td>
                        <td><?= e($r['emergency_contact'] ?: $r['company']) ?><?= $r['emergency_name'] ? '<div class="small text-muted">' . e($r['emergency_name']) . '</div>' : '' ?></td>
                        <td class="small"><?= e($r['occupation'] ?? '-') ?></td>
                        <td><?= status_badge($r['police_verification']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= $r['active_agreements'] ?></span></td>
                        <td><?= $r['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-info" href="tenant_view.php?id=<?= $r['id'] ?>"><i class="bi bi-eye"></i></a>
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'tenant_no' => $r['tenant_no'], 'full_name' => $r['full_name'], 'cnic' => $r['cnic'], 'police_verification' => $r['police_verification'], 'emergency_contact' => $r['emergency_contact'], 'emergency_name' => $r['emergency_name'], 'occupation' => $r['occupation'], 'company' => $r['company'], 'address' => $r['address'], 'status' => $r['status']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this tenant? This cannot be undone.">
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
                    <tr><td colspan="9"><div class="empty-state"><i class="bi bi-person-check"></i><p>No tenants yet</p></div></td></tr>
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
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header"><h5 class="modal-title">Add Tenant</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tenant No</label>
                            <input type="text" name="tenant_no" class="form-control" placeholder="Auto if blank">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CNIC</label>
                            <input type="text" name="cnic" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Police Verification</label>
                            <select name="police_verification" class="form-select">
                                <?php foreach (['pending', 'cleared', 'rejected'] as $s): ?>
                                    <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Emergency Name</label>
                            <input type="text" name="emergency_name" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Emergency Contact</label>
                            <input type="text" name="emergency_contact" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Occupation</label>
                            <input type="text" name="occupation" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company</label>
                            <input type="text" name="company" class="form-control">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Photo</label>
                            <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                        </div>
                        <div class="col-md-4">
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

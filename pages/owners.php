<?php
require_once '../includes/auth.php';
require_login();
require_permission('owners.view');
$title = 'Owners';
$active = 'owners';
$canEdit = has_permission('owners.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $owner_no = trim($_POST['owner_no'] ?? '');
        if ($owner_no === '') {
            $owner_no = next_number('OWN', 'owners', 'owner_no');
        }
        $full_name = trim($_POST['full_name'] ?? '');
        $cnic = trim($_POST['cnic'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $bank_id = (int)($_POST['bank_id'] ?? 0) ?: null;
        $bank_account_title = trim($_POST['bank_account_title'] ?? '');
        $bank_account_no = trim($_POST['bank_account_no'] ?? '');
        $commission_rate = (float)($_POST['commission_rate'] ?? 0);
        $status = (int)($_POST['status'] ?? 1);

        if ($full_name === '') {
            flash('danger', 'Owner name is required.');
        } elseif ($id > 0) {
            db_exec("UPDATE owners SET owner_no=?, full_name=?, cnic=?, phone=?, whatsapp=?, email=?, address=?, bank_id=?, bank_account_title=?, bank_account_no=?, commission_rate=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$owner_no, $full_name, $cnic, $phone, $whatsapp, $email, $address, $bank_id, $bank_account_title, $bank_account_no, $commission_rate, $status, $id]);
            flash('success', 'Owner updated successfully.');
        } else {
            db_exec("INSERT INTO owners (owner_no, full_name, cnic, phone, whatsapp, email, address, bank_id, bank_account_title, bank_account_no, commission_rate, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$owner_no, $full_name, $cnic, $phone, $whatsapp, $email, $address, $bank_id, $bank_account_title, $bank_account_no, $commission_rate, $status]);
            flash('success', 'Owner added successfully.');
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM owners WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Owner deleted successfully.');
    }
    redirect('owners.php');
}

$records = db_all("SELECT o.*,
                   (SELECT COUNT(*) FROM properties p WHERE p.owner_id = o.id) AS prop_count
                   FROM owners o ORDER BY o.full_name");
$banks = db_all("SELECT * FROM banks ORDER BY name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search owners...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Owner"><i class="bi bi-plus-lg me-1"></i>Add Owner</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Owner</th><th>CNIC</th><th>Phone</th><th>Properties</th><th>Commission %</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <a class="fw-medium text-decoration-none" href="owner_view.php?id=<?= $r['id'] ?>"><?= e($r['full_name']) ?></a>
                            <div class="small text-muted"><?= e($r['owner_no']) ?></div>
                        </td>
                        <td class="small"><?= e($r['cnic']) ?></td>
                        <td><?= e($r['phone']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= $r['prop_count'] ?></span></td>
                        <td><?= fmt_num($r['commission_rate']) ?>%</td>
                        <td><?= $r['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-info" href="owner_view.php?id=<?= $r['id'] ?>"><i class="bi bi-eye"></i></a>
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'owner_no' => $r['owner_no'], 'full_name' => $r['full_name'], 'cnic' => $r['cnic'], 'phone' => $r['phone'], 'whatsapp' => $r['whatsapp'], 'email' => $r['email'], 'address' => $r['address'], 'bank_id' => $r['bank_id'], 'bank_account_title' => $r['bank_account_title'], 'bank_account_no' => $r['bank_account_no'], 'commission_rate' => $r['commission_rate'], 'status' => $r['status']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this owner? This cannot be undone.">
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
                    <tr><td colspan="8"><div class="empty-state"><i class="bi bi-person-badge"></i><p>No owners yet</p></div></td></tr>
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
                <div class="modal-header"><h5 class="modal-title">Add Owner</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Owner No</label>
                            <input type="text" name="owner_no" class="form-control" placeholder="Auto if blank">
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
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">WhatsApp</label>
                            <input type="text" name="whatsapp" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Commission Rate %</label>
                            <input type="number" step="0.01" name="commission_rate" class="form-control" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank</label>
                            <select name="bank_id" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($banks as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank Account Title</label>
                            <input type="text" name="bank_account_title" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank Account No</label>
                            <input type="text" name="bank_account_no" class="form-control">
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
                            <input type="text" name="address" class="form-control">
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

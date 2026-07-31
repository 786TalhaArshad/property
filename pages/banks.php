<?php
require_once '../includes/auth.php';
require_login();
require_permission('master.view');
$title = 'Banks';
$active = 'master';
$canEdit = has_permission('settings.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $account_title = trim($_POST['account_title'] ?? '');
        $account_no = trim($_POST['account_no'] ?? '');
        $iban = trim($_POST['iban'] ?? '');
        $branch = trim($_POST['branch'] ?? '');
        $status = (int)($_POST['status'] ?? 1);
        if ($name === '') {
            flash('danger', 'Bank name is required.');
        } else {
            $dup = db_get("SELECT id FROM banks WHERE name = ? AND id <> ?", [$name, $id]);
            if ($dup) {
                flash('danger', 'A bank with this name already exists.');
            } elseif ($id > 0) {
                db_exec("UPDATE banks SET name=?, account_title=?, account_no=?, iban=?, branch=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$name, $account_title, $account_no, $iban, $branch, $status, $id]);
                flash('success', 'Bank updated successfully.');
            } else {
                db_exec("INSERT INTO banks (name, account_title, account_no, iban, branch, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$name, $account_title, $account_no, $iban, $branch, $status]);
                flash('success', 'Bank added successfully.');
            }
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM banks WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Bank deleted successfully.');
    }
    redirect('banks.php');
}

$records = db_all("SELECT * FROM banks ORDER BY name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Bank"><i class="bi bi-plus-lg me-1"></i>Add Bank</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Name</th><th>Account Title</th><th>Account No</th><th>IBAN</th><th>Branch</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-medium"><?= e($r['name']) ?></td>
                        <td><?= e($r['account_title']) ?></td>
                        <td><?= e($r['account_no']) ?></td>
                        <td class="small text-muted"><?= e($r['iban']) ?></td>
                        <td><?= e($r['branch']) ?></td>
                        <td><?= $r['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                        <td class="text-end">
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'name' => $r['name'], 'account_title' => $r['account_title'], 'account_no' => $r['account_no'], 'iban' => $r['iban'], 'branch' => $r['branch'], 'status' => $r['status']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this bank? This cannot be undone.">
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
                    <tr><td colspan="8"><div class="empty-state"><i class="bi bi-bank"></i><p>No banks yet</p></div></td></tr>
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
                <div class="modal-header"><h5 class="modal-title">Add Bank</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Bank Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Title</label>
                            <input type="text" name="account_title" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account No</label>
                            <input type="text" name="account_no" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">IBAN</label>
                            <input type="text" name="iban" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            <input type="text" name="branch" class="form-control">
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

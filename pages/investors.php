<?php
require_once '../includes/auth.php';
require_login();
require_permission('investors.view');
$title = 'Investors';
$active = 'investors';
$canEdit = has_permission('investors.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $investor_no = trim($_POST['investor_no'] ?? '');
        if ($investor_no === '') {
            $investor_no = next_number('INV', 'investors', 'investor_no');
        }
        $full_name = trim($_POST['full_name'] ?? '');
        $cnic = trim($_POST['cnic'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $bank_account_title = trim($_POST['bank_account_title'] ?? '');
        $bank_account_no = trim($_POST['bank_account_no'] ?? '');
        $investment_type = trim($_POST['investment_type'] ?? '');
        $status = (int)($_POST['status'] ?? 1);

        if ($full_name === '') {
            flash('danger', 'Investor name is required.');
        } elseif ($id > 0) {
            db_exec("UPDATE investors SET investor_no=?, full_name=?, cnic=?, phone=?, whatsapp=?, email=?, address=?, bank_account_title=?, bank_account_no=?, investment_type=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$investor_no, $full_name, $cnic, $phone, $whatsapp, $email, $address, $bank_account_title, $bank_account_no, $investment_type, $status, $id]);
            flash('success', 'Investor updated successfully.');
        } else {
            db_exec("INSERT INTO investors (investor_no, full_name, cnic, phone, whatsapp, email, address, bank_account_title, bank_account_no, investment_type, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$investor_no, $full_name, $cnic, $phone, $whatsapp, $email, $address, $bank_account_title, $bank_account_no, $investment_type, $status]);
            flash('success', 'Investor added successfully.');
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM investors WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Investor deleted successfully.');
    }
    redirect('investors.php');
}

$records = db_all("SELECT i.*,
                   (SELECT COALESCE(MAX(il.balance),0) FROM investor_ledger il WHERE il.investor_id = i.id) AS balance
                   FROM investors i ORDER BY i.full_name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search investors...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Investor"><i class="bi bi-plus-lg me-1"></i>Add Investor</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Investor</th><th>CNIC</th><th>Phone</th><th>Type</th><th>Balance</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <a class="fw-medium text-decoration-none" href="investor_view.php?id=<?= $r['id'] ?>"><?= e($r['full_name']) ?></a>
                            <div class="small text-muted"><?= e($r['investor_no']) ?></div>
                        </td>
                        <td class="small"><?= e($r['cnic']) ?></td>
                        <td><?= e($r['phone']) ?></td>
                        <td><?= e($r['investment_type'] ?? '-') ?></td>
                        <td class="fw-bold <?= (float)$r['balance'] > 0 ? 'text-success' : ((float)$r['balance'] < 0 ? 'text-danger' : '') ?>"><?= fmt_money($r['balance']) ?></td>
                        <td><?= $r['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-info" href="investor_view.php?id=<?= $r['id'] ?>"><i class="bi bi-eye"></i></a>
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'investor_no' => $r['investor_no'], 'full_name' => $r['full_name'], 'cnic' => $r['cnic'], 'phone' => $r['phone'], 'whatsapp' => $r['whatsapp'], 'email' => $r['email'], 'address' => $r['address'], 'bank_account_title' => $r['bank_account_title'], 'bank_account_no' => $r['bank_account_no'], 'investment_type' => $r['investment_type'], 'status' => $r['status']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this investor? This cannot be undone.">
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
                    <tr><td colspan="8"><div class="empty-state"><i class="bi bi-person-badge"></i><p>No investors yet</p></div></td></tr>
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
                <div class="modal-header"><h5 class="modal-title">Add Investor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Investor No</label>
                            <input type="text" name="investor_no" class="form-control" placeholder="Auto if blank">
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
                            <label class="form-label">Investment Type</label>
                            <input type="text" name="investment_type" class="form-control" placeholder="e.g. Real Estate, Joint Venture">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
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

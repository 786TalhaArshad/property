<?php
require_once '../includes/auth.php';
require_login();
require_permission('contractors.view');
$title = 'Contractors';
$active = 'contractors';
$canEdit = has_permission('contractors.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $contractor_no = trim($_POST['contractor_no'] ?? '');
        if ($contractor_no === '') {
            $contractor_no = next_number('CON', 'contractors', 'contractor_no');
        }
        $full_name = trim($_POST['full_name'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $specialty = trim($_POST['specialty'] ?? '');
        $cnic = trim($_POST['cnic'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $bank_id = (int)($_POST['bank_id'] ?? 0) ?: null;
        $bank_account_title = trim($_POST['bank_account_title'] ?? '');
        $bank_account_no = trim($_POST['bank_account_no'] ?? '');
        $status = (int)($_POST['status'] ?? 1);

        if ($full_name === '') {
            flash('danger', 'Contractor name is required.');
        } elseif ($id > 0) {
            db_exec("UPDATE contractors SET contractor_no=?, full_name=?, company=?, specialty=?, cnic=?, phone=?, whatsapp=?, email=?, address=?, bank_id=?, bank_account_title=?, bank_account_no=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?",
                [$contractor_no, $full_name, $company, $specialty, $cnic, $phone, $whatsapp, $email, $address, $bank_id, $bank_account_title, $bank_account_no, $status, $id]);
            flash('success', 'Contractor updated successfully.');
        } else {
            db_exec("INSERT INTO contractors (contractor_no, full_name, company, specialty, cnic, phone, whatsapp, email, address, bank_id, bank_account_title, bank_account_no, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                [$contractor_no, $full_name, $company, $specialty, $cnic, $phone, $whatsapp, $email, $address, $bank_id, $bank_account_title, $bank_account_no, $status]);
            flash('success', 'Contractor added successfully.');
        }
    } elseif ($action === 'delete') {
        $cid = (int)($_POST['id'] ?? 0);
        $cnt = (int)db_get("SELECT COUNT(*) AS c FROM contractor_entries WHERE contractor_id = ?", [$cid])['c'];
        if ($cnt > 0) {
            flash('danger', 'Cannot delete a contractor that has ledger entries.');
        } else {
            db_exec("DELETE FROM contractors WHERE id=?", [$cid]);
            flash('success', 'Contractor deleted successfully.');
        }
    }
    redirect('contractors.php');
}

$records = db_all("SELECT c.*, b.name AS bank_name,
                   (SELECT COALESCE(SUM(CASE WHEN ce.entry_type = 'payable' THEN ce.amount ELSE 0 END),0) FROM contractor_entries ce WHERE ce.contractor_id = c.id) AS total_payable,
                   (SELECT COALESCE(SUM(CASE WHEN ce.entry_type = 'paid' THEN ce.amount ELSE 0 END),0) FROM contractor_entries ce WHERE ce.contractor_id = c.id) AS total_paid
                   FROM contractors c
                   LEFT JOIN banks b ON b.id = c.bank_id
                   ORDER BY c.full_name");
$banks = db_all("SELECT * FROM banks ORDER BY name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search contractors...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Contractor"><i class="bi bi-plus-lg me-1"></i>Add Contractor</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Contractor</th><th>Company</th><th>Specialty</th><th>Phone</th><th>Payable</th><th>Paid</th><th>Balance</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <?php $bal = (float)$r['total_payable'] - (float)$r['total_paid']; ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <a class="fw-medium text-decoration-none" href="contractor_view.php?id=<?= $r['id'] ?>"><?= e($r['full_name']) ?></a>
                            <div class="small text-muted"><?= e($r['contractor_no']) ?></div>
                        </td>
                        <td class="small"><?= e($r['company'] ?: '-') ?></td>
                        <td class="small"><?= e($r['specialty'] ?: '-') ?></td>
                        <td><?= e($r['phone'] ?: '-') ?></td>
                        <td><?= fmt_money($r['total_payable']) ?></td>
                        <td><?= fmt_money($r['total_paid']) ?></td>
                        <td class="text-nowrap fw-medium <?= $bal > 0 ? 'text-danger' : ($bal < 0 ? 'text-success' : 'text-muted') ?>"><?= fmt_money($bal) ?></td>
                        <td><?= $r['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-info" href="contractor_view.php?id=<?= $r['id'] ?>"><i class="bi bi-eye"></i></a>
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'contractor_no' => $r['contractor_no'], 'full_name' => $r['full_name'], 'company' => $r['company'], 'specialty' => $r['specialty'], 'cnic' => $r['cnic'], 'phone' => $r['phone'], 'whatsapp' => $r['whatsapp'], 'email' => $r['email'], 'address' => $r['address'], 'bank_id' => $r['bank_id'], 'bank_account_title' => $r['bank_account_title'], 'bank_account_no' => $r['bank_account_no'], 'status' => $r['status']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this contractor? This cannot be undone.">
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
                    <tr><td colspan="10"><div class="empty-state"><i class="bi bi-person-badge"></i><p>No contractors yet</p></div></td></tr>
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
                <div class="modal-header"><h5 class="modal-title">Add Contractor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Contractor No</label>
                            <input type="text" name="contractor_no" class="form-control" placeholder="Auto if blank">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Company</label>
                            <input type="text" name="company" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Specialty</label>
                            <input type="text" name="specialty" class="form-control" placeholder="Plumbing / Electric / Masonry...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">CNIC</label>
                            <input type="text" name="cnic" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">WhatsApp</label>
                            <input type="text" name="whatsapp" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bank</label>
                            <select name="bank_id" class="form-select">
                                <option value="">Select Bank</option>
                                <?php foreach ($banks as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bank Account Title</label>
                            <input type="text" name="bank_account_title" class="form-control">
                        </div>
                        <div class="col-md-4">
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

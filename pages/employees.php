<?php
require_once '../includes/auth.php';
require_login();
require_permission('employees.view');
$title = 'Employees';
$active = 'employees';
$canEdit = has_permission('employees.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $employee_no = trim($_POST['employee_no'] ?? '');
        if ($employee_no === '') {
            $employee_no = next_number('EMP', 'employees', 'employee_no');
        }
        $full_name = trim($_POST['full_name'] ?? '');
        $father_name = trim($_POST['father_name'] ?? '');
        $cnic = trim($_POST['cnic'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $joining_date = $_POST['joining_date'] ?? '';
        $monthly_salary = (float)($_POST['monthly_salary'] ?? 0);
        $bank_id = (int)($_POST['bank_id'] ?? 0) ?: null;
        $bank_account_title = trim($_POST['bank_account_title'] ?? '');
        $bank_account_no = trim($_POST['bank_account_no'] ?? '');
        $status = (int)($_POST['status'] ?? 1);

        if ($full_name === '') {
            flash('danger', 'Employee name is required.');
        } elseif ($id > 0) {
            db_exec("UPDATE employees SET employee_no=?, full_name=?, father_name=?, cnic=?, phone=?, whatsapp=?, email=?, address=?, designation=?, department=?, joining_date=?, monthly_salary=?, bank_id=?, bank_account_title=?, bank_account_no=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?",
                [$employee_no, $full_name, $father_name, $cnic, $phone, $whatsapp, $email, $address, $designation, $department, $joining_date, $monthly_salary, $bank_id, $bank_account_title, $bank_account_no, $status, $id]);
            flash('success', 'Employee updated successfully.');
        } else {
            db_exec("INSERT INTO employees (employee_no, full_name, father_name, cnic, phone, whatsapp, email, address, designation, department, joining_date, monthly_salary, bank_id, bank_account_title, bank_account_no, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                [$employee_no, $full_name, $father_name, $cnic, $phone, $whatsapp, $email, $address, $designation, $department, $joining_date, $monthly_salary, $bank_id, $bank_account_title, $bank_account_no, $status]);
            flash('success', 'Employee added successfully.');
        }
    } elseif ($action === 'delete') {
        $eid = (int)($_POST['id'] ?? 0);
        $cnt = (int)db_get("SELECT COUNT(*) AS c FROM employee_entries WHERE employee_id = ?", [$eid])['c'];
        if ($cnt > 0) {
            flash('danger', 'Cannot delete an employee that has ledger entries.');
        } else {
            db_exec("DELETE FROM employees WHERE id=?", [$eid]);
            flash('success', 'Employee deleted successfully.');
        }
    }
    redirect('employees.php');
}

$records = db_all("SELECT e.*, b.name AS bank_name,
                   (SELECT COALESCE(SUM(CASE WHEN ee.entry_type = 'payable' THEN ee.amount ELSE 0 END),0) FROM employee_entries ee WHERE ee.employee_id = e.id) AS total_payable,
                   (SELECT COALESCE(SUM(CASE WHEN ee.entry_type = 'paid' THEN ee.amount ELSE 0 END),0) FROM employee_entries ee WHERE ee.employee_id = e.id) AS total_paid
                   FROM employees e
                   LEFT JOIN banks b ON b.id = e.bank_id
                   ORDER BY e.full_name");
$banks = db_all("SELECT * FROM banks ORDER BY name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search employees...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Employee"><i class="bi bi-plus-lg me-1"></i>Add Employee</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Employee</th><th>Designation</th><th>Department</th><th>Phone</th><th>Monthly Salary</th><th>Payable</th><th>Paid</th><th>Balance</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <?php $bal = (float)$r['total_payable'] - (float)$r['total_paid']; ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <a class="fw-medium text-decoration-none" href="employee_view.php?id=<?= $r['id'] ?>"><?= e($r['full_name']) ?></a>
                            <div class="small text-muted"><?= e($r['employee_no']) ?></div>
                        </td>
                        <td class="small"><?= e($r['designation'] ?: '-') ?></td>
                        <td class="small"><?= e($r['department'] ?: '-') ?></td>
                        <td><?= e($r['phone'] ?: '-') ?></td>
                        <td class="text-nowrap"><?= fmt_money($r['monthly_salary']) ?></td>
                        <td><?= fmt_money($r['total_payable']) ?></td>
                        <td><?= fmt_money($r['total_paid']) ?></td>
                        <td class="text-nowrap fw-medium <?= $bal > 0 ? 'text-danger' : ($bal < 0 ? 'text-success' : 'text-muted') ?>"><?= fmt_money($bal) ?></td>
                        <td><?= $r['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-info" href="employee_view.php?id=<?= $r['id'] ?>"><i class="bi bi-eye"></i></a>
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'employee_no' => $r['employee_no'], 'full_name' => $r['full_name'], 'father_name' => $r['father_name'], 'cnic' => $r['cnic'], 'phone' => $r['phone'], 'whatsapp' => $r['whatsapp'], 'email' => $r['email'], 'address' => $r['address'], 'designation' => $r['designation'], 'department' => $r['department'], 'joining_date' => $r['joining_date'], 'monthly_salary' => $r['monthly_salary'], 'bank_id' => $r['bank_id'], 'bank_account_title' => $r['bank_account_title'], 'bank_account_no' => $r['bank_account_no'], 'status' => $r['status']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this employee? This cannot be undone.">
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
                    <tr><td colspan="11"><div class="empty-state"><i class="bi bi-person-workspace"></i><p>No employees yet</p></div></td></tr>
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
                <div class="modal-header"><h5 class="modal-title">Add Employee</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Employee No</label>
                            <input type="text" name="employee_no" class="form-control" placeholder="Auto if blank">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Father Name</label>
                            <input type="text" name="father_name" class="form-control">
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
                            <label class="form-label">Designation</label>
                            <input type="text" name="designation" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Joining Date</label>
                            <input type="date" name="joining_date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Monthly Salary</label>
                            <input type="number" step="0.01" name="monthly_salary" class="form-control" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
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

<?php
require_once '../includes/auth.php';
require_login();
require_permission('employees.view');
$title = 'Employee Details';
$active = 'employees';
$canEdit = has_permission('employees.manage');

function employee_account_id($employee_id, $employee_name) {
    $parent = db_get("SELECT id FROM chart_of_accounts WHERE code = '2050'");
    if (!$parent) {
        $parentId = db_exec("INSERT INTO chart_of_accounts (code, name, account_type, parent_id, opening_balance, created_date, created_time, updated_date, updated_time) VALUES ('2050','Employee Payable','liability',NULL,0,CURDATE(),CURTIME(),CURDATE(),CURTIME())");
    } else {
        $parentId = (int)$parent['id'];
    }
    $code = '2050-' . str_pad((int)$employee_id, 3, '0', STR_PAD_LEFT);
    $acc = db_get("SELECT id FROM chart_of_accounts WHERE code = ?", [$code]);
    if ($acc) {
        return (int)$acc['id'];
    }
    return (int)db_exec("INSERT INTO chart_of_accounts (code, name, account_type, parent_id, opening_balance, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,0,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
        [$code, $employee_name, 'liability', $parentId]);
}

function employee_bank_account_id($bank_id) {
    $bank = db_get("SELECT * FROM banks WHERE id = ?", [$bank_id]);
    if (!$bank) {
        return 0;
    }
    $code = '1001-' . str_pad((int)$bank_id, 3, '0', STR_PAD_LEFT);
    $acc = db_get("SELECT id FROM chart_of_accounts WHERE code = ?", [$code]);
    if ($acc) {
        return (int)$acc['id'];
    }
    $parent = db_get("SELECT id FROM chart_of_accounts WHERE code = '1001'");
    return (int)db_exec("INSERT INTO chart_of_accounts (code, name, account_type, parent_id, opening_balance, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,0,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
        [$code, $bank['name'], 'asset', $parent ? (int)$parent['id'] : null]);
}

function post_employee_voucher($date, $narration, $lines) {
    $voucher_no = next_number('JV', 'vouchers', 'voucher_no');
    $vid = db_exec("INSERT INTO vouchers (voucher_no, voucher_date, voucher_type, narration, status, created_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
        [$voucher_no, $date, 'journal', $narration, 'posted', $GLOBALS['user']['id']]);
    foreach ($lines as $l) {
        db_exec("INSERT INTO voucher_items (voucher_id, account_id, item_description, debit, credit, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$vid, $l[0], $l[1], $l[2], $l[3]]);
    }
    return $vid;
}

$id = (int)($_GET['id'] ?? 0);
$employee = db_get("SELECT e.*, b.name AS bank_name FROM employees e LEFT JOIN banks b ON b.id = e.bank_id WHERE e.id = ?", [$id]);
if (!$employee) {
    flash('danger', 'Employee not found.');
    redirect('employees.php');
}

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'entry_add') {
        $type = $_POST['entry_type'] ?? '';
        $date = $_POST['entry_date'] ?? date('Y-m-d');
        $amount = (float)($_POST['amount'] ?? 0);
        $narration = trim($_POST['narration'] ?? '');
        $entry_no = trim($_POST['entry_no'] ?? '');
        if ($entry_no === '') {
            $entry_no = next_number('EMPE', 'employee_entries', 'entry_no');
        }
        $valid = in_array($type, ['payable', 'paid'], true);
        if (!$valid) {
            flash('danger', 'Invalid entry type.');
            redirect('employee_view.php?id=' . $id);
        }
        if ($amount <= 0) {
            flash('danger', 'Enter a valid amount.');
            redirect('employee_view.php?id=' . $id);
        }

        $empAcc = employee_account_id($id, $employee['full_name']);
        $voucherId = null;
        $narr = $narration !== '' ? $narration : ucfirst($type) . ' - ' . $employee['full_name'];
        $accountId = null;

        if ($type === 'payable') {
            $accountId = (int)($_POST['account_id'] ?? 0);
            if (!$accountId) {
                flash('danger', 'Select the expense account to debit.');
                redirect('employee_view.php?id=' . $id);
            }
            $voucherId = post_employee_voucher($date, $narr, [
                [$accountId, 'Payable to ' . $employee['full_name'], $amount, 0],
                [$empAcc, 'Payable to ' . $employee['full_name'], 0, $amount],
            ]);
        } else {
            $payFrom = $_POST['pay_from'] ?? '';
            $creditAcc = 0;
            $creditName = 'Cash';
            if ($payFrom === 'cash') {
                $cashAcc = db_get("SELECT id FROM chart_of_accounts WHERE code = '1000'");
                if (!$cashAcc) {
                    flash('danger', 'Cash account not found in chart of accounts.');
                    redirect('employee_view.php?id=' . $id);
                }
                $creditAcc = (int)$cashAcc['id'];
            } elseif (strpos($payFrom, 'bank:') === 0) {
                $bankId = (int)substr($payFrom, 5);
                if ($bankId <= 0) {
                    flash('danger', 'Select a bank.');
                    redirect('employee_view.php?id=' . $id);
                }
                $bank = db_get("SELECT * FROM banks WHERE id = ?", [$bankId]);
                if (!$bank) {
                    flash('danger', 'Bank not found.');
                    redirect('employee_view.php?id=' . $id);
                }
                $bankAcc = employee_bank_account_id($bankId);
                if (!$bankAcc) {
                    flash('danger', 'Bank account not found in chart of accounts.');
                    redirect('employee_view.php?id=' . $id);
                }
                $creditAcc = (int)$bankAcc;
                $creditName = $bank['name'];
            } else {
                flash('danger', 'Select where the money comes from.');
                redirect('employee_view.php?id=' . $id);
            }
            $voucherId = post_employee_voucher($date, $narr, [
                [$empAcc, 'Salary paid to ' . $employee['full_name'], $amount, 0],
                [$creditAcc, 'Salary paid - ' . $creditName, 0, $amount],
            ]);
        }

        db_exec("INSERT INTO employee_entries (employee_id, entry_no, entry_date, entry_type, amount, narration, account_id, voucher_id, created_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$id, $entry_no, $date, $type, $amount, $narr, $accountId, $voucherId, $user['id']]);
        flash('success', $type . ' entry ' . $entry_no . ' saved.');
    } elseif ($action === 'entry_delete') {
        $eid = (int)($_POST['id'] ?? 0);
        $ent = db_get("SELECT * FROM employee_entries WHERE id = ? AND employee_id = ?", [$eid, $id]);
        if ($ent) {
            if ($ent['voucher_id']) {
                db_exec("DELETE FROM vouchers WHERE id = ?", [$ent['voucher_id']]);
            }
            db_exec("DELETE FROM employee_entries WHERE id = ?", [$eid]);
            flash('success', 'Entry deleted.');
        }
    }
    redirect('employee_view.php?id=' . $id);
}

$entries = db_all("SELECT ee.*, u.full_name AS created_name FROM employee_entries ee LEFT JOIN users u ON u.id = ee.created_by WHERE ee.employee_id = ? ORDER BY ee.entry_date, ee.id", [$id]);
$banks = db_all("SELECT * FROM banks ORDER BY name");
$accounts = db_all("SELECT * FROM chart_of_accounts ORDER BY code");
$salariesAcc = db_get("SELECT id FROM chart_of_accounts WHERE code = '5000'");

$totalPayable = 0.0;
$totalPaid = 0.0;
$running = [];
$bal = 0.0;
foreach ($entries as $e) {
    $amt = (float)$e['amount'];
    if ($e['entry_type'] === 'payable') {
        $totalPayable += $amt;
        $bal += $amt;
    } else {
        $totalPaid += $amt;
        $bal -= $amt;
    }
    $running[$e['id']] = $bal;
}
$balance = $totalPayable - $totalPaid;

$typeLabels = [
    'payable' => ['Payable', 'danger'],
    'paid' => ['Paid', 'success'],
];

include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="employees.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= e($employee['full_name']) ?></h5>
    <span class="badge bg-light text-dark border"><?= e($employee['employee_no']) ?></span>
    <?= $employee['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-arrow-down-circle"></i></div><div><div class="stat-label">PAYABLE</div><div class="stat-value"><?= fmt_money($totalPayable) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-coin"></i></div><div><div class="stat-label">PAID</div><div class="stat-value"><?= fmt_money($totalPaid) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-red"><div class="stat-body"><div class="stat-icon"><i class="bi bi-wallet2"></i></div><div><div class="stat-label">BALANCE</div><div class="stat-value"><?= fmt_money($balance) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">MONTHLY SALARY</div><div class="stat-value"><?= fmt_money($employee['monthly_salary']) ?></div></div></div></div></div>
</div>

<?php if ($canEdit): ?>
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-pencil-square me-2"></i>Add Entry</div>
    <div class="card-body">
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="entry_add">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Entry Type</label>
                    <select name="entry_type" id="selType" class="form-select" required>
                        <option value="payable">Payable</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Entry No</label>
                    <input type="text" name="entry_no" class="form-control" placeholder="Auto if blank">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="entry_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required data-mask-money>
                </div>

                <div class="col-md-6" id="secPayable" data-sec="payable">
                    <label class="form-label">Debit Account (Expense)</label>
                    <select name="account_id" class="form-select">
                        <option value="">Select Account</option>
                        <?php foreach ($accounts as $a): ?><option value="<?= $a['id'] ?>" <?= $salariesAcc && (int)$salariesAcc['id'] === (int)$a['id'] ? 'selected' : '' ?>><?= e($a['code']) ?> - <?= e($a['name']) ?></option><?php endforeach; ?>
                    </select>
                    <div class="form-text">Salary payable kis expense head par lagega (default Salaries).</div>
                </div>

                <div class="col-md-6 d-none" id="secPayment" data-sec="paid">
                    <label class="form-label">Paid From</label>
                    <select name="pay_from" class="form-select">
                        <option value="cash">Cash</option>
                        <?php foreach ($banks as $b): ?><option value="bank:<?= $b['id'] ?>"><?= e($b['name']) ?> - <?= e($b['account_no'] ?? '') ?></option><?php endforeach; ?>
                    </select>
                    <div class="form-text">Salary kis se di gayi.</div>
                </div>

                <div class="col-12">
                    <label class="form-label">Narration</label>
                    <input type="text" name="narration" class="form-control" placeholder="Optional">
                </div>
            </div>
            <div class="mt-3">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Save Entry</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-journal-text me-2"></i>Ledger</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Entry No</th><th>Type</th><th>Narration</th><th class="text-end">Payable</th><th class="text-end">Paid</th><th class="text-end">Balance</th><?php if ($canEdit): ?><th class="text-end"></th><?php endif; ?></tr></thead>
                        <tbody>
                        <?php foreach ($entries as $e): ?>
                            <tr>
                                <td><?= fmt_date($e['entry_date']) ?></td>
                                <td class="fw-medium"><?= e($e['entry_no']) ?><?= $e['voucher_id'] ? ' <a class="small" href="vouchers.php" title="Journal voucher created"><i class="bi bi-journal-arrow-up text-muted"></i></a>' : '' ?></td>
                                <td><span class="badge bg-<?= $typeLabels[$e['entry_type']][1] ?? 'secondary' ?>"><?= e($typeLabels[$e['entry_type']][0] ?? $e['entry_type']) ?></span></td>
                                <td class="small"><?= e($e['narration'] ?? '-') ?></td>
                                <td class="text-end"><?= $e['entry_type'] === 'payable' ? fmt_money($e['amount']) : '-' ?></td>
                                <td class="text-end"><?= $e['entry_type'] === 'paid' ? fmt_money($e['amount']) : '-' ?></td>
                                <td class="text-end fw-medium <?= $running[$e['id']] > 0 ? 'text-danger' : ($running[$e['id']] < 0 ? 'text-success' : '') ?>"><?= fmt_money($running[$e['id']]) ?></td>
                                <?php if ($canEdit): ?>
                                <td class="text-end">
                                    <form method="post" class="d-inline" data-confirm="Delete this entry?<?= $e['voucher_id'] ? ' The linked journal voucher will also be removed.' : '' ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="entry_delete">
                                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$entries): ?><tr><td colspan="<?= $canEdit ? 8 : 7 ?>" class="text-center text-muted py-4">No entries yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Employee Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Designation</dt><dd class="col-sm-8"><?= e($employee['designation'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Department</dt><dd class="col-sm-8"><?= e($employee['department'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Father Name</dt><dd class="col-sm-8"><?= e($employee['father_name'] ?: '-') ?></dd>
                    <dt class="col-sm-4">CNIC</dt><dd class="col-sm-8"><?= e($employee['cnic'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Phone</dt><dd class="col-sm-8"><?= e($employee['phone'] ?: '-') ?></dd>
                    <dt class="col-sm-4">WhatsApp</dt><dd class="col-sm-8"><?= e($employee['whatsapp'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8"><?= e($employee['email'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Joining Date</dt><dd class="col-sm-8"><?= fmt_date($employee['joining_date']) ?></dd>
                    <dt class="col-sm-4">Bank</dt><dd class="col-sm-8"><?= e($employee['bank_name'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Bank Title</dt><dd class="col-sm-8"><?= e($employee['bank_account_title'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Bank Account</dt><dd class="col-sm-8"><?= e($employee['bank_account_no'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Address</dt><dd class="col-sm-8"><?= e($employee['address'] ?: '-') ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<script>
(function () {
    var sel = document.getElementById('selType');
    var sections = document.querySelectorAll('[data-sec]');
    function show(sec) {
        sections.forEach(function (s) {
            s.classList.toggle('d-none', s.dataset.sec !== sec);
        });
    }
    sel.addEventListener('change', function () { show(sel.value); });
    show(sel.value);
})();
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

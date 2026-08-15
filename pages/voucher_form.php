<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.manage');
$active = 'vouchers';

$edit_id = (int)($_GET['id'] ?? 0);
$record = null;
if ($edit_id > 0) {
    $record = db_get("SELECT * FROM vouchers WHERE id = ?", [$edit_id]);
    if (!$record) {
        flash('danger', 'Voucher not found.');
        redirect('vouchers.php');
    }
    $title = 'Edit Voucher - ' . $record['voucher_no'];
    $recordLines = db_all("SELECT * FROM voucher_items WHERE voucher_id = ? ORDER BY id", [$edit_id]);
} else {
    $title = 'New Voucher';
    $recordLines = [];
}

$project_id = $edit_id > 0 ? (int)($record['project_id'] ?? 0) : active_project_id();
$project_id = $project_id ?: null;

if (is_post()) {
    csrf_check();
    $voucher_type = $_POST['voucher_type'] ?? 'journal';
    $prefix = ['cash_payment' => 'CP', 'cash_receipt' => 'CR', 'bank_payment' => 'BP', 'bank_receipt' => 'BR', 'journal' => 'JV'][$voucher_type] ?? 'JV';
    $voucher_no = trim($_POST['voucher_no'] ?? '');
    if ($voucher_no === '') {
        $voucher_no = next_number($prefix, 'vouchers', 'voucher_no');
    }
    $voucher_date = $_POST['voucher_date'] ?? date('Y-m-d');
    $narration = trim($_POST['narration'] ?? '');
    $status = $_POST['status'] ?? 'posted';

    $accounts = $_POST['account_id'] ?? [];
    $descriptions = $_POST['item_description'] ?? [];
    $debits = $_POST['debit'] ?? [];
    $credits = $_POST['credit'] ?? [];

    $totalDebit = 0.0;
    $totalCredit = 0.0;
    $lines = [];
    for ($i = 0; $i < count($accounts); $i++) {
        $acc = (int)($accounts[$i] ?? 0);
        $d = (float)($debits[$i] ?? 0);
        $c = (float)($credits[$i] ?? 0);
        if ($acc <= 0 || ($d <= 0 && $c <= 0)) {
            continue;
        }
        if ($d > 0 && $c > 0) {
            flash('danger', 'Line ' . ($i + 1) . ': a line cannot have both debit and credit.');
            redirect($edit_id > 0 ? 'voucher_form.php?id=' . $edit_id : 'voucher_form.php');
        }
        $lines[] = [$acc, trim($descriptions[$i] ?? ''), $d, $c];
        $totalDebit += $d;
        $totalCredit += $c;
    }

    if (!$lines) {
        flash('danger', 'Add at least one entry line.');
        redirect($edit_id > 0 ? 'voucher_form.php?id=' . $edit_id : 'voucher_form.php');
    }
    if (abs($totalDebit - $totalCredit) > 0.01) {
        flash('danger', 'Debit total (' . fmt_money($totalDebit) . ') must equal credit total (' . fmt_money($totalCredit) . ').');
        redirect($edit_id > 0 ? 'voucher_form.php?id=' . $edit_id : 'voucher_form.php');
    }

    if ($edit_id > 0) {
        db_exec("UPDATE vouchers SET voucher_no=?, voucher_date=?, voucher_type=?, project_id=?, narration=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$voucher_no, $voucher_date, $voucher_type, $project_id, $narration, $status, $edit_id]);
        db_exec("DELETE FROM voucher_items WHERE voucher_id = ?", [$edit_id]);
        $vid = $edit_id;
    } else {
        $vid = db_exec("INSERT INTO vouchers (voucher_no, voucher_date, voucher_type, project_id, narration, status, created_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$voucher_no, $voucher_date, $voucher_type, $project_id, $narration, $status, $user['id']]);
    }
    foreach ($lines as $line) {
        db_exec("INSERT INTO voucher_items (voucher_id, account_id, item_description, debit, credit, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$vid, $line[0], $line[1], $line[2], $line[3]]);
    }
    flash('success', 'Voucher ' . $voucher_no . ' saved and posted.');
    redirect('vouchers.php');
}

$accounts = db_all("SELECT * FROM chart_of_accounts ORDER BY account_type, code");
$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
$employees = db_all("SELECT id, full_name FROM employees ORDER BY full_name");
include '../includes/header.php';
?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="vouchers.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= $edit_id > 0 ? 'Edit Voucher' : 'New Voucher' ?></h5>
</div>

<form method="post" id="voucherForm">
    <?= csrf_field() ?>
    <div class="card">
        <div class="card-header"><i class="bi bi-receipt me-2"></i>Voucher Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Voucher No</label>
                    <input type="text" name="voucher_no" class="form-control" placeholder="Auto" value="<?= e($record['voucher_no'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="voucher_date" class="form-control" value="<?= e($record['voucher_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="voucher_type" class="form-select">
                        <option value="cash_payment" <?= ($record['voucher_type'] ?? '') === 'cash_payment' ? 'selected' : '' ?>>Cash Payment</option>
                        <option value="cash_receipt" <?= ($record['voucher_type'] ?? '') === 'cash_receipt' ? 'selected' : '' ?>>Cash Receipt</option>
                        <option value="bank_payment" <?= ($record['voucher_type'] ?? '') === 'bank_payment' ? 'selected' : '' ?>>Bank Payment</option>
                        <option value="bank_receipt" <?= ($record['voucher_type'] ?? '') === 'bank_receipt' ? 'selected' : '' ?>>Bank Receipt</option>
                        <option value="journal" <?= ($record['voucher_type'] ?? '') === 'journal' ? 'selected' : '' ?>>Journal</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Project</label>
                    <select name="project_id" class="form-select" disabled>
                        <option value="">-- General / No Project --</option>
                        <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= (int)$project_id === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                    </select>
                    <div class="form-text">Locked to the active project selected in the header.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="posted" <?= ($record['status'] ?? '') === 'posted' ? 'selected' : '' ?>>Posted</option>
                        <option value="draft" <?= ($record['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Narration</label>
                    <input type="text" name="narration" class="form-control" value="<?= e($record['narration'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><i class="bi bi-list-ol me-2"></i>Entries</div>
        <div class="card-body">
            <div class="row g-2 align-items-center mb-2">
                <div class="col-md-4">
                    <select id="selAddEmp" class="form-select form-select-sm">
                        <option value="">-- Add Employee Salary Line --</option>
                        <?php foreach ($employees as $emp): ?><option value="<?= $emp['id'] ?>" data-name="<?= e($emp['full_name']) ?>"><?= e($emp['full_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" id="btnAddEmp" class="btn btn-sm btn-outline-success"><i class="bi bi-person-plus me-1"></i>Add Employee Line</button>
                </div>
                <div class="col-md-5"><div class="form-text">Employee choose karo aur Add daba do — uska 2050-&lt;id&gt; account + naam ki line auto-add ho jayegi, sirf amount dalni hai.</div></div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="linesTable">
                    <thead>
                    <tr>
                        <th style="width:38%">Account</th>
                        <th>Description</th>
                        <th style="width:140px">Debit</th>
                        <th style="width:140px">Credit</th>
                        <th style="width:40px"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $emptyRows = max(4, count($recordLines)); ?>
                    <?php for ($i = 0; $i < $emptyRows; $i++): ?>
                        <?php $line = $recordLines[$i] ?? null; ?>
                        <tr>
                            <td>
                                <select name="account_id[]" class="form-select form-select-sm">
                                    <option value="">Select Account</option>
                                    <?php foreach ($accounts as $a): ?><option value="<?= $a['id'] ?>" <?= $line && (int)$line['account_id'] === (int)$a['id'] ? 'selected' : '' ?>><?= e($a['code']) ?> - <?= e($a['name']) ?></option><?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="item_description[]" class="form-control form-control-sm" value="<?= e($line['item_description'] ?? '') ?>"></td>
                            <td><input type="number" step="0.01" name="debit[]" class="form-control form-control-sm line-debit" value="<?= $line ? (float)$line['debit'] : 0 ?>" data-mask-money></td>
                            <td><input type="number" step="0.01" name="credit[]" class="form-control form-control-sm line-credit" value="<?= $line ? (float)$line['credit'] : 0 ?>" data-mask-money></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger btn-del-line"><i class="bi bi-x-lg"></i></button></td>
                        </tr>
                    <?php endfor; ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td><button type="button" class="btn btn-sm btn-outline-primary" id="btnAddLine"><i class="bi bi-plus-lg me-1"></i>Add Line</button></td>
                        <td class="text-end fw-bold">Totals</td>
                        <td class="fw-bold" id="totDebit">0.00</td>
                        <td class="fw-bold" id="totCredit">0.00</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="5"><div class="alert alert-warning mb-0 py-1 small" id="diffAlert" style="display:none"><i class="bi bi-exclamation-triangle me-1"></i>Debit and credit totals must match.</div></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary" type="submit" id="btnSave"><i class="bi bi-check-lg me-1"></i><?= $edit_id > 0 ? 'Update Voucher' : 'Save Voucher' ?></button>
        <a href="vouchers.php" class="btn btn-light">Cancel</a>
    </div>
</form>

<?php
$accountOptions = '';
foreach ($accounts as $a) {
    $accountOptions .= '<option value="' . $a['id'] . '">' . h($a['code']) . ' - ' . h($a['name']) . '</option>';
}
?>

<script>
(function () {
    var table = document.getElementById('linesTable');
    var options = <?= json_encode($accountOptions) ?>;
    document.getElementById('btnAddLine').addEventListener('click', function () {
        var tr = document.createElement('tr');
        tr.innerHTML = '<td><select name="account_id[]" class="form-select form-select-sm"><option value="">Select Account</option>' + options + '</select></td>' +
            '<td><input type="text" name="item_description[]" class="form-control form-control-sm"></td>' +
            '<td><input type="number" step="0.01" name="debit[]" class="form-control form-control-sm line-debit" value="0"></td>' +
            '<td><input type="number" step="0.01" name="credit[]" class="form-control form-control-sm line-credit" value="0"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger btn-del-line"><i class="bi bi-x-lg"></i></button></td>';
        table.tBodies[0].appendChild(tr);
        bind(tr);
    });
    table.addEventListener('click', function (e) {
        if (e.target.closest('.btn-del-line')) {
            var rows = table.tBodies[0].rows;
            if (rows.length > 1) {
                e.target.closest('tr').remove();
                calc();
            }
        }
    });
    function bind(row) {
        row.querySelectorAll('.line-debit, .line-credit').forEach(function (el) {
            el.addEventListener('input', calc);
        });
    }
    table.tBodies[0].querySelectorAll('tr').forEach(function (row) { bind(row); });

    var selAddEmp = document.getElementById('selAddEmp');
    var btnAddEmp = document.getElementById('btnAddEmp');
    if (selAddEmp && btnAddEmp) {
        var empAccCache = {};
        function esc(s) {
            return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
        btnAddEmp.addEventListener('click', function () {
            var opt = selAddEmp.options[selAddEmp.selectedIndex];
            if (!opt || !opt.value) return;
            var empId = opt.value;
            function addLine(accId) {
                var tr = document.createElement('tr');
                tr.innerHTML = '<td><select name="account_id[]" class="form-select form-select-sm"><option value="">Select Account</option>' + options + '</select></td>' +
                    '<td><input type="text" name="item_description[]" class="form-control form-control-sm" value="' + esc(opt.dataset.name || '') + '"></td>' +
                    '<td><input type="number" step="0.01" name="debit[]" class="form-control form-control-sm line-debit" value="0"></td>' +
                    '<td><input type="number" step="0.01" name="credit[]" class="form-control form-control-sm line-credit" value="0"></td>' +
                    '<td><button type="button" class="btn btn-sm btn-outline-danger btn-del-line"><i class="bi bi-x-lg"></i></button></td>';
                var sel = tr.querySelector('select');
                if (accId) sel.value = String(accId);
                table.tBodies[0].appendChild(tr);
                bind(tr);
                var amt = tr.querySelector('.line-debit');
                if (amt) { amt.focus(); amt.select(); }
            }
            if (empAccCache[empId]) { addLine(empAccCache[empId]); return; }
            fetch('ajax.php?action=employee_payable_account&id=' + empId).then(function (r) { return r.json(); }).then(function (d) {
                empAccCache[empId] = (d && d.id) || 0;
                addLine(empAccCache[empId]);
            });
        });
    }

    function calc() {
        var d = 0, c = 0;
        table.tBodies[0].querySelectorAll('.line-debit').forEach(function (el) { d += parseFloat(el.value) || 0; });
        table.tBodies[0].querySelectorAll('.line-credit').forEach(function (el) { c += parseFloat(el.value) || 0; });
        document.getElementById('totDebit').textContent = d.toFixed(2);
        document.getElementById('totCredit').textContent = c.toFixed(2);
        document.getElementById('diffAlert').style.display = Math.abs(d - c) > 0.01 ? 'block' : 'none';
    }
    calc();
})();
</script>

<?php include '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.manage');
$title = 'New Voucher';
$active = 'vouchers';

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
            redirect('voucher_form.php');
        }
        $lines[] = [$acc, trim($descriptions[$i] ?? ''), $d, $c];
        $totalDebit += $d;
        $totalCredit += $c;
    }

    if (!$lines) {
        flash('danger', 'Add at least one entry line.');
        redirect('voucher_form.php');
    }
    if (abs($totalDebit - $totalCredit) > 0.01) {
        flash('danger', 'Debit total (' . fmt_money($totalDebit) . ') must equal credit total (' . fmt_money($totalCredit) . ').');
        redirect('voucher_form.php');
    }

    $vid = db_exec("INSERT INTO vouchers (voucher_no, voucher_date, voucher_type, narration, status, created_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$voucher_no, $voucher_date, $voucher_type, $narration, $status, $user['id']]);
    foreach ($lines as $line) {
        db_exec("INSERT INTO voucher_items (voucher_id, account_id, item_description, debit, credit, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$vid, $line[0], $line[1], $line[2], $line[3]]);
    }
    flash('success', 'Voucher ' . $voucher_no . ' saved and posted.');
    redirect('vouchers.php');
}

$accounts = db_all("SELECT * FROM chart_of_accounts ORDER BY account_type, code");
include '../includes/header.php';
?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="vouchers.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0">New Voucher</h5>
</div>

<form method="post" id="voucherForm">
    <?= csrf_field() ?>
    <div class="card">
        <div class="card-header"><i class="bi bi-receipt me-2"></i>Voucher Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Voucher No</label>
                    <input type="text" name="voucher_no" class="form-control" placeholder="Auto">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="voucher_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="voucher_type" class="form-select">
                        <option value="cash_payment">Cash Payment</option>
                        <option value="cash_receipt">Cash Receipt</option>
                        <option value="bank_payment">Bank Payment</option>
                        <option value="bank_receipt">Bank Receipt</option>
                        <option value="journal">Journal</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="posted">Posted</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Narration</label>
                    <input type="text" name="narration" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><i class="bi bi-list-ol me-2"></i>Entries</div>
        <div class="card-body">
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
                    <?php for ($i = 0; $i < 4; $i++): ?>
                        <tr>
                            <td>
                                <select name="account_id[]" class="form-select form-select-sm">
                                    <option value="">Select Account</option>
                                    <?php foreach ($accounts as $a): ?><option value="<?= $a['id'] ?>"><?= e($a['code']) ?> - <?= e($a['name']) ?></option><?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="item_description[]" class="form-control form-control-sm"></td>
                            <td><input type="number" step="0.01" name="debit[]" class="form-control form-control-sm line-debit" value="0" data-mask-money></td>
                            <td><input type="number" step="0.01" name="credit[]" class="form-control form-control-sm line-credit" value="0" data-mask-money></td>
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
        <button class="btn btn-primary" type="submit" id="btnSave"><i class="bi bi-check-lg me-1"></i>Save Voucher</button>
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

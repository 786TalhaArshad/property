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

    $projectId = (int)($_POST['project_id'] ?? 0) ?: null;

    if ($edit_id > 0) {
        db_exec("UPDATE vouchers SET voucher_no=?, voucher_date=?, voucher_type=?, project_id=?, narration=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$voucher_no, $voucher_date, $voucher_type, $projectId, $narration, $status, $edit_id]);
        db_exec("DELETE FROM voucher_items WHERE voucher_id = ?", [$edit_id]);
        $vid = $edit_id;
    } else {
        $vid = db_exec("INSERT INTO vouchers (voucher_no, voucher_date, voucher_type, project_id, narration, status, created_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$voucher_no, $voucher_date, $voucher_type, $projectId, $narration, $status, $user['id']]);
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
                <div class="col-md-6">
                    <label class="form-label">Search Party <small class="text-muted">(Quick Add — Name / CNIC / Phone / Code)</small></label>
                    <div class="position-relative">
                        <input type="text" id="partySearch" class="form-control form-control-lg" placeholder="Type customer, vendor, employee name, CNIC..." autocomplete="off">
                        <div id="searchResults" class="list-group position-absolute w-100" style="z-index:1050;display:none;max-height:350px;overflow-y:auto"></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div id="partyInfoMini" class="form-control-plaintext text-muted small" style="display:none">
                        <span class="fw-bold" id="partyNameMini"></span>
                        <span class="badge ms-1" id="partyTypeBadgeMini"></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-outline-success d-none" id="btnAddPartyLine"><i class="bi bi-plus-lg me-1"></i>Add Party Line</button>
                </div>
            </div>

            <div class="row g-3 mt-0">
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
                    <select name="project_id" class="form-select">
                        <option value="0">-- General / No Project --</option>
                        <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= (int)$project_id === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                    </select>
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

    var partySearch = document.getElementById('partySearch');
    var searchResults = document.getElementById('searchResults');
    var partyInfoMini = document.getElementById('partyInfoMini');
    var partyNameMini = document.getElementById('partyNameMini');
    var partyTypeBadgeMini = document.getElementById('partyTypeBadgeMini');
    var btnAddPartyLine = document.getElementById('btnAddPartyLine');
    var debounceTimer = null;
    var selectedPartyAccountId = 0;
    var selectedPartyName = '';

    var typeLabels = {
        customer: { label: 'Customer', cls: 'bg-primary' },
        vendor: { label: 'Vendor', cls: 'bg-success' },
        owner: { label: 'Owner', cls: 'bg-warning text-dark' },
        dealer: { label: 'Dealer', cls: 'bg-info' },
        employee: { label: 'Employee', cls: 'bg-secondary' },
        contractor: { label: 'Contractor', cls: 'bg-dark' },
        investor: { label: 'Investor', cls: 'bg-purple' },
        tenant: { label: 'Tenant', cls: 'bg-danger' }
    };

    function esc(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function addRow(accountId, description) {
        var tr = document.createElement('tr');
        tr.innerHTML = '<td><select name="account_id[]" class="form-select form-select-sm"><option value="">Select Account</option>' + options + '</select></td>' +
            '<td><input type="text" name="item_description[]" class="form-control form-control-sm" value="' + esc(description || '') + '"></td>' +
            '<td><input type="number" step="0.01" name="debit[]" class="form-control form-control-sm line-debit" value="0"></td>' +
            '<td><input type="number" step="0.01" name="credit[]" class="form-control form-control-sm line-credit" value="0"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger btn-del-line"><i class="bi bi-x-lg"></i></button></td>';
        var sel = tr.querySelector('select');
        if (accountId) sel.value = String(accountId);
        table.tBodies[0].appendChild(tr);
        bindRow(tr);
        var amt = tr.querySelector('.line-debit');
        if (amt) { amt.focus(); amt.select(); }
        return tr;
    }

    document.getElementById('btnAddLine').addEventListener('click', function () { addRow(0, ''); });

    table.addEventListener('click', function (e) {
        if (e.target.closest('.btn-del-line')) {
            var rows = table.tBodies[0].rows;
            if (rows.length > 1) {
                e.target.closest('tr').remove();
                calc();
            }
        }
    });

    function bindRow(row) {
        row.querySelectorAll('.line-debit, .line-credit').forEach(function (el) {
            el.addEventListener('input', calc);
        });
    }
    table.tBodies[0].querySelectorAll('tr').forEach(function (row) { bindRow(row); });

    btnAddPartyLine.addEventListener('click', function () {
        if (!selectedPartyAccountId) return;
        addRow(selectedPartyAccountId, selectedPartyName);
    });

    partySearch.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); }
    });

    partySearch.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        var q = partySearch.value.trim();
        if (q.length < 2) { searchResults.style.display = 'none'; return; }
        debounceTimer = setTimeout(function () {
            fetch('<?= BASE_URL ?>/pages/ajax.php?action=party_search&q=' + encodeURIComponent(q) + '&_t=' + Date.now())
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    searchResults.innerHTML = '';
                    if (data && data.error) {
                        searchResults.innerHTML = '<div class="list-group-item text-danger">' + data.error + '</div>';
                        searchResults.style.display = 'block';
                        return;
                    }
                    if (!data || !Array.isArray(data) || data.length === 0) {
                        searchResults.innerHTML = '<div class="list-group-item text-muted">No results found</div>';
                        searchResults.style.display = 'block';
                        return;
                    }
                    data.forEach(function (item) {
                        var tl = typeLabels[item.type] || { label: item.type, cls: 'bg-secondary' };
                        var detail = item.code ? item.code : '';
                        if (item.cnic) detail += (detail ? ' | ' : '') + 'CNIC: ' + item.cnic;
                        if (item.phone) detail += (detail ? ' | ' : '') + item.phone;
                        var div = document.createElement('button');
                        div.type = 'button';
                        div.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                        div.innerHTML = '<div><div class="fw-medium">' + (item.name || '') + '</div>' +
                            (detail ? '<div class="small text-muted">' + detail + '</div>' : '') + '</div>' +
                            '<span class="badge ' + tl.cls + '">' + tl.label + '</span>';
                        div.addEventListener('click', function () {
                            partySearch.value = item.name || '';
                            searchResults.style.display = 'none';
                            selectedPartyAccountId = 0;
                            selectedPartyName = item.name || '';
                            partyNameMini.textContent = item.name || '';
                            var tl2 = typeLabels[item.type] || { label: item.type, cls: 'bg-secondary' };
                            partyTypeBadgeMini.textContent = tl2.label;
                            partyTypeBadgeMini.className = 'badge ' + tl2.cls;
                            partyInfoMini.style.display = '';
                            btnAddPartyLine.classList.add('d-none');
                            fetch('<?= BASE_URL ?>/pages/ajax.php?action=party_account&type=' + item.type + '&id=' + item.id + '&_t=' + Date.now())
                                .then(function (r) { return r.json(); })
                                .then(function (d) {
                                    if (d && d.account_id) {
                                        selectedPartyAccountId = d.account_id;
                                        btnAddPartyLine.classList.remove('d-none');
                                    }
                                })
                                .catch(function () {});
                        });
                        searchResults.appendChild(div);
                    });
                    searchResults.style.display = 'block';
                })
                .catch(function () {
                    searchResults.innerHTML = '<div class="list-group-item text-danger">Search failed. Please try again.</div>';
                    searchResults.style.display = 'block';
                });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!partySearch.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });

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

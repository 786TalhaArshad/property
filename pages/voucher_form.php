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
} else {
    $title = 'New Journal Voucher';
}

$project_id = $edit_id > 0 ? (int)($record['project_id'] ?? 0) : active_project_id();
$project_id = $project_id ?: null;

function resolve_party_account($type, $id) {
    if ($type === 'customer') return coa_id_by_code('1100');
    if ($type === 'vendor') return coa_id_by_code('2000');
    if ($type === 'owner') return coa_id_by_code('3000');
    if ($type === 'dealer') return coa_id_by_code('2000');
    if ($type === 'employee') {
        $emp = db_get("SELECT full_name FROM employees WHERE id = ?", [$id]);
        return $emp ? employee_payable_account_id($id, $emp['full_name']) : 0;
    }
    if ($type === 'contractor') {
        $con = db_get("SELECT full_name FROM contractors WHERE id = ?", [$id]);
        return $con ? contractor_payable_account_id($id, $con['full_name']) : 0;
    }
    if ($type === 'investor') return coa_id_by_code('2070');
    if ($type === 'tenant') return coa_id_by_code('4100');
    return 0;
}

if (is_post()) {
    csrf_check();
    $voucher_no = trim($_POST['voucher_no'] ?? '');
    if ($voucher_no === '') {
        $voucher_no = next_number('JV', 'vouchers', 'voucher_no');
    }
    $voucher_date = $_POST['voucher_date'] ?? date('Y-m-d');
    $reference_no = trim($_POST['reference_no'] ?? '');
    $narration = trim($_POST['narration'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $status = $_POST['status'] ?? 'posted';
    $projectId = (int)($_POST['project_id'] ?? 0) ?: null;
    $amount = (float)($_POST['amount'] ?? 0);

    $creditPartyType = trim($_POST['credit_party_type'] ?? '');
    $creditPartyId = (int)($_POST['credit_party_id'] ?? 0);
    $debitPartyType = trim($_POST['debit_party_type'] ?? '');
    $debitPartyId = (int)($_POST['debit_party_id'] ?? 0);

    if ($creditPartyType === '' || $creditPartyId <= 0) {
        flash('danger', 'Please select a Credit party (Received From).');
        redirect($edit_id > 0 ? 'voucher_form.php?id=' . $edit_id : 'voucher_form.php');
    }
    if ($debitPartyType === '' || $debitPartyId <= 0) {
        flash('danger', 'Please select a Debit party (Paid To).');
        redirect($edit_id > 0 ? 'voucher_form.php?id=' . $edit_id : 'voucher_form.php');
    }
    if ($amount <= 0) {
        flash('danger', 'Amount must be greater than zero.');
        redirect($edit_id > 0 ? 'voucher_form.php?id=' . $edit_id : 'voucher_form.php');
    }

    $creditAccountId = resolve_party_account($creditPartyType, $creditPartyId);
    $debitAccountId = resolve_party_account($debitPartyType, $debitPartyId);

    if ($creditAccountId <= 0) {
        flash('danger', 'Could not resolve Credit party account.');
        redirect($edit_id > 0 ? 'voucher_form.php?id=' . $edit_id : 'voucher_form.php');
    }
    if ($debitAccountId <= 0) {
        flash('danger', 'Could not resolve Debit party account.');
        redirect($edit_id > 0 ? 'voucher_form.php?id=' . $edit_id : 'voucher_form.php');
    }

    global $mysqli;
    $mysqli->begin_transaction();
    try {
        if ($edit_id > 0) {
            db_exec("UPDATE vouchers SET voucher_no=?, voucher_date=?, voucher_type='journal', reference_no=?, project_id=?, narration=?, credit_party_type=?, credit_party_id=?, debit_party_type=?, debit_party_id=?, amount=?, remarks=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [
                $voucher_no, $voucher_date, $reference_no, $projectId, $narration,
                $creditPartyType, $creditPartyId, $debitPartyType, $debitPartyId, $amount,
                $remarks, $status, $edit_id
            ]);
            db_exec("DELETE FROM voucher_items WHERE voucher_id = ?", [$edit_id]);
            $vid = $edit_id;
        } else {
            $vid = db_exec("INSERT INTO vouchers (voucher_no, voucher_date, voucher_type, reference_no, project_id, narration, credit_party_type, credit_party_id, debit_party_type, debit_party_id, amount, remarks, status, created_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [
                $voucher_no, $voucher_date, 'journal', $reference_no, $projectId, $narration,
                $creditPartyType, $creditPartyId, $debitPartyType, $debitPartyId, $amount,
                $remarks, $status, $user['id']
            ]);
        }

        db_exec("INSERT INTO voucher_items (voucher_id, account_id, item_description, debit, credit, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,0,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$vid, $debitAccountId, 'Debit: ' . $narration, $amount]);
        db_exec("INSERT INTO voucher_items (voucher_id, account_id, item_description, debit, credit, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,0,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$vid, $creditAccountId, 'Credit: ' . $narration, $amount]);

        $mysqli->commit();
        flash('success', 'Journal Voucher ' . $voucher_no . ' saved successfully.');
        redirect('vouchers.php');
    } catch (\Exception $e) {
        $mysqli->rollback();
        flash('danger', 'Failed to save voucher. Error: ' . $e->getMessage());
        redirect($edit_id > 0 ? 'voucher_form.php?id=' . $edit_id : 'voucher_form.php');
    }
}

$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");

$editCreditType = $record['credit_party_type'] ?? '';
$editCreditId = (int)($record['credit_party_id'] ?? 0);
$editDebitType = $record['debit_party_type'] ?? '';
$editDebitId = (int)($record['debit_party_id'] ?? 0);
$editAmount = $record['amount'] ?? 0;

$editCreditName = '';
$editDebitName = '';
if ($editCreditId > 0 && $editCreditType !== '') {
    if ($editCreditType === 'customer') { $r = db_get("SELECT full_name AS name FROM customers WHERE id=?", [$editCreditId]); $editCreditName = $r['name'] ?? ''; }
    elseif ($editCreditType === 'vendor') { $r = db_get("SELECT business_name AS name FROM vendors WHERE id=?", [$editCreditId]); $editCreditName = $r['name'] ?? ''; }
    elseif ($editCreditType === 'owner') { $r = db_get("SELECT full_name AS name FROM owners WHERE id=?", [$editCreditId]); $editCreditName = $r['name'] ?? ''; }
    elseif ($editCreditType === 'dealer') { $r = db_get("SELECT full_name AS name FROM dealers WHERE id=?", [$editCreditId]); $editCreditName = $r['name'] ?? ''; }
    elseif ($editCreditType === 'employee') { $r = db_get("SELECT full_name AS name FROM employees WHERE id=?", [$editCreditId]); $editCreditName = $r['name'] ?? ''; }
    elseif ($editCreditType === 'contractor') { $r = db_get("SELECT full_name AS name FROM contractors WHERE id=?", [$editCreditId]); $editCreditName = $r['name'] ?? ''; }
    elseif ($editCreditType === 'investor') { $r = db_get("SELECT full_name AS name FROM investors WHERE id=?", [$editCreditId]); $editCreditName = $r['name'] ?? ''; }
    elseif ($editCreditType === 'tenant') { $r = db_get("SELECT full_name AS name FROM tenants WHERE id=?", [$editCreditId]); $editCreditName = $r['name'] ?? ''; }
}
if ($editDebitId > 0 && $editDebitType !== '') {
    if ($editDebitType === 'customer') { $r = db_get("SELECT full_name AS name FROM customers WHERE id=?", [$editDebitId]); $editDebitName = $r['name'] ?? ''; }
    elseif ($editDebitType === 'vendor') { $r = db_get("SELECT business_name AS name FROM vendors WHERE id=?", [$editDebitId]); $editDebitName = $r['name'] ?? ''; }
    elseif ($editDebitType === 'owner') { $r = db_get("SELECT full_name AS name FROM owners WHERE id=?", [$editDebitId]); $editDebitName = $r['name'] ?? ''; }
    elseif ($editDebitType === 'dealer') { $r = db_get("SELECT full_name AS name FROM dealers WHERE id=?", [$editDebitId]); $editDebitName = $r['name'] ?? ''; }
    elseif ($editDebitType === 'employee') { $r = db_get("SELECT full_name AS name FROM employees WHERE id=?", [$editDebitId]); $editDebitName = $r['name'] ?? ''; }
    elseif ($editDebitType === 'contractor') { $r = db_get("SELECT full_name AS name FROM contractors WHERE id=?", [$editDebitId]); $editDebitName = $r['name'] ?? ''; }
    elseif ($editDebitType === 'investor') { $r = db_get("SELECT full_name AS name FROM investors WHERE id=?", [$editDebitId]); $editDebitName = $r['name'] ?? ''; }
    elseif ($editDebitType === 'tenant') { $r = db_get("SELECT full_name AS name FROM tenants WHERE id=?", [$editDebitId]); $editDebitName = $r['name'] ?? ''; }
}

include '../includes/header.php';
?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="vouchers.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><i class="bi bi-journal-text me-2"></i><?= $edit_id > 0 ? 'Edit Journal Voucher' : 'New Journal Voucher' ?></h5>
</div>

<form method="post" id="voucherForm">
    <?= csrf_field() ?>
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-receipt me-2"></i>Voucher Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Voucher No</label>
                    <input type="text" name="voucher_no" class="form-control" placeholder="Auto (JV-xxxx)" value="<?= e($record['voucher_no'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Voucher Date *</label>
                    <input type="date" name="voucher_date" class="form-control" value="<?= e($record['voucher_date'] ?? date('Y-m-d')) ?>" required>
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
                <div class="col-md-3">
                    <label class="form-label">Reference No</label>
                    <input type="text" name="reference_no" class="form-control" placeholder="Invoice / Txn ref" value="<?= e($record['reference_no'] ?? '') ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Amount *</label>
                    <input type="number" step="0.01" name="amount" class="form-control form-control-lg fw-bold" placeholder="0.00" value="<?= $edit_id > 0 ? e($editAmount) : '' ?>" required id="txnAmount">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Narration *</label>
                    <input type="text" name="narration" class="form-control" placeholder="Brief description of the transaction" value="<?= e($record['narration'] ?? '') ?>" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Remarks</label>
                    <input type="text" name="remarks" class="form-control" placeholder="Internal notes (optional)" value="<?= e($record['remarks'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card border-success h-100">
                <div class="card-header bg-success text-white"><i class="bi bi-arrow-up-circle me-2"></i>Credit &mdash; Received From (Kis Sy Liya)</div>
                <div class="card-body">
                    <input type="hidden" name="credit_party_type" id="creditPartyType" value="<?= e($editCreditType) ?>">
                    <input type="hidden" name="credit_party_id" id="creditPartyId" value="<?= $editCreditId ?>">
                    <label class="form-label">Search Party</label>
                    <div class="position-relative">
                        <input type="text" id="creditSearch" class="form-control" placeholder="Type name, CNIC, phone..." autocomplete="off" value="<?= e($editCreditName) ?>">
                        <div id="creditResults" class="list-group position-absolute w-100" style="z-index:1050;display:none;max-height:300px;overflow-y:auto"></div>
                    </div>
                    <div id="creditInfo" class="mt-2" style="display:<?= $editCreditId > 0 ? '' : 'none' ?>">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold" id="creditNameDisplay"><?= e($editCreditName) ?></span>
                            <span class="badge bg-success" id="creditTypeBadge"></span>
                        </div>
                        <div class="mt-1">
                            <span class="text-muted small">Outstanding Balance:</span>
                            <span class="fs-5 fw-bold" id="creditBalanceDisplay">--</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-danger h-100">
                <div class="card-header bg-danger text-white"><i class="bi bi-arrow-down-circle me-2"></i>Debit &mdash; Paid To (Kis Ko Diya)</div>
                <div class="card-body">
                    <input type="hidden" name="debit_party_type" id="debitPartyType" value="<?= e($editDebitType) ?>">
                    <input type="hidden" name="debit_party_id" id="debitPartyId" value="<?= $editDebitId ?>">
                    <label class="form-label">Search Party</label>
                    <div class="position-relative">
                        <input type="text" id="debitSearch" class="form-control" placeholder="Type name, CNIC, phone..." autocomplete="off" value="<?= e($editDebitName) ?>">
                        <div id="debitResults" class="list-group position-absolute w-100" style="z-index:1050;display:none;max-height:300px;overflow-y:auto"></div>
                    </div>
                    <div id="debitInfo" class="mt-2" style="display:<?= $editDebitId > 0 ? '' : 'none' ?>">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold" id="debitNameDisplay"><?= e($editDebitName) ?></span>
                            <span class="badge bg-danger" id="debitTypeBadge"></span>
                        </div>
                        <div class="mt-1">
                            <span class="text-muted small">Outstanding Balance:</span>
                            <span class="fs-5 fw-bold" id="debitBalanceDisplay">--</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="previewCard" class="card mb-3" style="display:none">
        <div class="card-header"><i class="bi bi-eye me-2"></i>Voucher Preview</div>
        <div class="card-body">
            <table class="table table-sm mb-0">
                <thead><tr><th>Account</th><th class="text-end" style="width:150px">Debit</th><th class="text-end" style="width:150px">Credit</th></tr></thead>
                <tbody>
                    <tr><td id="previewDebitAccount"></td><td class="text-end fw-bold" id="previewDebitAmt"></td><td class="text-end">-</td></tr>
                    <tr><td id="previewCreditAccount"></td><td class="text-end">-</td><td class="text-end fw-bold" id="previewCreditAmt"></td></tr>
                </tbody>
                <tfoot><tr class="table-light fw-bold"><td>Total</td><td class="text-end" id="previewTotDebit"></td><td class="text-end" id="previewTotCredit"></td></tr></tfoot>
            </table>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit" id="btnSave"><i class="bi bi-check-lg me-1"></i><?= $edit_id > 0 ? 'Update Voucher' : 'Save & Post Voucher' ?></button>
        <a href="vouchers.php" class="btn btn-light">Cancel</a>
    </div>
</form>

<script>
(function () {
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

    var creditSearch = document.getElementById('creditSearch');
    var creditResults = document.getElementById('creditResults');
    var creditPartyType = document.getElementById('creditPartyType');
    var creditPartyId = document.getElementById('creditPartyId');
    var creditInfo = document.getElementById('creditInfo');
    var creditNameDisplay = document.getElementById('creditNameDisplay');
    var creditTypeBadge = document.getElementById('creditTypeBadge');
    var creditBalanceDisplay = document.getElementById('creditBalanceDisplay');

    var debitSearch = document.getElementById('debitSearch');
    var debitResults = document.getElementById('debitResults');
    var debitPartyType = document.getElementById('debitPartyType');
    var debitPartyId = document.getElementById('debitPartyId');
    var debitInfo = document.getElementById('debitInfo');
    var debitNameDisplay = document.getElementById('debitNameDisplay');
    var debitTypeBadge = document.getElementById('debitTypeBadge');
    var debitBalanceDisplay = document.getElementById('debitBalanceDisplay');

    var previewCard = document.getElementById('previewCard');
    var txnAmount = document.getElementById('txnAmount');
    var btnSave = document.getElementById('btnSave');
    var debounceTimers = {};

    function esc(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function setupSearch(inputEl, resultsEl, typeInputEl, idInputEl, infoEl, nameEl, badgeEl, balanceEl, side) {
        var timer = null;
        inputEl.addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });

        inputEl.addEventListener('input', function () {
            clearTimeout(timer);
            var q = inputEl.value.trim();
            if (q.length < 2) { resultsEl.style.display = 'none'; return; }
            timer = setTimeout(function () {
                fetch('<?= BASE_URL ?>/pages/ajax.php?action=party_search&q=' + encodeURIComponent(q) + '&_t=' + Date.now())
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        resultsEl.innerHTML = '';
                        if (!data || !Array.isArray(data) || data.length === 0) {
                            resultsEl.innerHTML = '<div class="list-group-item text-muted">No results found</div>';
                            resultsEl.style.display = 'block';
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
                            div.innerHTML = '<div><div class="fw-medium">' + esc(item.name || '') + '</div>' +
                                (detail ? '<div class="small text-muted">' + detail + '</div>' : '') + '</div>' +
                                '<span class="badge ' + tl.cls + '">' + tl.label + '</span>';
                            div.addEventListener('click', function () {
                                inputEl.value = item.name || '';
                                resultsEl.style.display = 'none';
                                typeInputEl.value = item.type;
                                idInputEl.value = item.id;
                                nameEl.textContent = item.name || '';
                                var tl2 = typeLabels[item.type] || { label: item.type, cls: 'bg-secondary' };
                                badgeEl.textContent = tl2.label;
                                badgeEl.className = 'badge ' + tl2.cls;
                                infoEl.style.display = '';
                                balanceEl.textContent = 'Loading...';
                                balanceEl.className = 'fs-5 fw-bold';
                                fetch('<?= BASE_URL ?>/pages/ajax.php?action=party_balance&type=' + item.type + '&id=' + item.id + '&_t=' + Date.now())
                                    .then(function (r) { return r.json(); })
                                    .then(function (b) {
                                        var bal = parseFloat(b.balance) || 0;
                                        var color = bal > 0 ? 'text-danger' : (bal < 0 ? 'text-success' : 'text-muted');
                                        balanceEl.textContent = 'Rs. ' + bal.toFixed(2);
                                        balanceEl.className = 'fs-5 fw-bold ' + color;
                                    })
                                    .catch(function () {
                                        balanceEl.textContent = 'Error loading balance';
                                        balanceEl.className = 'fs-5 fw-bold text-danger';
                                    });
                                updatePreview();
                            });
                            resultsEl.appendChild(div);
                        });
                        resultsEl.style.display = 'block';
                    })
                    .catch(function () {
                        resultsEl.innerHTML = '<div class="list-group-item text-danger">Search failed</div>';
                        resultsEl.style.display = 'block';
                    });
            }, 300);
        });

        inputEl.addEventListener('focus', function () {
            if (inputEl.value.trim().length >= 2) resultsEl.style.display = 'block';
        });
    }

    setupSearch(creditSearch, creditResults, creditPartyType, creditPartyId, creditInfo, creditNameDisplay, creditTypeBadge, creditBalanceDisplay, 'credit');
    setupSearch(debitSearch, debitResults, debitPartyType, debitPartyId, debitInfo, debitNameDisplay, debitTypeBadge, debitBalanceDisplay, 'debit');

    document.addEventListener('click', function (e) {
        if (!creditSearch.contains(e.target) && !creditResults.contains(e.target)) creditResults.style.display = 'none';
        if (!debitSearch.contains(e.target) && !debitResults.contains(e.target)) debitResults.style.display = 'none';
    });

    function updatePreview() {
        var amt = parseFloat(txnAmount.value) || 0;
        var creditType = creditPartyType.value;
        var creditId = creditPartyId.value;
        var debitType = debitPartyType.value;
        var debitId = debitPartyId.value;

        if (creditId > 0 && debitId > 0 && amt > 0) {
            previewCard.style.display = '';
            document.getElementById('previewDebitAccount').textContent = debitSearch.value + ' (' + debitType + ')';
            document.getElementById('previewCreditAccount').textContent = creditSearch.value + ' (' + creditType + ')';
            document.getElementById('previewDebitAmt').textContent = 'Rs. ' + amt.toFixed(2);
            document.getElementById('previewCreditAmt').textContent = 'Rs. ' + amt.toFixed(2);
            document.getElementById('previewTotDebit').textContent = 'Rs. ' + amt.toFixed(2);
            document.getElementById('previewTotCredit').textContent = 'Rs. ' + amt.toFixed(2);
            btnSave.disabled = false;
            btnSave.classList.remove('disabled');
        } else {
            previewCard.style.display = 'none';
            if (creditId > 0 && debitId > 0 && amt > 0) {
                btnSave.disabled = false;
            } else {
                btnSave.disabled = true;
                btnSave.classList.add('disabled');
            }
        }
    }

    txnAmount.addEventListener('input', updatePreview);

    var origCreditId = creditPartyId.value;
    var origDebitId = debitPartyId.value;
    var origAmt = txnAmount.value;
    if (origCreditId > 0 && origDebitId > 0 && parseFloat(origAmt) > 0) {
        updatePreview();
    } else {
        btnSave.disabled = true;
        btnSave.classList.add('disabled');
    }

    document.getElementById('voucherForm').addEventListener('submit', function () {
        btnSave.disabled = true;
        btnSave.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Saving...';
    });
})();
</script>

<?php include '../includes/footer.php'; ?>

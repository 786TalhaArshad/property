<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Cash Paid';
$active = 'cash_paid';
$canEdit = has_permission('accounting.manage');

$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
$banks = db_all("SELECT * FROM banks ORDER BY name");
$expenseAccounts = db_all("SELECT id, code, name FROM chart_of_accounts WHERE account_type = 'expense' ORDER BY code");
$allAccounts = db_all("SELECT id, code, name, account_type FROM chart_of_accounts ORDER BY code");

if (is_post() && $canEdit) {
    csrf_check();
    $date = $_POST['payment_date'] ?? date('Y-m-d');
    $amount = (float)($_POST['amount'] ?? 0);
    $narration = trim($_POST['narration'] ?? '');
    $paymentMode = $_POST['payment_mode'] ?? 'cash';
    $bankId = (int)($_POST['bank_id'] ?? 0) ?: null;
    $reference = trim($_POST['reference'] ?? '');
    $projectId = (int)($_POST['project_id'] ?? 0) ?: null;
    $partyType = $_POST['party_type'] ?? '';
    $partyId = (int)($_POST['party_id'] ?? 0);

    if ($amount <= 0) { flash('danger', 'Enter a valid amount.'); redirect('cash_paid.php'); }
    if ($paymentMode === 'bank' && !$bankId) { flash('danger', 'Select the bank.'); redirect('cash_paid.php'); }

    $partyName = '';
    $vendorId = $ownerId = $dealerId = $employeeId = $customerId = $contractorId = $investorId = 0;
    $accountId = 0;

    if (in_array($partyType, ['customer','vendor','owner','dealer','employee','contractor','investor'], true)) {
        if (!$partyId) { flash('danger', 'Select a party.'); redirect('cash_paid.php'); }
    }

    if ($partyType === 'customer') {
        $customerId = $partyId;
        $c = db_get("SELECT full_name FROM customers WHERE id = ?", [$customerId]);
        if ($c) $partyName = $c['full_name'];
        $accountId = coa_id_by_code('1100');
    } elseif ($partyType === 'vendor') {
        $vendorId = $partyId;
        $v = db_get("SELECT business_name FROM vendors WHERE id = ?", [$vendorId]);
        if ($v) $partyName = $v['business_name'];
        $accountId = coa_id_by_code('2000');
    } elseif ($partyType === 'owner') {
        $ownerId = $partyId;
        $o = db_get("SELECT full_name FROM owners WHERE id = ?", [$ownerId]);
        if ($o) $partyName = $o['full_name'];
        $accountId = coa_id_by_code('3000');
    } elseif ($partyType === 'dealer') {
        $dealerId = $partyId;
        $d = db_get("SELECT full_name FROM dealers WHERE id = ?", [$dealerId]);
        if ($d) $partyName = $d['full_name'];
        $accountId = coa_id_by_code('2000');
    } elseif ($partyType === 'employee') {
        $employeeId = $partyId;
        $emp = db_get("SELECT full_name FROM employees WHERE id = ?", [$employeeId]);
        if ($emp) { $partyName = $emp['full_name']; $accountId = employee_payable_account_id($employeeId, $emp['full_name']); }
    } elseif ($partyType === 'contractor') {
        $contractorId = $partyId;
        $con = db_get("SELECT full_name FROM contractors WHERE id = ?", [$contractorId]);
        if ($con) { $partyName = $con['full_name']; $accountId = contractor_payable_account_id($contractorId, $con['full_name']); }
    } elseif ($partyType === 'investor') {
        $investorId = $partyId;
        $inv = db_get("SELECT full_name FROM investors WHERE id = ?", [$investorId]);
        if ($inv) { $partyName = $inv['full_name']; $accountId = coa_id_by_code('2070'); }
    } elseif ($partyType === 'expense') {
        $accountId = (int)($_POST['expense_account_id'] ?? 0);
        if (!$accountId) { flash('danger', 'Select the expense head.'); redirect('cash_paid.php'); }
        $partyName = db_get("SELECT name FROM chart_of_accounts WHERE id = ?", [$accountId])['name'] ?? 'Expense';
    } elseif ($partyType === 'other') {
        $accountId = (int)($_POST['other_account_id'] ?? 0);
        if (!$accountId) { flash('danger', 'Select the account.'); redirect('cash_paid.php'); }
        $partyName = db_get("SELECT name FROM chart_of_accounts WHERE id = ?", [$accountId])['name'] ?? 'Other';
    }

    if (!$accountId) { flash('danger', 'Account not found.'); redirect('cash_paid.php'); }

    $cashAcc = cash_bank_account_id($bankId);
    if (!$cashAcc) { flash('danger', 'Cash / bank account not found in chart of accounts.'); redirect('cash_paid.php'); }

    $bankName = $bankId ? (db_get("SELECT name FROM banks WHERE id = ?", [$bankId])['name'] ?? 'Bank') : 'Cash';
    $narr = $narration !== '' ? $narration : ($partyName !== '' ? 'Paid to ' . $partyName : 'Cash paid');
    $voucherType = $paymentMode === 'bank' ? 'bank_payment' : 'cash_payment';
    $vid = post_cash_voucher($date, $voucherType, $narr, $projectId, $accountId, $cashAcc, $amount,
        'Paid to ' . ($partyName !== '' ? $partyName : 'N/A'), 'Paid from ' . $bankName);

    if ($partyType === 'customer') {
        db_exec("INSERT INTO customer_payments (customer_id, payment_date, amount, payment_mode, bank_id, reference, narration, voucher_id, project_id, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$customerId, $date, $amount, $paymentMode, $bankId, $reference, $narration, $vid, $projectId]);
        flash('success', 'Customer payment saved. Voucher ' . $vid . ' posted.');
    } elseif ($partyType === 'vendor') {
        db_exec("INSERT INTO vendor_payments (vendor_id, payment_date, amount, payment_method_id, bank_id, reference, remarks, voucher_id, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$vendorId, $date, $amount, null, $bankId, $reference, $narration, $vid]);
        flash('success', 'Vendor payment saved. Voucher ' . $vid . ' posted.');
    } elseif ($partyType === 'dealer') {
        db_exec("INSERT INTO dealer_payments (dealer_id, payment_date, amount, payment_method_id, bank_id, reference, remarks, voucher_id, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$dealerId, $date, $amount, null, $bankId, $reference, $narration, $vid]);
        flash('success', 'Dealer payment saved. Voucher ' . $vid . ' posted.');
    } elseif ($partyType === 'owner') {
        db_exec("INSERT INTO owner_settlements (owner_id, agreement_id, settlement_date, rent_income, deductions, settlement_amount, status, payment_method_id, bank_id, remarks, voucher_id, created_date, created_time, updated_date, updated_time) VALUES (?,NULL,?,0,0,?,'paid',NULL,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$ownerId, $date, $amount, $bankId, $narration, $vid]);
        $bal = (float)db_get("SELECT COALESCE(MAX(balance),0) b FROM owner_ledger WHERE owner_id = ?", [$ownerId])['b'];
        db_exec("INSERT INTO owner_ledger (owner_id, entry_date, description, debit, credit, balance, voucher_id, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,0,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$ownerId, $date, 'Payment to owner', $amount, $bal - $amount, $vid]);
        flash('success', 'Owner payment saved. Voucher ' . $vid . ' posted.');
    } elseif ($partyType === 'employee') {
        $entry_no = next_number('EMPE', 'employee_entries', 'entry_no');
        db_exec("INSERT INTO employee_entries (employee_id, entry_no, entry_date, entry_type, amount, narration, account_id, voucher_id, created_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$employeeId, $entry_no, $date, 'paid', $amount, $narr, $accountId, $vid, $user['id']]);
        flash('success', 'Employee salary paid. Entry ' . $entry_no . ' saved, Voucher ' . $vid . ' posted.');
    } elseif ($partyType === 'contractor') {
        $entry_no = next_number('CONE', 'contractor_entries', 'entry_no');
        db_exec("INSERT INTO contractor_entries (contractor_id, entry_no, entry_date, entry_type, amount, narration, account_id, project_id, voucher_id, created_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$contractorId, $entry_no, $date, 'paid', $amount, $narr, $accountId, $projectId, $vid, $user['id']]);
        flash('success', 'Contractor payment saved. Entry ' . $entry_no . ' saved, Voucher ' . $vid . ' posted.');
    } elseif ($partyType === 'investor') {
        $lastBal = (float)db_get("SELECT COALESCE(MAX(balance),0) b FROM investor_ledger WHERE investor_id = ?", [$investorId])['b'];
        db_exec("INSERT INTO investor_ledger (investor_id, entry_date, description, debit, credit, balance, voucher_id, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,0,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$investorId, $date, 'Payment to investor', $amount, $lastBal - $amount, $vid]);
        flash('success', 'Investor payment saved. Voucher ' . $vid . ' posted.');
    } else {
        flash('success', 'Payment saved. Voucher ' . $vid . ' posted.');
    }
    redirect('cash_paid.php');
}

include '../includes/header.php';
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h5 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Cash Paid</h5>
</div>

<form method="post" id="cashPaidForm">
    <?= csrf_field() ?>
    <input type="hidden" name="party_type" id="partyType" value="">
    <input type="hidden" name="party_id" id="partyId" value="">
    <div class="card">
        <div class="card-header"><i class="bi bi-arrow-up-circle me-2"></i>Payment Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Search Party <small class="text-muted">(Name / CNIC / Phone / Code)</small></label>
                    <div class="position-relative">
                        <input type="text" id="partySearch" class="form-control form-control-lg" placeholder="Type customer name, vendor, CNIC..." autocomplete="off">
                        <div id="searchResults" class="list-group position-absolute w-100" style="z-index:1050;display:none;max-height:350px;overflow-y:auto"></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>
            </div>

            <div class="row g-3 mt-0" id="partyInfoBox" style="display:none">
                <div class="col-md-6">
                    <label class="form-label">Selected Party</label>
                    <div class="p-3 bg-light rounded border d-flex align-items-start gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold fs-5" id="partyNameDisplay">-</span>
                                <span class="badge" id="partyTypeBadge">-</span>
                            </div>
                            <div class="small text-muted" id="partyDetailDisplay"></div>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">Outstanding Balance</div>
                            <div class="fs-5 fw-bold" id="partyBalanceDisplay">0.00</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="clearParty" title="Clear"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-0" id="secExpense" style="display:none">
                <div class="col-md-6">
                    <label class="form-label">Expense Head *</label>
                    <select name="expense_account_id" class="form-select">
                        <option value="">Select Expense Head</option>
                        <?php foreach ($expenseAccounts as $a): ?><option value="<?= $a['id'] ?>"><?= e($a['code']) ?> - <?= e($a['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mt-0" id="secOther" style="display:none">
                <div class="col-md-6">
                    <label class="form-label">Account *</label>
                    <select name="other_account_id" class="form-select">
                        <option value="">Select Account</option>
                        <?php foreach ($allAccounts as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= e($a['code']) ?> - <?= e($a['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mt-0">
                <div class="col-md-4">
                    <label class="form-label">Project</label>
                    <select name="project_id" class="form-select">
                        <option value="0">All Projects</option>
                        <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= (int)active_project_id() === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Payment Mode</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="payment_mode" id="pmCash" value="cash" checked>
                            <label class="form-check-label" for="pmCash">Cash</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="payment_mode" id="pmBank" value="bank">
                            <label class="form-check-label" for="pmBank">Bank</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 d-none" id="blockBank">
                    <label class="form-label">Bank</label>
                    <select name="bank_id" class="form-select">
                        <option value="">Select Bank</option>
                        <?php foreach ($banks as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?> - <?= e($b['account_no'] ?? '') ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reference</label>
                    <input type="text" name="reference" class="form-control" placeholder="Cheque / Txn id">
                </div>
            </div>

            <div class="row g-3 mt-0">
                <div class="col-md-6">
                    <label class="form-label">Narration</label>
                    <input type="text" name="narration" class="form-control" placeholder="Optional">
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex gap-2 flex-wrap">
        <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Save &amp; Post Voucher</button>
        <button type="button" class="btn btn-outline-secondary" id="btnExpense"><i class="bi bi-receipt me-1"></i>Pay Expense</button>
        <button type="button" class="btn btn-outline-secondary" id="btnOther"><i class="bi bi-journal-text me-1"></i>Pay Other</button>
        <a href="vouchers.php" class="btn btn-light ms-auto">View Vouchers</a>
    </div>
</form>

<script>
(function () {
    var partySearch = document.getElementById('partySearch');
    var partyId = document.getElementById('partyId');
    var partyType = document.getElementById('partyType');
    var searchResults = document.getElementById('searchResults');
    var partyInfoBox = document.getElementById('partyInfoBox');
    var secExpense = document.getElementById('secExpense');
    var secOther = document.getElementById('secOther');
    var pmCash = document.getElementById('pmCash');
    var pmBank = document.getElementById('pmBank');
    var blockBank = document.getElementById('blockBank');
    var debounceTimer = null;

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

    function clearSelection() {
        partyId.value = '';
        partyType.value = '';
        partySearch.value = '';
        searchResults.style.display = 'none';
        searchResults.innerHTML = '';
        partyInfoBox.style.display = 'none';
    }

    document.getElementById('clearParty').addEventListener('click', clearSelection);

    document.getElementById('btnExpense').addEventListener('click', function () {
        clearSelection();
        secExpense.style.display = secExpense.style.display === 'none' ? '' : 'none';
        secOther.style.display = 'none';
        partyType.value = 'expense';
    });

    document.getElementById('btnOther').addEventListener('click', function () {
        clearSelection();
        secOther.style.display = secOther.style.display === 'none' ? '' : 'none';
        secExpense.style.display = 'none';
        partyType.value = 'other';
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
                    secExpense.style.display = 'none';
                    secOther.style.display = 'none';
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
                            partyId.value = item.id;
                            partyType.value = item.type;
                            partySearch.value = item.name || '';
                            searchResults.style.display = 'none';
                            partyInfoBox.style.display = '';
                            secExpense.style.display = 'none';
                            secOther.style.display = 'none';
                            var tl2 = typeLabels[item.type] || { label: item.type, cls: 'bg-secondary' };
                            document.getElementById('partyNameDisplay').textContent = item.name || '';
                            document.getElementById('partyTypeBadge').textContent = tl2.label;
                            document.getElementById('partyTypeBadge').className = 'badge ' + tl2.cls;
                            document.getElementById('partyDetailDisplay').textContent = detail;
                            document.getElementById('partyBalanceDisplay').textContent = 'Loading...';
                            document.getElementById('partyBalanceDisplay').className = 'fs-5 fw-bold';
                            fetch('<?= BASE_URL ?>/pages/ajax.php?action=party_balance&type=' + item.type + '&id=' + item.id + '&_t=' + Date.now())
                                .then(function (r) { return r.json(); })
                                .then(function (b) {
                                    if (b && b.error) {
                                        document.getElementById('partyBalanceDisplay').textContent = b.error;
                                        document.getElementById('partyBalanceDisplay').className = 'fs-5 fw-bold text-danger';
                                        return;
                                    }
                                    var bal = parseFloat(b.balance) || 0;
                                    var color = bal > 0 ? 'text-danger' : (bal < 0 ? 'text-success' : 'text-muted');
                                    document.getElementById('partyBalanceDisplay').textContent = 'Rs. ' + bal.toFixed(2);
                                    document.getElementById('partyBalanceDisplay').className = 'fs-5 fw-bold ' + color;
                                })
                                .catch(function () {
                                    document.getElementById('partyBalanceDisplay').textContent = 'Error loading balance';
                                    document.getElementById('partyBalanceDisplay').className = 'fs-5 fw-bold text-danger';
                                });
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

    function toggleBank() { blockBank.classList.toggle('d-none', !pmBank.checked); }
    pmCash.addEventListener('change', toggleBank);
    pmBank.addEventListener('change', toggleBank);
})();
</script>
<?php include '../includes/footer.php'; ?>

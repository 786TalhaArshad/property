<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Cash Paid';
$active = 'cash_paid';
$canEdit = has_permission('accounting.manage');

$defaultDebit = [
    'customer' => '1100',
    'vendor' => '2000',
    'owner' => '3000',
    'dealer' => '2000',
    'employee' => '2050',
    'contractor' => '2060',
    'expense' => '',
    'other' => '',
];

$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
$banks = db_all("SELECT * FROM banks ORDER BY name");
$customers = db_all("SELECT * FROM customers ORDER BY full_name");
$vendors = db_all("SELECT * FROM vendors ORDER BY business_name");
$owners = db_all("SELECT * FROM owners ORDER BY full_name");
$dealers = db_all("SELECT * FROM dealers ORDER BY full_name");
$employees = db_all("SELECT * FROM employees ORDER BY full_name");
$contractors = db_all("SELECT * FROM contractors ORDER BY full_name");
$expenseAccounts = db_all("SELECT id, code, name FROM chart_of_accounts WHERE account_type = 'expense' ORDER BY code");
$accounts = db_all("SELECT id, code, name, account_type FROM chart_of_accounts ORDER BY code");

if (is_post() && $canEdit) {
    csrf_check();
    $partyType = $_POST['party_type'] ?? '';
    $date = $_POST['payment_date'] ?? date('Y-m-d');
    $amount = (float)($_POST['amount'] ?? 0);
    $narration = trim($_POST['narration'] ?? '');
    $paymentMode = $_POST['payment_mode'] ?? 'cash';
    $bankId = (int)($_POST['bank_id'] ?? 0) ?: null;
    $reference = trim($_POST['reference'] ?? '');
    $accountId = (int)($_POST['account_id'] ?? 0);
    $projectId = (int)($_POST['project_id'] ?? 0) ?: null;

    $valid = in_array($partyType, ['customer', 'vendor', 'owner', 'dealer', 'employee', 'contractor', 'expense', 'other'], true);
    if (!$valid) {
        flash('danger', 'Invalid payment type.');
        redirect('cash_paid.php');
    }
    if ($amount <= 0) {
        flash('danger', 'Enter a valid amount.');
        redirect('cash_paid.php');
    }
    if ($paymentMode === 'bank' && !$bankId) {
        flash('danger', 'Select the bank.');
        redirect('cash_paid.php');
    }

    $partyName = '';
    $vendorId = $ownerId = $dealerId = $employeeId = $customerId = $contractorId = 0;

    if ($partyType === 'customer') {
        $customerId = (int)($_POST['customer_id'] ?? 0);
        if (!$customerId) {
            flash('danger', 'Select the customer.');
            redirect('cash_paid.php');
        }
        $c = db_get("SELECT full_name FROM customers WHERE id = ?", [$customerId]);
        if ($c) $partyName = $c['full_name'];
    } elseif ($partyType === 'vendor') {
        $vendorId = (int)($_POST['vendor_id'] ?? 0);
        if (!$vendorId) {
            flash('danger', 'Select the vendor.');
            redirect('cash_paid.php');
        }
        $v = db_get("SELECT business_name FROM vendors WHERE id = ?", [$vendorId]);
        if ($v) $partyName = $v['business_name'];
    } elseif ($partyType === 'owner') {
        $ownerId = (int)($_POST['owner_id'] ?? 0);
        if (!$ownerId) {
            flash('danger', 'Select the owner.');
            redirect('cash_paid.php');
        }
        $o = db_get("SELECT full_name FROM owners WHERE id = ?", [$ownerId]);
        if ($o) $partyName = $o['full_name'];
    } elseif ($partyType === 'dealer') {
        $dealerId = (int)($_POST['dealer_id'] ?? 0);
        if (!$dealerId) {
            flash('danger', 'Select the dealer.');
            redirect('cash_paid.php');
        }
        $d = db_get("SELECT full_name FROM dealers WHERE id = ?", [$dealerId]);
        if ($d) $partyName = $d['full_name'];
    } elseif ($partyType === 'employee') {
        $employeeId = (int)($_POST['employee_id'] ?? 0);
        if (!$employeeId) {
            flash('danger', 'Select the employee.');
            redirect('cash_paid.php');
        }
        $emp = db_get("SELECT full_name FROM employees WHERE id = ?", [$employeeId]);
        if ($emp) {
            $partyName = $emp['full_name'];
            if (!$accountId) $accountId = employee_payable_account_id($employeeId, $emp['full_name']);
        }
    } elseif ($partyType === 'contractor') {
        $contractorId = (int)($_POST['contractor_id'] ?? 0);
        if (!$contractorId) {
            flash('danger', 'Select the contractor.');
            redirect('cash_paid.php');
        }
        $con = db_get("SELECT full_name FROM contractors WHERE id = ?", [$contractorId]);
        if ($con) {
            $partyName = $con['full_name'];
            if (!$accountId) $accountId = contractor_payable_account_id($contractorId, $con['full_name']);
        }
    } elseif ($partyType === 'expense') {
        $expenseAccount = (int)($_POST['expense_account_id'] ?? 0);
        if (!$expenseAccount) {
            flash('danger', 'Select the expense head.');
            redirect('cash_paid.php');
        }
        $partyName = db_get("SELECT name FROM chart_of_accounts WHERE id = ?", [$expenseAccount])['name'] ?? 'Expense';
        if (!$accountId) $accountId = $expenseAccount;
    }

    $defaultCode = $defaultDebit[$partyType] ?? '';
    if (!$accountId && $defaultCode) $accountId = coa_id_by_code($defaultCode);
    if (!$accountId) {
        flash('danger', 'Select the account to debit.');
        redirect('cash_paid.php');
    }

    $cashAcc = cash_bank_account_id($bankId);
    if (!$cashAcc) {
        flash('danger', 'Cash / bank account not found in chart of accounts.');
        redirect('cash_paid.php');
    }

    $bankName = $bankId ? (db_get("SELECT name FROM banks WHERE id = ?", [$bankId])['name'] ?? 'Bank') : 'Cash';
    $narr = $narration !== '' ? $narration : ($partyName !== '' ? 'Paid to ' . $partyName : 'Cash paid');
    $voucherType = $paymentMode === 'bank' ? 'bank_payment' : 'cash_payment';
    $vid = post_cash_voucher($date, $voucherType, $narr, $projectId, $accountId, $cashAcc, $amount,
        'Paid to ' . ($partyName !== '' ? $partyName : 'N/A'), 'Paid from ' . $bankName);

    if ($partyType === 'vendor') {
        db_exec("INSERT INTO vendor_payments (vendor_id, payment_date, amount, payment_method_id, bank_id, reference, remarks, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$vendorId, $date, $amount, null, $bankId, $reference, $narration]);
        flash('success', 'Vendor payment saved. Voucher ' . $vid . ' posted.');
    } elseif ($partyType === 'dealer') {
        db_exec("INSERT INTO dealer_payments (dealer_id, payment_date, amount, payment_method_id, bank_id, reference, remarks, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$dealerId, $date, $amount, null, $bankId, $reference, $narration]);
        flash('success', 'Dealer payment saved. Voucher ' . $vid . ' posted.');
    } elseif ($partyType === 'owner') {
        $bal = (float)db_get("SELECT COALESCE(MAX(balance),0) b FROM owner_ledger WHERE owner_id = ?", [$ownerId])['b'];
        db_exec("INSERT INTO owner_ledger (owner_id, entry_date, description, debit, credit, balance, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,0,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$ownerId, $date, 'Payment to owner', $amount, $bal - $amount]);
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

<form method="post">
    <?= csrf_field() ?>
    <div class="card">
        <div class="card-header"><i class="bi bi-arrow-up-circle me-2"></i>Payment Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Payment Type</label>
                    <select name="party_type" id="selType" class="form-select" required>
                        <option value="vendor" selected>To Vendor</option>
                        <option value="employee">Employee Salary</option>
                        <option value="contractor">To Contractor</option>
                        <option value="owner">To Owner</option>
                        <option value="dealer">To Dealer / Agent</option>
                        <option value="customer">To Customer (Refund)</option>
                        <option value="expense">Expense / Bill</option>
                        <option value="other">Other / General</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Project</label>
                    <select name="project_id" class="form-select">
                        <option value="0">All Projects</option>
                        <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= (int)active_project_id() === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" data-mask-money required>
                </div>
            </div>

            <div class="row g-3 mt-0 d-none" id="secVendor" data-sec="vendor">
                <div class="col-md-6">
                    <label class="form-label">Vendor</label>
                    <select name="vendor_id" class="form-select">
                        <option value="">Select Vendor</option>
                        <?php foreach ($vendors as $v): ?><option value="<?= $v['id'] ?>"><?= e($v['business_name']) ?></option><?php endforeach; ?>
                    </select>
                    <div class="form-text">Payment vendor ke payments register me save hoti hai.</div>
                </div>
            </div>

            <div class="row g-3 mt-0 d-none" id="secEmployee" data-sec="employee">
                <div class="col-md-6">
                    <label class="form-label">Employee</label>
                    <select name="employee_id" id="selEmployee" class="form-select">
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $emp): ?><option value="<?= $emp['id'] ?>"><?= e($emp['full_name']) ?></option><?php endforeach; ?>
                    </select>
                    <div class="form-text">Salary ledger me 'paid' entry ban jati hai.</div>
                </div>
            </div>

            <div class="row g-3 mt-0 d-none" id="secContractor" data-sec="contractor">
                <div class="col-md-6">
                    <label class="form-label">Contractor</label>
                    <select name="contractor_id" id="selContractor" class="form-select">
                        <option value="">Select Contractor</option>
                        <?php foreach ($contractors as $con): ?><option value="<?= $con['id'] ?>"><?= e($con['full_name']) ?><?= $con['company'] ? ' (' . e($con['company']) . ')' : '' ?></option><?php endforeach; ?>
                    </select>
                    <div class="form-text">Contractor ledger me 'paid' entry ban jati hai.</div>
                </div>
            </div>

            <div class="row g-3 mt-0 d-none" id="secOwner" data-sec="owner">
                <div class="col-md-6">
                    <label class="form-label">Owner</label>
                    <select name="owner_id" class="form-select">
                        <option value="">Select Owner</option>
                        <?php foreach ($owners as $o): ?><option value="<?= $o['id'] ?>"><?= e($o['full_name']) ?></option><?php endforeach; ?>
                    </select>
                    <div class="form-text">Owner ledger me debit ho jayega.</div>
                </div>
            </div>

            <div class="row g-3 mt-0 d-none" id="secDealer" data-sec="dealer">
                <div class="col-md-6">
                    <label class="form-label">Dealer / Agent</label>
                    <select name="dealer_id" class="form-select">
                        <option value="">Select Dealer</option>
                        <?php foreach ($dealers as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['full_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mt-0 d-none" id="secCustomer" data-sec="customer">
                <div class="col-md-6">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-select">
                        <option value="">Select Customer</option>
                        <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['full_name']) ?> (<?= e($c['customer_no']) ?>)</option><?php endforeach; ?>
                    </select>
                    <div class="form-text">Refund / payment back to customer.</div>
                </div>
            </div>

            <div class="row g-3 mt-0 d-none" id="secExpense" data-sec="expense">
                <div class="col-md-6">
                    <label class="form-label">Expense Head</label>
                    <select name="expense_account_id" class="form-select">
                        <option value="">Select Expense Head</option>
                        <?php foreach ($expenseAccounts as $a): ?><option value="<?= $a['id'] ?>"><?= e($a['code']) ?> - <?= e($a['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mt-0">
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
                    <div class="form-text">Is bank ke ledger me entry ho jayegi.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reference</label>
                    <input type="text" name="reference" class="form-control" placeholder="Cheque no / Txn id (optional)">
                </div>
            </div>

            <div class="row g-3 mt-0">
                <div class="col-md-6">
                    <label class="form-label">Account to Debit</label>
                    <select name="account_id" id="selAccount" class="form-select">
                        <option value="0">-- Default per type --</option>
                        <?php
                        $groups = ['asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity', 'income' => 'Income', 'expense' => 'Expenses'];
                        foreach ($groups as $g => $gname): ?>
                        <optgroup label="<?= $gname ?>">
                            <?php foreach ($accounts as $a): if ($a['account_type'] !== $g) continue; ?>
                            <option value="<?= $a['id'] ?>" data-code="<?= $a['code'] ?>"><?= e($a['code']) ?> - <?= e($a['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Default: Vendor/Dealer=Accounts Payable (2000), Owner=Capital (3000), Customer=Accounts Receivable (1100), Employee=Employee Payable (2050), Contractor=Contractor Payable (2060).</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Narration</label>
                    <input type="text" name="narration" class="form-control" placeholder="Optional">
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Save &amp; Post Voucher</button>
        <a href="vouchers.php" class="btn btn-light">View Vouchers</a>
    </div>
</form>

<script>
(function () {
    var sel = document.getElementById('selType');
    var sections = document.querySelectorAll('[data-sec]');
    function show(sec) {
        sections.forEach(function (s) {
            s.classList.toggle('d-none', s.dataset.sec !== sec);
        });
    }
    sel.addEventListener('change', function () { show(sel.value); applyDefaultAccount(); });
    show(sel.value);

    var defaults = <?= json_encode(array_filter(array_map(function ($c) { return $c === '' ? null : coa_id_by_code($c); }, $defaultDebit))) ?>;
    var account = document.getElementById('selAccount');
    function applyDefaultAccount() {
        var id = defaults[sel.value];
        if (id) account.value = id;
        else account.value = '0';
    }
    applyDefaultAccount();

    var pmCash = document.getElementById('pmCash');
    var pmBank = document.getElementById('pmBank');
    var blockBank = document.getElementById('blockBank');
    function toggleBank() {
        blockBank.classList.toggle('d-none', !pmBank.checked);
    }
    pmCash.addEventListener('change', toggleBank);
    pmBank.addEventListener('change', toggleBank);
})();
</script>
<?php include '../includes/footer.php'; ?>

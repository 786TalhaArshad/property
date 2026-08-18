<?php
require_once '../includes/auth.php';
require_login();
require_permission('purchases.manage');
$title = 'Purchase Form';
$active = 'purchases';

$editId = (int)($_GET['id'] ?? 0);
$purchase = null;
$items = [];
if ($editId > 0) {
    $purchase = db_get("SELECT * FROM purchases WHERE id = ?", [$editId]);
    if (!$purchase) { flash('danger', 'Purchase not found.'); redirect('purchases.php'); }
    $items = db_all("SELECT * FROM purchase_items WHERE purchase_id = ? ORDER BY id", [$editId]);
    $title = 'Edit Purchase ' . $purchase['purchase_no'];
} else {
    $title = 'New Purchase';
}

if (is_post()) {
    csrf_check();

    if (isset($_POST['cancel'])) {
        redirect('purchases.php');
    }

    $vendorId = (int)($_POST['vendor_id'] ?? 0);
    $purchaseDate = $_POST['purchase_date'] ?? date('Y-m-d');
    $projectId = (int)($_POST['project_id'] ?? 0) ?: null;
    $narration = trim($_POST['narration'] ?? '');
    $discount = (float)($_POST['discount'] ?? 0);
    $paidAmount = (float)($_POST['paid_amount'] ?? 0);
    $paymentMode = $_POST['payment_mode'] ?? 'cash';
    $bankId = (int)($_POST['bank_id'] ?? 0) ?: null;
    $reference = trim($_POST['reference'] ?? '');

    $descriptions = $_POST['item_description'] ?? [];
    $quantities = $_POST['item_quantity'] ?? [];
    $unitPrices = $_POST['item_unit_price'] ?? [];

    $itemCount = count($descriptions);
    if ($vendorId <= 0) {
        flash('danger', 'Select a vendor.');
        redirect('purchase_form.php' . ($editId ? "?id=$editId" : ''));
    }
    if ($itemCount === 0) {
        flash('danger', 'Add at least one line item.');
        redirect('purchase_form.php' . ($editId ? "?id=$editId" : ''));
    }

    if ($paymentMode === 'bank' && !$bankId) {
        flash('danger', 'Select the bank for bank payment.');
        redirect('purchase_form.php' . ($editId ? "?id=$editId" : ''));
    }

    $totalAmount = 0;
    $lineItems = [];
    for ($i = 0; $i < $itemCount; $i++) {
        $desc = trim($descriptions[$i] ?? '');
        $qty = (float)($quantities[$i] ?? 1);
        $price = (float)($unitPrices[$i] ?? 0);
        if ($desc === '' && $price <= 0) continue;
        $amt = round($qty * $price, 2);
        $totalAmount += $amt;
        $lineItems[] = ['description' => $desc, 'quantity' => $qty, 'unit_price' => $price, 'amount' => $amt];
    }
    $netAmount = $totalAmount - $discount;
    if ($netAmount < 0) $netAmount = 0;
    if ($paidAmount > $netAmount) $paidAmount = $netAmount;

    $status = 'pending';
    if ($paidAmount >= $netAmount && $netAmount > 0) $status = 'paid';
    elseif ($paidAmount > 0) $status = 'partial';

    $oldVoucherId = null;
    $oldPaymentVoucherId = null;
    if ($editId && $purchase) {
        $oldVoucherId = $purchase['voucher_id'] ? (int)$purchase['voucher_id'] : null;
        $oldPaidAmount = (float)$purchase['paid_amount'];
        if ($oldPaidAmount > 0 && $purchase['payment_voucher_id']) {
            $oldPaymentVoucherId = (int)$purchase['payment_voucher_id'];
        }
    }

    if ($editId && $purchase) {
        db_exec("UPDATE purchases SET vendor_id=?, purchase_date=?, project_id=?, narration=?, total_amount=?, discount=?, paid_amount=?, payment_mode=?, bank_id=?, reference=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?",
            [$vendorId, $purchaseDate, $projectId, $narration, $totalAmount, $discount, $paidAmount, $paymentMode, $bankId, $reference, $status, $editId]);
        db_exec("DELETE FROM purchase_items WHERE purchase_id = ?", [$editId]);
        foreach ($lineItems as $li) {
            db_exec("INSERT INTO purchase_items (purchase_id, description, quantity, unit_price, amount, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                [$editId, $li['description'], $li['quantity'], $li['unit_price'], $li['amount']]);
        }
        if ($oldVoucherId) {
            db_exec("DELETE FROM voucher_items WHERE voucher_id = ?", [$oldVoucherId]);
            db_exec("DELETE FROM vouchers WHERE id = ?", [$oldVoucherId]);
        }
        if ($oldPaymentVoucherId) {
            db_exec("DELETE FROM voucher_items WHERE voucher_id = ?", [$oldPaymentVoucherId]);
            db_exec("DELETE FROM vouchers WHERE id = ?", [$oldPaymentVoucherId]);
        }
        $purchaseId = $editId;
        flash('success', 'Purchase updated.');
    } else {
        $purchaseNo = next_number('PUR', 'purchases', 'purchase_no');
        $purchaseId = db_exec("INSERT INTO purchases (purchase_no, vendor_id, purchase_date, project_id, narration, total_amount, discount, paid_amount, payment_mode, bank_id, reference, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$purchaseNo, $vendorId, $purchaseDate, $projectId, $narration, $totalAmount, $discount, $paidAmount, $paymentMode, $bankId, $reference, $status]);
        foreach ($lineItems as $li) {
            db_exec("INSERT INTO purchase_items (purchase_id, description, quantity, unit_price, amount, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                [$purchaseId, $li['description'], $li['quantity'], $li['unit_price'], $li['amount']]);
        }
        flash('success', 'Purchase saved.');
    }

    $purchasesAccId = coa_id_by_code('5700');
    if (!$purchasesAccId) {
        $parentId = coa_id_by_code('5000');
        $purchasesAccId = db_exec("INSERT INTO chart_of_accounts (code, name, account_type, parent_id, opening_balance, created_date, created_time, updated_date, updated_time) VALUES ('5700','Purchases','expense',?,0,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$parentId]);
    }
    $apId = coa_id_by_code('2000');
    $vendorName = db_get("SELECT business_name FROM vendors WHERE id = ?", [$vendorId])['business_name'] ?? 'Vendor';

    $prefix = 'JV';
    $vn = next_number($prefix, 'vouchers', 'voucher_no');
    $vid = db_exec("INSERT INTO vouchers (voucher_no, voucher_date, voucher_type, project_id, narration, status, created_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
        [$vn, $purchaseDate, 'journal', $projectId, 'Purchase: ' . $vendorName, 'posted', $user['id']]);
    db_exec("INSERT INTO voucher_items (voucher_id, account_id, item_description, debit, credit, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,0,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
        [$vid, $purchasesAccId, 'Purchase - ' . $vendorName, $netAmount]);
    db_exec("INSERT INTO voucher_items (voucher_id, account_id, item_description, debit, credit, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,0,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
        [$vid, $apId, 'Accounts Payable - ' . $vendorName, $netAmount]);
    db_exec("UPDATE purchases SET voucher_id = ? WHERE id = ?", [$vid, $purchaseId]);

    $paymentVid = null;
    if ($paidAmount > 0) {
        $cashAcc = cash_bank_account_id($bankId);
        $pVoucherType = $paymentMode === 'bank' ? 'bank_payment' : 'cash_payment';
        $pPrefix = $paymentMode === 'bank' ? 'BP' : 'CP';
        $pVn = next_number($pPrefix, 'vouchers', 'voucher_no');
        $pDesc = $paymentMode === 'bank' ? 'Paid from Bank' : 'Paid from Cash';
        $paymentVid = db_exec("INSERT INTO vouchers (voucher_no, voucher_date, voucher_type, project_id, narration, status, created_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$pVn, $purchaseDate, $pVoucherType, $projectId, 'Payment for purchase: ' . $vendorName, 'posted', $user['id']]);
        db_exec("INSERT INTO voucher_items (voucher_id, account_id, item_description, debit, credit, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,0,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$paymentVid, $apId, 'Paid to ' . $vendorName, $paidAmount]);
        db_exec("INSERT INTO voucher_items (voucher_id, account_id, item_description, debit, credit, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,0,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$paymentVid, $cashAcc, $pDesc, $paidAmount]);
        db_exec("UPDATE purchases SET payment_voucher_id = ? WHERE id = ?", [$paymentVid, $purchaseId]);
    }

    redirect('purchase_view.php?id=' . $purchaseId);
}

$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
$vendors = db_all("SELECT * FROM vendors ORDER BY business_name");
$banks = db_all("SELECT * FROM banks ORDER BY name");
$defaultProject = $editId && $purchase ? $purchase['project_id'] : active_project_id();
$defaultDate = $editId && $purchase ? $purchase['purchase_date'] : date('Y-m-d');

include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="purchases.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><i class="bi bi-bag me-2"></i><?= e($title) ?></h5>
</div>

<form method="post" id="purchaseForm">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-info-circle me-2"></i>Purchase Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Purchase No</label>
                            <input type="text" class="form-control" value="<?= e($purchase['purchase_no'] ?? next_number('PUR', 'purchases', 'purchase_no')) ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="purchase_date" class="form-control" value="<?= e($defaultDate) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Project</label>
                            <select name="project_id" class="form-select">
                                <option value="0">All Projects</option>
                                <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= (int)$defaultProject === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Vendor *</label>
                            <select name="vendor_id" class="form-select" required id="vendorSelect">
                                <option value="">Select Vendor</option>
                                <?php foreach ($vendors as $v): ?>
                                <option value="<?= $v['id'] ?>" <?= (int)($purchase['vendor_id'] ?? 0) === (int)$v['id'] ? 'selected' : '' ?>><?= e($v['business_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Narration</label>
                            <input type="text" name="narration" class="form-control" value="<?= e($purchase['narration'] ?? '') ?>" placeholder="Optional description">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-list-ul me-2"></i>Line Items</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn"><i class="bi bi-plus-lg"></i> Add Row</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0" id="itemsTable">
                            <thead>
                                <tr>
                                    <th style="width:30px">#</th>
                                    <th>Description</th>
                                    <th style="width:100px">Qty</th>
                                    <th style="width:140px">Unit Price</th>
                                    <th style="width:140px">Amount</th>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Subtotal</td>
                                    <td class="fw-bold" id="subtotal">0.00</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end">Discount</td>
                                    <td><input type="number" step="0.01" name="discount" class="form-control form-control-sm text-end" id="discount" value="<?= e($purchase['discount'] ?? 0) ?>"></td>
                                    <td></td>
                                </tr>
                                <tr class="table-success">
                                    <td colspan="4" class="text-end fw-bold">Net Amount</td>
                                    <td class="fw-bold" id="netAmount">0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-credit-card me-2"></i>Payment</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Amount Paid</label>
                        <input type="number" step="0.01" name="paid_amount" class="form-control" id="paidAmount" value="<?= e($purchase['paid_amount'] ?? 0) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Mode</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="payment_mode" id="pmCash" value="cash" <?= ($purchase['payment_mode'] ?? 'cash') === 'cash' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="pmCash">Cash</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="payment_mode" id="pmBank" value="bank" <?= ($purchase['payment_mode'] ?? '') === 'bank' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="pmBank">Bank</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 d-none" id="blockBank">
                        <label class="form-label">Bank</label>
                        <select name="bank_id" class="form-select">
                            <option value="">Select Bank</option>
                            <?php foreach ($banks as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= (int)($purchase['bank_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reference</label>
                        <input type="text" name="reference" class="form-control" value="<?= e($purchase['reference'] ?? '') ?>" placeholder="Cheque / Txn ID">
                    </div>
                </div>
            </div>

            <div class="card mb-3" id="vendorBalanceCard" style="display:none">
                <div class="card-header"><i class="bi bi-calculator me-2"></i>Vendor Balance</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Previous Balance:</span>
                        <strong id="prevBalance">0.00</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>This Purchase:</span>
                        <strong id="thisPurchase">0.00</strong>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Total Due:</span>
                        <strong class="text-danger" id="totalDue">0.00</strong>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-check-lg me-1"></i><?= $editId ? 'Update' : 'Save' ?> Purchase</button>
                <button type="submit" name="cancel" value="1" class="btn btn-light">Cancel</button>
            </div>
        </div>
    </div>
</form>

<script>
(function () {
    var existingItems = <?= json_encode($items) ?>;
    var body = document.getElementById('itemsBody');
    var discountEl = document.getElementById('discount');
    var paidAmountEl = document.getElementById('paidAmount');
    var vendorSel = document.getElementById('vendorSelect');
    var balanceCard = document.getElementById('vendorBalanceCard');
    var pmCash = document.getElementById('pmCash');
    var pmBank = document.getElementById('pmBank');
    var blockBank = document.getElementById('blockBank');
    var rowIdx = 0;
    var prevBalance = 0;

    function recalc() {
        var subtotal = 0;
        body.querySelectorAll('tr').forEach(function (tr) {
            var qty = parseFloat(tr.querySelector('.item-qty').value) || 0;
            var price = parseFloat(tr.querySelector('.item-price').value) || 0;
            var amt = qty * price;
            var amtCell = tr.querySelector('.item-amount');
            if (amtCell) amtCell.textContent = amt.toFixed(2);
            subtotal += amt;
        });
        var disc = parseFloat(discountEl.value) || 0;
        var net = subtotal - disc;
        if (net < 0) net = 0;
        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('netAmount').textContent = net.toFixed(2);
        document.getElementById('thisPurchase').textContent = net.toFixed(2);
        var totalDue = prevBalance + net;
        document.getElementById('totalDue').textContent = totalDue.toFixed(2);
    }

    function addRow(data) {
        data = data || {};
        var tr = document.createElement('tr');
        tr.dataset.idx = rowIdx++;
        var initialAmt = (data.amount ? parseFloat(data.amount) : ((parseFloat(data.quantity) || 1) * (parseFloat(data.unit_price) || 0)));
        tr.innerHTML =
            '<td class="pt-2">' + tr.dataset.idx + '</td>' +
            '<td><input type="text" name="item_description[]" class="form-control form-control-sm" value="' + (data.description || '') + '"></td>' +
            '<td><input type="number" step="0.01" name="item_quantity[]" class="form-control form-control-sm item-qty" value="' + (data.quantity || 1) + '"></td>' +
            '<td><input type="number" step="0.01" name="item_unit_price[]" class="form-control form-control-sm item-price" value="' + (data.unit_price || 0) + '"></td>' +
            '<td class="item-amount pt-2 fw-medium text-end">' + initialAmt.toFixed(2) + '</td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>';
        body.appendChild(tr);
        tr.querySelector('.item-qty').addEventListener('input', recalc);
        tr.querySelector('.item-price').addEventListener('input', recalc);
        recalc();
    }

    if (existingItems.length > 0) {
        existingItems.forEach(function (it) { addRow(it); });
    } else {
        addRow();
    }

    document.getElementById('addItemBtn').addEventListener('click', function () { addRow(); });

    body.addEventListener('click', function (e) {
        var btn = e.target.closest('.remove-row');
        if (btn) { btn.closest('tr').remove(); recalc(); }
    });

    discountEl.addEventListener('input', recalc);
    paidAmountEl.addEventListener('input', recalc);

    function toggleBank() { blockBank.classList.toggle('d-none', !pmBank.checked); }
    pmCash.addEventListener('change', toggleBank);
    pmBank.addEventListener('change', toggleBank);
    toggleBank();

    vendorSel.addEventListener('change', function () {
        var vid = vendorSel.value;
        if (!vid) { balanceCard.style.display = 'none'; prevBalance = 0; recalc(); return; }
        fetch('<?= BASE_URL ?>/pages/ajax.php?action=vendor_balance&id=' + vid)
            .then(function (r) { return r.json(); })
            .then(function (d) {
                prevBalance = parseFloat(d.balance) || 0;
                document.getElementById('prevBalance').textContent = prevBalance.toFixed(2);
                balanceCard.style.display = prevBalance > 0 ? '' : 'none';
                recalc();
            });
    });
    if (vendorSel.value) vendorSel.dispatchEvent(new Event('change'));
})();
</script>

<?php include '../includes/footer.php'; ?>

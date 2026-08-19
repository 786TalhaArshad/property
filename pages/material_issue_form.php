<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.manage');
$title = 'Material Issue Form';
$active = 'material_issues';

$editId = (int)($_GET['id'] ?? 0);
$mi = null;
$existingItems = [];
if ($editId > 0) {
    $mi = db_get("SELECT * FROM material_issues WHERE id = ?", [$editId]);
    if (!$mi) { flash('danger', 'Material issue not found.'); redirect('material_issues.php'); }
    $existingItems = db_all("SELECT mii.*, p.name AS product_name FROM material_issue_items mii JOIN products p ON p.id = mii.product_id WHERE mii.material_issue_id = ? ORDER BY mii.id", [$editId]);
    $title = 'Edit Material Issue ' . $mi['issue_no'];
} else {
    $title = 'New Material Issue';
}

if (is_post()) {
    csrf_check();
    if (isset($_POST['cancel'])) redirect('material_issues.php');

    $issueDate = $_POST['issue_date'] ?? date('Y-m-d');
    $projectId = (int)($_POST['project_id'] ?? 0);
    $contractorId = (int)($_POST['contractor_id'] ?? 0);
    $narration = trim($_POST['narration'] ?? '');

    $productIds = $_POST['item_product_id'] ?? [];
    $quantities = $_POST['item_quantity'] ?? [];
    $unitCosts = $_POST['item_unit_price'] ?? [];

    $itemCount = count($productIds);
    if ($projectId <= 0) { flash('danger', 'Select a project.'); redirect('material_issue_form.php' . ($editId ? "?id=$editId" : '')); }
    if ($contractorId <= 0) { flash('danger', 'Select a contractor.'); redirect('material_issue_form.php' . ($editId ? "?id=$editId" : '')); }
    if ($itemCount === 0) { flash('danger', 'Add at least one item.'); redirect('material_issue_form.php' . ($editId ? "?id=$editId" : '')); }

    $lineItems = [];
    for ($i = 0; $i < $itemCount; $i++) {
        $pid = (int)($productIds[$i] ?? 0);
        $qty = (float)($quantities[$i] ?? 0);
        $cost = (float)($unitCosts[$i] ?? 0);
        if ($pid <= 0 || $qty <= 0) continue;
        $product = db_get("SELECT stock_qty, avg_cost FROM products WHERE id = ?", [$pid]);
        if (!$product) continue;
        $available = (float)$product['stock_qty'];
        $effectiveQty = $editId ? $qty : $qty;
        if ($editId) {
            $oldItem = null;
            foreach ($existingItems as $ei) {
                if ((int)$ei['product_id'] === $pid) { $oldItem = $ei; break; }
            }
            if ($oldItem) $available += (float)$oldItem['quantity'];
        }
        if ($qty > $available) {
            flash('danger', 'Insufficient stock for a product. Available: ' . $available);
            redirect('material_issue_form.php' . ($editId ? "?id=$editId" : ''));
        }
        $totalCost = round($qty * $cost, 2);
        $lineItems[] = ['product_id' => $pid, 'quantity' => $qty, 'unit_cost' => $cost, 'total_cost' => $totalCost];
    }

    $totalAmount = 0;
    foreach ($lineItems as $li) $totalAmount += $li['total_cost'];

    if ($editId && $mi) {
        $oldItems = db_all("SELECT product_id, quantity, unit_cost FROM material_issue_items WHERE material_issue_id = ?", [$editId]);
        foreach ($oldItems as $oi) {
            stock_adjust($oi['product_id'], 'purchase', (float)$oi['quantity'], (float)$oi['unit_cost'], 'material_issue', $editId);
        }
        if ($mi['voucher_id']) {
            db_exec("DELETE FROM voucher_items WHERE voucher_id = ?", [$mi['voucher_id']]);
            db_exec("DELETE FROM vouchers WHERE id = ?", [$mi['voucher_id']]);
        }
        db_exec("UPDATE material_issues SET issue_date=?, project_id=?, contractor_id=?, narration=?, total_amount=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?",
            [$issueDate, $projectId, $contractorId, $narration, $totalAmount, $editId]);
        db_exec("DELETE FROM material_issue_items WHERE material_issue_id = ?", [$editId]);
        foreach ($lineItems as $li) {
            db_exec("INSERT INTO material_issue_items (material_issue_id, product_id, quantity, unit_cost, total_cost, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                [$editId, $li['product_id'], $li['quantity'], $li['unit_cost'], $li['total_cost']]);
            stock_adjust($li['product_id'], 'issue', $li['quantity'], $li['unit_cost'], 'material_issue', $editId, $projectId, $contractorId);
        }
        $issueId = $editId;
        flash('success', 'Material issue updated.');
    } else {
        $issueNo = next_number('MISS', 'material_issues', 'issue_no');
        $issueId = db_exec("INSERT INTO material_issues (issue_no, issue_date, project_id, contractor_id, narration, total_amount, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$issueNo, $issueDate, $projectId, $contractorId, $narration, $totalAmount]);
        foreach ($lineItems as $li) {
            db_exec("INSERT INTO material_issue_items (material_issue_id, product_id, quantity, unit_cost, total_cost, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                [$issueId, $li['product_id'], $li['quantity'], $li['unit_cost'], $li['total_cost']]);
            stock_adjust($li['product_id'], 'issue', $li['quantity'], $li['unit_cost'], 'material_issue', $issueId, $projectId, $contractorId);
        }
        flash('success', 'Material issue created. Stock updated.');
    }

    $conExpAcc = coa_id_by_code('5600');
    if (!$conExpAcc) {
        $parentId = coa_id_by_code('5000');
        $conExpAcc = db_exec("INSERT INTO chart_of_accounts (code, name, account_type, parent_id, opening_balance, created_date, created_time, updated_date, updated_time) VALUES ('5600','Construction Expense','expense',?,0,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$parentId]);
    }
    $stockAcc = coa_id_by_code('1200');
    if (!$stockAcc) {
        $stockAcc = db_exec("INSERT INTO chart_of_accounts (code, name, account_type, parent_id, opening_balance, created_date, created_time, updated_date, updated_time) VALUES ('1200','Stock in Hand','asset',0,0,CURDATE(),CURTIME(),CURDATE(),CURTIME())");
    }
    $projectName = db_get("SELECT name FROM projects WHERE id = ?", [$projectId])['name'] ?? 'Project';
    $contractorName = db_get("SELECT full_name FROM contractors WHERE id = ?", [$contractorId])['full_name'] ?? 'Contractor';
    $narrationText = 'Material issued to ' . $contractorName . ' for ' . $projectName;
    $vn = next_number('JV', 'vouchers', 'voucher_no');
    $vid = db_exec("INSERT INTO vouchers (voucher_no, voucher_date, voucher_type, project_id, narration, status, created_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
        [$vn, $issueDate, 'journal', $projectId, $narrationText, 'posted', $user['id']]);
    db_exec("INSERT INTO voucher_items (voucher_id, account_id, item_description, debit, credit, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,0,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
        [$vid, $conExpAcc, $narrationText, $totalAmount]);
    db_exec("INSERT INTO voucher_items (voucher_id, account_id, item_description, debit, credit, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,0,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
        [$vid, $stockAcc, $narrationText, $totalAmount]);
    db_exec("UPDATE material_issues SET voucher_id = ? WHERE id = ?", [$vid, $issueId]);

    redirect('material_issue_view.php?id=' . $issueId);
}

$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
$contractors = db_all("SELECT * FROM contractors WHERE status = 1 ORDER BY full_name");
$products = db_all("SELECT id, product_no, name, unit, stock_qty, avg_cost FROM products WHERE status = 1 AND stock_qty > 0 ORDER BY name");
$defaultProject = $editId && $mi ? $mi['project_id'] : active_project_id();
$defaultDate = $editId && $mi ? $mi['issue_date'] : date('Y-m-d');

include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="material_issues.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i><?= e($title) ?></h5>
</div>

<form method="post" id="miForm">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-info-circle me-2"></i>Issue Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Issue No</label>
                            <input type="text" class="form-control" value="<?= e($mi['issue_no'] ?? next_number('MISS', 'material_issues', 'issue_no')) ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="issue_date" class="form-control" value="<?= e($defaultDate) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Project *</label>
                            <select name="project_id" class="form-select" required>
                                <option value="">Select Project</option>
                                <?php foreach ($projects as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= (int)$defaultProject === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Contractor *</label>
                            <select name="contractor_id" class="form-select" required>
                                <option value="">Select Contractor</option>
                                <?php foreach ($contractors as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= (int)($mi['contractor_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Narration</label>
                            <input type="text" name="narration" class="form-control" value="<?= e($mi['narration'] ?? '') ?>" placeholder="Optional">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-list-ul me-2"></i>Items to Issue</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn"><i class="bi bi-plus-lg"></i> Add Row</button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0" id="itemsTable">
                            <thead>
                                <tr>
                                    <th style="width:30px">#</th>
                                    <th>Product</th>
                                    <th style="width:100px">In Stock</th>
                                    <th style="width:100px">Qty</th>
                                    <th style="width:140px">Unit Cost</th>
                                    <th style="width:140px">Amount</th>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody"></tbody>
                            <tfoot>
                                <tr class="table-success">
                                    <td colspan="5" class="text-end fw-bold">Total Amount</td>
                                    <td class="fw-bold" id="totalAmount">0.00</td>
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
                <div class="card-header"><i class="bi bi-calculator me-2"></i>Summary</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2"><span>Items:</span><strong id="summaryCount">0</strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Total Amount:</span><strong id="summaryTotal">0.00</strong></div>
                    <hr class="my-2">
                    <p class="small text-muted mb-0">Journal: Dr Construction Expense (5600) / Cr Stock in Hand (1200)</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-check-lg me-1"></i><?= $editId ? 'Update' : 'Save' ?> Issue</button>
                <button type="submit" name="cancel" value="1" class="btn btn-light">Cancel</button>
            </div>
        </div>
    </div>
</form>

<script>
(function () {
    var existingItems = <?= json_encode($existingItems) ?>;
    var products = <?= json_encode($products) ?>;
    var body = document.getElementById('itemsBody');
    var rowIdx = 0;

    function recalc() {
        var total = 0, count = 0;
        body.querySelectorAll('tr').forEach(function (tr) {
            var qty = parseFloat(tr.querySelector('.item-qty').value) || 0;
            var cost = parseFloat(tr.querySelector('.item-cost').value) || 0;
            var amt = qty * cost;
            var amtCell = tr.querySelector('.item-amount');
            if (amtCell) amtCell.textContent = amt.toFixed(2);
            total += amt;
            if (qty > 0) count++;
        });
        document.getElementById('totalAmount').textContent = total.toFixed(2);
        document.getElementById('summaryCount').textContent = count;
        document.getElementById('summaryTotal').textContent = total.toFixed(2);
    }

    function addRow(data) {
        data = data || {};
        var tr = document.createElement('tr');
        tr.dataset.idx = rowIdx++;
        var prodOpts = '<option value="">-- Select --</option>';
        products.forEach(function(p) {
            var sel = (data.product_id && parseInt(data.product_id) === p.id) ? ' selected' : '';
            prodOpts += '<option value="' + p.id + '" data-stock="' + p.stock_qty + '" data-cost="' + p.avg_cost + '"' + sel + '>' + p.name + ' [' + p.stock_qty + ' ' + (p.unit || '') + ']</option>';
        });
        var initialAmt = (data.quantity || 0) * (data.unit_cost || 0);
        tr.innerHTML =
            '<td class="pt-2">' + tr.dataset.idx + '</td>' +
            '<td><select name="item_product_id[]" class="form-select form-select-sm item-product">' + prodOpts + '</select></td>' +
            '<td class="item-stock pt-2 text-muted small">' + (data.product_id ? '' : '-') + '</td>' +
            '<td><input type="number" step="0.01" name="item_quantity[]" class="form-control form-control-sm item-qty" value="' + (data.quantity || '') + '" min="0.01"></td>' +
            '<td><input type="number" step="0.01" name="item_unit_price[]" class="form-control form-control-sm item-cost" value="' + (data.unit_cost || 0) + '"></td>' +
            '<td class="item-amount pt-2 fw-medium text-end">' + initialAmt.toFixed(2) + '</td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x"></i></button></td>';
        body.appendChild(tr);

        var prodSel = tr.querySelector('.item-product');
        var stockCell = tr.querySelector('.item-stock');
        prodSel.addEventListener('change', function () {
            var opt = this.options[this.selectedIndex];
            if (this.value) {
                var stock = parseFloat(opt.dataset.stock) || 0;
                var cost = parseFloat(opt.dataset.cost) || 0;
                stockCell.textContent = stock + ' in stock';
                tr.querySelector('.item-cost').value = cost > 0 ? cost : '';
                recalc();
            } else {
                stockCell.textContent = '-';
            }
        });
        if (data.product_id) prodSel.dispatchEvent(new Event('change'));
        tr.querySelector('.item-qty').addEventListener('input', recalc);
        tr.querySelector('.item-cost').addEventListener('input', recalc);
        recalc();
    }

    if (existingItems.length > 0) {
        existingItems.forEach(function (it) { addRow({product_id: it.product_id, quantity: it.quantity, unit_cost: it.unit_cost}); });
    } else {
        addRow();
    }

    document.getElementById('addItemBtn').addEventListener('click', function () { addRow(); });
    body.addEventListener('click', function (e) {
        var btn = e.target.closest('.remove-row');
        if (btn) { btn.closest('tr').remove(); recalc(); }
    });
})();
</script>

<?php include '../includes/footer.php'; ?>

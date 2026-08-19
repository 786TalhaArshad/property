<?php
require_once '../includes/auth.php';
require_login();
require_permission('vendors.view');
$title = 'Vendor Details';
$active = 'vendors';
$canEdit = has_permission('vendors.manage');

$id = (int)($_GET['id'] ?? 0);
$vendor = db_get("SELECT v.*, ci.name AS city_name FROM vendors v LEFT JOIN cities ci ON ci.id = v.city_id WHERE v.id = ?", [$id]);
if (!$vendor) {
    flash('danger', 'Vendor not found.');
    redirect('vendors.php');
}

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'payment_add') {
        $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
        $amount = (float)($_POST['amount'] ?? 0);
        $payment_method_id = (int)($_POST['payment_method_id'] ?? 0) ?: null;
        $bank_id = (int)($_POST['bank_id'] ?? 0) ?: null;
        $reference = trim($_POST['reference'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');
        if ($amount <= 0) {
            flash('danger', 'Enter a valid amount.');
        } else {
            $cashAcc = cash_bank_account_id($bank_id);
            $bankName = $bank_id ? (db_get("SELECT name FROM banks WHERE id = ?", [$bank_id])['name'] ?? 'Bank') : 'Cash';
            $partyName = $vendor['business_name'] ?? 'Vendor';
            $narr = 'Paid to ' . $partyName;
            $voucherType = $bank_id ? 'bank_payment' : 'cash_payment';
            $vid = post_cash_voucher($payment_date, $voucherType, $narr, null, coa_id_by_code('2000'), $cashAcc, $amount,
                'Paid to ' . $partyName, 'Paid from ' . $bankName);
            db_exec("INSERT INTO vendor_payments (vendor_id, payment_date, amount, payment_method_id, bank_id, reference, remarks, voucher_id, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$id, $payment_date, $amount, $payment_method_id, $bank_id, $reference, $remarks, $vid]);
            flash('success', 'Vendor payment recorded. Voucher #' . $vid . ' posted.');
        }
    } elseif ($action === 'payment_delete') {
        $payId = (int)($_POST['id'] ?? 0);
        $pay = db_get("SELECT voucher_id FROM vendor_payments WHERE id = ? AND vendor_id = ?", [$payId, $id]);
        if ($pay) {
            if ($pay['voucher_id']) {
                db_exec("DELETE FROM voucher_items WHERE voucher_id = ?", [$pay['voucher_id']]);
                db_exec("DELETE FROM vouchers WHERE id = ?", [$pay['voucher_id']]);
            }
            db_exec("DELETE FROM vendor_payments WHERE id = ?", [$payId]);
            flash('success', 'Payment deleted.');
        }
    }
    redirect('vendor_view.php?id=' . $id);
}

$ledgerStart = trim($_GET['start_date'] ?? '');
$ledgerEnd = trim($_GET['end_date'] ?? '');
$project_id = (int)($_GET['project_id'] ?? active_project_id());

$payments = db_all("SELECT vp.*, pm.name AS method_name
                    FROM vendor_payments vp
                    LEFT JOIN payment_methods pm ON pm.id = vp.payment_method_id
                    WHERE vp.vendor_id = ?
                    ORDER BY vp.payment_date DESC", [$id]);
$paymentMethods = db_all("SELECT * FROM payment_methods ORDER BY name");
$banks = db_all("SELECT * FROM banks ORDER BY name");

$purchases = db_all("SELECT p.*,
    (SELECT COALESCE(SUM(pi.amount),0) FROM purchase_items pi WHERE pi.purchase_id = p.id) AS items_total,
    pr.name AS project_name,
    COALESCE(p.project_id, 0) AS eff_project_id
    FROM purchases p
    LEFT JOIN projects pr ON pr.id = p.project_id
    WHERE p.vendor_id = ?
    ORDER BY p.purchase_date DESC", [$id]);

$vendorProjects = db_all("SELECT pr.id, pr.name, pr.status,
                          COUNT(DISTINCT p.id) AS purchases_count,
                          SUM(p.total_amount) AS total_amount
                          FROM purchases p
                          JOIN projects pr ON pr.id = p.project_id
                          WHERE p.vendor_id = ?
                          GROUP BY pr.id, pr.name, pr.status
                          ORDER BY pr.name", [$id]);

$ledPurchases = [];
foreach ($purchases as $pu) {
    if ($ledgerStart !== '' && $pu['purchase_date'] < $ledgerStart) continue;
    if ($ledgerEnd !== '' && $pu['purchase_date'] > $ledgerEnd) continue;
    if ($project_id > 0 && (int)$pu['eff_project_id'] !== $project_id) continue;
    $ledPurchases[] = $pu;
}
$ledPayments = [];
foreach ($payments as $p) {
    if ($ledgerStart !== '' && $p['payment_date'] < $ledgerStart) continue;
    if ($ledgerEnd !== '' && $p['payment_date'] > $ledgerEnd) continue;
    $ledPayments[] = $p;
}

$totalPurchases = 0.0;
$totalPaid = 0.0;
foreach ($ledPurchases as $pu) {
    $totalPurchases += (float)$pu['total_amount'];
}
foreach ($ledPayments as $p) {
    $totalPaid += (float)$p['amount'];
}

$openingBalance = 0.0;
if ($ledgerStart !== '') {
    $op = (float)db_get("SELECT COALESCE(SUM(total_amount),0) amt
                         FROM purchases
                         WHERE vendor_id = ? AND purchase_date < ?
                         AND (? = 0 OR project_id = ?)",
        [$id, $ledgerStart, $project_id, $project_id])['amt'];
    $oy = (float)db_get("SELECT COALESCE(SUM(amount),0) amt
                         FROM vendor_payments
                         WHERE vendor_id = ? AND payment_date < ?",
        [$id, $ledgerStart])['amt'];
    $openingBalance = $op - $oy;
}

$balance = $openingBalance + $totalPurchases - $totalPaid;

include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="vendors.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= e($vendor['business_name']) ?></h5>
    <span class="badge bg-light text-dark border"><?= e($vendor['vendor_no']) ?></span>
    <?= $vendor['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-bag"></i></div><div><div class="stat-label">PURCHASES</div><div class="stat-value"><?= count($purchases) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-receipt"></i></div><div><div class="stat-label">PURCHASE AMOUNT</div><div class="stat-value"><?= fmt_money($totalPurchases) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-coin"></i></div><div><div class="stat-label">TOTAL PAID</div><div class="stat-value"><?= fmt_money($totalPaid) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card <?= $balance > 0 ? 'bg-grad-orange' : 'bg-grad-cyan' ?>"><div class="stat-body"><div class="stat-icon"><i class="bi bi-wallet2"></i></div><div><div class="stat-label">OUTSTANDING</div><div class="stat-value"><?= fmt_money($balance) ?></div></div></div></div></div>
</div>

<ul class="nav nav-pills mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#vProfile">Profile</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#vPurchases">Purchases (<?= count($purchases) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#vProjects">Projects (<?= count($vendorProjects) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#vPayments">Payments (<?= count($payments) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#vLedger">Ledger</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="vProfile">
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3"><div class="text-muted small">Vendor No</div><div class="fw-medium"><?= e($vendor['vendor_no']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Business Name</div><div class="fw-medium"><?= e($vendor['business_name'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Contact Person</div><div class="fw-medium"><?= e($vendor['contact_person'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">CNIC</div><div class="fw-medium"><?= e($vendor['cnic'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Phone</div><div class="fw-medium"><?= e($vendor['phone'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">WhatsApp</div><div class="fw-medium"><?= e($vendor['whatsapp'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Email</div><div class="fw-medium"><?= e($vendor['email'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">City</div><div class="fw-medium"><?= e($vendor['city_name'] ?? '-') ?></div></div>
                    <div class="col-md-6"><div class="text-muted small">Address</div><div class="fw-medium"><?= e($vendor['address'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Bank Account Title</div><div class="fw-medium"><?= e($vendor['bank_account_title'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Bank Account No</div><div class="fw-medium"><?= e($vendor['bank_account_no'] ?? '-') ?></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="vPurchases">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Purchase</th><th>Date</th><th>Project</th><th class="text-end">Amount</th><th class="text-end">Paid</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($purchases as $pu): ?>
                            <tr>
                                <td><a href="purchase_view.php?id=<?= $pu['id'] ?>"><?= e($pu['purchase_no']) ?></a></td>
                                <td><?= fmt_date($pu['purchase_date']) ?></td>
                                <td><?= e($pu['project_name'] ?? '-') ?></td>
                                <td class="text-end"><?= fmt_money($pu['total_amount']) ?></td>
                                <td class="text-end"><?= fmt_money($pu['paid_amount']) ?></td>
                                <td><?= status_badge($pu['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$purchases): ?><tr><td colspan="6" class="text-center text-muted py-4">No purchases yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="vProjects">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Project</th><th>Purchases</th><th class="text-end">Total Amount</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($vendorProjects as $pj): ?>
                            <tr>
                                <td><a href="project_view.php?id=<?= $pj['id'] ?>"><?= e($pj['name']) ?></a></td>
                                <td><?= (int)$pj['purchases_count'] ?></td>
                                <td class="text-end"><?= fmt_money((float)$pj['total_amount']) ?></td>
                                <td class="text-end">
                                    <a href="vendor_view.php?id=<?= $id ?>&project_id=<?= $pj['id'] ?>#vLedger" class="btn btn-sm btn-outline-primary"><i class="bi bi-book"></i> Ledger</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$vendorProjects): ?><tr><td colspan="4" class="text-center text-muted py-4">No projects yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="vPayments">
        <div class="card">
            <div class="card-header"><i class="bi bi-cash-coin me-2"></i>Vendor Payments</div>
            <div class="card-body">
                <?php if ($canEdit): ?>
                <form method="post" class="row g-2 mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="payment_add">
                    <div class="col-md-3"><input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                    <div class="col-md-3"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required data-mask-money></div>
                    <div class="col-md-3">
                        <select name="payment_method_id" class="form-select">
                            <option value="">Method</option>
                            <?php foreach ($paymentMethods as $pm): ?><option value="<?= $pm['id'] ?>"><?= e($pm['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="bank_id" class="form-select">
                            <option value="">Bank</option>
                            <?php foreach ($banks as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6"><input type="text" name="reference" class="form-control" placeholder="Reference"></div>
                    <div class="col-md-6"><input type="text" name="remarks" class="form-control" placeholder="Remarks"></div>
                    <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Pay</button></div>
                </form>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Reference</th><th>Remarks</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><?= fmt_date($p['payment_date']) ?></td>
                                <td><?= fmt_money($p['amount']) ?></td>
                                <td class="small"><?= e($p['method_name'] ?? '-') ?></td>
                                <td class="small"><?= e($p['reference'] ?? '-') ?></td>
                                <td class="small"><?= e($p['remarks'] ?? '-') ?></td>
                                <td class="text-end">
                                    <?php if ($canEdit): ?>
                                    <form method="post" class="d-inline" data-confirm="Delete this payment?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="payment_delete">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$payments): ?><tr><td colspan="6" class="text-center text-muted py-4">No payments yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="vLedger">
        <div class="card">
            <div class="card-body">
                <form method="get" action="vendor_view.php#vLedger" class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <select name="project_id" class="form-select form-select-sm" style="max-width:220px">
                        <option value="0">All Projects</option>
                        <?php foreach ($vendorProjects as $pj): ?>
                            <option value="<?= $pj['id'] ?>" <?= $project_id === (int)$pj['id'] ? 'selected' : '' ?>><?= e($pj['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="input-group input-group-sm" style="max-width:170px">
                        <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                        <input type="date" name="start_date" class="form-control" value="<?= e($ledgerStart) ?>">
                    </div>
                    <div class="input-group input-group-sm" style="max-width:170px">
                        <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                        <input type="date" name="end_date" class="form-control" value="<?= e($ledgerEnd) ?>">
                    </div>
                    <button class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="vendor_view.php?id=<?= $id ?>#vLedger" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
                    <a href="vendor_ledger_print.php?id=<?= $id ?>&project_id=<?= $project_id ?>&start_date=<?= e($ledgerStart) ?>&end_date=<?= e($ledgerEnd) ?>" class="btn btn-outline-secondary btn-sm ms-auto" target="_blank"><i class="bi bi-printer me-1"></i> Print</a>
                </form>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Description</th><th>Project</th><th>Credit (Purchase)</th><th>Debit (Paid)</th><th>Balance</th></tr></thead>
                        <tbody>
                        <?php
                        $bal = $openingBalance;
                        if ($ledgerStart !== '' || $ledgerEnd !== '') {
                            echo '<tr><td>' . fmt_date($ledgerStart) . '</td><td>Opening Balance</td><td>-</td><td>-</td><td>-</td><td>' . fmt_money($bal) . '</td></tr>';
                        }
                        foreach ($ledPurchases as $pu) {
                            $bal += (float)$pu['total_amount'];
                            echo '<tr><td>' . fmt_date($pu['purchase_date']) . '</td><td>Purchase - ' . e($pu['purchase_no']) . '</td><td>' . e($pu['project_name'] ?? '-') . '</td><td>' . fmt_money((float)$pu['total_amount']) . '</td><td>-</td><td>' . fmt_money($bal) . '</td></tr>';
                        }
                        foreach ($ledPayments as $p) {
                            $bal -= (float)$p['amount'];
                            $desc = 'Payment';
                            if ($p['method_name']) $desc .= ' - ' . e($p['method_name']);
                            if ($p['reference']) $desc .= ' (' . e($p['reference']) . ')';
                            if ($p['remarks']) $desc .= ' [' . e($p['remarks']) . ']';
                            echo '<tr><td>' . fmt_date($p['payment_date']) . '</td><td>' . $desc . '</td><td>-</td><td>-</td><td>' . fmt_money((float)$p['amount']) . '</td><td>' . fmt_money($bal) . '</td></tr>';
                        }
                        if (!$ledPurchases && !$ledPayments && $ledgerStart === '' && $ledgerEnd === '') {
                            echo '<tr><td colspan="6" class="text-center text-muted py-4">No ledger entries yet</td></tr>';
                        }
                        ?>
                        </tbody>
                        <tfoot>
                        <tr class="table-light">
                            <td colspan="5" class="text-end fw-bold"><?= ($ledgerStart !== '' || $ledgerEnd !== '') ? 'Closing Balance' : 'Outstanding' ?></td>
                            <td class="fw-bold"><?= fmt_money($bal) ?></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    if (location.hash) {
        $('.nav-pills .nav-link[data-bs-target="' + location.hash.replace(/[^a-zA-Z0-9_#]/g, '') + '"]').tab('show');
    }
});
</script>

<?php include '../includes/footer.php'; ?>

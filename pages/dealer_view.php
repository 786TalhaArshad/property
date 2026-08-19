<?php
require_once '../includes/auth.php';
require_login();
require_permission('dealers.view');
$title = 'Dealer Details';
$active = 'dealers';
$canEdit = has_permission('dealers.manage');

$id = (int)($_GET['id'] ?? 0);
$dealer = db_get("SELECT * FROM dealers WHERE id = ?", [$id]);
if (!$dealer) {
    flash('danger', 'Dealer not found.');
    redirect('dealers.php');
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
            $partyName = $dealer['business_name'] ?? $dealer['name'] ?? 'Dealer';
            $narr = 'Paid to ' . $partyName;
            $voucherType = $bank_id ? 'bank_payment' : 'cash_payment';
            $vid = post_cash_voucher($payment_date, $voucherType, $narr, null, coa_id_by_code('2000'), $cashAcc, $amount,
                'Paid to ' . $partyName, 'Paid from ' . $bankName);
            db_exec("INSERT INTO dealer_payments (dealer_id, payment_date, amount, payment_method_id, bank_id, reference, remarks, voucher_id, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$id, $payment_date, $amount, $payment_method_id, $bank_id, $reference, $remarks, $vid]);
            flash('success', 'Commission payment recorded. Voucher #' . $vid . ' posted.');
        }
    } elseif ($action === 'payment_delete') {
        $payId = (int)($_POST['id'] ?? 0);
        $pay = db_get("SELECT voucher_id FROM dealer_payments WHERE id = ? AND dealer_id = ?", [$payId, $id]);
        if ($pay) {
            if ($pay['voucher_id']) {
                db_exec("DELETE FROM voucher_items WHERE voucher_id = ?", [$pay['voucher_id']]);
                db_exec("DELETE FROM vouchers WHERE id = ?", [$pay['voucher_id']]);
            }
            db_exec("DELETE FROM dealer_payments WHERE id = ?", [$payId]);
            flash('success', 'Payment deleted.');
        }
    }
    redirect('dealer_view.php?id=' . $id);
}

$ledgerStart = trim($_GET['start_date'] ?? '');
$ledgerEnd = trim($_GET['end_date'] ?? '');
$project_id = (int)($_GET['project_id'] ?? active_project_id());

$bookings = db_all("SELECT b.*, c.full_name AS customer_name, p.property_no,
                    p.project_id, COALESCE(p.project_id, 0) AS eff_project_id,
                    pr.name AS project_name
                    FROM bookings b
                    JOIN customers c ON c.id = b.customer_id
                    JOIN properties p ON p.id = b.property_id
                    LEFT JOIN projects pr ON pr.id = p.project_id
                    WHERE b.dealer_id = ? AND b.status <> 'cancelled'
                    ORDER BY b.id DESC", [$id]);
$payments = db_all("SELECT dp.*, pm.name AS method_name
                    FROM dealer_payments dp
                    LEFT JOIN payment_methods pm ON pm.id = dp.payment_method_id
                    WHERE dp.dealer_id = ?
                    ORDER BY dp.payment_date DESC", [$id]);
$paymentMethods = db_all("SELECT * FROM payment_methods ORDER BY name");
$banks = db_all("SELECT * FROM banks ORDER BY name");

$dealerProjects = db_all("SELECT pr.id, pr.name, pr.status,
                          COUNT(DISTINCT b.id) AS bookings_count,
                          SUM(b.total_price) AS total_sales
                          FROM bookings b
                          JOIN properties p ON p.id = b.property_id
                          JOIN projects pr ON pr.id = p.project_id
                          WHERE b.dealer_id = ? AND b.status <> 'cancelled'
                          GROUP BY pr.id, pr.name, pr.status
                          ORDER BY pr.name", [$id]);

$ledBookings = [];
foreach ($bookings as $b) {
    if ($ledgerStart !== '' && $b['booking_date'] < $ledgerStart) continue;
    if ($ledgerEnd !== '' && $b['booking_date'] > $ledgerEnd) continue;
    if ($project_id > 0 && (int)$b['eff_project_id'] !== $project_id) continue;
    $ledBookings[] = $b;
}
$ledPayments = [];
foreach ($payments as $p) {
    if ($ledgerStart !== '' && $p['payment_date'] < $ledgerStart) continue;
    if ($ledgerEnd !== '' && $p['payment_date'] > $ledgerEnd) continue;
    $ledPayments[] = $p;
}

$commission = 0.0;
$paid = 0.0;
foreach ($ledBookings as $b) {
    $commission += (float)$b['total_price'] * (float)$dealer['commission_rate'] / 100;
}
foreach ($ledPayments as $p) {
    $paid += (float)$p['amount'];
}

$openingBalance = 0.0;
if ($ledgerStart !== '') {
    $oc = (float)db_get("SELECT COALESCE(SUM(b.total_price * ? / 100), 0) amt
                         FROM bookings b
                         JOIN properties p ON p.id = b.property_id
                         WHERE b.dealer_id = ? AND b.status <> 'cancelled'
                         AND b.booking_date < ?
                         AND (? = 0 OR p.project_id = ?)",
        [$dealer['commission_rate'], $id, $ledgerStart, $project_id, $project_id])['amt'];
    $op = (float)db_get("SELECT COALESCE(SUM(amount), 0) amt
                         FROM dealer_payments
                         WHERE dealer_id = ? AND payment_date < ?",
        [$id, $ledgerStart])['amt'];
    $openingBalance = $oc - $op;
}

$balance = $openingBalance + $commission - $paid;

include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="dealers.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= e($dealer['full_name']) ?></h5>
    <span class="badge bg-light text-dark border"><?= ucfirst(e($dealer['dealer_type'])) ?></span>
    <?php if (!$dealer['status']): ?><span class="badge bg-secondary">Inactive</span><?php endif; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cart-check"></i></div><div><div class="stat-label">SALES</div><div class="stat-value"><?= count($bookings) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-percent"></i></div><div><div class="stat-label">COMMISSION</div><div class="stat-value"><?= fmt_money($commission) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-coin"></i></div><div><div class="stat-label">PAID</div><div class="stat-value"><?= fmt_money($paid) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card <?= $balance > 0 ? 'bg-grad-orange' : 'bg-grad-cyan' ?>"><div class="stat-body"><div class="stat-icon"><i class="bi bi-wallet2"></i></div><div><div class="stat-label">BALANCE</div><div class="stat-value"><?= fmt_money($balance) ?></div></div></div></div></div>
</div>

<ul class="nav nav-pills mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#dProfile">Profile</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#dSales">Sales (<?= count($bookings) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#dProjects">Projects (<?= count($dealerProjects) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#dPayments">Payments (<?= count($payments) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#dLedger">Ledger</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="dProfile">
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3"><div class="text-muted small">Dealer No</div><div class="fw-medium"><?= e($dealer['dealer_no']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Type</div><div class="fw-medium"><?= e(ucfirst($dealer['dealer_type'])) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">CNIC</div><div class="fw-medium"><?= e($dealer['cnic'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Phone</div><div class="fw-medium"><?= e($dealer['phone'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">WhatsApp</div><div class="fw-medium"><?= e($dealer['whatsapp'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Email</div><div class="fw-medium"><?= e($dealer['email'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Commission Rate</div><div class="fw-medium"><?= fmt_num($dealer['commission_rate']) ?>%</div></div>
                    <div class="col-md-6"><div class="text-muted small">Address</div><div class="fw-medium"><?= e($dealer['address'] ?? '-') ?></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="dSales">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Booking</th><th>Customer</th><th>Property</th><th>Project</th><th>Date</th><th>Total</th><th>Commission</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td><a href="booking_view.php?id=<?= $b['id'] ?>"><?= e($b['booking_no']) ?></a></td>
                                <td><?= e($b['customer_name']) ?></td>
                                <td><?= e($b['property_no']) ?></td>
                                <td><?= e($b['project_name'] ?? '-') ?></td>
                                <td><?= fmt_date($b['booking_date']) ?></td>
                                <td><?= fmt_money($b['total_price']) ?></td>
                                <td><?= fmt_money((float)$b['total_price'] * (float)$dealer['commission_rate'] / 100) ?></td>
                                <td><?= status_badge($b['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$bookings): ?><tr><td colspan="8" class="text-center text-muted py-4">No sales yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="dProjects">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Project</th><th>Bookings</th><th class="text-end">Total Sales</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($dealerProjects as $pj): ?>
                            <tr>
                                <td><a href="project_view.php?id=<?= $pj['id'] ?>"><?= e($pj['name']) ?></a></td>
                                <td><?= (int)$pj['bookings_count'] ?></td>
                                <td class="text-end"><?= fmt_money((float)$pj['total_sales']) ?></td>
                                <td class="text-end">
                                    <a href="dealer_view.php?id=<?= $id ?>&project_id=<?= $pj['id'] ?>#dLedger" class="btn btn-sm btn-outline-primary"><i class="bi bi-book"></i> Ledger</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$dealerProjects): ?><tr><td colspan="4" class="text-center text-muted py-4">No projects yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="dPayments">
        <div class="card">
            <div class="card-header"><i class="bi bi-cash-coin me-2"></i>Commission Payments</div>
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

    <div class="tab-pane fade" id="dLedger">
        <div class="card">
            <div class="card-body">
                <form method="get" action="dealer_view.php#dLedger" class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <select name="project_id" class="form-select form-select-sm" style="max-width:220px">
                        <option value="0">All Projects</option>
                        <?php foreach ($dealerProjects as $pj): ?>
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
                    <a href="dealer_view.php?id=<?= $id ?>#dLedger" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
                    <a href="dealer_ledger_print.php?id=<?= $id ?>&project_id=<?= $project_id ?>&start_date=<?= e($ledgerStart) ?>&end_date=<?= e($ledgerEnd) ?>" class="btn btn-outline-secondary btn-sm ms-auto" target="_blank"><i class="bi bi-printer me-1"></i> Print</a>
                </form>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Description</th><th>Project</th><th>Credit (Commission)</th><th>Debit (Paid)</th><th>Balance</th></tr></thead>
                        <tbody>
                        <?php
                        $bal = $openingBalance;
                        if ($ledgerStart !== '' || $ledgerEnd !== '') {
                            echo '<tr><td>' . fmt_date($ledgerStart) . '</td><td>Opening Balance</td><td>-</td><td>-</td><td>-</td><td>' . fmt_money($bal) . '</td></tr>';
                        }
                        foreach ($ledBookings as $b) {
                            $comm = (float)$b['total_price'] * (float)$dealer['commission_rate'] / 100;
                            $bal += $comm;
                            echo '<tr><td>' . fmt_date($b['booking_date']) . '</td><td>Commission - ' . e($b['booking_no']) . ' (' . e($b['customer_name']) . ')</td><td>' . e($b['project_name'] ?? '-') . '</td><td>' . fmt_money($comm) . '</td><td>-</td><td>' . fmt_money($bal) . '</td></tr>';
                        }
                        foreach ($ledPayments as $p) {
                            $bal -= (float)$p['amount'];
                            $desc = 'Payment received';
                            if ($p['method_name']) $desc .= ' - ' . e($p['method_name']);
                            if ($p['reference']) $desc .= ' (' . e($p['reference']) . ')';
                            if ($p['remarks']) $desc .= ' [' . e($p['remarks']) . ']';
                            echo '<tr><td>' . fmt_date($p['payment_date']) . '</td><td>' . $desc . '</td><td>-</td><td>-</td><td>' . fmt_money((float)$p['amount']) . '</td><td>' . fmt_money($bal) . '</td></tr>';
                        }
                        if (!$ledBookings && !$ledPayments && $ledgerStart === '' && $ledgerEnd === '') {
                            echo '<tr><td colspan="6" class="text-center text-muted py-4">No ledger entries yet</td></tr>';
                        }
                        ?>
                        </tbody>
                        <tfoot>
                        <tr class="table-light">
                            <td colspan="5" class="text-end fw-bold"><?= ($ledgerStart !== '' || $ledgerEnd !== '') ? 'Closing Balance' : 'Balance Due' ?></td>
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

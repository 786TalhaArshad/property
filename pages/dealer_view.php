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
            db_exec("INSERT INTO dealer_payments (dealer_id, payment_date, amount, payment_method_id, bank_id, reference, remarks, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$id, $payment_date, $amount, $payment_method_id, $bank_id, $reference, $remarks]);
            flash('success', 'Commission payment recorded.');
        }
    } elseif ($action === 'payment_delete') {
        db_exec("DELETE FROM dealer_payments WHERE id = ? AND dealer_id = ?", [(int)($_POST['id'] ?? 0), $id]);
        flash('success', 'Payment deleted.');
    }
    redirect('dealer_view.php?id=' . $id);
}

$bookings = db_all("SELECT b.*, c.full_name AS customer_name, p.property_no FROM bookings b JOIN customers c ON c.id = b.customer_id JOIN properties p ON p.id = b.property_id WHERE b.dealer_id = ? AND b.status <> 'cancelled' ORDER BY b.id DESC", [$id]);
$payments = db_all("SELECT dp.*, pm.name AS method_name FROM dealer_payments dp LEFT JOIN payment_methods pm ON pm.id = dp.payment_method_id WHERE dp.dealer_id = ? ORDER BY dp.payment_date DESC", [$id]);
$paymentMethods = db_all("SELECT * FROM payment_methods ORDER BY name");
$banks = db_all("SELECT * FROM banks ORDER BY name");

$commission = 0.0;
foreach ($bookings as $b) {
    $commission += (float)$b['total_price'] * (float)$dealer['commission_rate'] / 100;
}
$paid = 0.0;
foreach ($payments as $p) {
    $paid += (float)$p['amount'];
}
$balance = $commission - $paid;

include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="dealers.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= e($dealer['full_name']) ?></h5>
    <span class="badge bg-light text-dark border"><?= ucfirst(e($dealer['dealer_type'])) ?></span>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cart-check"></i></div><div><div class="stat-label">SALES</div><div class="stat-value"><?= count($bookings) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-percent"></i></div><div><div class="stat-label">COMMISSION</div><div class="stat-value"><?= fmt_money($commission) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-coin"></i></div><div><div class="stat-label">PAID</div><div class="stat-value"><?= fmt_money($paid) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card <?= $balance > 0 ? 'bg-grad-orange' : 'bg-grad-cyan' ?>"><div class="stat-body"><div class="stat-icon"><i class="bi bi-wallet2"></i></div><div><div class="stat-label">BALANCE</div><div class="stat-value"><?= fmt_money($balance) ?></div></div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-xl-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-cash-coin me-2"></i>Commission Payments</div>
            <div class="card-body">
                <?php if ($canEdit): ?>
                <form method="post" class="row g-2 mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="payment_add">
                    <div class="col-md-4"><input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                    <div class="col-md-4"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required data-mask-money></div>
                    <div class="col-md-4"><button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Pay</button></div>
                    <div class="col-md-4">
                        <select name="payment_method_id" class="form-select">
                            <option value="">Method</option>
                            <?php foreach ($paymentMethods as $pm): ?><option value="<?= $pm['id'] ?>"><?= e($pm['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="bank_id" class="form-select">
                            <option value="">Bank</option>
                            <?php foreach ($banks as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4"><input type="text" name="reference" class="form-control" placeholder="Reference"></div>
                    <div class="col-12"><input type="text" name="remarks" class="form-control" placeholder="Remarks"></div>
                </form>
                <?php endif; ?>
                <div class="table-responsive" style="max-height:400px">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><?= fmt_date($p['payment_date']) ?></td>
                                <td><?= fmt_money($p['amount']) ?></td>
                                <td class="small"><?= e($p['method_name'] ?? '-') ?><?= $p['reference'] ? ' / ' . e($p['reference']) : '' ?></td>
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
                        <?php if (!$payments): ?><tr><td colspan="4" class="text-center text-muted py-4">No payments yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-journal-check me-2"></i>Sales</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Booking</th><th>Customer</th><th>Property</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td><a href="booking_view.php?id=<?= $b['id'] ?>"><?= e($b['booking_no']) ?></a></td>
                                <td><?= e($b['customer_name']) ?></td>
                                <td><?= e($b['property_no']) ?></td>
                                <td><?= fmt_date($b['booking_date']) ?></td>
                                <td><?= fmt_money($b['total_price']) ?></td>
                                <td><?= status_badge($b['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$bookings): ?><tr><td colspan="6" class="text-center text-muted py-4">No sales yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

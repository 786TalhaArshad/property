<?php
require_once '../includes/auth.php';
require_login();
require_permission('purchases.view');
$title = 'Purchase Details';
$active = 'purchases';
$canEdit = has_permission('purchases.manage');

$id = (int)($_GET['id'] ?? 0);
$purchase = db_get("SELECT p.*, v.business_name AS vendor_name, v.contact_person, v.phone AS vendor_phone
                    FROM purchases p
                    LEFT JOIN vendors v ON v.id = p.vendor_id
                    WHERE p.id = ?", [$id]);
if (!$purchase) { flash('danger', 'Purchase not found.'); redirect('purchases.php'); }

$items = db_all("SELECT pi.*
                 FROM purchase_items pi
                 WHERE pi.purchase_id = ? ORDER BY pi.id", [$id]);

$voucher = null;
$paymentVoucher = null;
if ($purchase['voucher_id']) {
    $voucher = db_get("SELECT * FROM vouchers WHERE id = ?", [$purchase['voucher_id']]);
    if ($voucher) {
        $voucher['items'] = db_all("SELECT vi.*, ca.code, ca.name FROM voucher_items vi LEFT JOIN chart_of_accounts ca ON ca.id = vi.account_id WHERE vi.voucher_id = ? ORDER BY vi.id", [$voucher['id']]);
    }
}
if (!empty($purchase['payment_voucher_id'])) {
    $paymentVoucher = db_get("SELECT * FROM vouchers WHERE id = ?", [$purchase['payment_voucher_id']]);
    if ($paymentVoucher) {
        $paymentVoucher['items'] = db_all("SELECT vi.*, ca.code, ca.name FROM voucher_items vi LEFT JOIN chart_of_accounts ca ON ca.id = vi.account_id WHERE vi.voucher_id = ? ORDER BY vi.id", [$paymentVoucher['id']]);
    }
}

$vendorPayments = db_all("SELECT vp.*, pm.name AS method_name, b.name AS bank_name
                         FROM vendor_payments vp
                         LEFT JOIN payment_methods pm ON pm.id = vp.payment_method_id
                         LEFT JOIN banks b ON b.id = vp.bank_id
                         WHERE vp.vendor_id = ? AND vp.payment_date = ? AND vp.amount = ?
                         ORDER BY vp.id DESC LIMIT 1",
    [$purchase['vendor_id'], $purchase['purchase_date'], $purchase['paid_amount']]);

$projectName = '-';
if ($purchase['project_id']) {
    $pr = db_get("SELECT name FROM projects WHERE id = ?", [$purchase['project_id']]);
    if ($pr) $projectName = $pr['name'];
}
$balance = (float)$purchase['total_amount'] - (float)$purchase['paid_amount'];

include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="purchases.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= e($purchase['purchase_no']) ?></h5>
    <?= status_badge($purchase['status']) ?>
    <?php if ($canEdit): ?>
    <a href="purchase_form.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary ms-auto"><i class="bi bi-pencil me-1"></i>Edit</a>
    <?php endif; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-receipt"></i></div><div><div class="stat-label">TOTAL AMOUNT</div><div class="stat-value"><?= fmt_money($purchase['total_amount']) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">PAID</div><div class="stat-value"><?= fmt_money($purchase['paid_amount']) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-clock-history"></i></div><div><div class="stat-label">BALANCE</div><div class="stat-value"><?= fmt_money($balance) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-calendar"></i></div><div><div class="stat-label">DATE</div><div class="stat-value small"><?= fmt_date($purchase['purchase_date']) ?></div></div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Purchase Info</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Purchase No</dt><dd class="col-sm-7"><?= e($purchase['purchase_no']) ?></dd>
                    <dt class="col-sm-5">Vendor</dt><dd class="col-sm-7"><a href="vendor_view.php?id=<?= $purchase['vendor_id'] ?>"><?= e($purchase['vendor_name']) ?></a></dd>
                    <dt class="col-sm-5">Contact</dt><dd class="col-sm-7"><?= e($purchase['contact_person'] ?: '-') ?> <?= $purchase['vendor_phone'] ? '<br>' . e($purchase['vendor_phone']) : '' ?></dd>
                    <dt class="col-sm-5">Project</dt><dd class="col-sm-7"><?= e($projectName) ?></dd>
                    <dt class="col-sm-5">Payment Mode</dt><dd class="col-sm-7"><?= e(ucfirst($purchase['payment_mode'])) ?></dd>
                    <dt class="col-sm-5">Reference</dt><dd class="col-sm-7"><?= e($purchase['reference'] ?: '-') ?></dd>
                    <dt class="col-sm-5">Narration</dt><dd class="col-sm-7"><?= e($purchase['narration'] ?: '-') ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-list-ul me-2"></i>Line Items</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>#</th><th>Description</th><th class="text-end">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                        <?php foreach ($items as $i => $it): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= e($it['description'] ?: '-') ?></td>
                                <td class="text-end"><?= number_format((float)$it['quantity'], 2) ?></td>
                                <td class="text-end"><?= fmt_money($it['unit_price']) ?></td>
                                <td class="text-end fw-medium"><?= fmt_money($it['amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr><td colspan="5" class="text-end fw-bold">Subtotal</td><td class="text-end fw-bold"><?= fmt_money($purchase['total_amount'] + $purchase['discount']) ?></td></tr>
                            <?php if ($purchase['discount'] > 0): ?>
                            <tr><td colspan="5" class="text-end">Discount</td><td class="text-end text-danger">-<?= fmt_money($purchase['discount']) ?></td></tr>
                            <?php endif; ?>
                            <tr class="table-success"><td colspan="5" class="text-end fw-bold">Net Amount</td><td class="text-end fw-bold"><?= fmt_money($purchase['total_amount']) ?></td></tr>
                            <?php if ($purchase['paid_amount'] > 0): ?>
                            <tr><td colspan="5" class="text-end">Paid</td><td class="text-end text-success">-<?= fmt_money($purchase['paid_amount']) ?></td></tr>
                            <?php endif; ?>
                            <tr class="table-warning"><td colspan="5" class="text-end fw-bold">Balance</td><td class="text-end fw-bold"><?= fmt_money($balance) ?></td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($voucher): ?>
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-journal-text me-2"></i>Purchase Voucher: <?= e($voucher['voucher_no']) ?></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Account</th><th>Description</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
                    <tbody>
                    <?php foreach ($voucher['items'] as $vi): ?>
                        <tr>
                            <td class="small"><?= e($vi['code']) ?> - <?= e($vi['name']) ?></td>
                            <td><?= e($vi['item_description'] ?: '-') ?></td>
                            <td class="text-end"><?= $vi['debit'] > 0 ? fmt_money($vi['debit']) : '-' ?></td>
                            <td class="text-end"><?= $vi['credit'] > 0 ? fmt_money($vi['credit']) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($paymentVoucher): ?>
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-cash-coin me-2"></i>Payment Voucher: <?= e($paymentVoucher['voucher_no']) ?></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Account</th><th>Description</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
                    <tbody>
                    <?php foreach ($paymentVoucher['items'] as $vi): ?>
                        <tr>
                            <td class="small"><?= e($vi['code']) ?> - <?= e($vi['name']) ?></td>
                            <td><?= e($vi['item_description'] ?: '-') ?></td>
                            <td class="text-end"><?= $vi['debit'] > 0 ? fmt_money($vi['debit']) : '-' ?></td>
                            <td class="text-end"><?= $vi['credit'] > 0 ? fmt_money($vi['credit']) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

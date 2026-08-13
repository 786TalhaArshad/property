<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Recovery Report';
$active = 'reports';

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$project_id = (int)($_GET['project_id'] ?? active_project_id());

$receipts = db_all("SELECT r.*, c.full_name AS customer_name, c.customer_no, b.booking_no, p.property_no, pm.name AS method_name, bk.name AS bank_name
                    FROM receipts r
                    JOIN customers c ON c.id = r.customer_id
                    LEFT JOIN bookings b ON b.id = r.booking_id
                    LEFT JOIN properties p ON p.id = b.property_id
                    LEFT JOIN payment_methods pm ON pm.id = r.payment_method_id
                    LEFT JOIN banks bk ON bk.id = r.bank_id
                    WHERE r.receipt_date BETWEEN ? AND ?
                    AND (? = 0 OR r.project_id = ?)
                    ORDER BY r.receipt_date DESC", [$from, $to, $project_id, $project_id]);

$rentCols = db_all("SELECT rc.*, ra.agreement_no, p.property_no, t.full_name AS tenant_name, pm.name AS method_name
                    FROM rent_collections rc
                    JOIN rental_agreements ra ON ra.id = rc.agreement_id
                    JOIN properties p ON p.id = ra.property_id
                    JOIN tenants t ON t.id = ra.tenant_id
                    LEFT JOIN payment_methods pm ON pm.id = rc.payment_method_id
                    WHERE rc.collection_date BETWEEN ? AND ?
                    AND (? = 0 OR p.project_id = ?)
                    ORDER BY rc.collection_date DESC", [$from, $to, $project_id, $project_id]);

$totalSale = 0.0; $byMethod = [];
foreach ($receipts as $r) {
    $totalSale += (float)$r['amount'];
    $m = $r['method_name'] ?: 'Other';
    $byMethod[$m] = ($byMethod[$m] ?? 0) + (float)$r['amount'];
}
$totalRent = 0.0;
foreach ($rentCols as $r) $totalRent += (float)$r['amount'];
$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
include '../../includes/header.php';
?>

<form method="get" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-2"><input type="date" name="from" class="form-control" value="<?= e($from) ?>"></div>
            <div class="col-md-2"><input type="date" name="to" class="form-control" value="<?= e($to) ?>"></div>
            <div class="col-md-3">
                <select name="project_id" class="form-select">
                    <option value="">All Projects</option>
                    <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-coin"></i></div><div><div class="stat-label">SALE RECEIPTS</div><div class="stat-value"><?= fmt_money($totalSale) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-house-heart"></i></div><div><div class="stat-label">RENT COLLECTED</div><div class="stat-value"><?= fmt_money($totalRent) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-piggy-bank"></i></div><div><div class="stat-label">TOTAL RECOVERY</div><div class="stat-value"><?= fmt_money($totalSale + $totalRent) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card">
        <div class="card-body p-3">
            <div class="text-muted small mb-2">BY METHOD</div>
            <?php foreach ($byMethod as $m => $amt): ?>
                <div class="d-flex justify-content-between small"><span><?= e($m) ?></span><span class="fw-medium"><?= fmt_money($amt) ?></span></div>
            <?php endforeach; ?>
        </div>
    </div></div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-cash-coin me-2"></i>Sale Receipts: <?= fmt_date($from) ?> to <?= fmt_date($to) ?></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Receipt</th><th>Date</th><th>Customer</th><th>Booking</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                        <?php foreach ($receipts as $r): ?>
                            <tr><td class="fw-medium"><?= e($r['receipt_no']) ?></td><td><?= fmt_date($r['receipt_date']) ?></td><td><?= e($r['customer_name']) ?></td><td class="small"><?= e($r['booking_no'] ?? '-') ?></td><td class="text-end"><?= fmt_money($r['amount']) ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (!$receipts): ?><tr><td colspan="5"><div class="empty-state"><i class="bi bi-inbox"></i><p>No sale receipts</p></div></td></tr><?php endif; ?>
                        </tbody>
                        <tfoot><tr class="table-light"><td colspan="4" class="fw-bold">Total</td><td class="text-end fw-bold"><?= fmt_money($totalSale) ?></td></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-house-heart me-2"></i>Rent Collections: <?= fmt_date($from) ?> to <?= fmt_date($to) ?></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Property</th><th>Tenant</th><th>Method</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                        <?php foreach ($rentCols as $r): ?>
                            <tr><td><?= fmt_date($r['collection_date']) ?></td><td class="fw-medium"><?= e($r['property_no']) ?></td><td class="small"><?= e($r['tenant_name']) ?></td><td class="small"><?= e($r['method_name'] ?? '-') ?></td><td class="text-end"><?= fmt_money($r['amount']) ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (!$rentCols): ?><tr><td colspan="5"><div class="empty-state"><i class="bi bi-inbox"></i><p>No rent collections</p></div></td></tr><?php endif; ?>
                        </tbody>
                        <tfoot><tr class="table-light"><td colspan="4" class="fw-bold">Total</td><td class="text-end fw-bold"><?= fmt_money($totalRent) ?></td></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <div class="text-muted small">PERIOD</div>
            <div class="fw-medium"><?= fmt_date($from) ?> - <?= fmt_date($to) ?></div>
        </div>
        <div class="text-center">
            <div class="text-muted small">TOTAL RECOVERY</div>
            <div class="fs-3 fw-bold text-success"><?= fmt_money($totalSale + $totalRent) ?></div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

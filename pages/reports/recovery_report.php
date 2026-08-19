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
$projectName = '';
foreach ($projects as $p) { if ((int)$p['id'] === $project_id) { $projectName = $p['name']; break; } }

$monthlySale = []; $monthlyRent = [];
foreach ($receipts as $r) { $ym = date('Y-m', strtotime($r['receipt_date'])); $monthlySale[$ym] = ($monthlySale[$ym] ?? 0) + (float)$r['amount']; }
foreach ($rentCols as $r) { $ym = date('Y-m', strtotime($r['collection_date'])); $monthlyRent[$ym] = ($monthlyRent[$ym] ?? 0) + (float)$r['amount']; }
$allMonths = array_unique(array_merge(array_keys($monthlySale), array_keys($monthlyRent)));
sort($allMonths);
$chartLabels = []; $chartSale = []; $chartRent = [];
foreach ($allMonths as $m) {
    $chartLabels[] = date('M y', strtotime($m . '-01'));
    $chartSale[] = $monthlySale[$m] ?? 0;
    $chartRent[] = $monthlyRent[$m] ?? 0;
}
include '../../includes/header.php';
?>

<style>
@media print {
    .no-print, .sidebar, .main-header, .main-footer, .quick-action-btn { display: none !important; }
    .main-content { margin: 0 !important; padding: 10px !important; }
    .card { border: none !important; box-shadow: none !important; }
    body { font-size: 11px; }
    .table td, .table th { padding: 4px 6px !important; font-size: 11px; }
}
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Recovery Report</h5>
    <div class="ms-auto"><button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print</button></div>
</div>

<form method="get" class="card mb-3 no-print">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-2"><label class="form-label small text-muted mb-0">From</label><input type="date" name="from" class="form-control" value="<?= e($from) ?>"></div>
            <div class="col-md-2"><label class="form-label small text-muted mb-0">To</label><input type="date" name="to" class="form-control" value="<?= e($to) ?>"></div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-0">Project</label>
                <select name="project_id" class="form-select">
                    <option value="">All Projects</option>
                    <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><label class="form-label mb-0">&nbsp;</label><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="card print-only" style="display:none">
    <div class="text-center mb-2">
        <h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5>
        <div class="small text-muted">Recovery Report from <?= fmt_date($from) ?> to <?= fmt_date($to) ?><?= $projectName ? ' | ' . e($projectName) : '' ?></div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-coin"></i></div><div><div class="stat-label">SALE RECEIPTS</div><div class="stat-value"><?= fmt_money($totalSale) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-house-heart"></i></div><div><div class="stat-label">RENT COLLECTED</div><div class="stat-value"><?= fmt_money($totalRent) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-piggy-bank"></i></div><div><div class="stat-label">TOTAL RECOVERY</div><div class="stat-value"><?= fmt_money($totalSale + $totalRent) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-cyan"><div class="card-body p-3">
        <div class="text-muted small mb-2">BY METHOD</div>
        <?php foreach ($byMethod as $m => $amt): ?>
            <div class="d-flex justify-content-between small"><span><?= e($m) ?></span><span class="fw-medium"><?= fmt_money($amt) ?></span></div>
        <?php endforeach; ?>
    </div></div></div>
</div>

<div class="row g-3 mb-3 no-print">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Monthly Recovery</div>
            <div class="card-body"><canvas id="recoveryChart" height="280"></canvas></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-pie-chart me-2"></i>Payment Methods</div>
            <div class="card-body"><canvas id="methodChart" height="280"></canvas></div>
        </div>
    </div>
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

<script>
new Chart(document.getElementById('recoveryChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [
            { label: 'Sale Receipts', data: <?= json_encode($chartSale) ?>, backgroundColor: 'rgba(54,162,235,0.7)' },
            { label: 'Rent Collected', data: <?= json_encode($chartRent) ?>, backgroundColor: 'rgba(75,192,192,0.7)' }
        ]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});
new Chart(document.getElementById('methodChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($byMethod)) ?>,
        datasets: [{ data: <?= json_encode(array_values($byMethod)) ?>, backgroundColor: ['#36a2eb','#ffce56','#4bc0c0','#ff6384','#9966ff','#ff9f40'] }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php include '../../includes/footer.php'; ?>

<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Sales Pipeline Report';
$active = 'reports';

$from = $_GET['from'] ?? date('Y-01-01');
$to = $_GET['to'] ?? date('Y-m-d');
$project_id = (int)($_GET['project_id'] ?? active_project_id());

$whereQ = "q.quotation_date BETWEEN ? AND ?";
$paramsQ = [$from, $to];
if ($project_id) { $whereQ .= " AND p.project_id = ?"; $paramsQ[] = $project_id; }

$quotations = db_all("SELECT q.*, c.full_name AS customer_name, p.property_no, pr.name AS project_name, d.full_name AS dealer_name
                      FROM quotations q
                      LEFT JOIN customers c ON c.id = q.customer_id
                      LEFT JOIN properties p ON p.id = q.property_id
                      LEFT JOIN projects pr ON pr.id = p.project_id
                      LEFT JOIN dealers d ON d.id = q.dealer_id
                      WHERE $whereQ ORDER BY q.quotation_date DESC", $paramsQ);

$statusCounts = $statusAmounts = [];
$totalAmount = 0.0;
foreach ($quotations as $q) {
    $s = $q['status'];
    $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
    $statusAmounts[$s] = ($statusAmounts[$s] ?? 0) + (float)$q['amount'];
    $totalAmount += (float)$q['amount'];
}
$accepted = $statusCounts['accepted'] ?? 0;
$convQ = "SELECT COUNT(*) AS cnt FROM bookings b WHERE b.booking_date BETWEEN ? AND ?";
$convParams = [$from, $to];
if ($project_id) { $convQ .= " AND EXISTS (SELECT 1 FROM properties p WHERE p.id = b.property_id AND p.project_id = ?)"; $convParams[] = $project_id; }
$converted = db_get($convQ, $convParams)['cnt'];
$conversionRate = count($quotations) > 0 ? ($converted / count($quotations) * 100) : 0;
$projects = db_all("SELECT id, name FROM projects ORDER BY name");
$projectName = '';
foreach ($projects as $p) { if ((int)$p['id'] === $project_id) { $projectName = $p['name']; break; } }
include '../../includes/header.php';
?>

<style>
@media print { .no-print,.sidebar,.main-header,.main-footer,.quick-action-btn{display:none!important}.main-content{margin:0!important;padding:10px!important}.card{border:none!important;box-shadow:none!important}body{font-size:11px}.table td,.table th{padding:4px 6px!important;font-size:11px} }
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Sales Pipeline Report</h5>
    <div class="ms-auto"><button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print</button></div>
</div>

<form method="get" class="card mb-3 no-print">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-2"><label class="form-label small text-muted mb-0">From</label><input type="date" name="from" class="form-control" value="<?= e($from) ?>"></div>
            <div class="col-md-2"><label class="form-label small text-muted mb-0">To</label><input type="date" name="to" class="form-control" value="<?= e($to) ?>"></div>
            <div class="col-md-3"><label class="form-label small text-muted mb-0">Project</label>
                <select name="project_id" class="form-select"><option value="">All Projects</option><?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?></select>
            </div>
            <div class="col-md-2"><label class="form-label mb-0">&nbsp;</label><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="card print-only" style="display:none"><div class="text-center mb-2"><h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5><div class="small text-muted">Sales Pipeline Report from <?= fmt_date($from) ?> to <?= fmt_date($to) ?><?= $projectName ? ' | ' . e($projectName) : '' ?></div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-funnel"></i></div><div><div class="stat-label">QUOTATIONS</div><div class="stat-value"><?= count($quotations) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-check-circle"></i></div><div><div class="stat-label">ACCEPTED</div><div class="stat-value"><?= $accepted ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-arrow-left-right"></i></div><div><div class="stat-label">CONVERTED TO BOOKING</div><div class="stat-value"><?= $converted ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-percent"></i></div><div><div class="stat-label">CONVERSION RATE</div><div class="stat-value"><?= number_format($conversionRate, 1) ?>%</div></div></div></div></div>
</div>

<div class="row g-3 mb-3 no-print">
    <div class="col-md-6"><div class="card"><div class="card-header"><i class="bi bi-bar-chart me-2"></i>Quotations by Status</div><div class="card-body"><canvas id="statusChart" height="280"></canvas></div></div></div>
    <div class="col-md-6"><div class="card"><div class="card-header"><i class="bi bi-pie-chart me-2"></i>Amount by Status</div><div class="card-body"><canvas id="amountChart" height="280"></canvas></div></div></div>
</div>

<div class="card">
    <div class="card-header no-print"><i class="bi bi-table me-2"></i>Quotations</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Quotation#</th><th>Date</th><th>Customer</th><th>Property</th><th>Project</th><th>Dealer</th><th class="text-end">Amount</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($quotations as $q): ?>
                    <tr><td class="fw-medium"><?= e($q['quotation_no']) ?></td><td><?= fmt_date($q['quotation_date']) ?></td><td><?= e($q['customer_name'] ?? '-') ?></td><td><?= e($q['property_no'] ?? '-') ?></td><td class="small"><?= e($q['project_name'] ?? '-') ?></td><td class="small"><?= e($q['dealer_name'] ?? '-') ?></td><td class="text-end"><?= fmt_money($q['amount']) ?></td><td><?= status_badge($q['status']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$quotations): ?><tr><td colspan="8"><div class="empty-state"><i class="bi bi-inbox"></i><p>No quotations in this period</p></div></td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr class="table-light"><td colspan="6" class="fw-bold">Total</td><td class="text-end fw-bold"><?= fmt_money($totalAmount) ?></td><td></td></tr></tfoot>
            </table>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('statusChart'), { type: 'bar', data: { labels: <?= json_encode(array_keys($statusCounts)) ?>, datasets: [{ label: 'Count', data: <?= json_encode(array_values($statusCounts)) ?>, backgroundColor: ['#36a2eb','#4bc0c0','#ffce56','#ff6384'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });
new Chart(document.getElementById('amountChart'), { type: 'doughnut', data: { labels: <?= json_encode(array_keys($statusAmounts)) ?>, datasets: [{ data: <?= json_encode(array_values($statusAmounts)) ?>, backgroundColor: ['#36a2eb','#4bc0c0','#ffce56','#ff6384'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
</script>

<?php include '../../includes/footer.php'; ?>

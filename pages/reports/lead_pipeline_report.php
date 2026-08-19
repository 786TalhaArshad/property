<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Lead Pipeline Report';
$active = 'reports';

$from = $_GET['from'] ?? date('Y-01-01');
$to = $_GET['to'] ?? date('Y-m-d');

$leads = db_all("SELECT l.*, pt.name AS property_type_name, pr.name AS project_name, u.full_name AS assigned_name
                 FROM leads l
                 LEFT JOIN property_types pt ON pt.id = l.property_type_id
                 LEFT JOIN projects pr ON pr.id = l.project_id
                 LEFT JOIN users u ON u.id = l.assigned_to
                 WHERE l.created_date BETWEEN ? AND ?
                 ORDER BY l.created_date DESC", [$from, $to]);

$statusCounts = $sourceCounts = []; $totalBudget = 0.0;
foreach ($leads as $l) {
    $s = $l['status']; $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
    $src = $l['source'] ?: 'other'; $sourceCounts[$src] = ($sourceCounts[$src] ?? 0) + 1;
    $totalBudget += (float)$l['budget'];
}
$converted = $statusCounts['converted'] ?? 0;
$lost = $statusCounts['lost'] ?? 0;
$total = count($leads);
$convRate = $total > 0 ? ($converted / $total * 100) : 0;
include '../../includes/header.php';
?>

<style>
@media print { .no-print,.sidebar,.main-header,.main-footer,.quick-action-btn{display:none!important}.main-content{margin:0!important;padding:10px!important}.card{border:none!important;box-shadow:none!important}body{font-size:11px}.table td,.table th{padding:4px 6px!important;font-size:11px} }
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Lead Pipeline Report</h5>
    <div class="ms-auto"><button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print</button></div>
</div>

<form method="get" class="card mb-3 no-print">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-2"><label class="form-label small text-muted mb-0">From</label><input type="date" name="from" class="form-control" value="<?= e($from) ?>"></div>
            <div class="col-md-2"><label class="form-label small text-muted mb-0">To</label><input type="date" name="to" class="form-control" value="<?= e($to) ?>"></div>
            <div class="col-md-2"><label class="form-label mb-0">&nbsp;</label><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="card print-only" style="display:none"><div class="text-center mb-2"><h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5><div class="small text-muted">Lead Pipeline Report from <?= fmt_date($from) ?> to <?= fmt_date($to) ?></div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-funnel"></i></div><div><div class="stat-label">TOTAL LEADS</div><div class="stat-value"><?= $total ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-check-circle"></i></div><div><div class="stat-label">CONVERTED</div><div class="stat-value"><?= $converted ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-red"><div class="stat-body"><div class="stat-icon"><i class="bi bi-x-circle"></i></div><div><div class="stat-label">LOST</div><div class="stat-value"><?= $lost ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-percent"></i></div><div><div class="stat-label">CONVERSION RATE</div><div class="stat-value"><?= number_format($convRate, 1) ?>%</div></div></div></div></div>
</div>

<div class="row g-3 mb-3 no-print">
    <div class="col-md-6"><div class="card"><div class="card-header"><i class="bi bi-bar-chart me-2"></i>By Status</div><div class="card-body"><canvas id="statusChart" height="250"></canvas></div></div></div>
    <div class="col-md-6"><div class="card"><div class="card-header"><i class="bi bi-pie-chart me-2"></i>By Source</div><div class="card-body"><canvas id="sourceChart" height="250"></canvas></div></div></div>
</div>

<div class="card">
    <div class="card-header no-print"><i class="bi bi-table me-2"></i>Leads</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Lead#</th><th>Date</th><th>Name</th><th>Phone</th><th>Source</th><th>Property Type</th><th>Project</th><th>Assigned</th><th class="text-end">Budget</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($leads as $l): ?>
                    <tr><td class="fw-medium"><?= e($l['lead_no']) ?></td><td><?= fmt_date($l['created_date']) ?></td><td><?= e($l['name']) ?></td><td class="small"><?= e($l['phone']) ?></td><td><span class="badge bg-secondary"><?= e($l['source']) ?></span></td><td class="small"><?= e($l['property_type_name'] ?? '-') ?></td><td class="small"><?= e($l['project_name'] ?? '-') ?></td><td class="small"><?= e($l['assigned_name'] ?? '-') ?></td><td class="text-end"><?= $l['budget'] > 0 ? fmt_money($l['budget']) : '-' ?></td><td><?= status_badge($l['status']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$leads): ?><tr><td colspan="10"><div class="empty-state"><i class="bi bi-inbox"></i><p>No leads</p></div></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('statusChart'), { type: 'bar', data: { labels: <?= json_encode(array_keys($statusCounts)) ?>, datasets: [{ data: <?= json_encode(array_values($statusCounts)) ?>, backgroundColor: ['#36a2eb','#ffce56','#4bc0c0','#9966ff','#ff9f40','#ff6384','#c9cbcf'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });
new Chart(document.getElementById('sourceChart'), { type: 'doughnut', data: { labels: <?= json_encode(array_keys($sourceCounts)) ?>, datasets: [{ data: <?= json_encode(array_values($sourceCounts)) ?>, backgroundColor: ['#36a2eb','#ffce56','#4bc0c0','#ff6384','#9966ff','#ff9f40'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
</script>

<?php include '../../includes/footer.php'; ?>

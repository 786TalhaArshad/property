<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Maintenance Report';
$active = 'reports';

$from = $_GET['from'] ?? date('Y-01-01');
$to = $_GET['to'] ?? date('Y-m-d');
$project_id = (int)($_GET['project_id'] ?? active_project_id());

$where = "mc.reported_date BETWEEN ? AND ?";
$params = [$from, $to];
if ($project_id) { $where .= " AND p.project_id = ?"; $params[] = $project_id; }

$complaints = db_all("SELECT mc.*, p.property_no, t.full_name AS tenant_name, pr.name AS project_name,
                      (SELECT COALESCE(SUM(mt.cost),0) FROM maintenance_tasks mt WHERE mt.complaint_id = mc.id) AS task_cost,
                      (SELECT GROUP_CONCAT(te.name SEPARATOR ', ') FROM maintenance_tasks mt JOIN technicians te ON te.id = mt.technician_id WHERE mt.complaint_id = mc.id) AS tech_names
                      FROM maintenance_complaints mc
                      LEFT JOIN properties p ON p.id = mc.property_id
                      LEFT JOIN tenants t ON t.id = mc.tenant_id
                      LEFT JOIN projects pr ON pr.id = p.project_id
                      WHERE $where ORDER BY mc.reported_date DESC", $params);

$totalCost = 0.0; $statusCounts = $catCounts = $priCounts = [];
foreach ($complaints as $c) {
    $totalCost += (float)$c['task_cost'];
    $s = $c['status']; $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
    $cat = $c['category']; $catCounts[$cat] = ($catCounts[$cat] ?? 0) + 1;
    $pr = $c['priority']; $priCounts[$pr] = ($priCounts[$pr] ?? 0) + 1;
}
$projects = db_all("SELECT id, name FROM projects ORDER BY name");
$projectName = '';
foreach ($projects as $p) { if ((int)$p['id'] === $project_id) { $projectName = $p['name']; break; } }
include '../../includes/header.php';
?>

<style>
@media print { .no-print,.sidebar,.main-header,.main-footer,.quick-action-btn{display:none!important}.main-content{margin:0!important;padding:10px!important}.card{border:none!important;box-shadow:none!important}body{font-size:11px}.table td,.table th{padding:4px 6px!important;font-size:11px} }
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-tools me-2"></i>Maintenance Report</h5>
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

<div class="card print-only" style="display:none"><div class="text-center mb-2"><h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5><div class="small text-muted">Maintenance Report from <?= fmt_date($from) ?> to <?= fmt_date($to) ?><?= $projectName ? ' | ' . e($projectName) : '' ?></div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-tools"></i></div><div><div class="stat-label">TOTAL COMPLAINTS</div><div class="stat-value"><?= count($complaints) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-clock-history"></i></div><div><div class="stat-label">OPEN</div><div class="stat-value"><?= $statusCounts['open'] ?? 0 ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-check-circle"></i></div><div><div class="stat-label">COMPLETED</div><div class="stat-value"><?= $statusCounts['completed'] ?? 0 ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-red"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">TOTAL COST</div><div class="stat-value"><?= fmt_money($totalCost) ?></div></div></div></div></div>
</div>

<div class="row g-3 mb-3 no-print">
    <div class="col-md-4"><div class="card"><div class="card-header"><i class="bi bi-pie-chart me-2"></i>By Status</div><div class="card-body"><canvas id="statusChart" height="250"></canvas></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-header"><i class="bi bi-bar-chart me-2"></i>By Category</div><div class="card-body"><canvas id="catChart" height="250"></canvas></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-header"><i class="bi bi-pie-chart me-2"></i>By Priority</div><div class="card-body"><canvas id="priChart" height="250"></canvas></div></div></div>
</div>

<div class="card">
    <div class="card-header no-print"><i class="bi bi-table me-2"></i>Complaints</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>#</th><th>Date</th><th>Property</th><th>Tenant</th><th>Category</th><th>Priority</th><th>Status</th><th>Technician</th><th class="text-end">Cost</th></tr></thead>
                <tbody>
                <?php foreach ($complaints as $i => $c): ?>
                    <tr><td><?= $i + 1 ?></td><td><?= fmt_date($c['reported_date']) ?></td><td class="fw-medium"><?= e($c['property_no'] ?? '-') ?></td><td class="small"><?= e($c['tenant_name'] ?? '-') ?></td><td><span class="badge bg-info"><?= e($c['category']) ?></span></td><td><?= status_badge($c['priority']) ?></td><td><?= status_badge($c['status']) ?></td><td class="small"><?= e($c['tech_names'] ?? '-') ?></td><td class="text-end"><?= fmt_money($c['task_cost']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$complaints): ?><tr><td colspan="9"><div class="empty-state"><i class="bi bi-inbox"></i><p>No complaints</p></div></td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr class="table-light"><td colspan="8" class="fw-bold">Total Cost</td><td class="text-end fw-bold"><?= fmt_money($totalCost) ?></td></tr></tfoot>
            </table>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('statusChart'), { type: 'doughnut', data: { labels: <?= json_encode(array_keys($statusCounts)) ?>, datasets: [{ data: <?= json_encode(array_values($statusCounts)) ?>, backgroundColor: ['#ffce56','#36a2eb','#4bc0c0','#ff6384'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
new Chart(document.getElementById('catChart'), { type: 'bar', data: { labels: <?= json_encode(array_keys($catCounts)) ?>, datasets: [{ data: <?= json_encode(array_values($catCounts)) ?>, backgroundColor: 'rgba(54,162,235,0.7)' }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });
new Chart(document.getElementById('priChart'), { type: 'doughnut', data: { labels: <?= json_encode(array_keys($priCounts)) ?>, datasets: [{ data: <?= json_encode(array_values($priCounts)) ?>, backgroundColor: ['#4bc0c0','#ffce56','#ff9f40','#ff6384'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
</script>

<?php include '../../includes/footer.php'; ?>

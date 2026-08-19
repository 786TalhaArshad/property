<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Material Issue Report';
$active = 'reports';

$from = $_GET['from'] ?? date('Y-01-01');
$to = $_GET['to'] ?? date('Y-m-d');
$project_id = (int)($_GET['project_id'] ?? active_project_id());

$hasMi = (bool)(db_get("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'material_issues'")['c'] ?? 0);
$hasMii = $hasMi && (bool)(db_get("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'material_issue_items'")['c'] ?? 0);

$issues = [];
if ($hasMi) {
    $where = "mi.issue_date BETWEEN ? AND ?";
    $params = [$from, $to];
    if ($project_id) { $where .= " AND mi.project_id = ?"; $params[] = $project_id; }

    $miiJoin = $hasMii ? "LEFT JOIN (SELECT material_issue_id, COUNT(*) AS item_count FROM material_issue_items GROUP BY material_issue_id) mii ON mii.material_issue_id = mi.id" : "";
    $miiCol = $hasMii ? "mii.item_count" : "0";
    $issues = db_all("SELECT mi.*, c.full_name AS contractor_name, c.contractor_no, pr.name AS project_name,
                      $miiCol AS item_count
                      FROM material_issues mi
                      LEFT JOIN contractors c ON c.id = mi.contractor_id
                      LEFT JOIN projects pr ON pr.id = mi.project_id
                      $miiJoin
                      WHERE $where ORDER BY mi.issue_date DESC", $params);
}

$totalCost = 0.0; $byProject = []; $byContractor = [];
foreach ($issues as $i) {
    $totalCost += (float)$i['total_amount'];
    $pn = $i['project_name'] ?? 'No Project';
    if (!isset($byProject[$pn])) $byProject[$pn] = 0;
    $byProject[$pn] += (float)$i['total_amount'];
    $cn = $i['contractor_name'] ?? '-';
    if (!isset($byContractor[$cn])) $byContractor[$cn] = 0;
    $byContractor[$cn] += (float)$i['total_amount'];
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
    <h5 class="mb-0"><i class="bi bi-truck me-2"></i>Material Issue Report</h5>
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

<div class="card print-only" style="display:none"><div class="text-center mb-2"><h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5><div class="small text-muted">Material Issue Report from <?= fmt_date($from) ?> to <?= fmt_date($to) ?><?= $projectName ? ' | ' . e($projectName) : '' ?></div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-truck"></i></div><div><div class="stat-label">TOTAL ISSUES</div><div class="stat-value"><?= count($issues) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-red"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">TOTAL COST</div><div class="stat-value"><?= fmt_money($totalCost) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-people"></i></div><div><div class="stat-label">CONTRACTORS</div><div class="stat-value"><?= count($byContractor) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-diagram-3"></i></div><div><div class="stat-label">PROJECTS</div><div class="stat-value"><?= count($byProject) ?></div></div></div></div></div>
</div>

<div class="row g-3 mb-3 no-print">
    <div class="col-md-6"><div class="card"><div class="card-header"><i class="bi bi-bar-chart me-2"></i>Cost by Project</div><div class="card-body"><canvas id="projChart" height="280"></canvas></div></div></div>
    <div class="col-md-6"><div class="card"><div class="card-header"><i class="bi bi-bar-chart me-2"></i>Cost by Contractor</div><div class="card-body"><canvas id="conChart" height="280"></canvas></div></div></div>
</div>

<div class="card">
    <div class="card-header no-print"><i class="bi bi-table me-2"></i>Issues</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Issue#</th><th>Date</th><th>Project</th><th>Contractor</th><th>Items</th><th class="text-end">Total Cost</th></tr></thead>
                <tbody>
                <?php foreach ($issues as $i): ?>
                    <tr><td class="fw-medium"><?= e($i['issue_no']) ?></td><td><?= fmt_date($i['issue_date']) ?></td><td><?= e($i['project_name'] ?? '-') ?></td><td><?= e($i['contractor_name'] ?? '-') ?></td><td class="text-center"><?= $i['item_count'] ?></td><td class="text-end fw-bold"><?= fmt_money($i['total_amount']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$issues): ?><tr><td colspan="6"><div class="empty-state"><i class="bi bi-inbox"></i><p>No material issues</p></div></td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr class="table-light"><td colspan="5" class="fw-bold">Total</td><td class="text-end fw-bold"><?= fmt_money($totalCost) ?></td></tr></tfoot>
            </table>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('projChart'), { type: 'bar', data: { labels: <?= json_encode(array_keys($byProject)) ?>, datasets: [{ label: 'Cost', data: <?= json_encode(array_values($byProject)) ?>, backgroundColor: 'rgba(54,162,235,0.7)' }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });
new Chart(document.getElementById('conChart'), { type: 'bar', data: { labels: <?= json_encode(array_keys($byContractor)) ?>, datasets: [{ label: 'Cost', data: <?= json_encode(array_values($byContractor)) ?>, backgroundColor: 'rgba(255,159,64,0.7)' }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });
</script>

<?php include '../../includes/footer.php'; ?>

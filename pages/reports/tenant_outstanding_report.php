<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Tenant Outstanding Report';
$active = 'reports';

$project_id = (int)($_GET['project_id'] ?? active_project_id());
$asOf = $_GET['as_of'] ?? date('Y-m-d');

$where = "ra.status IN ('active','renewed') AND rs.status IN ('pending','partial','overdue')";
$params = [];
if ($project_id) { $where .= " AND p.project_id = ?"; $params[] = $project_id; }

$rent = db_all("SELECT ra.id AS agreement_id, ra.agreement_no, ra.monthly_rent, p.property_no, t.full_name AS tenant_name, t.emergency_contact AS phone, pr.name AS project_name,
                rs.period, rs.due_date, rs.rent_amount, rs.paid_amount, rs.status AS sched_status
                FROM rent_schedule rs
                JOIN rental_agreements ra ON ra.id = rs.agreement_id
                JOIN properties p ON p.id = ra.property_id
                JOIN tenants t ON t.id = ra.tenant_id
                LEFT JOIN projects pr ON pr.id = p.project_id
                WHERE $where ORDER BY t.full_name, rs.due_date", $params);

$totalOut = 0.0; $totalRows = 0;
$byTenant = [];
foreach ($rent as $r) {
    $out = (float)$r['rent_amount'] - (float)$r['paid_amount'];
    if ($out <= 0) continue;
    $totalOut += $out;
    $totalRows++;
    $tid = $r['tenant_name'];
    if (!isset($byTenant[$tid])) $byTenant[$tid] = ['phone' => $r['phone'], 'property' => $r['property_no'], 'agreement' => $r['agreement_no'], 'project' => $r['project_name'] ?? '-', 'out' => 0.0, 'months' => 0];
    $byTenant[$tid]['out'] += $out;
    $byTenant[$tid]['months']++;
}
uasort($byTenant, function($a, $b) { return $b['out'] <=> $a['out']; });
$projects = db_all("SELECT id, name FROM projects ORDER BY name");
$projectName = '';
foreach ($projects as $p) { if ((int)$p['id'] === $project_id) { $projectName = $p['name']; break; } }
include '../../includes/header.php';
?>

<style>
@media print { .no-print,.sidebar,.main-header,.main-footer,.quick-action-btn{display:none!important}.main-content{margin:0!important;padding:10px!important}.card{border:none!important;box-shadow:none!important}body{font-size:11px}.table td,.table th{padding:4px 6px!important;font-size:11px} }
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-house-exclude me-2"></i>Tenant Outstanding Report</h5>
    <div class="ms-auto"><button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print</button></div>
</div>

<form method="get" class="card mb-3 no-print">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-2"><label class="form-label small text-muted mb-0">As of</label><input type="date" name="as_of" class="form-control" value="<?= e($asOf) ?>"></div>
            <div class="col-md-3"><label class="form-label small text-muted mb-0">Project</label>
                <select name="project_id" class="form-select"><option value="">All Projects</option><?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?></select>
            </div>
            <div class="col-md-2"><label class="form-label mb-0">&nbsp;</label><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="card print-only" style="display:none"><div class="text-center mb-2"><h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5><div class="small text-muted">Tenant Outstanding Report as of <?= fmt_date($asOf) ?><?= $projectName ? ' | ' . e($projectName) : '' ?></div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-people"></i></div><div><div class="stat-label">TENANTS WITH DUES</div><div class="stat-value"><?= count($byTenant) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-red"><div class="stat-body"><div class="stat-icon"><i class="bi bi-calendar-x"></i></div><div><div class="stat-label">OVERDUE MONTHS</div><div class="stat-value"><?= $totalRows ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-label">TOTAL OUTSTANDING</div><div class="stat-value"><?= fmt_money($totalOut) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-cyan"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash"></i></div><div><div class="stat-label">AVG PER TENANT</div><div class="stat-value"><?= count($byTenant) > 0 ? fmt_money($totalOut / count($byTenant)) : fmt_money(0) ?></div></div></div></div></div>
</div>

<div class="row g-3 mb-3 no-print">
    <div class="col-md-8"><div class="card"><div class="card-header"><i class="bi bi-bar-chart me-2"></i>Top 10 Tenants with Dues</div><div class="card-body"><canvas id="barChart" height="280"></canvas></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-header"><i class="bi bi-pie-chart me-2"></i>Outstanding Split</div><div class="card-body"><canvas id="pieChart" height="280"></canvas></div></div></div>
</div>

<div class="card">
    <div class="card-header no-print"><i class="bi bi-table me-2"></i>Tenant Dues</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>#</th><th>Tenant</th><th>Phone</th><th>Property</th><th>Agreement</th><th>Project</th><th>Overdue Months</th><th class="text-end">Outstanding</th></tr></thead>
                <tbody>
                <?php $i = 1; foreach ($byTenant as $name => $d): ?>
                    <tr><td><?= $i++ ?></td><td class="fw-medium"><?= e($name) ?></td><td class="small"><?= e($d['phone']) ?></td><td><?= e($d['property']) ?></td><td class="small"><?= e($d['agreement']) ?></td><td class="small"><?= e($d['project']) ?></td><td class="text-center"><span class="badge bg-danger"><?= $d['months'] ?></span></td><td class="text-end fw-bold text-danger"><?= fmt_money($d['out']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$byTenant): ?><tr><td colspan="8"><div class="empty-state"><i class="bi bi-check-circle"></i><p>No outstanding rent</p></div></td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr class="table-light"><td colspan="6" class="fw-bold">Total</td><td class="text-center fw-bold"><?= $totalRows ?></td><td class="text-end fw-bold text-danger"><?= fmt_money($totalOut) ?></td></tr></tfoot>
            </table>
        </div>
    </div>
</div>

<script>
var top10 = <?= json_encode(array_slice(array_keys($byTenant), 0, 10)) ?>;
var top10d = <?= json_encode(array_slice(array_map(fn($t) => $byTenant[$t]['out'], array_keys($byTenant)), 0, 10)) ?>;
new Chart(document.getElementById('barChart'), { type: 'bar', data: { labels: top10, datasets: [{ label: 'Outstanding', data: top10d, backgroundColor: 'rgba(255,99,132,0.7)' }] }, options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } } } });
new Chart(document.getElementById('pieChart'), { type: 'doughnut', data: { labels: <?= json_encode(array_slice(array_keys($byTenant), 0, 6)) ?>, datasets: [{ data: <?= json_encode(array_slice(array_map(fn($t) => $byTenant[$t]['out'], array_keys($byTenant)), 0, 6)) ?>, backgroundColor: ['#ff6384','#36a2eb','#ffce56','#4bc0c0','#9966ff','#ff9f40'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
</script>

<?php include '../../includes/footer.php'; ?>

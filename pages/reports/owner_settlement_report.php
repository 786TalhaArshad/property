<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Owner Settlement Report';
$active = 'reports';

$from = $_GET['from'] ?? date('Y-01-01');
$to = $_GET['to'] ?? date('Y-m-d');
$project_id = (int)($_GET['project_id'] ?? active_project_id());

$where = "os.settlement_date BETWEEN ? AND ?";
$params = [$from, $to];
if ($project_id) { $where .= " AND p.project_id = ?"; $params[] = $project_id; }

$settlements = db_all("SELECT os.*, o.full_name AS owner_name, ra.agreement_no, p.property_no, pr.name AS project_name
                       FROM owner_settlements os
                       JOIN owners o ON o.id = os.owner_id
                       LEFT JOIN rental_agreements ra ON ra.id = os.agreement_id
                       LEFT JOIN properties p ON p.id = ra.property_id
                       LEFT JOIN projects pr ON pr.id = p.project_id
                       WHERE $where ORDER BY os.settlement_date DESC", $params);

$totalSettled = 0.0; $totalPending = 0.0; $byOwner = [];
foreach ($settlements as $s) {
    if ($s['status'] === 'paid') $totalSettled += (float)$s['settlement_amount'];
    else $totalPending += (float)$s['settlement_amount'];
    $o = $s['owner_name'];
    if (!isset($byOwner[$o])) $byOwner[$o] = 0;
    $byOwner[$o] += (float)$s['settlement_amount'];
}
arsort($byOwner);
$projects = db_all("SELECT id, name FROM projects ORDER BY name");
$projectName = '';
foreach ($projects as $p) { if ((int)$p['id'] === $project_id) { $projectName = $p['name']; break; } }
include '../../includes/header.php';
?>

<style>
@media print { .no-print,.sidebar,.main-header,.main-footer,.quick-action-btn{display:none!important}.main-content{margin:0!important;padding:10px!important}.card{border:none!important;box-shadow:none!important}body{font-size:11px}.table td,.table th{padding:4px 6px!important;font-size:11px} }
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-wallet2 me-2"></i>Owner Settlement Report</h5>
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

<div class="card print-only" style="display:none"><div class="text-center mb-2"><h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5><div class="small text-muted">Owner Settlement Report from <?= fmt_date($from) ?> to <?= fmt_date($to) ?><?= $projectName ? ' | ' . e($projectName) : '' ?></div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-wallet2"></i></div><div><div class="stat-label">TOTAL SETTLEMENTS</div><div class="stat-value"><?= count($settlements) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-check-circle"></i></div><div><div class="stat-label">TOTAL PAID</div><div class="stat-value"><?= fmt_money($totalSettled) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-label">PENDING</div><div class="stat-value"><?= fmt_money($totalPending) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-people"></i></div><div><div class="stat-label">UNIQUE OWNERS</div><div class="stat-value"><?= count($byOwner) ?></div></div></div></div></div>
</div>

<div class="row g-3 mb-3 no-print">
    <div class="col-md-8"><div class="card"><div class="card-header"><i class="bi bi-bar-chart me-2"></i>Settlements by Owner</div><div class="card-body"><canvas id="barChart" height="280"></canvas></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-header"><i class="bi bi-pie-chart me-2"></i>Status</div><div class="card-body"><canvas id="pieChart" height="280"></canvas></div></div></div>
</div>

<div class="card">
    <div class="card-header no-print"><i class="bi bi-table me-2"></i>Settlements</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Date</th><th>Owner</th><th>Agreement</th><th>Property</th><th>Project</th><th class="text-end">Rent Income</th><th class="text-end">Deductions</th><th class="text-end">Settlement</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($settlements as $s): ?>
                    <tr><td><?= fmt_date($s['settlement_date']) ?></td><td class="fw-medium"><?= e($s['owner_name']) ?></td><td class="small"><?= e($s['agreement_no'] ?? '-') ?></td><td><?= e($s['property_no'] ?? '-') ?></td><td class="small"><?= e($s['project_name'] ?? '-') ?></td><td class="text-end"><?= fmt_money($s['rent_income']) ?></td><td class="text-end text-danger"><?= fmt_money($s['deductions']) ?></td><td class="text-end fw-bold"><?= fmt_money($s['settlement_amount']) ?></td><td><?= status_badge($s['status']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$settlements): ?><tr><td colspan="9"><div class="empty-state"><i class="bi bi-inbox"></i><p>No settlements in this period</p></div></td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr class="table-light"><td colspan="5" class="fw-bold">Total</td><td class="text-end fw-bold"><?= fmt_money(array_sum(array_column($settlements, 'rent_income'))) ?></td><td class="text-end fw-bold text-danger"><?= fmt_money(array_sum(array_column($settlements, 'deductions'))) ?></td><td class="text-end fw-bold"><?= fmt_money($totalSettled + $totalPending) ?></td><td></td></tr></tfoot>
            </table>
        </div>
    </div>
</div>

<script>
var ownerLabels = <?= json_encode(array_keys(array_slice($byOwner, 0, 10))) ?>;
var ownerData = <?= json_encode(array_values(array_slice($byOwner, 0, 10))) ?>;
new Chart(document.getElementById('barChart'), { type: 'bar', data: { labels: ownerLabels, datasets: [{ label: 'Settlement Amount', data: ownerData, backgroundColor: 'rgba(54,162,235,0.7)' }] }, options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } } } });
new Chart(document.getElementById('pieChart'), { type: 'doughnut', data: { labels: ['Paid', 'Pending'], datasets: [{ data: [<?= $totalSettled ?>, <?= $totalPending ?>], backgroundColor: ['#4bc0c0', '#ffce56'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
</script>

<?php include '../../includes/footer.php'; ?>

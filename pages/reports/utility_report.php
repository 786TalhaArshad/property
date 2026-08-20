<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Utility Report';
$active = 'reports';

$from = $_GET['from'] ?? date('Y-01-01');
$to = $_GET['to'] ?? date('Y-m-d');
$project_id = (int)($_GET['project_id'] ?? active_project_id());

$where = "ub.billing_month >= ? AND ub.billing_month <= ?";
$ymFrom = date('Y-m', strtotime($from));
$ymTo = date('Y-m', strtotime($to));
$params = [$ymFrom, $ymTo];
if ($project_id) { $where .= " AND p.project_id = ?"; $params[] = $project_id; }

$bills = db_all("SELECT ub.*, u.utility_type, p.property_no, t.full_name AS tenant_name, pr.name AS project_name
                 FROM utility_bills ub
                 JOIN utilities u ON u.id = ub.utility_id
                 JOIN properties p ON p.id = ub.property_id
                 LEFT JOIN tenants t ON t.id = ub.tenant_id
                 LEFT JOIN projects pr ON pr.id = p.project_id
                 WHERE $where ORDER BY ub.billing_month DESC, p.property_no", $params);

$totalAmount = 0.0; $totalPaid = 0.0; $byType = [];
foreach ($bills as $b) {
    $totalAmount += (float)$b['amount'];
    $totalPaid += (float)$b['paid_amount'];
    $t = $b['utility_type'];
    if (!isset($byType[$t])) $byType[$t] = ['amount' => 0, 'paid' => 0, 'count' => 0];
    $byType[$t]['amount'] += (float)$b['amount'];
    $byType[$t]['paid'] += (float)$b['paid_amount'];
    $byType[$t]['count']++;
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
    <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Utility Report</h5>
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

<div class="card print-only" style="display:none"><div class="text-center mb-2"><h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5><div class="small text-muted">Utility Report from <?= fmt_date($from) ?> to <?= fmt_date($to) ?><?= $projectName ? ' | ' . e($projectName) : '' ?></div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-lightning"></i></div><div><div class="stat-label">TOTAL BILLS</div><div class="stat-value"><?= count($bills) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">TOTAL AMOUNT</div><div class="stat-value"><?= fmt_money($totalAmount) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-check-circle"></i></div><div><div class="stat-label">TOTAL PAID</div><div class="stat-value"><?= fmt_money($totalPaid) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-red"><div class="stat-body"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-label">OUTSTANDING</div><div class="stat-value"><?= fmt_money($totalAmount - $totalPaid) ?></div></div></div></div></div>
</div>

<div class="row g-3 mb-3 no-print">
    <div class="col-md-8"><div class="card"><div class="card-header"><i class="bi bi-bar-chart me-2"></i>By Utility Type</div><div class="card-body"><canvas id="barChart" height="250"></canvas></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-header"><i class="bi bi-pie-chart me-2"></i>Split</div><div class="card-body"><canvas id="pieChart" height="250"></canvas></div></div></div>
</div>

<div class="card">
    <div class="card-header no-print"><i class="bi bi-table me-2"></i>Utility Bills</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Property</th><th>Tenant</th><th>Project</th><th>Type</th><th>Month</th><th class="text-end">Amount</th><th class="text-end">Paid</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($bills as $b): ?>
                    <tr><td class="fw-medium"><?= e($b['property_no']) ?></td><td><?= e($b['tenant_name'] ?? '-') ?></td><td class="small"><?= e($b['project_name'] ?? '-') ?></td><td><span class="badge bg-info"><?= e($b['utility_type']) ?></span></td><td><?= e($b['billing_month']) ?></td><td class="text-end"><?= fmt_money($b['amount']) ?></td><td class="text-end"><?= fmt_money($b['paid_amount']) ?></td><td><?= status_badge($b['status']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$bills): ?><tr><td colspan="8"><div class="empty-state"><i class="bi bi-inbox"></i><p>No utility bills</p></div></td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr class="table-light"><td colspan="5" class="fw-bold">Total</td><td class="text-end fw-bold"><?= fmt_money($totalAmount) ?></td><td class="text-end fw-bold"><?= fmt_money($totalPaid) ?></td><td></td></tr></tfoot>
            </table>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('barChart'), { type: 'bar', data: { labels: <?= json_encode(array_keys($byType)) ?>, datasets: [{ label: 'Amount', data: <?= json_encode(array_column($byType, 'amount')) ?>, backgroundColor: 'rgba(54,162,235,0.7)' }, { label: 'Paid', data: <?= json_encode(array_column($byType, 'paid')) ?>, backgroundColor: 'rgba(75,192,192,0.7)' }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
new Chart(document.getElementById('pieChart'), { type: 'doughnut', data: { labels: <?= json_encode(array_keys($byType)) ?>, datasets: [{ data: <?= json_encode(array_column($byType, 'amount')) ?>, backgroundColor: ['#36a2eb','#ffce56','#4bc0c0','#ff6384','#9966ff'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
</script>

<?php include '../../includes/footer.php'; ?>

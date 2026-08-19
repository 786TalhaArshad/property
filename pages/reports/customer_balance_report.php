<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Customer Balance Report';
$active = 'reports';

$project_id = (int)($_GET['project_id'] ?? active_project_id());

$where = "b.status <> 'cancelled'";
$params = [];
if ($project_id) { $where .= " AND p.project_id = ?"; $params[] = $project_id; }

$customers = db_all("SELECT c.id, c.full_name, c.customer_no, c.phone, c.balance_type,
                     COUNT(DISTINCT b.id) AS bookings,
                     COALESCE(SUM(b.total_price),0) AS total_value,
                     COALESCE((SELECT SUM(r.amount) FROM receipts r WHERE r.customer_id = c.id),0) AS total_paid,
                     COALESCE(SUM(b.total_price),0) - COALESCE((SELECT SUM(r.amount) FROM receipts r WHERE r.customer_id = c.id),0) AS balance
                     FROM customers c
                     LEFT JOIN bookings b ON b.customer_id = c.id AND $where
                     GROUP BY c.id HAVING balance != 0
                     ORDER BY balance DESC", $params);

$totalReceivable = 0.0; $totalPayable = 0.0;
foreach ($customers as $c) {
    if ($c['balance'] > 0) $totalReceivable += $c['balance'];
    else $totalPayable += abs($c['balance']);
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
    <h5 class="mb-0"><i class="bi bi-people me-2"></i>Customer Balance Report</h5>
    <div class="ms-auto"><button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print</button></div>
</div>

<form method="get" class="card mb-3 no-print">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-3"><label class="form-label small text-muted mb-0">Project</label>
                <select name="project_id" class="form-select"><option value="">All Projects</option><?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?></select>
            </div>
            <div class="col-md-2"><label class="form-label mb-0">&nbsp;</label><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="card print-only" style="display:none"><div class="text-center mb-2"><h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5><div class="small text-muted">Customer Balance Report<?= $projectName ? ' | ' . e($projectName) : '' ?></div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-people"></i></div><div><div class="stat-label">CUSTOMERS</div><div class="stat-value"><?= count($customers) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-arrow-up-circle"></i></div><div><div class="stat-label">RECEIVABLE</div><div class="stat-value"><?= fmt_money($totalReceivable) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-red"><div class="stat-body"><div class="stat-icon"><i class="bi bi-arrow-down-circle"></i></div><div><div class="stat-label">PAYABLE</div><div class="stat-value"><?= fmt_money($totalPayable) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-balance"></i></div><div><div class="stat-label">NET</div><div class="stat-value"><?= fmt_money($totalReceivable - $totalPayable) ?></div></div></div></div></div>
</div>

<div class="row g-3 mb-3 no-print">
    <div class="col-md-6"><div class="card"><div class="card-header"><i class="bi bi-pie-chart me-2"></i>Receivable vs Payable</div><div class="card-body"><canvas id="pieChart" height="250"></canvas></div></div></div>
    <div class="col-md-6"><div class="card"><div class="card-header"><i class="bi bi-bar-chart me-2"></i>Top 10 Balances</div><div class="card-body"><canvas id="barChart" height="250"></canvas></div></div></div>
</div>

<div class="card">
    <div class="card-header no-print"><i class="bi bi-table me-2"></i>Customer Balances</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>#</th><th>Customer</th><th>CNIC</th><th>Phone</th><th>Bookings</th><th class="text-end">Total Value</th><th class="text-end">Total Paid</th><th class="text-end">Balance</th><th>Type</th></tr></thead>
                <tbody>
                <?php foreach ($customers as $i => $c): ?>
                    <tr><td><?= $i + 1 ?></td><td class="fw-medium"><?= e($c['full_name']) ?></td><td class="small"><?= e($c['customer_no']) ?></td><td><?= e($c['phone']) ?></td><td class="text-center"><?= $c['bookings'] ?></td><td class="text-end"><?= fmt_money($c['total_value']) ?></td><td class="text-end"><?= fmt_money($c['total_paid']) ?></td><td class="text-end fw-bold <?= $c['balance'] > 0 ? 'text-danger' : 'text-success' ?>"><?= fmt_money($c['balance']) ?></td><td><span class="badge bg-<?= $c['balance'] > 0 ? 'danger' : 'success' ?>"><?= $c['balance'] > 0 ? 'Receivable' : 'Payable' ?></span></td></tr>
                <?php endforeach; ?>
                <?php if (!$customers): ?><tr><td colspan="9"><div class="empty-state"><i class="bi bi-check-circle"></i><p>All clear — no balances</p></div></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('pieChart'), { type: 'doughnut', data: { labels: ['Receivable', 'Payable'], datasets: [{ data: [<?= $totalReceivable ?>, <?= $totalPayable ?>], backgroundColor: ['#ff6384', '#4bc0c0'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
var top10 = <?= json_encode(array_slice($customers, 0, 10)) ?>;
new Chart(document.getElementById('barChart'), { type: 'bar', data: { labels: top10.map(c => c.full_name), datasets: [{ label: 'Balance', data: top10.map(c => parseFloat(c.balance)), backgroundColor: top10.map(c => parseFloat(c.balance) > 0 ? 'rgba(255,99,132,0.7)' : 'rgba(75,192,192,0.7)') }] }, options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } } } });
</script>

<?php include '../../includes/footer.php'; ?>

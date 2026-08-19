<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Customer Outstanding Report';
$active = 'reports';

$project_id = (int)($_GET['project_id'] ?? active_project_id());
$asOf = $_GET['as_of'] ?? date('Y-m-d');

$where = "b.status <> 'cancelled' AND i.status IN ('pending','partial','overdue')";
$params = [];
if ($project_id) { $where .= " AND p.project_id = ?"; $params[] = $project_id; }

$rows = db_all("SELECT c.id, c.full_name, c.customer_no, c.phone,
               COUNT(DISTINCT b.id) AS bookings,
               SUM(b.total_price) AS total_value,
               SUM((SELECT COALESCE(SUM(r.amount),0) FROM receipts r WHERE r.booking_id = b.id)) AS total_paid,
               SUM(i.amount - i.paid_amount) AS outstanding
               FROM installments i
               JOIN bookings b ON b.id = i.booking_id
               JOIN customers c ON c.id = b.customer_id
               JOIN properties p ON p.id = b.property_id
               WHERE $where AND (i.amount - i.paid_amount) > 0
               GROUP BY c.id ORDER BY outstanding DESC", $params);

$totalOutstanding = 0.0; $totalBookings = 0; $totalValue = 0.0; $totalPaid = 0.0;
foreach ($rows as $r) {
    $totalOutstanding += (float)$r['outstanding'];
    $totalBookings += (int)$r['bookings'];
    $totalValue += (float)$r['total_value'];
    $totalPaid += (float)$r['total_paid'];
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
    <h5 class="mb-0"><i class="bi bi-people me-2"></i>Customer Outstanding Report</h5>
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

<div class="card print-only" style="display:none"><div class="text-center mb-2"><h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5><div class="small text-muted">Customer Outstanding Report as of <?= fmt_date($asOf) ?><?= $projectName ? ' | ' . e($projectName) : '' ?></div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-people"></i></div><div><div class="stat-label">CUSTOMERS</div><div class="stat-value"><?= count($rows) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-journal-check"></i></div><div><div class="stat-label">TOTAL BOOKINGS</div><div class="stat-value"><?= $totalBookings ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-label">TOTAL OUTSTANDING</div><div class="stat-value"><?= fmt_money($totalOutstanding) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-check-circle"></i></div><div><div class="stat-label">COLLECTION RATE</div><div class="stat-value"><?= $totalValue > 0 ? number_format($totalPaid / $totalValue * 100, 1) : '0.0' ?>%</div></div></div></div></div>
</div>

<div class="row g-3 mb-3 no-print">
    <div class="col-md-8"><div class="card"><div class="card-header"><i class="bi bi-bar-chart me-2"></i>Top 10 Outstanding Customers</div><div class="card-body"><canvas id="barChart" height="280"></canvas></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-header"><i class="bi bi-pie-chart me-2"></i>Outstanding Split</div><div class="card-body"><canvas id="pieChart" height="280"></canvas></div></div></div>
</div>

<div class="card">
    <div class="card-header no-print"><i class="bi bi-table me-2"></i>Customer Balances</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>#</th><th>Customer</th><th>CNIC</th><th>Phone</th><th>Bookings</th><th class="text-end">Total Value</th><th class="text-end">Total Paid</th><th class="text-end">Outstanding</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $i => $r): ?>
                    <tr><td><?= $i + 1 ?></td><td class="fw-medium"><?= e($r['full_name']) ?></td><td class="small"><?= e($r['customer_no']) ?></td><td><?= e($r['phone']) ?></td><td class="text-center"><?= $r['bookings'] ?></td><td class="text-end"><?= fmt_money($r['total_value']) ?></td><td class="text-end"><?= fmt_money($r['total_paid']) ?></td><td class="text-end fw-bold text-danger"><?= fmt_money($r['outstanding']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?><tr><td colspan="8"><div class="empty-state"><i class="bi bi-check-circle"></i><p>No outstanding customers</p></div></td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr class="table-light"><td colspan="4" class="fw-bold">Total</td><td class="text-center fw-bold"><?= $totalBookings ?></td><td class="text-end fw-bold"><?= fmt_money($totalValue) ?></td><td class="text-end fw-bold"><?= fmt_money($totalPaid) ?></td><td class="text-end fw-bold text-danger"><?= fmt_money($totalOutstanding) ?></td></tr></tfoot>
            </table>
        </div>
    </div>
</div>

<script>
var top10 = <?= json_encode(array_slice($rows, 0, 10)) ?>;
new Chart(document.getElementById('barChart'), { type: 'bar', data: { labels: top10.map(r => r.full_name), datasets: [{ label: 'Outstanding', data: top10.map(r => parseFloat(r.outstanding)), backgroundColor: 'rgba(255,159,64,0.7)' }] }, options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } } } });
new Chart(document.getElementById('pieChart'), { type: 'doughnut', data: { labels: ['Paid', 'Outstanding'], datasets: [{ data: [<?= $totalPaid ?>, <?= $totalOutstanding ?>], backgroundColor: ['#4bc0c0', '#ff6384'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
</script>

<?php include '../../includes/footer.php'; ?>

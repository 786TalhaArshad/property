<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Vendor Outstanding Report';
$active = 'reports';

$vendors = db_all("SELECT v.id, v.business_name, v.vendor_no, v.phone,
                   (SELECT COALESCE(SUM(pu.total_amount),0) FROM purchases pu WHERE pu.vendor_id = v.id) AS total_purchases,
                   (SELECT COALESCE(SUM(vp.amount),0) FROM vendor_payments vp WHERE vp.vendor_id = v.id) AS total_paid
                   FROM vendors v WHERE v.status = 1
                   HAVING total_purchases > 0
                   ORDER BY (total_purchases - total_paid) DESC");

$totalPurchases = 0.0; $totalPaid = 0.0; $totalOutstanding = 0.0;
foreach ($vendors as $v) {
    $totalPurchases += (float)$v['total_purchases'];
    $totalPaid += (float)$v['total_paid'];
    $totalOutstanding += (float)$v['total_purchases'] - (float)$v['total_paid'];
}
$chartLabels = []; $chartData = [];
foreach (array_slice($vendors, 0, 10) as $v) { $chartLabels[] = $v['business_name']; $chartData[] = (float)$v['total_purchases'] - (float)$v['total_paid']; }
include '../../includes/header.php';
?>

<style>
@media print { .no-print,.sidebar,.main-header,.main-footer,.quick-action-btn{display:none!important}.main-content{margin:0!important;padding:10px!important}.card{border:none!important;box-shadow:none!important}body{font-size:11px}.table td,.table th{padding:4px 6px!important;font-size:11px} }
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-shop me-2"></i>Vendor Outstanding Report</h5>
    <div class="ms-auto"><button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print</button></div>
</div>

<div class="card print-only" style="display:none"><div class="text-center mb-2"><h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5><div class="small text-muted">Vendor Outstanding Report</div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-shop"></i></div><div><div class="stat-label">VENDORS</div><div class="stat-value"><?= count($vendors) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-bag"></i></div><div><div class="stat-label">TOTAL PURCHASES</div><div class="stat-value"><?= fmt_money($totalPurchases) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-check-circle"></i></div><div><div class="stat-label">TOTAL PAID</div><div class="stat-value"><?= fmt_money($totalPaid) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-red"><div class="stat-body"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-label">OUTSTANDING</div><div class="stat-value"><?= fmt_money($totalOutstanding) ?></div></div></div></div></div>
</div>

<div class="row g-3 mb-3 no-print">
    <div class="col-md-8"><div class="card"><div class="card-header"><i class="bi bi-bar-chart me-2"></i>Top 10 Vendors by Outstanding</div><div class="card-body"><canvas id="barChart" height="280"></canvas></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-header"><i class="bi bi-pie-chart me-2"></i>Paid vs Outstanding</div><div class="card-body"><canvas id="pieChart" height="280"></canvas></div></div></div>
</div>

<div class="card">
    <div class="card-header no-print"><i class="bi bi-table me-2"></i>Vendor Balances</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>#</th><th>Vendor</th><th>Code</th><th>Phone</th><th class="text-end">Total Purchases</th><th class="text-end">Total Paid</th><th class="text-end">Outstanding</th></tr></thead>
                <tbody>
                <?php foreach ($vendors as $i => $v): $out = (float)$v['total_purchases'] - (float)$v['total_paid']; ?>
                    <tr><td><?= $i + 1 ?></td><td class="fw-medium"><?= e($v['business_name']) ?></td><td class="small"><?= e($v['vendor_no']) ?></td><td><?= e($v['phone']) ?></td><td class="text-end"><?= fmt_money($v['total_purchases']) ?></td><td class="text-end"><?= fmt_money($v['total_paid']) ?></td><td class="text-end fw-bold <?= $out > 0 ? 'text-danger' : '' ?>"><?= fmt_money($out) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$vendors): ?><tr><td colspan="7"><div class="empty-state"><i class="bi bi-check-circle"></i><p>No vendor outstanding</p></div></td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr class="table-light"><td colspan="4" class="fw-bold">Total</td><td class="text-end fw-bold"><?= fmt_money($totalPurchases) ?></td><td class="text-end fw-bold"><?= fmt_money($totalPaid) ?></td><td class="text-end fw-bold text-danger"><?= fmt_money($totalOutstanding) ?></td></tr></tfoot>
            </table>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('barChart'), { type: 'bar', data: { labels: <?= json_encode($chartLabels) ?>, datasets: [{ label: 'Outstanding', data: <?= json_encode($chartData) ?>, backgroundColor: 'rgba(255,159,64,0.7)' }] }, options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } } } });
new Chart(document.getElementById('pieChart'), { type: 'doughnut', data: { labels: ['Paid', 'Outstanding'], datasets: [{ data: [<?= $totalPaid ?>, <?= $totalOutstanding ?>], backgroundColor: ['#4bc0c0', '#ff6384'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
</script>

<?php include '../../includes/footer.php'; ?>

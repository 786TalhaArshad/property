<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Project Summary Report';
$active = 'reports';

$hasMi = (bool)(db_get("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'material_issues'")['c'] ?? 0);
$miCol = $hasMi ? '(SELECT COALESCE(SUM(mi.total_amount),0) FROM material_issues mi WHERE mi.project_id = p.id)' : '0';
$projects = db_all("SELECT p.*,
    (SELECT COUNT(*) FROM properties pr WHERE pr.project_id = p.id) AS total_properties,
    (SELECT COUNT(*) FROM properties pr WHERE pr.project_id = p.id AND pr.status = 'available') AS available,
    (SELECT COUNT(*) FROM properties pr WHERE pr.project_id = p.id AND pr.status = 'booked') AS booked,
    (SELECT COUNT(*) FROM properties pr WHERE pr.project_id = p.id AND pr.status = 'sold') AS sold,
    (SELECT COALESCE(SUM(b.total_price),0) FROM bookings b JOIN properties pr ON pr.id = b.property_id WHERE pr.project_id = p.id AND b.status <> 'cancelled') AS sales_revenue,
    (SELECT COALESCE(SUM(rs.paid_amount),0) FROM rent_schedule rs JOIN rental_agreements ra ON ra.id = rs.agreement_id JOIN properties pr ON pr.id = ra.property_id WHERE pr.project_id = p.id) AS rent_revenue,
    " . $miCol . " AS material_cost,
    (SELECT COALESCE(SUM(ce.amount),0) FROM contractor_entries ce WHERE ce.project_id = p.id AND ce.entry_type = 'paid') AS contractor_paid
    FROM projects p WHERE p.status = 1 ORDER BY p.name");

$totalProperties = 0; $totalSold = 0; $totalSales = 0.0; $totalRent = 0.0; $totalMaterial = 0.0; $totalContractor = 0.0;
foreach ($projects as $p) {
    $totalProperties += (int)$p['total_properties'];
    $totalSold += (int)$p['sold'];
    $totalSales += (float)$p['sales_revenue'];
    $totalRent += (float)$p['rent_revenue'];
    $totalMaterial += (float)$p['material_cost'];
    $totalContractor += (float)$p['contractor_paid'];
}
include '../../includes/header.php';
?>

<style>
@media print { .no-print,.sidebar,.main-header,.main-footer,.quick-action-btn{display:none!important}.main-content{margin:0!important;padding:10px!important}.card{border:none!important;box-shadow:none!important}body{font-size:11px}.table td,.table th{padding:4px 6px!important;font-size:11px} }
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-building me-2"></i>Project Summary Report</h5>
    <div class="ms-auto"><button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print</button></div>
</div>

<div class="card print-only" style="display:none"><div class="text-center mb-2"><h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5><div class="small text-muted">Project Summary Report</div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-building"></i></div><div><div class="stat-label">PROJECTS</div><div class="stat-value"><?= count($projects) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">SALES REVENUE</div><div class="stat-value"><?= fmt_money($totalSales) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-house-heart"></i></div><div><div class="stat-label">RENT REVENUE</div><div class="stat-value"><?= fmt_money($totalRent) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-hammer"></i></div><div><div class="stat-label">CONSTRUCTION COST</div><div class="stat-value"><?= fmt_money($totalMaterial + $totalContractor) ?></div></div></div></div></div>
</div>

<div class="row g-3 mb-3 no-print">
    <div class="col-md-8"><div class="card"><div class="card-header"><i class="bi bi-bar-chart me-2"></i>Revenue by Project</div><div class="card-body"><canvas id="barChart" height="300"></canvas></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-header"><i class="bi bi-pie-chart me-2"></i>Property Status</div><div class="card-body"><canvas id="pieChart" height="300"></canvas></div></div></div>
</div>

<div class="card">
    <div class="card-header no-print"><i class="bi bi-table me-2"></i>Project Performance</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Project</th><th class="text-center">Properties</th><th class="text-center">Available</th><th class="text-center">Booked</th><th class="text-center">Sold</th><th class="text-end">Sales Revenue</th><th class="text-end">Rent Revenue</th><th class="text-end">Material Cost</th><th class="text-end">Contractor Paid</th><th class="text-end">Net</th></tr></thead>
                <tbody>
                <?php foreach ($projects as $p):
                    $net = (float)$p['sales_revenue'] + (float)$p['rent_revenue'] - (float)$p['material_cost'] - (float)$p['contractor_paid'];
                ?>
                    <tr><td class="fw-medium"><?= e($p['name']) ?></td><td class="text-center"><?= $p['total_properties'] ?></td><td class="text-center"><span class="badge bg-success"><?= $p['available'] ?></span></td><td class="text-center"><span class="badge bg-warning"><?= $p['booked'] ?></span></td><td class="text-center"><span class="badge bg-info"><?= $p['sold'] ?></span></td><td class="text-end"><?= fmt_money($p['sales_revenue']) ?></td><td class="text-end"><?= fmt_money($p['rent_revenue']) ?></td><td class="text-end text-danger"><?= fmt_money($p['material_cost']) ?></td><td class="text-end text-danger"><?= fmt_money($p['contractor_paid']) ?></td><td class="text-end fw-bold <?= $net >= 0 ? 'text-success' : 'text-danger' ?>"><?= fmt_money($net) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$projects): ?><tr><td colspan="10"><div class="empty-state"><i class="bi bi-inbox"></i><p>No projects</p></div></td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr class="table-light"><td class="fw-bold">Total</td><td class="text-center fw-bold"><?= $totalProperties ?></td><td></td><td></td><td class="text-center fw-bold"><?= $totalSold ?></td><td class="text-end fw-bold"><?= fmt_money($totalSales) ?></td><td class="text-end fw-bold"><?= fmt_money($totalRent) ?></td><td class="text-end fw-bold text-danger"><?= fmt_money($totalMaterial) ?></td><td class="text-end fw-bold text-danger"><?= fmt_money($totalContractor) ?></td><td class="text-end fw-bold"><?= fmt_money($totalSales + $totalRent - $totalMaterial - $totalContractor) ?></td></tr></tfoot>
            </table>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('barChart'), { type: 'bar', data: { labels: <?= json_encode(array_column($projects, 'name')) ?>, datasets: [{ label: 'Sales', data: <?= json_encode(array_map(fn($p) => (float)$p['sales_revenue'], $projects)) ?>, backgroundColor: 'rgba(54,162,235,0.7)' }, { label: 'Rent', data: <?= json_encode(array_map(fn($p) => (float)$p['rent_revenue'], $projects)) ?>, backgroundColor: 'rgba(75,192,192,0.7)' }, { label: 'Cost', data: <?= json_encode(array_map(fn($p) => (float)$p['material_cost'] + (float)$p['contractor_paid'], $projects)) ?>, backgroundColor: 'rgba(255,99,132,0.7)' }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
new Chart(document.getElementById('pieChart'), { type: 'doughnut', data: { labels: ['Available', 'Booked', 'Sold'], datasets: [{ data: [<?= array_sum(array_column($projects, 'available')) ?>, <?= array_sum(array_column($projects, 'booked')) ?>, <?= $totalSold ?>], backgroundColor: ['#4bc0c0', '#ffce56', '#36a2eb'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
</script>

<?php include '../../includes/footer.php'; ?>

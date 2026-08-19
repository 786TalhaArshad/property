<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Stock Summary Report';
$active = 'reports';

$hasSm = (bool)(db_get("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'stock_movements'")['c'] ?? 0);
$hasStockCol = (bool)(db_get("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'stock_qty'")['c'] ?? 0);
$hasAvgCol = (bool)(db_get("SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'avg_cost'")['c'] ?? 0);
$sqExpr = $hasStockCol ? 'p.stock_qty' : '0';
$acExpr = $hasAvgCol ? 'p.avg_cost' : '0';
$smPurchased = $hasSm ? "(SELECT COALESCE(SUM(CASE WHEN movement_type='purchase' THEN quantity ELSE 0 END),0) FROM stock_movements sm WHERE sm.product_id = p.id)" : "0";
$smIssued = $hasSm ? "(SELECT COALESCE(SUM(CASE WHEN movement_type='issue' THEN quantity ELSE 0 END),0) FROM stock_movements sm WHERE sm.product_id = p.id)" : "0";

$products = db_all("SELECT p.*, 
                    $sqExpr AS stock_qty,
                    $acExpr AS avg_cost,
                    $smPurchased AS purchased,
                    $smIssued AS issued
                    FROM products p WHERE p.status = 1 ORDER BY p.name");

$totalProducts = 0; $totalValue = 0.0; $lowStock = 0;
foreach ($products as $p) {
    $totalProducts++;
    $totalValue += (float)$p['stock_qty'] * (float)$p['avg_cost'];
    if ((float)$p['stock_qty'] <= 0) $lowStock++;
}
$chartLabels = []; $chartValues = [];
foreach (array_slice($products, 0, 10) as $p) {
    $chartLabels[] = $p['name'];
    $chartValues[] = (float)$p['stock_qty'] * (float)$p['avg_cost'];
}
include '../../includes/header.php';
?>

<style>
@media print { .no-print,.sidebar,.main-header,.main-footer,.quick-action-btn{display:none!important}.main-content{margin:0!important;padding:10px!important}.card{border:none!important;box-shadow:none!important}body{font-size:11px}.table td,.table th{padding:4px 6px!important;font-size:11px} }
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Stock Summary Report</h5>
    <div class="ms-auto"><button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print</button></div>
</div>

<div class="card print-only" style="display:none"><div class="text-center mb-2"><h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5><div class="small text-muted">Stock Summary Report</div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-box-seam"></i></div><div><div class="stat-label">TOTAL PRODUCTS</div><div class="stat-value"><?= $totalProducts ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">TOTAL STOCK VALUE</div><div class="stat-value"><?= fmt_money($totalValue) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div><div><div class="stat-label">OUT OF STOCK</div><div class="stat-value"><?= $lowStock ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-arrow-up-right"></i></div><div><div class="stat-label">TOTAL ISSUED</div><div class="stat-value"><?= fmt_money(array_sum(array_column($products, 'issued'))) ?></div></div></div></div></div>
</div>

<div class="row g-3 mb-3 no-print">
    <div class="col-md-8"><div class="card"><div class="card-header"><i class="bi bi-bar-chart me-2"></i>Top 10 Products by Value</div><div class="card-body"><canvas id="barChart" height="280"></canvas></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-header"><i class="bi bi-pie-chart me-2"></i>Stock Distribution</div><div class="card-body"><canvas id="pieChart" height="280"></canvas></div></div></div>
</div>

<div class="card">
    <div class="card-header no-print"><i class="bi bi-table me-2"></i>Product Stock</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>#</th><th>Product</th><th>Category</th><th>Unit</th><th class="text-end">In Stock</th><th class="text-end">Avg Cost</th><th class="text-end">Total Value</th><th class="text-end">Purchased</th><th class="text-end">Issued</th></tr></thead>
                <tbody>
                <?php foreach ($products as $i => $p): $val = (float)$p['stock_qty'] * (float)$p['avg_cost']; ?>
                    <tr><td><?= $i + 1 ?></td><td class="fw-medium"><?= e($p['product_no']) ?> - <?= e($p['name']) ?></td><td class="small"><?= e($p['category'] ?? '-') ?></td><td><?= e($p['unit'] ?? '-') ?></td><td class="text-end <?= (float)$p['stock_qty'] <= 0 ? 'text-danger fw-bold' : '' ?>"><?= number_format((float)$p['stock_qty'], 2) ?></td><td class="text-end"><?= fmt_money($p['avg_cost']) ?></td><td class="text-end fw-bold"><?= fmt_money($val) ?></td><td class="text-end"><?= number_format((float)$p['purchased'], 2) ?></td><td class="text-end text-danger"><?= number_format((float)$p['issued'], 2) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$products): ?><tr><td colspan="9"><div class="empty-state"><i class="bi bi-inbox"></i><p>No products</p></div></td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr class="table-light"><td colspan="5" class="fw-bold">Total Value</td><td></td><td class="text-end fw-bold"><?= fmt_money($totalValue) ?></td><td></td><td></td></tr></tfoot>
            </table>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('barChart'), { type: 'bar', data: { labels: <?= json_encode($chartLabels) ?>, datasets: [{ label: 'Value', data: <?= json_encode($chartValues) ?>, backgroundColor: 'rgba(54,162,235,0.7)' }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });
new Chart(document.getElementById('pieChart'), { type: 'doughnut', data: { labels: <?= json_encode($chartLabels) ?>, datasets: [{ data: <?= json_encode($chartValues) ?>, backgroundColor: ['#36a2eb','#ffce56','#4bc0c0','#ff6384','#9966ff','#ff9f40','#c9cbcf','#7bc8a4','#e7e9ed','#f7464a'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
</script>

<?php include '../../includes/footer.php'; ?>

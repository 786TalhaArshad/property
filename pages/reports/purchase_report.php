<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Purchase Report';
$active = 'reports';

$from = $_GET['from'] ?? date('Y-01-01');
$to = $_GET['to'] ?? date('Y-m-d');
$project_id = (int)($_GET['project_id'] ?? active_project_id());

$hasPu = (bool)(db_get("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'purchases'")['c'] ?? 0);
$hasPi = $hasPu && (bool)(db_get("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'purchase_items'")['c'] ?? 0);

$purchases = [];
if ($hasPu) {
    $where = "pu.purchase_date BETWEEN ? AND ?";
    $params = [$from, $to];
    if ($project_id) { $where .= " AND pu.project_id = ?"; $params[] = $project_id; }

    $piJoin = $hasPi ? "LEFT JOIN (SELECT purchase_id, COUNT(*) AS item_count FROM purchase_items GROUP BY purchase_id) pi ON pi.purchase_id = pu.id" : "";
    $piCol = $hasPi ? "pi.item_count" : "0";
    $purchases = db_all("SELECT pu.*, v.business_name AS vendor_name, v.vendor_no, pr.name AS project_name,
                         $piCol AS item_count
                         FROM purchases pu
                         LEFT JOIN vendors v ON v.id = pu.vendor_id
                         LEFT JOIN projects pr ON pr.id = pu.project_id
                         $piJoin
                         WHERE $where ORDER BY pu.purchase_date DESC", $params);
}

$totalAmount = 0.0; $totalPaid = 0.0; $byVendor = [];
foreach ($purchases as $p) {
    $totalAmount += (float)$p['total_amount'];
    $totalPaid += (float)$p['paid_amount'];
    $vn = $p['vendor_name'] ?? 'Direct';
    if (!isset($byVendor[$vn])) $byVendor[$vn] = 0;
    $byVendor[$vn] += (float)$p['total_amount'];
}
$monthly = [];
foreach ($purchases as $p) { $ym = date('Y-m', strtotime($p['purchase_date'])); $monthly[$ym] = ($monthly[$ym] ?? 0) + (float)$p['total_amount']; }
ksort($monthly);
$chartLabels = []; $chartData = [];
foreach ($monthly as $ym => $amt) { $chartLabels[] = date('M y', strtotime($ym . '-01')); $chartData[] = $amt; }
$projects = db_all("SELECT id, name FROM projects ORDER BY name");
$projectName = '';
foreach ($projects as $p) { if ((int)$p['id'] === $project_id) { $projectName = $p['name']; break; } }
include '../../includes/header.php';
?>

<style>
@media print { .no-print,.sidebar,.main-header,.main-footer,.quick-action-btn{display:none!important}.main-content{margin:0!important;padding:10px!important}.card{border:none!important;box-shadow:none!important}body{font-size:11px}.table td,.table th{padding:4px 6px!important;font-size:11px} }
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-bag me-2"></i>Purchase Report</h5>
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

<div class="card print-only" style="display:none"><div class="text-center mb-2"><h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5><div class="small text-muted">Purchase Report from <?= fmt_date($from) ?> to <?= fmt_date($to) ?><?= $projectName ? ' | ' . e($projectName) : '' ?></div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-bag"></i></div><div><div class="stat-label">TOTAL PURCHASES</div><div class="stat-value"><?= count($purchases) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">TOTAL AMOUNT</div><div class="stat-value"><?= fmt_money($totalAmount) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-check-circle"></i></div><div><div class="stat-label">TOTAL PAID</div><div class="stat-value"><?= fmt_money($totalPaid) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-label">OUTSTANDING</div><div class="stat-value"><?= fmt_money($totalAmount - $totalPaid) ?></div></div></div></div></div>
</div>

<div class="row g-3 mb-3 no-print">
    <div class="col-md-8"><div class="card"><div class="card-header"><i class="bi bi-bar-chart me-2"></i>Monthly Purchases</div><div class="card-body"><canvas id="barChart" height="280"></canvas></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-header"><i class="bi bi-pie-chart me-2"></i>By Vendor</div><div class="card-body"><canvas id="pieChart" height="280"></canvas></div></div></div>
</div>

<div class="card">
    <div class="card-header no-print"><i class="bi bi-table me-2"></i>Purchases</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Purchase#</th><th>Date</th><th>Vendor</th><th>Project</th><th>Items</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($purchases as $p): $bal = (float)$p['total_amount'] - (float)$p['paid_amount']; ?>
                    <tr><td class="fw-medium"><?= e($p['purchase_no']) ?></td><td><?= fmt_date($p['purchase_date']) ?></td><td><?= e($p['vendor_name'] ?? '-') ?></td><td class="small"><?= e($p['project_name'] ?? '-') ?></td><td class="text-center"><?= $p['item_count'] ?></td><td class="text-end"><?= fmt_money($p['total_amount']) ?></td><td class="text-end"><?= fmt_money($p['paid_amount']) ?></td><td class="text-end <?= $bal > 0 ? 'text-danger fw-bold' : '' ?>"><?= fmt_money($bal) ?></td><td><?= status_badge($p['status']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$purchases): ?><tr><td colspan="9"><div class="empty-state"><i class="bi bi-inbox"></i><p>No purchases</p></div></td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr class="table-light"><td colspan="5" class="fw-bold">Total</td><td class="text-end fw-bold"><?= fmt_money($totalAmount) ?></td><td class="text-end fw-bold"><?= fmt_money($totalPaid) ?></td><td class="text-end fw-bold text-danger"><?= fmt_money($totalAmount - $totalPaid) ?></td><td></td></tr></tfoot>
            </table>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('barChart'), { type: 'bar', data: { labels: <?= json_encode($chartLabels) ?>, datasets: [{ label: 'Purchases', data: <?= json_encode($chartData) ?>, backgroundColor: 'rgba(54,162,235,0.7)' }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });
new Chart(document.getElementById('pieChart'), { type: 'doughnut', data: { labels: <?= json_encode(array_keys($byVendor)) ?>, datasets: [{ data: <?= json_encode(array_values($byVendor)) ?>, backgroundColor: ['#36a2eb','#ffce56','#4bc0c0','#ff6384','#9966ff','#ff9f40'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
</script>

<?php include '../../includes/footer.php'; ?>

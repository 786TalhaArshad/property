<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Balance Sheet';
$active = 'reports';

$asof = $_GET['asof'] ?? date('Y-m-d');
$project_id = (int)($_GET['project_id'] ?? active_project_id());
$projCond = $project_id > 0 ? ' AND v.project_id = ?' : '';

$records = db_all("SELECT a.code, a.name, a.account_type, a.opening_balance,
                   (SELECT COALESCE(SUM(vi.debit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted' AND v.voucher_date <= ?" . $projCond . ") AS dr,
                   (SELECT COALESCE(SUM(vi.credit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted' AND v.voucher_date <= ?" . $projCond . ") AS cr
                   FROM chart_of_accounts a
                   WHERE a.account_type IN ('asset','liability','equity')
                   ORDER BY a.account_type, a.code",
    $project_id > 0 ? [$asof, $project_id, $asof, $project_id] : [$asof, $asof]);

$assets = [];
$liabilities = [];
$equity = [];
foreach ($records as $r) {
    $ob = $project_id > 0 ? 0 : (float)$r['opening_balance'];
    $net = $ob + (float)$r['dr'] - (float)$r['cr'];
    if ($project_id > 0 && $net == 0) continue;
    if ($r['account_type'] === 'asset') {
        $assets[] = ['name' => $r['name'], 'net' => $net];
    } elseif ($r['account_type'] === 'liability') {
        $liabilities[] = ['name' => $r['name'], 'net' => -$net];
    } else {
        $equity[] = ['name' => $r['name'], 'net' => -$net];
    }
}
$totalAssets = array_sum(array_map(function ($a) { return $a['net']; }, $assets));
$totalLiab = array_sum(array_map(function ($a) { return $a['net']; }, $liabilities));
$totalEquity = array_sum(array_map(function ($a) { return $a['net']; }, $equity));

$netProfit = (float)db_get("SELECT
    (SELECT COALESCE(SUM(vi.credit),0) - COALESCE(SUM(vi.debit),0)
     FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id JOIN chart_of_accounts a ON a.id = vi.account_id
     WHERE a.account_type = 'income' AND v.status = 'posted' AND v.voucher_date <= ?" . $projCond . ") -
    (SELECT COALESCE(SUM(vi.debit),0) - COALESCE(SUM(vi.credit),0)
     FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id JOIN chart_of_accounts a ON a.id = vi.account_id
     WHERE a.account_type = 'expense' AND v.status = 'posted' AND v.voucher_date <= ?" . $projCond . ") AS np",
    $project_id > 0 ? [$asof, $project_id, $asof, $project_id] : [$asof, $asof])['np'];

$totalLiabEquity = $totalLiab + $totalEquity + $netProfit;

$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
$projectName = '';
foreach ($projects as $p) {
    if ((int)$p['id'] === $project_id) {
        $projectName = $p['name'];
        break;
    }
}
include '../includes/header.php';
?>

<style>
@media print {
    .no-print, .sidebar, .main-header, .main-footer, .quick-action-btn { display: none !important; }
    .main-content { margin: 0 !important; padding: 10px !important; }
    .card { border: none !important; box-shadow: none !important; }
    body { font-size: 11px; }
    .table td, .table th { padding: 4px 6px !important; font-size: 11px; }
}
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-bank me-2"></i>Balance Sheet</h5>
    <div class="ms-auto"><button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print</button></div>
</div>

<form method="get" class="card mb-3 no-print">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-0">Project</label>
                <select name="project_id" class="form-select form-select-sm">
                    <option value="0">All Projects</option>
                    <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-0">As of</label>
                <input type="date" name="asof" class="form-control" value="<?= e($asof) ?>">
            </div>
            <div class="col-md-2"><label class="form-label mb-0">&nbsp;</label><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="card print-only" style="display:none">
    <div class="text-center mb-2">
        <h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5>
        <div class="small text-muted">Balance Sheet as of <?= fmt_date($asof) ?><?= $projectName ? ' | ' . e($projectName) : '' ?></div>
    </div>
</div>

<div class="mb-3 text-muted small no-print">Balance Sheet as of <?= fmt_date($asof) ?><?= $projectName ? ' &bull; ' . e($projectName) : '' ?></div>

<div class="row g-3 mb-3 no-print">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-pie-chart me-2"></i>Assets vs Liabilities & Equity</div>
            <div class="card-body"><canvas id="bsChart" height="250"></canvas></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Asset Breakdown</div>
            <div class="card-body"><canvas id="assetChart" height="250"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-boxes me-2"></i>Assets</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <tbody>
                    <?php foreach ($assets as $a): ?>
                        <tr><td><?= e($a['name']) ?></td><td class="text-end"><?= fmt_money($a['net']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$assets): ?><tr><td colspan="2"><div class="empty-state"><i class="bi bi-inbox"></i><p>No assets</p></div></td></tr><?php endif; ?>
                    </tbody>
                    <tfoot><tr class="table-light"><td class="fw-bold">Total Assets</td><td class="text-end fw-bold"><?= fmt_money($totalAssets) ?></td></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-bank me-2"></i>Liabilities &amp; Equity</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <tbody>
                    <?php foreach ($liabilities as $a): ?>
                        <tr><td><?= e($a['name']) ?></td><td class="text-end"><?= fmt_money($a['net']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php foreach ($equity as $a): ?>
                        <tr><td><?= e($a['name']) ?></td><td class="text-end"><?= fmt_money($a['net']) ?></td></tr>
                    <?php endforeach; ?>
                    <tr><td>Retained Earnings (Net Profit)</td><td class="text-end"><?= fmt_money($netProfit) ?></td></tr>
                    </tbody>
                    <tfoot><tr class="table-light"><td class="fw-bold">Total Liabilities &amp; Equity</td><td class="text-end fw-bold"><?= fmt_money($totalLiabEquity) ?></td></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-body text-center">
        <div class="text-muted small">BALANCE CHECK (Assets - Liab &amp; Equity)</div>
        <div class="fs-4 fw-bold <?= abs($totalAssets - $totalLiabEquity) < 0.01 ? 'text-success' : 'text-danger' ?>"><?= fmt_money($totalAssets - $totalLiabEquity) ?></div>
    </div>
</div>

<script>
new Chart(document.getElementById('bsChart'), {
    type: 'doughnut',
    data: {
        labels: ['Assets', 'Liabilities', 'Equity'],
        datasets: [{ data: [<?= $totalAssets ?>, <?= $totalLiab ?>, <?= $totalEquity + $netProfit ?>], backgroundColor: ['rgba(54,162,235,0.7)', 'rgba(255,99,132,0.7)', 'rgba(75,192,192,0.7)'] }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});
new Chart(document.getElementById('assetChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($assets, 'name')) ?>,
        datasets: [{ data: <?= json_encode(array_column($assets, 'net')) ?>, backgroundColor: 'rgba(54,162,235,0.7)' }]
    },
    options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } } }
});
</script>

<?php include '../includes/footer.php'; ?>

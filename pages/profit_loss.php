<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Profit & Loss';
$active = 'reports';

$from = $_GET['from'] ?? date('Y-01-01');
$to = $_GET['to'] ?? date('Y-m-d');
$project_id = (int)($_GET['project_id'] ?? active_project_id());

$projCond = $project_id > 0 ? " AND v.project_id = ?" : '';

$income = db_all("SELECT a.code, a.name,
                  (SELECT COALESCE(SUM(vi.credit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted' AND v.voucher_date BETWEEN ? AND ?" . $projCond . ") AS cr,
                  (SELECT COALESCE(SUM(vi.debit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted' AND v.voucher_date BETWEEN ? AND ?" . $projCond . ") AS dr
                  FROM chart_of_accounts a WHERE a.account_type = 'income' ORDER BY a.code",
    $project_id > 0 ? [$from, $to, $project_id, $from, $to, $project_id] : [$from, $to, $from, $to]);

$expense = db_all("SELECT a.code, a.name,
                  (SELECT COALESCE(SUM(vi.debit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted' AND v.voucher_date BETWEEN ? AND ?" . $projCond . ") AS dr,
                  (SELECT COALESCE(SUM(vi.credit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted' AND v.voucher_date BETWEEN ? AND ?" . $projCond . ") AS cr
                  FROM chart_of_accounts a WHERE a.account_type = 'expense' ORDER BY a.code",
    $project_id > 0 ? [$from, $to, $project_id, $from, $to, $project_id] : [$from, $to, $from, $to]);

$totalIncome = 0.0;
$incomeRows = [];
foreach ($income as $r) {
    $amt = (float)$r['cr'] - (float)$r['dr'];
    if ($amt > 0) { $totalIncome += $amt; $incomeRows[] = ['name' => $r['name'], 'amount' => $amt]; }
}
$totalExpense = 0.0;
$expenseRows = [];
foreach ($expense as $r) {
    $amt = (float)$r['dr'] - (float)$r['cr'];
    if ($amt > 0) { $totalExpense += $amt; $expenseRows[] = ['name' => $r['name'], 'amount' => $amt]; }
}
$netProfit = $totalIncome - $totalExpense;
$projects = db_all("SELECT id, name FROM projects ORDER BY name");
$projectName = '';
foreach ($projects as $p) { if ((int)$p['id'] === $project_id) { $projectName = $p['name']; break; } }
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
    <h5 class="mb-0"><i class="bi bi-bullseye me-2"></i>Profit & Loss</h5>
    <div class="ms-auto"><button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print</button></div>
</div>

<form method="get" class="card mb-3 no-print">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-2"><label class="form-label small text-muted mb-0">From</label><input type="date" name="from" class="form-control" value="<?= e($from) ?>"></div>
            <div class="col-md-2"><label class="form-label small text-muted mb-0">To</label><input type="date" name="to" class="form-control" value="<?= e($to) ?>"></div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-0">Project</label>
                <select name="project_id" class="form-select">
                    <option value="0">All Projects</option>
                    <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><label class="form-label mb-0">&nbsp;</label><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="card print-only" style="display:none">
    <div class="text-center mb-2">
        <h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5>
        <div class="small text-muted">Profit & Loss from <?= fmt_date($from) ?> to <?= fmt_date($to) ?><?= $projectName ? ' | ' . e($projectName) : '' ?></div>
    </div>
</div>

<div class="row g-3 mb-3 no-print">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Income vs Expenses</div>
            <div class="card-body"><canvas id="plChart" height="250"></canvas></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-pie-chart me-2"></i>Expense Breakdown</div>
            <div class="card-body"><canvas id="expChart" height="250"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success-subtle"><i class="bi bi-graph-up-arrow me-2"></i>Income</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Account</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($incomeRows as $r): ?>
                        <tr><td><?= e($r['name']) ?></td><td class="text-end"><?= fmt_money($r['amount']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$incomeRows): ?><tr><td colspan="2"><div class="empty-state"><i class="bi bi-inbox"></i><p>No income</p></div></td></tr><?php endif; ?>
                    </tbody>
                    <tfoot><tr class="table-light"><td class="fw-bold">Total Income</td><td class="text-end fw-bold"><?= fmt_money($totalIncome) ?></td></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-danger-subtle"><i class="bi bi-graph-down-arrow me-2"></i>Expenses</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Account</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($expenseRows as $r): ?>
                        <tr><td><?= e($r['name']) ?></td><td class="text-end"><?= fmt_money($r['amount']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$expenseRows): ?><tr><td colspan="2"><div class="empty-state"><i class="bi bi-inbox"></i><p>No expenses</p></div></td></tr><?php endif; ?>
                    </tbody>
                    <tfoot><tr class="table-light"><td class="fw-bold">Total Expenses</td><td class="text-end fw-bold"><?= fmt_money($totalExpense) ?></td></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <div class="text-muted small">PERIOD</div>
            <div class="fw-medium"><?= fmt_date($from) ?> - <?= fmt_date($to) ?><?= $projectName ? ' &bull; ' . e($projectName) : '' ?></div>
        </div>
        <div class="text-center">
            <div class="text-muted small">NET PROFIT</div>
            <div class="fs-3 fw-bold <?= $netProfit >= 0 ? 'text-success' : 'text-danger' ?>"><?= fmt_money($netProfit) ?></div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('plChart'), {
    type: 'bar',
    data: {
        labels: ['Income', 'Expenses'],
        datasets: [{ data: [<?= $totalIncome ?>, <?= $totalExpense ?>], backgroundColor: ['rgba(75,192,192,0.7)', 'rgba(255,99,132,0.7)'] }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
});
new Chart(document.getElementById('expChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($expenseRows, 'name')) ?>,
        datasets: [{ data: <?= json_encode(array_column($expenseRows, 'amount')) ?>, backgroundColor: ['#ff6384','#36a2eb','#ffce56','#4bc0c0','#9966ff','#ff9f40','#c9cbcf'] }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php include '../includes/footer.php'; ?>

<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Bank Summary Report';
$active = 'reports';

$asOf = $_GET['asof'] ?? date('Y-m-d');

$banks = db_all("SELECT id, name, account_title, account_no, opening_balance FROM banks WHERE status = 1 ORDER BY name");

$bankData = [];
foreach ($banks as $b) {
    $code = '1001-' . str_pad($b['id'], 3, '0', STR_PAD_LEFT);
    $acc = db_get("SELECT id FROM chart_of_accounts WHERE code = ?", [$code]);
    $dr = 0.0; $cr = 0.0;
    if ($acc) {
        $dr = (float)(db_get("SELECT COALESCE(SUM(vi.debit),0) AS total FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = ? AND v.status = 'posted' AND v.voucher_date <= ?", [$acc['id'], $asOf])['total'] ?? 0);
        $cr = (float)(db_get("SELECT COALESCE(SUM(vi.credit),0) AS total FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = ? AND v.status = 'posted' AND v.voucher_date <= ?", [$acc['id'], $asOf])['total'] ?? 0);
    }
    $balance = (float)$b['opening_balance'] + $dr - $cr;
    if ($dr > 0 || $cr > 0 || $balance != 0) {
        $bankData[] = ['name' => $b['name'], 'title' => $b['account_title'] ?? '-', 'acc_no' => $b['account_no'] ?? '-', 'opening' => (float)$b['opening_balance'], 'debit' => $dr, 'credit' => $cr, 'balance' => $balance];
    }
}
$totalBalance = array_sum(array_column($bankData, 'balance'));
$mostActive = '';
$maxTxn = 0;
foreach ($bankData as $bd) { $txn = $bd['debit'] + $bd['credit']; if ($txn > $maxTxn) { $maxTxn = $txn; $mostActive = $bd['name']; } }
include '../../includes/header.php';
?>

<style>
@media print { .no-print,.sidebar,.main-header,.main-footer,.quick-action-btn{display:none!important}.main-content{margin:0!important;padding:10px!important}.card{border:none!important;box-shadow:none!important}body{font-size:11px}.table td,.table th{padding:4px 6px!important;font-size:11px} }
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-bank me-2"></i>Bank Summary Report</h5>
    <div class="ms-auto"><button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print</button></div>
</div>

<form method="get" class="card mb-3 no-print">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-2"><label class="form-label small text-muted mb-0">As of</label><input type="date" name="asof" class="form-control" value="<?= e($asOf) ?>"></div>
            <div class="col-md-2"><label class="form-label mb-0">&nbsp;</label><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="card print-only" style="display:none"><div class="text-center mb-2"><h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5><div class="small text-muted">Bank Summary Report as of <?= fmt_date($asOf) ?></div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-bank"></i></div><div><div class="stat-label">TOTAL BANKS</div><div class="stat-value"><?= count($bankData) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">TOTAL BALANCE</div><div class="stat-value"><?= fmt_money($totalBalance) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-arrow-left-right"></i></div><div><div class="stat-label">MOST ACTIVE</div><div class="stat-value" style="font-size:14px"><?= e($mostActive ?: '-') ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-graph-up"></i></div><div><div class="stat-label">TOTAL TRANSACTIONS</div><div class="stat-value"><?= fmt_money(array_sum(array_map(fn($b) => $b['debit'] + $b['credit'], $bankData))) ?></div></div></div></div></div>
</div>

<div class="row g-3 mb-3 no-print">
    <div class="col-md-8"><div class="card"><div class="card-header"><i class="bi bi-bar-chart me-2"></i>Balance by Bank</div><div class="card-body"><canvas id="barChart" height="280"></canvas></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-header"><i class="bi bi-pie-chart me-2"></i>Share</div><div class="card-body"><canvas id="pieChart" height="280"></canvas></div></div></div>
</div>

<div class="card">
    <div class="card-header no-print"><i class="bi bi-table me-2"></i>Bank Accounts</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Bank</th><th>Account Title</th><th>Account#</th><th class="text-end">Opening</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th></tr></thead>
                <tbody>
                <?php foreach ($bankData as $bd): ?>
                    <tr><td class="fw-medium"><?= e($bd['name']) ?></td><td><?= e($bd['title']) ?></td><td class="small"><?= e($bd['acc_no']) ?></td><td class="text-end"><?= fmt_money($bd['opening']) ?></td><td class="text-end text-success"><?= fmt_money($bd['debit']) ?></td><td class="text-end text-danger"><?= fmt_money($bd['credit']) ?></td><td class="text-end fw-bold <?= $bd['balance'] < 0 ? 'text-danger' : '' ?>"><?= fmt_money($bd['balance']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$bankData): ?><tr><td colspan="7"><div class="empty-state"><i class="bi bi-inbox"></i><p>No bank accounts</p></div></td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr class="table-light"><td colspan="3" class="fw-bold">Total</td><td class="text-end fw-bold"><?= fmt_money(array_sum(array_column($bankData, 'opening'))) ?></td><td class="text-end fw-bold text-success"><?= fmt_money(array_sum(array_column($bankData, 'debit'))) ?></td><td class="text-end fw-bold text-danger"><?= fmt_money(array_sum(array_column($bankData, 'credit'))) ?></td><td class="text-end fw-bold"><?= fmt_money($totalBalance) ?></td></tr></tfoot>
            </table>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('barChart'), { type: 'bar', data: { labels: <?= json_encode(array_column($bankData, 'name')) ?>, datasets: [{ label: 'Balance', data: <?= json_encode(array_column($bankData, 'balance')) ?>, backgroundColor: 'rgba(54,162,235,0.7)' }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });
new Chart(document.getElementById('pieChart'), { type: 'doughnut', data: { labels: <?= json_encode(array_column($bankData, 'name')) ?>, datasets: [{ data: <?= json_encode(array_map(fn($b) => max(0, $b['balance']), $bankData)) ?>, backgroundColor: ['#36a2eb','#ffce56','#4bc0c0','#ff6384','#9966ff','#ff9f40'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
</script>

<?php include '../../includes/footer.php'; ?>

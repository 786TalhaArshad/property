<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Cash Flow Report';
$active = 'reports';

$from = $_GET['from'] ?? date('Y-01-01');
$to = $_GET['to'] ?? date('Y-m-d');
$project_id = (int)($_GET['project_id'] ?? active_project_id());

$projCond = $project_id > 0 ? " AND v.project_id = $project_id" : '';
$projCondR = $project_id > 0 ? " AND r.project_id = $project_id" : '';
$projCondRC = $project_id > 0 ? " AND EXISTS (SELECT 1 FROM rental_agreements ra2 JOIN properties p2 ON p2.id = ra2.property_id WHERE ra2.id = rc.agreement_id AND p2.project_id = $project_id)" : '';

$voucherItems = db_all("SELECT v.voucher_date AS tdate, CONCAT(v.voucher_no, ' - ', COALESCE(v.narration,'')) AS description,
                        vi.debit, vi.credit, a.code AS account_code
                        FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id JOIN chart_of_accounts a ON a.id = vi.account_id
                        WHERE v.status = 'posted' AND v.voucher_date BETWEEN ? AND ? AND (a.code = '1000' OR a.code LIKE '1001%') $projCond
                        ORDER BY v.voucher_date, v.id", [$from, $to]);

$cashReceipts = db_all("SELECT r.receipt_date AS tdate, CONCAT('Receipt ', r.receipt_no, ' - ', c.full_name) AS description, r.amount, 0 AS credit
                        FROM receipts r JOIN customers c ON c.id = r.customer_id
                        WHERE r.receipt_date BETWEEN ? AND ? AND (r.bank_id IS NULL) $projCondR ORDER BY r.receipt_date", [$from, $to]);

$rentCollections = db_all("SELECT rc.collection_date AS tdate, CONCAT('Rent - ', t.full_name) AS description, rc.amount, 0 AS credit
                           FROM rent_collections rc JOIN rental_agreements ra ON ra.id = rc.agreement_id JOIN tenants t ON t.id = ra.tenant_id
                           WHERE rc.collection_date BETWEEN ? AND ? AND (rc.bank_id IS NULL) $projCondRC ORDER BY rc.collection_date", [$from, $to]);

$all = [];
foreach ($voucherItems as $v) {
    $in = (float)$v['credit']; $out = (float)$v['debit'];
    if ($in > 0 || $out > 0) $all[] = ['date' => $v['tdate'], 'desc' => $v['description'], 'in' => $in, 'out' => $out];
}
foreach ($cashReceipts as $r) $all[] = ['date' => $r['tdate'], 'desc' => $r['description'], 'in' => (float)$r['amount'], 'out' => 0];
foreach ($rentCollections as $r) $all[] = ['date' => $r['tdate'], 'desc' => $r['description'], 'in' => (float)$r['amount'], 'out' => 0];
usort($all, function($a, $b) { return $a['date'] <=> $b['date']; });

$totalIn = 0.0; $totalOut = 0.0;
foreach ($all as $r) { $totalIn += $r['in']; $totalOut += $r['out']; }

$projCondOB = $project_id > 0 ? '' : '';
$openingBalance = (float)(db_get("SELECT opening_balance FROM chart_of_accounts WHERE code = '1000' LIMIT 1")['opening_balance'] ?? 0);
$closingBalance = $openingBalance + $totalIn - $totalOut;

$monthly = [];
foreach ($all as $r) { $ym = date('Y-m', strtotime($r['date'])); $monthly[$ym]['in'] = ($monthly[$ym]['in'] ?? 0) + $r['in']; $monthly[$ym]['out'] = ($monthly[$ym]['out'] ?? 0) + $r['out']; }
ksort($monthly);
$chartLabels = []; $chartIn = []; $chartOut = [];
foreach ($monthly as $ym => $m) { $chartLabels[] = date('M y', strtotime($ym . '-01')); $chartIn[] = $m['in']; $chartOut[] = $m['out']; }
$projects = db_all("SELECT id, name FROM projects ORDER BY name");
$projectName = '';
foreach ($projects as $p) { if ((int)$p['id'] === $project_id) { $projectName = $p['name']; break; } }
include '../../includes/header.php';
?>

<style>
@media print { .no-print,.sidebar,.main-header,.main-footer,.quick-action-btn{display:none!important}.main-content{margin:0!important;padding:10px!important}.card{border:none!important;box-shadow:none!important}body{font-size:11px}.table td,.table th{padding:4px 6px!important;font-size:11px} }
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-cash me-2"></i>Cash Flow Report</h5>
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

<div class="card print-only" style="display:none"><div class="text-center mb-2"><h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5><div class="small text-muted">Cash Flow Report from <?= fmt_date($from) ?> to <?= fmt_date($to) ?><?= $projectName ? ' | ' . e($projectName) : '' ?></div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-cyan"><div class="stat-body"><div class="stat-icon"><i class="bi bi-wallet2"></i></div><div><div class="stat-label">OPENING BALANCE</div><div class="stat-value"><?= fmt_money($openingBalance) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-arrow-down-circle"></i></div><div><div class="stat-label">CASH IN</div><div class="stat-value"><?= fmt_money($totalIn) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-red"><div class="stat-body"><div class="stat-icon"><i class="bi bi-arrow-up-circle"></i></div><div><div class="stat-label">CASH OUT</div><div class="stat-value"><?= fmt_money($totalOut) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">CLOSING BALANCE</div><div class="stat-value"><?= fmt_money($closingBalance) ?></div></div></div></div></div>
</div>

<div class="row g-3 mb-3 no-print">
    <div class="col-md-8"><div class="card"><div class="card-header"><i class="bi bi-bar-chart me-2"></i>Monthly Cash Flow</div><div class="card-body"><canvas id="cfChart" height="280"></canvas></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-header"><i class="bi bi-pie-chart me-2"></i>In vs Out</div><div class="card-body"><canvas id="pieChart" height="280"></canvas></div></div></div>
</div>

<div class="card">
    <div class="card-header no-print"><i class="bi bi-table me-2"></i>Cash Flow Transactions</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Date</th><th>Description</th><th class="text-end">Cash In</th><th class="text-end">Cash Out</th></tr></thead>
                <tbody>
                <tr class="table-light"><td colspan="2" class="fw-bold">Opening Balance</td><td class="text-end fw-bold"><?= fmt_money($openingBalance) ?></td><td></td></tr>
                <?php $running = $openingBalance; foreach ($all as $r): $running += $r['in'] - $r['out']; ?>
                    <tr><td><?= fmt_date($r['date']) ?></td><td><?= e($r['desc']) ?></td><td class="text-end text-success"><?= $r['in'] > 0 ? fmt_money($r['in']) : '' ?></td><td class="text-end text-danger"><?= $r['out'] > 0 ? fmt_money($r['out']) : '' ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$all): ?><tr><td colspan="4"><div class="empty-state"><i class="bi bi-inbox"></i><p>No transactions</p></div></td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr class="table-light"><td colspan="2" class="fw-bold">Closing Balance</td><td class="text-end fw-bold text-success"><?= fmt_money($totalIn + $openingBalance) ?></td><td class="text-end fw-bold text-danger"><?= fmt_money($totalOut) ?></td></tr></tfoot>
            </table>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('cfChart'), { type: 'bar', data: { labels: <?= json_encode($chartLabels) ?>, datasets: [{ label: 'Cash In', data: <?= json_encode($chartIn) ?>, backgroundColor: 'rgba(75,192,192,0.7)' }, { label: 'Cash Out', data: <?= json_encode($chartOut) ?>, backgroundColor: 'rgba(255,99,132,0.7)' }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
new Chart(document.getElementById('pieChart'), { type: 'doughnut', data: { labels: ['Cash In', 'Cash Out'], datasets: [{ data: [<?= $totalIn ?>, <?= $totalOut ?>], backgroundColor: ['#4bc0c0', '#ff6384'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
</script>

<?php include '../../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'AI Dashboard';
$active = 'ai_dashboard';

$today = date('Y-m-d');
$yearStart = date('Y-01-01');
$last12 = date('Y-m-d', strtotime('-12 months'));

$monthlyRevenue = db_all("SELECT DATE_FORMAT(v.voucher_date, '%Y-%m') AS ym,
                          SUM(CASE WHEN a.account_type = 'income' THEN vi.credit ELSE 0 END) AS income,
                          SUM(CASE WHEN a.account_type = 'expense' THEN vi.debit ELSE 0 END) AS expense
                          FROM vouchers v JOIN voucher_items vi ON vi.voucher_id = v.id JOIN chart_of_accounts a ON a.id = vi.account_id
                          WHERE v.status = 'posted' AND v.voucher_date >= ? GROUP BY ym ORDER BY ym", [$last12]);

$chartLabels = []; $chartIncome = []; $chartExpense = []; $chartNet = [];
foreach ($monthlyRevenue as $m) {
    $chartLabels[] = date('M y', strtotime($m['ym'] . '-01'));
    $chartIncome[] = (float)$m['income'];
    $chartExpense[] = (float)$m['expense'];
    $chartNet[] = (float)$m['income'] - (float)$m['expense'];
}

$overdueInst = db_all("SELECT i.*, b.booking_no, c.full_name AS customer_name, c.phone, p.property_no
                       FROM installments i JOIN bookings b ON b.id = i.booking_id JOIN customers c ON c.id = b.customer_id JOIN properties p ON p.id = b.property_id
                       WHERE b.status <> 'cancelled' AND i.status IN ('pending','partial','overdue') AND i.due_date < ? AND (i.amount - i.paid_amount) > 0
                       ORDER BY i.due_date LIMIT 15", [$today]);
$overdueRent = db_all("SELECT rs.*, ra.agreement_no, p.property_no, t.full_name AS tenant_name
                       FROM rent_schedule rs JOIN rental_agreements ra ON ra.id = rs.agreement_id JOIN properties p ON p.id = ra.property_id JOIN tenants t ON t.id = ra.tenant_id
                       WHERE ra.status IN ('active','renewed') AND rs.status IN ('pending','partial','overdue') AND rs.due_date < ? AND (rs.rent_amount - rs.paid_amount) > 0
                       ORDER BY rs.due_date LIMIT 15", [$today]);

$hasMiTable = (bool)(db_get("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'material_issues'")['c'] ?? 0);
$miSelect = $hasMiTable ? '(SELECT COALESCE(SUM(mi.total_amount),0) FROM material_issues mi WHERE mi.project_id = p.id)' : '0';
$projectPerf = db_all("SELECT p.name,
    (SELECT COALESCE(SUM(b.total_price),0) FROM bookings b JOIN properties pr ON pr.id = b.property_id WHERE pr.project_id = p.id AND b.status <> 'cancelled') AS revenue,
    " . $miSelect . " AS mat_cost,
    (SELECT COALESCE(SUM(ce.amount),0) FROM contractor_entries ce WHERE ce.project_id = p.id AND ce.entry_type = 'paid') AS con_cost,
    (SELECT COUNT(*) FROM properties pr WHERE pr.project_id = p.id AND pr.status = 'sold') AS sold,
    (SELECT COUNT(*) FROM properties pr WHERE pr.project_id = p.id) AS total_p
    FROM projects p WHERE p.status = 1 ORDER BY revenue DESC");

$topCustomers = db_all("SELECT c.full_name, SUM(r.amount) AS total_paid
                        FROM receipts r JOIN customers c ON c.id = r.customer_id
                        WHERE r.receipt_date >= ? GROUP BY c.id ORDER BY total_paid DESC LIMIT 5", [$yearStart]);

$collectionRate = db_get("SELECT
    (SELECT COALESCE(SUM(paid_amount),0) FROM installments i JOIN bookings b ON b.id = i.booking_id WHERE b.status <> 'cancelled' AND i.due_date BETWEEN ? AND ?) AS collected,
    (SELECT COALESCE(SUM(amount),0) FROM installments i JOIN bookings b ON b.id = i.booking_id WHERE b.status <> 'cancelled' AND i.due_date BETWEEN ? AND ?) AS billed",
    [$yearStart, $today, $yearStart, $today]);
$collRate = (float)$collectionRate['billed'] > 0 ? ((float)$collectionRate['collected'] / (float)$collectionRate['billed'] * 100) : 0;

$avgTransaction = (float)(db_get("SELECT AVG(total) AS avg_t FROM (SELECT SUM(vi.debit + vi.credit) AS total FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE v.status = 'posted' AND v.voucher_date >= ? GROUP BY v.id) sub", [$last12])['avg_t'] ?? 0);
$anomalies = db_all("SELECT v.voucher_no, v.voucher_date, v.narration, vi.debit + vi.credit AS amount
                     FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id
                     WHERE v.status = 'posted' AND v.voucher_date >= ?
                     GROUP BY v.id HAVING amount > ? ORDER BY amount DESC LIMIT 5", [$last12, $avgTransaction * 3]);

$totalInstOut = 0; foreach ($overdueInst as $i) $totalInstOut += (float)$i['amount'] - (float)$i['paid_amount'];
$totalRentOut = 0; foreach ($overdueRent as $r) $totalRentOut += (float)$r['rent_amount'] - (float)$r['paid_amount'];

$recommendations = [];
if (count($overdueInst) > 5) $recommendations[] = ['icon' => 'bi-exclamation-triangle', 'color' => 'warning', 'text' => count($overdueInst) . ' installments are overdue. Consider sending reminders or late fee notices.'];
if ($collRate < 70) $recommendations[] = ['icon' => 'bi-graph-down', 'color' => 'danger', 'text' => 'Collection rate is ' . number_format($collRate, 1) . '%. Revenue recovery needs attention.'];
foreach ($projectPerf as $pp) {
    $net = (float)$pp['revenue'] - (float)$pp['mat_cost'] - (float)$pp['con_cost'];
    if ((float)$pp['sold'] > 0 && (int)$pp['total_p'] > 0 && ($pp['sold'] / $pp['total_p']) > 0.8)
        $recommendations[] = ['icon' => 'bi-arrow-up', 'color' => 'success', 'text' => e($pp['name']) . ': ' . number_format($pp['sold'] / $pp['total_p'] * 100, 0) . '% properties sold. Consider price revision.'];
}
if (count($overdueRent) > 3) $recommendations[] = ['icon' => 'bi-house-exclude', 'color' => 'warning', 'text' => count($overdueRent) . ' rent payments overdue. Follow up with tenants.'];
if (empty($recommendations)) $recommendations[] = ['icon' => 'bi-check-circle', 'color' => 'success', 'text' => 'All systems running smoothly. No critical alerts.'];

include '../includes/header.php';
?>

<style>
@media print { .no-print,.sidebar,.main-header,.main-footer,.quick-action-btn{display:none!important}.main-content{margin:0!important;padding:10px!important}.card{border:none!important;box-shadow:none!important}body{font-size:11px} }
.ai-card { border-left: 4px solid; }
.ai-card.border-blue { border-left-color: #36a2eb; }
.ai-card.border-green { border-left-color: #4bc0c0; }
.ai-card.border-orange { border-left-color: #ff9f40; }
.ai-card.border-red { border-left-color: #ff6384; }
.ai-card.border-purple { border-left-color: #9966ff; }
.rec-item { padding: 10px 14px; border-radius: 8px; margin-bottom: 8px; }
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-robot me-2"></i>AI Dashboard</h5>
    <span class="badge bg-primary">Intelligent Insights</span>
    <div class="ms-auto">
        <span class="text-muted small me-2">Last updated: <?= date('d M Y h:i A') ?></span>
        <a href="ai_dashboard.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print</button>
    </div>
</div>

<div class="card print-only" style="display:none"><div class="text-center mb-2"><h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5><div class="small text-muted">AI Dashboard - Intelligent Business Insights</div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue ai-card border-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-graph-up"></i></div><div><div class="stat-label">TOTAL INCOME (YTD)</div><div class="stat-value"><?= fmt_money(array_sum($chartIncome)) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green ai-card border-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-check-circle"></i></div><div><div class="stat-label">COLLECTION RATE</div><div class="stat-value"><?= number_format($collRate, 1) ?>%</div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange ai-card border-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div><div><div class="stat-label">OVERDUE ALERTS</div><div class="stat-value"><?= count($overdueInst) + count($overdueRent) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple ai-card border-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-lightning"></i></div><div><div class="stat-label">ANOMALIES DETECTED</div><div class="stat-value"><?= count($anomalies) ?></div></div></div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card ai-card border-blue">
            <div class="card-header no-print"><i class="bi bi-graph-up me-2"></i>Revenue Trend (12 Months)</div>
            <div class="card-body"><canvas id="trendChart" height="300"></canvas></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card ai-card border-green">
            <div class="card-header no-print"><i class="bi bi-speedometer me-2"></i>Collection Efficiency</div>
            <div class="card-body text-center">
                <canvas id="gaugeChart" height="200"></canvas>
                <div class="mt-2">
                    <div class="text-muted small">Collected: <?= fmt_money((float)$collectionRate['collected']) ?></div>
                    <div class="text-muted small">Billed: <?= fmt_money((float)$collectionRate['billed']) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card ai-card border-orange">
            <div class="card-header"><i class="bi bi-exclamation-triangle me-2 text-warning"></i>Overdue Alerts (<?= count($overdueInst) + count($overdueRent) ?>)</div>
            <div class="card-body p-0" style="max-height:300px;overflow-y:auto">
                <table class="table table-hover mb-0 table-sm">
                    <thead><tr><th>Type</th><th>Person</th><th>Property</th><th>Due</th><th class="text-end">Outstanding</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($overdueInst, 0, 10) as $i): ?>
                        <tr><td><span class="badge bg-warning">Installment</span></td><td><?= e($i['customer_name']) ?></td><td class="small"><?= e($i['property_no']) ?></td><td class="small"><?= fmt_date($i['due_date']) ?></td><td class="text-end fw-bold text-danger"><?= fmt_money((float)$i['amount'] - (float)$i['paid_amount']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php foreach (array_slice($overdueRent, 0, 10) as $r): ?>
                        <tr><td><span class="badge bg-info">Rent</span></td><td><?= e($r['tenant_name']) ?></td><td class="small"><?= e($r['property_no']) ?></td><td class="small"><?= fmt_date($r['due_date']) ?></td><td class="text-end fw-bold text-danger"><?= fmt_money((float)$r['rent_amount'] - (float)$r['paid_amount']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (empty($overdueInst) && empty($overdueRent)): ?><tr><td colspan="5" class="text-center text-muted py-3"><i class="bi bi-check-circle me-1"></i>No overdue payments</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card ai-card border-purple">
            <div class="card-header"><i class="bi bi-robot me-2 text-primary"></i>AI Recommendations</div>
            <div class="card-body">
                <?php foreach ($recommendations as $rec): ?>
                    <div class="rec-item bg-<?= $rec['color'] ?>-subtle"><i class="bi <?= $rec['icon'] ?> me-2 text-<?= $rec['color'] ?>"></i><?= $rec['text'] ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card ai-card border-green">
            <div class="card-header"><i class="bi bi-building me-2"></i>Project ROI</div>
            <div class="card-body"><canvas id="roiChart" height="280"></canvas></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card ai-card border-blue">
            <div class="card-header"><i class="bi bi-trophy me-2"></i>Top 5 Customers by Collection</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>#</th><th>Customer</th><th class="text-end">Total Paid</th></tr></thead>
                    <tbody>
                    <?php foreach ($topCustomers as $i => $c): ?>
                        <tr><td><?= $i + 1 ?></td><td class="fw-medium"><?= e($c['full_name']) ?></td><td class="text-end fw-bold text-success"><?= fmt_money($c['total_paid']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$topCustomers): ?><tr><td colspan="3" class="text-center text-muted">No data</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($anomalies): ?>
<div class="card ai-card border-red mb-3">
    <div class="card-header"><i class="bi bi-lightning me-2 text-danger"></i>Anomaly Detection — Unusually Large Transactions (>3x Average)</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-sm">
                <thead><tr><th>Voucher</th><th>Date</th><th>Narration</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                <?php foreach ($anomalies as $a): ?>
                    <tr><td class="fw-medium"><?= e($a['voucher_no']) ?></td><td><?= fmt_date($a['voucher_date']) ?></td><td class="small"><?= e($a['narration'] ?? '-') ?></td><td class="text-end fw-bold text-danger"><?= fmt_money($a['amount']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
new Chart(document.getElementById('trendChart'), { type: 'line', data: { labels: <?= json_encode($chartLabels) ?>, datasets: [{ label: 'Income', data: <?= json_encode($chartIncome) ?>, borderColor: '#4bc0c0', backgroundColor: 'rgba(75,192,192,0.2)', fill: true, tension: 0.3 }, { label: 'Expense', data: <?= json_encode($chartExpense) ?>, borderColor: '#ff6384', backgroundColor: 'rgba(255,99,132,0.2)', fill: true, tension: 0.3 }, { label: 'Net Profit', data: <?= json_encode($chartNet) ?>, borderColor: '#9966ff', borderDash: [5,5], tension: 0.3 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
new Chart(document.getElementById('gaugeChart'), { type: 'doughnut', data: { labels: ['Collected', 'Remaining'], datasets: [{ data: [<?= $collRate ?>, <?= 100 - $collRate ?>], backgroundColor: ['#4bc0c0', '#e9ecef'] }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '75%', plugins: { legend: { display: false } } } });
var roiLabels = <?= json_encode(array_column($projectPerf, 'name')) ?>;
var roiRevenue = <?= json_encode(array_map(fn($p) => (float)$p['revenue'], $projectPerf)) ?>;
var roiCost = <?= json_encode(array_map(fn($p) => (float)$p['mat_cost'] + (float)$p['con_cost'], $projectPerf)) ?>;
new Chart(document.getElementById('roiChart'), { type: 'bar', data: { labels: roiLabels, datasets: [{ label: 'Revenue', data: roiRevenue, backgroundColor: 'rgba(75,192,192,0.7)' }, { label: 'Cost', data: roiCost, backgroundColor: 'rgba(255,99,132,0.7)' }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
</script>

<?php include '../includes/footer.php'; ?>

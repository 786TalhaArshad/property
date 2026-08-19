<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Sales Report';
$active = 'reports';

$from = $_GET['from'] ?? date('Y-01-01');
$to = $_GET['to'] ?? date('Y-m-d');
$project_id = (int)($_GET['project_id'] ?? active_project_id());

$where = "b.booking_date BETWEEN ? AND ?";
$params = [$from, $to];
if ($project_id) { $where .= " AND p.project_id = ?"; $params[] = $project_id; }

$bookings = db_all("SELECT b.*, c.full_name AS customer_name, c.customer_no, p.property_no, pr.name AS project_name,
                    (SELECT COALESCE(SUM(amount),0) FROM receipts r WHERE r.booking_id = b.id) AS received
                    FROM bookings b
                    JOIN customers c ON c.id = b.customer_id
                    JOIN properties p ON p.id = b.property_id
                    LEFT JOIN projects pr ON pr.id = p.project_id
                    WHERE $where ORDER BY b.booking_date DESC", $params);

$totalPrice = 0.0; $totalDiscount = 0.0; $totalToken = 0.0; $totalReceived = 0.0;
$statusCounts = [];
foreach ($bookings as $b) {
    $totalPrice += (float)$b['total_price'];
    $totalDiscount += (float)$b['discount'];
    $totalToken += (float)$b['token_amount'];
    $totalReceived += (float)$b['received'];
    $s = $b['status'];
    $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
}
$agreements = db_all("SELECT COUNT(*) AS cnt, SUM(b.total_price) AS tot FROM sale_agreements sa JOIN bookings b ON b.id = sa.booking_id WHERE sa.agreement_date BETWEEN ? AND ?", [$from, $to]);
$projects = db_all("SELECT id, name FROM projects ORDER BY name");
$projectName = '';
foreach ($projects as $p) { if ((int)$p['id'] === $project_id) { $projectName = $p['name']; break; } }

$monthlyData = db_all("SELECT DATE_FORMAT(b.booking_date, '%Y-%m') AS ym,
                       SUM(b.total_price) AS total, SUM(b.discount) AS discount,
                       (SELECT COALESCE(SUM(r.amount),0) FROM receipts r JOIN bookings rb ON rb.id = r.booking_id WHERE rb.booking_date BETWEEN b.booking_date AND b.booking_date) AS collected
                       FROM bookings b JOIN properties p ON p.id = b.property_id
                       WHERE $where GROUP BY ym ORDER BY ym", $params);

$chartLabels = []; $chartTotal = []; $chartCollected = [];
foreach ($monthlyData as $m) {
    $chartLabels[] = date('M y', strtotime($m['ym'] . '-01'));
    $chartTotal[] = (float)$m['total'];
    $chartCollected[] = (float)$m['collected'];
}
include '../../includes/header.php';
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
    <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Sales Report</h5>
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
                    <option value="">All Projects</option>
                    <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><label class="form-label mb-0">&nbsp;</label><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="card print-only" style="display:none">
    <div class="text-center mb-2">
        <h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5>
        <div class="small text-muted">Sales Report from <?= fmt_date($from) ?> to <?= fmt_date($to) ?><?= $projectName ? ' | ' . e($projectName) : '' ?></div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-journal-check"></i></div><div><div class="stat-label">BOOKINGS</div><div class="stat-value"><?= count($bookings) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">BOOKED VALUE</div><div class="stat-value"><?= fmt_money($totalPrice) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-wallet2"></i></div><div><div class="stat-label">COLLECTED</div><div class="stat-value"><?= fmt_money($totalReceived) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-file-earmark-check"></i></div><div><div class="stat-label">AGREEMENTS</div><div class="stat-value"><?= (int)$agreements[0]['cnt'] ?></div></div></div></div></div>
</div>

<div class="row g-3 mb-3 no-print">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Monthly Booking Value vs Collected</div>
            <div class="card-body"><canvas id="salesChart" height="280"></canvas></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-pie-chart me-2"></i>By Status</div>
            <div class="card-body"><canvas id="statusChart" height="280"></canvas></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center no-print">
        <span><i class="bi bi-table me-2"></i>Bookings: <?= fmt_date($from) ?> to <?= fmt_date($to) ?><?= $projectName ? ' &bull; ' . e($projectName) : '' ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Booking</th><th>Date</th><th>Customer</th><th>Property</th><th>Project</th><th class="text-end">Total</th><th class="text-end">Discount</th><th class="text-end">Collected</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($bookings as $r): ?>
                    <tr>
                        <td class="fw-medium"><?= e($r['booking_no']) ?></td>
                        <td><?= fmt_date($r['booking_date']) ?></td>
                        <td><?= e($r['customer_name']) ?></td>
                        <td><?= e($r['property_no']) ?></td>
                        <td class="small"><?= e($r['project_name'] ?? '-') ?></td>
                        <td class="text-end"><?= fmt_money($r['total_price']) ?></td>
                        <td class="text-end"><?= fmt_money($r['discount']) ?></td>
                        <td class="text-end"><?= fmt_money($r['received']) ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$bookings): ?><tr><td colspan="9"><div class="empty-state"><i class="bi bi-inbox"></i><p>No bookings in this period</p></div></td></tr><?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light"><td colspan="5" class="fw-bold">Totals</td>
                        <td class="text-end fw-bold"><?= fmt_money($totalPrice) ?></td>
                        <td class="text-end fw-bold"><?= fmt_money($totalDiscount) ?></td>
                        <td class="text-end fw-bold"><?= fmt_money($totalReceived) ?></td>
                        <td></td></tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('salesChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [
            { label: 'Booked Value', data: <?= json_encode($chartTotal) ?>, backgroundColor: 'rgba(54,162,235,0.7)' },
            { label: 'Collected', data: <?= json_encode($chartCollected) ?>, backgroundColor: 'rgba(75,192,192,0.7)' }
        ]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($statusCounts)) ?>,
        datasets: [{ data: <?= json_encode(array_values($statusCounts)) ?>, backgroundColor: ['#36a2eb','#ffce56','#4bc0c0','#ff6384','#9966ff','#ff9f40'] }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php include '../../includes/footer.php'; ?>

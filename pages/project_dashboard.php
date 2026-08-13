<?php
require_once '../includes/auth.php';
require_login();
require_permission('projects.view');

$id = (int)($_GET['id'] ?? 0);
$project = db_get("SELECT * FROM projects WHERE id = ?", [$id]);
if (!$project) {
    flash('danger', 'Project not found.');
    redirect('projects.php');
}
set_active_project($id);

$title = 'Dashboard - ' . $project['name'];
$active = 'projects';

function pkpi($sql, $params = []) {
    $r = db_get($sql, $params);
    return (int)($r['c'] ?? 0);
}

$statusCounts = db_all("SELECT status, COUNT(*) c FROM properties WHERE project_id = ? GROUP BY status", [$id]);
$stat = [];
foreach ($statusCounts as $s) {
    $stat[$s['status']] = (int)$s['c'];
}
$stat += ['available' => 0, 'booked' => 0, 'sold' => 0, 'reserved' => 0, 'transferred' => 0, 'rental' => 0, 'occupied' => 0, 'vacant' => 0];

$typeCounts = db_all("SELECT pt.name, COUNT(*) c FROM properties p JOIN property_types pt ON pt.id = p.property_type_id WHERE p.project_id = ? GROUP BY p.property_type_id", [$id]);
$catCounts = db_all("SELECT pc.name, COUNT(*) c FROM properties p LEFT JOIN property_categories pc ON pc.id = p.property_category_id WHERE p.project_id = ? GROUP BY p.property_category_id", [$id]);
$plots = $houses = $apartments = $commercial = 0;
foreach ($typeCounts as $t) {
    if ($t['name'] === 'Plot') $plots = (int)$t['c'];
    if ($t['name'] === 'House') $houses = (int)$t['c'];
    if ($t['name'] === 'Apartment') $apartments = (int)$t['c'];
}
foreach ($catCounts as $c) {
    if ($c['name'] === 'Commercial') $commercial = (int)$c['c'];
}

$propertyCount = (int)db_get("SELECT COUNT(*) c FROM properties WHERE project_id = ?", [$id])['c'];
$blocksCount = (int)db_get("SELECT COUNT(*) c FROM blocks WHERE project_id = ?", [$id])['c'];
$roadsCount = (int)db_get("SELECT COUNT(*) c FROM roads WHERE project_id = ?", [$id])['c'];
$streetsCount = (int)db_get("SELECT COUNT(*) c FROM streets WHERE project_id = ?", [$id])['c'];

$customerCount = pkpi("SELECT COUNT(DISTINCT b.customer_id) c FROM bookings b JOIN properties p ON p.id = b.property_id WHERE p.project_id = ?", [$id]);

$bookingCount = pkpi("SELECT COUNT(*) c FROM bookings b JOIN properties p ON p.id = b.property_id WHERE p.project_id = ? AND b.status <> 'cancelled'", [$id]);
$bookingValue = (float)db_get("SELECT COALESCE(SUM(b.total_price - b.discount),0) amt FROM bookings b JOIN properties p ON p.id = b.property_id WHERE p.project_id = ? AND b.status <> 'cancelled'", [$id])['amt'];
$collected = (float)db_get("SELECT COALESCE(SUM(r.amount),0) amt FROM receipts r JOIN bookings b ON b.id = r.booking_id JOIN properties p ON p.id = b.property_id WHERE p.project_id = ?", [$id])['amt'];
$outstanding = $bookingValue - $collected;

$pendingInst = db_get("SELECT COUNT(*) c, COALESCE(SUM(i.amount - i.paid_amount),0) amt FROM installments i JOIN bookings b ON b.id = i.booking_id JOIN properties p ON p.id = b.property_id WHERE p.project_id = ? AND i.status IN ('pending','partial') AND i.due_date <= CURDATE()", [$id]);
$overdue = db_get("SELECT COUNT(*) c, COALESCE(SUM(i.amount - i.paid_amount),0) amt FROM installments i JOIN bookings b ON b.id = i.booking_id JOIN properties p ON p.id = b.property_id WHERE p.project_id = ? AND i.status IN ('pending','partial') AND i.due_date < CURDATE()", [$id]);

$todayRecovery = (float)db_get("SELECT COALESCE(SUM(r.amount),0) amt FROM receipts r JOIN bookings b ON b.id = r.booking_id JOIN properties p ON p.id = b.property_id WHERE p.project_id = ? AND r.receipt_date = CURDATE()", [$id])['amt'];

$months = [];
$salesData = [];
$rentData = [];
for ($i = 5; $i >= 0; $i--) {
    $months[] = date('M-y', strtotime("first day of -$i months"));
    $m = date('Y-m', strtotime("first day of -$i months"));
    $sr = db_get("SELECT COALESCE(SUM(b.total_price - b.discount),0) amt FROM bookings b JOIN properties p ON p.id = b.property_id WHERE p.project_id = ? AND DATE_FORMAT(b.booking_date,'%Y-%m') = ? AND b.status <> 'cancelled'", [$id, $m]);
    $salesData[] = round((float)$sr['amt']);
    $rr = db_get("SELECT COALESCE(SUM(r.amount),0) amt FROM receipts r JOIN bookings b ON b.id = r.booking_id JOIN properties p ON p.id = b.property_id WHERE p.project_id = ? AND DATE_FORMAT(r.receipt_date,'%Y-%m') = ?", [$id, $m]);
    $rentData[] = round((float)$rr['amt']);
}

$upcomingInst = db_all("SELECT i.due_date, i.amount - i.paid_amount AS due, b.booking_no, c.full_name, p.property_no
                        FROM installments i
                        JOIN bookings b ON b.id = i.booking_id
                        JOIN customers c ON c.id = b.customer_id
                        JOIN properties p ON p.id = b.property_id
                        WHERE p.project_id = ? AND i.status IN ('pending','partial') AND i.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                        ORDER BY i.due_date LIMIT 8", [$id]);

$recentReceipts = db_all("SELECT r.receipt_no, r.receipt_date, r.amount, c.full_name, p.property_no
                          FROM receipts r
                          JOIN bookings b ON b.id = r.booking_id
                          JOIN customers c ON c.id = r.customer_id
                          JOIN properties p ON p.id = b.property_id
                          WHERE p.project_id = ?
                          ORDER BY r.receipt_date DESC, r.id DESC LIMIT 8", [$id]);

include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="projects.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    <h5 class="mb-0"><?= e($project['name']) ?></h5>
    <?php if ($project['status']): ?><span class="badge bg-success">Active</span><?php else: ?><span class="badge bg-secondary">Inactive</span><?php endif; ?>
    <span class="ms-auto text-muted small">Developer: <?= e($project['developer'] ?? '-') ?></span>
    <a class="btn btn-outline-info btn-sm" href="project_view.php?id=<?= $id ?>"><i class="bi bi-diagram-2 me-1"></i>Manage Project</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-blue"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-grid-1x2"></i></div>
        <div><div class="stat-label">PROPERTIES</div><div class="stat-value"><?= $propertyCount ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-green"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-people"></i></div>
        <div><div class="stat-label">CUSTOMERS</div><div class="stat-value"><?= $customerCount ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-purple"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-cart-check"></i></div>
        <div><div class="stat-label">BOOKINGS</div><div class="stat-value"><?= $bookingCount ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-orange"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-grid"></i></div>
        <div><div class="stat-label">BLOCKS</div><div class="stat-value"><?= $blocksCount ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-cyan"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-signpost"></i></div>
        <div><div class="stat-label">ROADS</div><div class="stat-value"><?= $roadsCount ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-slate"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-signpost-split"></i></div>
        <div><div class="stat-label">STREETS</div><div class="stat-value"><?= $streetsCount ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-red"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-exclamation-octagon"></i></div>
        <div><div class="stat-label">OVERDUE INSTALLMENTS</div><div class="stat-value"><?= (int)$overdue['c'] ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-blue"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-alarm"></i></div>
        <div><div class="stat-label">PENDING INSTALLMENTS</div><div class="stat-value"><?= (int)$pendingInst['c'] ?></div></div></div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-green"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
        <div><div class="stat-label">TODAY'S RECOVERY</div><div class="stat-value"><?= e(setting('currency', 'Rs.')) ?> <?= fmt_money($todayRecovery) ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-blue"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-bag-check"></i></div>
        <div><div class="stat-label">BOOKING VALUE</div><div class="stat-value"><?= e(setting('currency', 'Rs.')) ?> <?= fmt_money($bookingValue) ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-purple"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
        <div><div class="stat-label">COLLECTED</div><div class="stat-value"><?= e(setting('currency', 'Rs.')) ?> <?= fmt_money($collected) ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-orange"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
        <div><div class="stat-label">OUTSTANDING</div><div class="stat-value"><?= e(setting('currency', 'Rs.')) ?> <?= fmt_money($outstanding) ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-slate"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-pin-map"></i></div>
        <div><div class="stat-label">PLOTS</div><div class="stat-value"><?= $plots ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-cyan"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-house"></i></div>
        <div><div class="stat-label">HOUSES</div><div class="stat-value"><?= $houses ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-purple"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-building"></i></div>
        <div><div class="stat-label">APARTMENTS</div><div class="stat-value"><?= $apartments ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-red"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-shop"></i></div>
        <div><div class="stat-label">COMMERCIAL UNITS</div><div class="stat-value"><?= $commercial ?></div></div></div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-green"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-door-open"></i></div>
        <div><div class="stat-label">AVAILABLE</div><div class="stat-value"><?= $stat['available'] ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-cyan"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-bookmark-check"></i></div>
        <div><div class="stat-label">BOOKED</div><div class="stat-value"><?= $stat['booked'] ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-purple"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
        <div><div class="stat-label">SOLD</div><div class="stat-value"><?= $stat['sold'] ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-orange"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-key"></i></div>
        <div><div class="stat-label">RENTAL</div><div class="stat-value"><?= $stat['rental'] ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-slate"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
        <div><div class="stat-label">OCCUPIED</div><div class="stat-value"><?= $stat['occupied'] ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-red"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-piggy-bank"></i></div>
        <div><div class="stat-label">PENDING AMOUNT</div><div class="stat-value"><?= e(setting('currency', 'Rs.')) ?> <?= fmt_money((float)$pendingInst['amt']) ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-blue"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-alarm"></i></div>
        <div><div class="stat-label">OVERDUE AMOUNT</div><div class="stat-value"><?= e(setting('currency', 'Rs.')) ?> <?= fmt_money((float)$overdue['amt']) ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-green"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
        <div><div class="stat-label">VACANT</div><div class="stat-value"><?= $stat['vacant'] ?></div></div></div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center"><i class="bi bi-bar-chart me-2"></i> Monthly Sales <span class="ms-auto text-muted small">Last 6 months</span></div>
            <div class="card-body"><canvas id="chartSales" height="100"></canvas></div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center"><i class="bi bi-cash-coin me-2"></i> Monthly Collection <span class="ms-auto text-muted small">Last 6 months</span></div>
            <div class="card-body"><canvas id="chartRent" height="100"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-calendar2-check me-2"></i> Upcoming Installments (30 days)</div>
            <div class="card-body p-0">
                <?php if ($upcomingInst): ?>
                <table class="table table-hover mb-0">
                    <thead><tr><th>Due Date</th><th>Customer</th><th>Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($upcomingInst as $u): ?>
                        <tr>
                            <td class="text-nowrap"><?= fmt_date($u['due_date']) ?></td>
                            <td><?= e($u['full_name']) ?><div class="small text-muted"><?= e($u['property_no']) ?></div></td>
                            <td><?= fmt_money($u['due']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty-state"><i class="bi bi-check-circle"></i><p>No upcoming installments</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-cash-stack me-2"></i> Recent Receipts</div>
            <div class="card-body p-0">
                <?php if ($recentReceipts): ?>
                <table class="table table-hover mb-0">
                    <thead><tr><th>Date</th><th>Receipt</th><th>Customer</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentReceipts as $r): ?>
                        <tr>
                            <td class="text-nowrap"><?= fmt_date($r['receipt_date']) ?></td>
                            <td><?= e($r['receipt_no']) ?></td>
                            <td><?= e($r['full_name']) ?><div class="small text-muted"><?= e($r['property_no']) ?></div></td>
                            <td class="text-end"><?= fmt_money($r['amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty-state"><i class="bi bi-cash-stack"></i><p>No receipts yet</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const months = <?= json_encode($months) ?>;
new Chart(document.getElementById('chartSales'), {
    type: 'bar',
    data: { labels: months, datasets: [{ label: 'Sales (Rs.)', data: <?= json_encode($salesData) ?>, backgroundColor: '#2d6cdf', borderRadius: 8 }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
new Chart(document.getElementById('chartRent'), {
    type: 'bar',
    data: { labels: months, datasets: [{ label: 'Collection (Rs.)', data: <?= json_encode($rentData) ?>, backgroundColor: '#1f9d55', borderRadius: 8 }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>

<?php include '../includes/footer.php'; ?>

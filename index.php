<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_permission('dashboard.view');

$title = 'Dashboard';
$active = 'dashboard';

function kpi($sql, $params = []) {
    $r = db_get($sql, $params);
    return (int)($r['c'] ?? 0);
}

$projects = kpi("SELECT COUNT(*) c FROM projects");
$properties = kpi("SELECT COUNT(*) c FROM properties");
$customers = kpi("SELECT COUNT(*) c FROM customers");
$tenants = kpi("SELECT COUNT(*) c FROM tenants");
$owners = kpi("SELECT COUNT(*) c FROM owners");
$dealers = kpi("SELECT COUNT(*) c FROM dealers");

$typeCounts = db_all("SELECT pt.name, COUNT(*) c FROM properties p JOIN property_types pt ON pt.id = p.property_type_id GROUP BY p.property_type_id");
$statusCounts = db_all("SELECT status, COUNT(*) c FROM properties GROUP BY status");
$catCounts = db_all("SELECT pc.name, COUNT(*) c FROM properties p LEFT JOIN property_categories pc ON pc.id = p.property_category_id GROUP BY p.property_category_id");

$stat = [];
foreach ($statusCounts as $s) {
    $stat[$s['status']] = (int)$s['c'];
}
$stat += ['available' => 0, 'booked' => 0, 'sold' => 0, 'reserved' => 0, 'transferred' => 0, 'rental' => 0, 'occupied' => 0, 'vacant' => 0];
$plots = $houses = $apartments = $commercial = 0;
foreach ($typeCounts as $t) {
    if ($t['name'] === 'Plot') $plots = (int)$t['c'];
    if ($t['name'] === 'House') $houses = (int)$t['c'];
    if ($t['name'] === 'Apartment') $apartments = (int)$t['c'];
}
foreach ($catCounts as $c) {
    if ($c['name'] === 'Commercial') $commercial = (int)$c['c'];
}

$pendingInst = db_get("SELECT COUNT(*) c, COALESCE(SUM(amount - paid_amount),0) amt FROM installments WHERE status IN ('pending','partial') AND due_date <= CURDATE()");
$pendingInstCount = (int)$pendingInst['c'];
$pendingInstAmt = (float)$pendingInst['amt'];

$overdueCount = kpi("SELECT COUNT(*) c FROM installments WHERE status IN ('pending','partial') AND due_date < CURDATE()");
$overdueAmt = (float)db_get("SELECT COALESCE(SUM(amount - paid_amount),0) amt FROM installments WHERE status IN ('pending','partial') AND due_date < CURDATE()")['amt'];

$todayRecovery = (float)db_get("SELECT COALESCE(SUM(amount),0) amt FROM receipts WHERE receipt_date = CURDATE()")['amt'];

function acct_balance($code) {
    $r = db_get("SELECT COALESCE(SUM(vi.debit - vi.credit),0) amt FROM voucher_items vi JOIN chart_of_accounts c ON c.id = vi.account_id WHERE c.code LIKE ?", [$code . '%']);
    return (float)$r['amt'];
}
$cashBalance = acct_balance('1000');
$bankBalance = acct_balance('1001');

$income = (float)db_get("SELECT COALESCE(SUM(vi.credit - vi.debit),0) amt FROM voucher_items vi JOIN chart_of_accounts c ON c.id = vi.account_id WHERE c.account_type = 'income'")['amt'];
$expense = (float)db_get("SELECT COALESCE(SUM(vi.debit - vi.credit),0) amt FROM voucher_items vi JOIN chart_of_accounts c ON c.id = vi.account_id WHERE c.account_type = 'expense'")['amt'];

$projectSummaries = db_all("SELECT p.id, p.name, p.developer, p.status,
    (SELECT COUNT(*) FROM properties pr WHERE pr.project_id = p.id) AS properties,
    (SELECT COUNT(*) FROM properties pr WHERE pr.project_id = p.id AND pr.status = 'available') AS available,
    (SELECT COUNT(*) FROM properties pr WHERE pr.project_id = p.id AND pr.status = 'booked') AS booked,
    (SELECT COUNT(*) FROM properties pr WHERE pr.project_id = p.id AND pr.status = 'sold') AS sold,
    (SELECT COUNT(DISTINCT b.customer_id) FROM bookings b JOIN properties pr ON pr.id = b.property_id WHERE pr.project_id = p.id) AS customers,
    (SELECT COUNT(*) FROM bookings b JOIN properties pr ON pr.id = b.property_id WHERE pr.project_id = p.id AND b.status <> 'cancelled') AS bookings,
    (SELECT COALESCE(SUM(b.total_price - b.discount),0) FROM bookings b JOIN properties pr ON pr.id = b.property_id WHERE pr.project_id = p.id AND b.status <> 'cancelled') AS booking_value,
    (SELECT COALESCE(SUM(r.amount),0) FROM receipts r JOIN bookings b ON b.id = r.booking_id JOIN properties pr ON pr.id = b.property_id WHERE pr.project_id = p.id) AS collected,
    (SELECT COALESCE(SUM(i.amount - i.paid_amount),0) FROM installments i JOIN bookings b ON b.id = i.booking_id JOIN properties pr ON pr.id = b.property_id WHERE pr.project_id = p.id AND i.status IN ('pending','partial')) AS pending_amt
    FROM projects p ORDER BY p.name");

$months = [];
$salesData = [];
$rentData = [];
for ($i = 5; $i >= 0; $i--) {
    $months[] = date('M-y', strtotime("first day of -$i months"));
    $m = date('Y-m', strtotime("first day of -$i months"));
    $sr = db_get("SELECT COALESCE(SUM(total_price),0) amt FROM bookings WHERE DATE_FORMAT(booking_date,'%Y-%m') = ?", [$m]);
    $salesData[] = round((float)$sr['amt']);
    $rr = db_get("SELECT COALESCE(SUM(amount),0) amt FROM rent_collections WHERE DATE_FORMAT(collection_date,'%Y-%m') = ?", [$m]);
    $rentData[] = round((float)$rr['amt']);
}

$upcomingInst = db_all("SELECT i.due_date, i.amount - i.paid_amount AS due, b.booking_no, c.full_name, p.property_no
                        FROM installments i
                        JOIN bookings b ON b.id = i.booking_id
                        JOIN customers c ON c.id = b.customer_id
                        JOIN properties p ON p.id = b.property_id
                        WHERE i.status IN ('pending','partial') AND i.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                        ORDER BY i.due_date LIMIT 8");

$upcomingRent = db_all("SELECT rs.due_date, rs.rent_amount - rs.paid_amount AS due, ra.agreement_no, t.full_name
                        FROM rent_schedule rs
                        JOIN rental_agreements ra ON ra.id = rs.agreement_id
                        JOIN tenants t ON t.id = ra.tenant_id
                        WHERE rs.status IN ('pending','partial') AND rs.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                        ORDER BY rs.due_date LIMIT 8");

$expiring = db_all("SELECT end_date, agreement_no, t.full_name, p.property_no
                    FROM rental_agreements ra
                    JOIN tenants t ON t.id = ra.tenant_id
                    JOIN properties p ON p.id = ra.property_id
                    WHERE ra.status = 'active' AND ra.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                    ORDER BY ra.end_date LIMIT 8");

include __DIR__ . '/includes/header.php';
?>

<?php if ($user['is_super_admin']): ?>
<div class="alert alert-primary d-flex align-items-center py-2 mb-3">
    <i class="bi bi-person-badge me-2"></i>
    <span class="small">Signed in as <strong><?= e($user['full_name']) ?></strong> (<?= e($user['role_name']) ?>) &mdash; Today is <?= e(date('l, d F Y')) ?></span>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-blue"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-grid-1x2"></i></div>
        <div><div class="stat-label">PROJECTS</div><div class="stat-value"><?= $projects ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-green"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-house-door"></i></div>
        <div><div class="stat-label">PROPERTIES</div><div class="stat-value"><?= $properties ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-purple"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-people"></i></div>
        <div><div class="stat-label">CUSTOMERS</div><div class="stat-value"><?= $customers ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-orange"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-person-check"></i></div>
        <div><div class="stat-label">TENANTS</div><div class="stat-value"><?= $tenants ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-slate"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
        <div><div class="stat-label">OWNERS</div><div class="stat-value"><?= $owners ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-cyan"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-handshake"></i></div>
        <div><div class="stat-label">DEALERS / AGENTS</div><div class="stat-value"><?= $dealers ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-red"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-exclamation-octagon"></i></div>
        <div><div class="stat-label">PENDING INSTALLMENTS</div><div class="stat-value"><?= $pendingInstCount ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-red"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-alarm"></i></div>
        <div><div class="stat-label">OVERDUE INSTALLMENTS</div><div class="stat-value"><?= $overdueCount ?></div></div></div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-green"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
        <div><div class="stat-label">TODAY'S RECOVERY</div><div class="stat-value"><?= e(setting('currency', 'Rs.')) ?> <?= fmt_money($todayRecovery) ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-blue"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-wallet"></i></div>
        <div><div class="stat-label">CASH BALANCE</div><div class="stat-value"><?= e(setting('currency', 'Rs.')) ?> <?= fmt_money($cashBalance) ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-purple"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-bank"></i></div>
        <div><div class="stat-label">BANK BALANCE</div><div class="stat-value"><?= e(setting('currency', 'Rs.')) ?> <?= fmt_money($bankBalance) ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-orange"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-graph-up"></i></div>
        <div><div class="stat-label">INCOME</div><div class="stat-value"><?= e(setting('currency', 'Rs.')) ?> <?= fmt_money($income) ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-red"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-graph-down"></i></div>
        <div><div class="stat-label">EXPENSES</div><div class="stat-value"><?= e(setting('currency', 'Rs.')) ?> <?= fmt_money($expense) ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-slate"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-bookmark-check"></i></div>
        <div><div class="stat-label">AVAILABLE</div><div class="stat-value"><?= $stat['available'] ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-cyan"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-cart-check"></i></div>
        <div><div class="stat-label">BOOKED</div><div class="stat-value"><?= $stat['booked'] ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-purple"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
        <div><div class="stat-label">SOLD</div><div class="stat-value"><?= $stat['sold'] ?></div></div></div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-blue"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-pin-map"></i></div>
        <div><div class="stat-label">PLOTS</div><div class="stat-value"><?= $plots ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-green"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-house"></i></div>
        <div><div class="stat-label">HOUSES</div><div class="stat-value"><?= $houses ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-orange"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-building"></i></div>
        <div><div class="stat-label">APARTMENTS</div><div class="stat-value"><?= $apartments ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-purple"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-shop"></i></div>
        <div><div class="stat-label">COMMERCIAL UNITS</div><div class="stat-value"><?= $commercial ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-cyan"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-key"></i></div>
        <div><div class="stat-label">RENTAL</div><div class="stat-value"><?= $stat['rental'] ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-red"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
        <div><div class="stat-label">OCCUPIED UNITS</div><div class="stat-value"><?= $stat['occupied'] ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-slate"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-door-open"></i></div>
        <div><div class="stat-label">VACANT UNITS</div><div class="stat-value"><?= $stat['vacant'] ?></div></div></div></div></div>
    <div class="col-xl-3 col-md-6"><div class="card stat-card bg-grad-blue"><div class="stat-body">
        <div class="stat-icon"><i class="bi bi-piggy-bank"></i></div>
        <div><div class="stat-label">PENDING AMOUNT</div><div class="stat-value"><?= e(setting('currency', 'Rs.')) ?> <?= fmt_money($pendingInstAmt) ?></div></div></div></div></div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex align-items-center">
        <i class="bi bi-grid-1x2 me-2"></i> Projects Overview
        <span class="ms-auto text-muted small">Har project ki apni dashboard</span>
    </div>
    <div class="card-body">
        <?php if ($projectSummaries): ?>
        <div class="row g-3">
            <?php foreach ($projectSummaries as $pj): ?>
            <?php $pOutstanding = (float)$pj['booking_value'] - (float)$pj['collected']; ?>
            <div class="col-xl-4 col-md-6">
                <div class="card h-100 border">
                    <div class="card-body">
                        <div class="d-flex align-items-start mb-3">
                            <i class="bi bi-building fs-5 me-2 text-primary"></i>
                            <div class="min-w-0">
                                <div class="fw-semibold text-truncate"><?= e($pj['name']) ?></div>
                                <div class="small text-muted text-truncate"><?= e($pj['developer'] ?? '-') ?></div>
                            </div>
                            <?php if ($pj['status']): ?><span class="badge bg-success ms-auto">Active</span><?php else: ?><span class="badge bg-secondary ms-auto">Inactive</span><?php endif; ?>
                        </div>
                        <div class="row text-center small mb-3">
                            <div class="col-3"><div class="fw-bold fs-6"><?= $pj['properties'] ?></div><div class="text-muted">Props</div></div>
                            <div class="col-3"><div class="fw-bold fs-6 text-info"><?= $pj['booked'] ?></div><div class="text-muted">Booked</div></div>
                            <div class="col-3"><div class="fw-bold fs-6 text-success"><?= $pj['sold'] ?></div><div class="text-muted">Sold</div></div>
                            <div class="col-3"><div class="fw-bold fs-6"><?= $pj['customers'] ?></div><div class="text-muted">Cust</div></div>
                        </div>
                        <div class="small">
                            <div class="d-flex justify-content-between py-1"><span class="text-muted">Booking Value</span><span class="fw-semibold"><?= fmt_money($pj['booking_value']) ?></span></div>
                            <div class="d-flex justify-content-between py-1"><span class="text-muted">Collected</span><span class="fw-semibold text-success"><?= fmt_money($pj['collected']) ?></span></div>
                            <div class="d-flex justify-content-between py-1"><span class="text-muted">Outstanding</span><span class="fw-semibold text-danger"><?= fmt_money($pOutstanding) ?></span></div>
                            <div class="d-flex justify-content-between py-1"><span class="text-muted">Pending Inst.</span><span class="fw-semibold text-warning"><?= fmt_money($pj['pending_amt']) ?></span></div>
                        </div>
                        <a class="btn btn-sm btn-primary w-100 mt-3" href="<?= BASE_URL ?>/pages/project_dashboard.php?id=<?= $pj['id'] ?>"><i class="bi bi-speedometer2 me-1"></i>Open Dashboard</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <div class="empty-state"><i class="bi bi-grid-1x2"></i><p>No projects yet</p></div>
        <?php endif; ?>
    </div>
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
            <div class="card-header d-flex align-items-center"><i class="bi bi-bar-chart me-2"></i> Monthly Rentals <span class="ms-auto text-muted small">Last 6 months</span></div>
            <div class="card-body"><canvas id="chartRent" height="100"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-4">
        <div class="card">
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
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-cash-coin me-2"></i> Upcoming Rent (30 days)</div>
            <div class="card-body p-0">
                <?php if ($upcomingRent): ?>
                <table class="table table-hover mb-0">
                    <thead><tr><th>Due Date</th><th>Tenant</th><th>Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($upcomingRent as $u): ?>
                        <tr>
                            <td class="text-nowrap"><?= fmt_date($u['due_date']) ?></td>
                            <td><?= e($u['full_name']) ?></td>
                            <td><?= fmt_money($u['due']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty-state"><i class="bi bi-check-circle"></i><p>No upcoming rents</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-file-earmark-text me-2"></i> Agreements Expiring (30 days)</div>
            <div class="card-body p-0">
                <?php if ($expiring): ?>
                <table class="table table-hover mb-0">
                    <thead><tr><th>Expiry</th><th>Tenant</th><th>Property</th></tr></thead>
                    <tbody>
                    <?php foreach ($expiring as $u): ?>
                        <tr>
                            <td class="text-nowrap"><?= fmt_date($u['end_date']) ?></td>
                            <td><?= e($u['full_name']) ?></td>
                            <td><?= e($u['property_no']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="empty-state"><i class="bi bi-check-circle"></i><p>No expiring agreements</p></div>
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
    data: { labels: months, datasets: [{ label: 'Rent Collected (Rs.)', data: <?= json_encode($rentData) ?>, backgroundColor: '#1f9d55', borderRadius: 8 }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

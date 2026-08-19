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
    $r = db_get("SELECT COALESCE(SUM(vi.debit - vi.credit),0) + (SELECT COALESCE(SUM(opening_balance),0) FROM chart_of_accounts WHERE code LIKE ?) amt FROM voucher_items vi JOIN chart_of_accounts c ON c.id = vi.account_id WHERE c.code LIKE ?", [$code . '%', $code . '%']);
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

<style>
.dash-greeting { background: linear-gradient(135deg, #2d6cdf 0%, #1a4fa0 100%); color: #fff; border-radius: 12px; padding: 20px 28px; margin-bottom: 20px; }
.dash-greeting h4 { font-weight: 700; margin-bottom: 2px; }
.dash-greeting p { opacity: .85; margin: 0; font-size: 14px; }
.dash-finance .card { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.06); transition: transform .15s; }
.dash-finance .card:hover { transform: translateY(-2px); }
.dash-finance .fi-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: #fff; flex-shrink: 0; }
.dash-finance .fi-value { font-size: 20px; font-weight: 700; line-height: 1.2; }
.dash-finance .fi-label { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #6b7280; font-weight: 600; }
.property-bar { display: flex; border-radius: 10px; overflow: hidden; height: 14px; }
.property-bar > div { transition: width .3s; }
.property-legend { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 10px; }
.property-legend span { font-size: 12px; display: flex; align-items: center; gap: 5px; }
.property-legend span::before { content: ''; width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }
.pl-available::before { background: #10b981; }
.pl-booked::before { background: #f59e0b; }
.pl-sold::before { background: #2d6cdf; }
.pl-reserved::before { background: #8b5cf6; }
.pl-rental::before { background: #06b6d4; }
</style>

<div class="d-flex flex-wrap align-items-center gap-3 mb-3">
    <div class="flex-grow-1">
        <div class="dash-greeting d-flex align-items-center justify-content-between">
            <div>
                <h4><?= e(setting('company_name', APP_NAME)) ?></h4>
                <p><?= e(date('l, d F Y')) ?> &mdash; Welcome back, <?= e($user['full_name']) ?></p>
            </div>
            <div class="d-none d-md-flex gap-2">
                <a href="<?= BASE_URL ?>/pages/cash_received.php" class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3)"><i class="bi bi-arrow-down-circle me-1"></i>Receive</a>
                <a href="<?= BASE_URL ?>/pages/cash_paid.php" class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3)"><i class="bi bi-arrow-up-circle me-1"></i>Pay</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3 dash-finance">
    <div class="col-xl-3 col-sm-6">
        <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="fi-icon" style="background:linear-gradient(135deg,#10b981,#34d399)"><i class="bi bi-wallet2"></i></div>
            <div><div class="fi-label">Cash Balance</div><div class="fi-value"><?= fmt_money($cashBalance) ?></div></div>
        </div></div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="fi-icon" style="background:linear-gradient(135deg,#2d6cdf,#4d9bff)"><i class="bi bi-bank"></i></div>
            <div><div class="fi-label">Bank Balance</div><div class="fi-value"><?= fmt_money($bankBalance) ?></div></div>
        </div></div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="fi-icon" style="background:linear-gradient(135deg,#059669,#10b981)"><i class="bi bi-graph-up-arrow"></i></div>
            <div><div class="fi-label">Total Income</div><div class="fi-value text-success"><?= fmt_money($income) ?></div></div>
        </div></div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="fi-icon" style="background:linear-gradient(135deg,#dc2626,#ef4444)"><i class="bi bi-graph-down-arrow"></i></div>
            <div><div class="fi-label">Total Expenses</div><div class="fi-value text-danger"><?= fmt_money($expense) ?></div></div>
        </div></div>
    </div>
</div>

<div class="row g-3 mb-3 dash-finance">
    <div class="col-xl-3 col-sm-6">
        <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="fi-icon" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)"><i class="bi bi-cash-stack"></i></div>
            <div><div class="fi-label">Today's Recovery</div><div class="fi-value"><?= fmt_money($todayRecovery) ?></div></div>
        </div></div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="fi-icon" style="background:linear-gradient(135deg,#dc2626,#f87171)"><i class="bi bi-exclamation-triangle"></i></div>
            <div><div class="fi-label">Overdue Installments</div><div class="fi-value"><?= $overdueCount ?></div><div class="small text-danger"><?= fmt_money($overdueAmt) ?></div></div>
        </div></div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="fi-icon" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa)"><i class="bi bi-hourglass-split"></i></div>
            <div><div class="fi-label">Pending Installments</div><div class="fi-value"><?= $pendingInstCount ?></div><div class="small text-muted"><?= fmt_money($pendingInstAmt) ?></div></div>
        </div></div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
            <div class="fi-icon" style="background:linear-gradient(135deg,#06b6d4,#22d3ee)"><i class="bi bi-building"></i></div>
            <div><div class="fi-label">Properties</div><div class="fi-value"><?= $properties ?></div><div class="small text-muted"><?= $projects ?> projects</div></div>
        </div></div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-3 justify-content-center">
            <a href="<?= BASE_URL ?>/pages/bookings.php" class="quick-action-btn" title="Bookings (F4)"><div class="quick-action-circle"><i class="bi bi-journal-check"></i></div><span>Bookings</span><span class="quick-key">F4</span></a>
            <a href="<?= BASE_URL ?>/pages/customers.php" class="quick-action-btn" title="Customers (F5)"><div class="quick-action-circle"><i class="bi bi-people"></i></div><span>Customers</span><span class="quick-key">F5</span></a>
            <a href="<?= BASE_URL ?>/pages/cashbook.php" class="quick-action-btn" title="Cashbook (F6)"><div class="quick-action-circle"><i class="bi bi-cash-stack"></i></div><span>Cashbook</span><span class="quick-key">F6</span></a>
            <a href="<?= BASE_URL ?>/pages/purchase_form.php" class="quick-action-btn" title="New Purchase (F7)"><div class="quick-action-circle"><i class="bi bi-bag-plus"></i></div><span>Purchase</span><span class="quick-key">F7</span></a>
            <a href="<?= BASE_URL ?>/pages/installments.php" class="quick-action-btn" title="Installments (F8)"><div class="quick-action-circle"><i class="bi bi-calendar2-check"></i></div><span>Installments</span><span class="quick-key">F8</span></a>
            <a href="<?= BASE_URL ?>/pages/vouchers.php" class="quick-action-btn" title="Vouchers (F9)"><div class="quick-action-circle"><i class="bi bi-receipt"></i></div><span>Vouchers</span><span class="quick-key">F9</span></a>
            <a href="<?= BASE_URL ?>/pages/cash_received.php" class="quick-action-btn" title="Cash Received (F10)"><div class="quick-action-circle"><i class="bi bi-arrow-down-circle"></i></div><span>Received</span><span class="quick-key">F10</span></a>
            <a href="<?= BASE_URL ?>/pages/cash_paid.php" class="quick-action-btn" title="Cash Paid (F11)"><div class="quick-action-circle"><i class="bi bi-arrow-up-circle"></i></div><span>Paid</span><span class="quick-key">F11</span></a>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center"><i class="bi bi-building me-2"></i> Properties</div>
            <div class="card-body">
                <?php $totalProps = $stat['available'] + $stat['booked'] + $stat['sold'] + $stat['reserved'] + $stat['rental']; ?>
                <?php if ($totalProps > 0): ?>
                <div class="property-bar mb-2">
                    <?php if ($stat['available']): ?><div style="width:<?= round($stat['available']/$totalProps*100) ?>%;background:#10b981" title="Available: <?= $stat['available'] ?>"></div><?php endif; ?>
                    <?php if ($stat['booked']): ?><div style="width:<?= round($stat['booked']/$totalProps*100) ?>%;background:#f59e0b" title="Booked: <?= $stat['booked'] ?>"></div><?php endif; ?>
                    <?php if ($stat['sold']): ?><div style="width:<?= round($stat['sold']/$totalProps*100) ?>%;background:#2d6cdf" title="Sold: <?= $stat['sold'] ?>"></div><?php endif; ?>
                    <?php if ($stat['reserved']): ?><div style="width:<?= round($stat['reserved']/$totalProps*100) ?>%;background:#8b5cf6" title="Reserved: <?= $stat['reserved'] ?>"></div><?php endif; ?>
                    <?php if ($stat['rental']): ?><div style="width:<?= round($stat['rental']/$totalProps*100) ?>%;background:#06b6d4" title="Rental: <?= $stat['rental'] ?>"></div><?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="property-legend">
                    <span class="pl-available">Available <strong><?= $stat['available'] ?></strong></span>
                    <span class="pl-booked">Booked <strong><?= $stat['booked'] ?></strong></span>
                    <span class="pl-sold">Sold <strong><?= $stat['sold'] ?></strong></span>
                    <span class="pl-reserved">Reserved <strong><?= $stat['reserved'] ?></strong></span>
                    <span class="pl-rental">Rental <strong><?= $stat['rental'] ?></strong></span>
                </div>
                <hr>
                <div class="row text-center small">
                    <div class="col"><div class="fw-bold fs-5"><?= $plots ?></div><div class="text-muted">Plots</div></div>
                    <div class="col"><div class="fw-bold fs-5"><?= $houses ?></div><div class="text-muted">Houses</div></div>
                    <div class="col"><div class="fw-bold fs-5"><?= $apartments ?></div><div class="text-muted">Apartments</div></div>
                    <div class="col"><div class="fw-bold fs-5"><?= $commercial ?></div><div class="text-muted">Commercial</div></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center"><i class="bi bi-people me-2"></i> People</div>
            <div class="card-body d-flex flex-column justify-content-center gap-3">
                <div class="d-flex align-items-center justify-content-between p-2 rounded" style="background:#f0f6ff">
                    <div class="d-flex align-items-center gap-2"><i class="bi bi-person-lines-fill text-primary"></i> Customers</div>
                    <span class="fw-bold fs-5"><?= $customers ?></span>
                </div>
                <div class="d-flex align-items-center justify-content-between p-2 rounded" style="background:#f0fdf4">
                    <div class="d-flex align-items-center gap-2"><i class="bi bi-person-badge text-success"></i> Owners</div>
                    <span class="fw-bold fs-5"><?= $owners ?></span>
                </div>
                <div class="d-flex align-items-center justify-content-between p-2 rounded" style="background:#fef3c7">
                    <div class="d-flex align-items-center gap-2"><i class="bi bi-handshake text-warning"></i> Dealers</div>
                    <span class="fw-bold fs-5"><?= $dealers ?></span>
                </div>
                <div class="d-flex align-items-center justify-content-between p-2 rounded" style="background:#f0f9ff">
                    <div class="d-flex align-items-center gap-2"><i class="bi bi-person-check text-info"></i> Tenants</div>
                    <span class="fw-bold fs-5"><?= $tenants ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex align-items-center">
        <i class="bi bi-grid-1x2 me-2"></i> Projects Overview
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
                            <div class="col-3"><div class="fw-bold fs-6 text-warning"><?= $pj['booked'] ?></div><div class="text-muted">Booked</div></div>
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
document.addEventListener('keydown', function(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT' || e.target.tagName === 'TEXTAREA') return;
    var map = { 115: 'pages/bookings.php', 116: 'pages/customers.php', 117: 'pages/cashbook.php', 118: 'pages/purchase_form.php', 119: 'pages/installments.php', 120: 'pages/vouchers.php', 121: 'pages/cash_received.php', 122: 'pages/cash_paid.php' };
    if (map[e.keyCode]) { e.preventDefault(); window.location.href = '<?= BASE_URL ?>/' + map[e.keyCode]; }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

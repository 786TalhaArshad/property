<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Outstanding Report';
$active = 'reports';

$asOf = $_GET['as_of'] ?? date('Y-m-d');
$project_id = (int)($_GET['project_id'] ?? active_project_id());

$inst = db_all("SELECT i.*, b.booking_no, c.full_name AS customer_name, c.phone, p.property_no
                FROM installments i
                JOIN bookings b ON b.id = i.booking_id
                JOIN customers c ON c.id = b.customer_id
                JOIN properties p ON p.id = b.property_id
                WHERE b.status <> 'cancelled' AND i.status IN ('pending','partial','overdue')
                AND (? = 0 OR p.project_id = ?)
                ORDER BY i.due_date", [$project_id, $project_id]);

$instOut = 0.0; $instTotal = 0; $overdue = 0;
$instByBooking = [];
foreach ($inst as $r) {
    $amt = (float)$r['amount'] - (float)$r['paid_amount'];
    if ($amt <= 0) continue;
    $instOut += $amt;
    $instTotal++;
    if ($r['due_date'] < $asOf) $overdue++;
    $bk = $r['booking_id'];
    if (!isset($instByBooking[$bk])) {
        $instByBooking[$bk] = ['booking_no' => $r['booking_no'], 'customer' => $r['customer_name'], 'phone' => $r['phone'], 'property' => $r['property_no'], 'out' => 0.0];
    }
    $instByBooking[$bk]['out'] += $amt;
}

$rent = db_all("SELECT rs.*, ra.agreement_no, p.property_no, t.full_name AS tenant_name, t.emergency_contact AS phone
                FROM rent_schedule rs
                JOIN rental_agreements ra ON ra.id = rs.agreement_id
                JOIN properties p ON p.id = ra.property_id
                JOIN tenants t ON t.id = ra.tenant_id
                WHERE ra.status IN ('active','renewed') AND rs.status IN ('pending','partial','overdue')
                AND (? = 0 OR p.project_id = ?)
                ORDER BY rs.due_date", [$project_id, $project_id]);

$rentOut = 0.0; $rentRows = 0; $rentOverdue = 0;
foreach ($rent as $r) {
    $amt = (float)$r['rent_amount'] - (float)$r['paid_amount'];
    if ($amt <= 0) continue;
    $rentOut += $amt;
    $rentRows++;
    if ($r['due_date'] < $asOf) $rentOverdue++;
}
$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
include '../../includes/header.php';
?>

<form method="get" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-2"><input type="date" name="as_of" class="form-control" value="<?= e($asOf) ?>"></div>
            <div class="col-md-3">
                <select name="project_id" class="form-select">
                    <option value="">All Projects</option>
                    <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-calendar2-x"></i></div><div><div class="stat-label">INSTALLMENTS OUTSTANDING</div><div class="stat-value"><?= fmt_money($instOut) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-house-x"></i></div><div><div class="stat-label">RENT OUTSTANDING</div><div class="stat-value"><?= fmt_money($rentOut) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-label">TOTAL OUTSTANDING</div><div class="stat-value"><?= fmt_money($instOut + $rentOut) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-red"><div class="stat-body"><div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div><div><div class="stat-label">OVERDUE ROWS</div><div class="stat-value"><?= $overdue + $rentOverdue ?></div></div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-calendar2-check me-2"></i>Installment Outstanding (by Booking)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Booking</th><th>Customer</th><th>Property</th><th class="text-end">Outstanding</th></tr></thead>
                        <tbody>
                        <?php foreach ($instByBooking as $b): ?>
                            <tr><td class="fw-medium"><?= e($b['booking_no']) ?></td><td><?= e($b['customer']) ?><div class="small text-muted"><?= e($b['phone'] ?? '') ?></div></td><td><?= e($b['property']) ?></td><td class="text-end fw-bold"><?= fmt_money($b['out']) ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (!$instByBooking): ?><tr><td colspan="4"><div class="empty-state"><i class="bi bi-check-circle"></i><p>No outstanding installments</p></div></td></tr><?php endif; ?>
                        </tbody>
                        <tfoot><tr class="table-light"><td colspan="3" class="fw-bold">Total</td><td class="text-end fw-bold"><?= fmt_money($instOut) ?></td></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-house-x me-2"></i>Rent Outstanding (by Property)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Property</th><th>Tenant</th><th>Agreement</th><th class="text-end">Outstanding</th></tr></thead>
                        <tbody>
                        <?php $rentSeen = []; foreach ($rent as $r): $amt = (float)$r['rent_amount'] - (float)$r['paid_amount']; if ($amt <= 0) continue; $k = $r['agreement_id']; if (isset($rentSeen[$k])) { $rentSeen[$k]['out'] += $amt; continue; } $rentSeen[$k] = ['property' => $r['property_no'], 'tenant' => $r['tenant_name'], 'agr' => $r['agreement_no'], 'out' => $amt]; endforeach; ?>
                        <?php foreach ($rentSeen as $b): ?>
                            <tr><td class="fw-medium"><?= e($b['property']) ?></td><td><?= e($b['tenant']) ?></td><td class="small"><?= e($b['agr']) ?></td><td class="text-end fw-bold"><?= fmt_money($b['out']) ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (!$rentSeen): ?><tr><td colspan="4"><div class="empty-state"><i class="bi bi-check-circle"></i><p>No outstanding rent</p></div></td></tr><?php endif; ?>
                        </tbody>
                        <tfoot><tr class="table-light"><td colspan="3" class="fw-bold">Total</td><td class="text-end fw-bold"><?= fmt_money($rentOut) ?></td></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

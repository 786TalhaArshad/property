<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Rental Report';
$active = 'reports';

$from = $_GET['from'] ?? date('Y-01-01');
$to = $_GET['to'] ?? date('Y-m-d');

$agreements = db_all("SELECT ra.*, p.property_no, t.full_name AS tenant_name, o.full_name AS owner_name,
                      (SELECT COALESCE(SUM(rs.rent_amount),0) FROM rent_schedule rs WHERE rs.agreement_id = ra.id AND rs.due_date BETWEEN ? AND ?) AS billed,
                      (SELECT COALESCE(SUM(rs.paid_amount),0) FROM rent_schedule rs WHERE rs.agreement_id = ra.id AND rs.due_date BETWEEN ? AND ?) AS collected
                      FROM rental_agreements ra
                      JOIN properties p ON p.id = ra.property_id
                      JOIN tenants t ON t.id = ra.tenant_id
                      LEFT JOIN owners o ON o.id = ra.owner_id
                      WHERE ra.start_date <= ? AND ra.end_date >= ?
                      ORDER BY ra.start_date DESC", [$from, $to, $from, $to, $to, $from]);

$totalRent = 0.0; $totalCollected = 0.0;
foreach ($agreements as $r) {
    $totalRent += (float)$r['billed'];
    $totalCollected += (float)$r['collected'];
}
$settlements = db_all("SELECT COUNT(*) AS cnt, COALESCE(SUM(settlement_amount),0) AS tot FROM owner_settlements WHERE settlement_date BETWEEN ? AND ? AND status = 'paid'", [$from, $to]);
include '../../includes/header.php';
?>

<form method="get" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-3"><input type="date" name="from" class="form-control" value="<?= e($from) ?>"></div>
            <div class="col-md-3"><input type="date" name="to" class="form-control" value="<?= e($to) ?>"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-house-heart"></i></div><div><div class="stat-label">ACTIVE AGREEMENTS</div><div class="stat-value"><?= count($agreements) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-receipt"></i></div><div><div class="stat-label">RENT BILLED</div><div class="stat-value"><?= fmt_money($totalRent) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash"></i></div><div><div class="stat-label">RENT COLLECTED</div><div class="stat-value"><?= fmt_money($totalCollected) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-wallet2"></i></div><div><div class="stat-label">OWNER SETTLED</div><div class="stat-value"><?= fmt_money($settlements[0]['tot']) ?></div></div></div></div></div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-bar-chart me-2"></i>Agreements Active: <?= fmt_date($from) ?> to <?= fmt_date($to) ?></span>
        <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Agreement</th><th>Property</th><th>Tenant</th><th>Owner</th><th class="text-end">Monthly Rent</th><th class="text-end">Billed</th><th class="text-end">Collected</th><th class="text-end">Outstanding</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($agreements as $r): $out = (float)$r['billed'] - (float)$r['collected']; ?>
                    <tr>
                        <td class="fw-medium"><?= e($r['agreement_no']) ?></td>
                        <td><?= e($r['property_no']) ?></td>
                        <td><?= e($r['tenant_name']) ?></td>
                        <td class="small"><?= e($r['owner_name'] ?? '-') ?></td>
                        <td class="text-end"><?= fmt_money($r['monthly_rent']) ?></td>
                        <td class="text-end"><?= fmt_money($r['billed']) ?></td>
                        <td class="text-end"><?= fmt_money($r['collected']) ?></td>
                        <td class="text-end <?= $out > 0 ? 'text-danger fw-bold' : '' ?>"><?= fmt_money($out) ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$agreements): ?><tr><td colspan="9"><div class="empty-state"><i class="bi bi-inbox"></i><p>No rental agreements in this period</p></div></td></tr><?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light"><td colspan="5" class="fw-bold">Totals</td>
                        <td class="text-end fw-bold"><?= fmt_money($totalRent) ?></td>
                        <td class="text-end fw-bold"><?= fmt_money($totalCollected) ?></td>
                        <td class="text-end fw-bold text-danger"><?= fmt_money($totalRent - $totalCollected) ?></td>
                        <td></td></tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

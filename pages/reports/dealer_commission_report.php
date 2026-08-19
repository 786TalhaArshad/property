<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Dealer Commission Report';
$active = 'reports';

$from = $_GET['from'] ?? date('Y-01-01');
$to = $_GET['to'] ?? date('Y-m-d');

$dealers = db_all("SELECT d.id, d.full_name, d.dealer_no, d.dealer_type, d.commission_rate,
                   COUNT(DISTINCT b.id) AS bookings,
                   COALESCE(SUM(b.total_price),0) AS total_value,
                   COALESCE(SUM(b.total_price * d.commission_rate / 100),0) AS total_commission
                   FROM dealers d
                   LEFT JOIN bookings b ON b.dealer_id = d.id AND b.booking_date BETWEEN ? AND ? AND b.status <> 'cancelled'
                   WHERE d.status = 1
                   GROUP BY d.id
                   ORDER BY total_commission DESC", [$from, $to]);

$payments = db_all("SELECT dp.dealer_id, COALESCE(SUM(dp.amount),0) AS paid
                    FROM dealer_payments dp WHERE dp.payment_date BETWEEN ? AND ?
                    GROUP BY dp.dealer_id", [$from, $to]);
$payMap = [];
foreach ($payments as $p) $payMap[$p['dealer_id']] = (float)$p['paid'];

$totalCommission = 0.0; $totalPaid = 0.0;
foreach ($dealers as $d) {
    $totalCommission += (float)$d['total_commission'];
    $totalPaid += $payMap[$d['id']] ?? 0;
}
include '../../includes/header.php';
?>

<style>
@media print { .no-print,.sidebar,.main-header,.main-footer,.quick-action-btn{display:none!important}.main-content{margin:0!important;padding:10px!important}.card{border:none!important;box-shadow:none!important}body{font-size:11px}.table td,.table th{padding:4px 6px!important;font-size:11px} }
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>Dealer Commission Report</h5>
    <div class="ms-auto"><button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print</button></div>
</div>

<form method="get" class="card mb-3 no-print">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-2"><label class="form-label small text-muted mb-0">From</label><input type="date" name="from" class="form-control" value="<?= e($from) ?>"></div>
            <div class="col-md-2"><label class="form-label small text-muted mb-0">To</label><input type="date" name="to" class="form-control" value="<?= e($to) ?>"></div>
            <div class="col-md-2"><label class="form-label mb-0">&nbsp;</label><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="card print-only" style="display:none"><div class="text-center mb-2"><h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5><div class="small text-muted">Dealer Commission Report from <?= fmt_date($from) ?> to <?= fmt_date($to) ?></div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-person-badge"></i></div><div><div class="stat-label">DEALERS</div><div class="stat-value"><?= count($dealers) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">TOTAL COMMISSION</div><div class="stat-value"><?= fmt_money($totalCommission) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-check-circle"></i></div><div><div class="stat-label">TOTAL PAID</div><div class="stat-value"><?= fmt_money($totalPaid) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-label">PENDING</div><div class="stat-value"><?= fmt_money($totalCommission - $totalPaid) ?></div></div></div></div></div>
</div>

<div class="card">
    <div class="card-header no-print"><i class="bi bi-table me-2"></i>Dealer Summary</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>#</th><th>Dealer</th><th>Type</th><th>Rate %</th><th>Bookings</th><th class="text-end">Total Value</th><th class="text-end">Commission</th><th class="text-end">Paid</th><th class="text-end">Pending</th></tr></thead>
                <tbody>
                <?php foreach ($dealers as $i => $d): $pend = (float)$d['total_commission'] - ($payMap[$d['id']] ?? 0); ?>
                    <tr><td><?= $i + 1 ?></td><td class="fw-medium"><?= e($d['full_name']) ?></td><td><?= e($d['dealer_type']) ?></td><td><?= number_format((float)$d['commission_rate'], 1) ?>%</td><td class="text-center"><?= $d['bookings'] ?></td><td class="text-end"><?= fmt_money($d['total_value']) ?></td><td class="text-end fw-bold"><?= fmt_money($d['total_commission']) ?></td><td class="text-end text-success"><?= fmt_money($payMap[$d['id']] ?? 0) ?></td><td class="text-end <?= $pend > 0 ? 'text-danger fw-bold' : '' ?>"><?= fmt_money($pend) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$dealers): ?><tr><td colspan="9"><div class="empty-state"><i class="bi bi-inbox"></i><p>No dealer activity</p></div></td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr class="table-light"><td colspan="5" class="fw-bold">Total</td><td class="text-end fw-bold"><?= fmt_money(array_sum(array_column($dealers, 'total_value'))) ?></td><td class="text-end fw-bold"><?= fmt_money($totalCommission) ?></td><td class="text-end fw-bold text-success"><?= fmt_money($totalPaid) ?></td><td class="text-end fw-bold text-danger"><?= fmt_money($totalCommission - $totalPaid) ?></td></tr></tfoot>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

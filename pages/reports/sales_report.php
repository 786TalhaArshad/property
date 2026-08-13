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
foreach ($bookings as $b) {
    $totalPrice += (float)$b['total_price'];
    $totalDiscount += (float)$b['discount'];
    $totalToken += (float)$b['token_amount'];
    $totalReceived += (float)$b['received'];
}
$agreements = db_all("SELECT COUNT(*) AS cnt, SUM(b.total_price) AS tot FROM sale_agreements sa JOIN bookings b ON b.id = sa.booking_id WHERE sa.agreement_date BETWEEN ? AND ?", [$from, $to]);
$projects = db_all("SELECT id, name FROM projects ORDER BY name");
include '../../includes/header.php';
?>

<form method="get" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-3"><input type="date" name="from" class="form-control" value="<?= e($from) ?>"></div>
            <div class="col-md-3"><input type="date" name="to" class="form-control" value="<?= e($to) ?>"></div>
            <div class="col-md-3">
                <select name="project_id" class="form-select">
                    <option value="">All Projects</option>
                    <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-journal-check"></i></div><div><div class="stat-label">BOOKINGS</div><div class="stat-value"><?= count($bookings) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">BOOKED VALUE</div><div class="stat-value"><?= fmt_money($totalPrice) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-wallet2"></i></div><div><div class="stat-label">COLLECTED</div><div class="stat-value"><?= fmt_money($totalReceived) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-file-earmark-check"></i></div><div><div class="stat-label">AGREEMENTS</div><div class="stat-value"><?= (int)$agreements[0]['cnt'] ?></div></div></div></div></div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-bar-chart me-2"></i>Bookings: <?= fmt_date($from) ?> to <?= fmt_date($to) ?></span>
        <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
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

<?php include '../../includes/footer.php'; ?>

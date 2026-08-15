<?php
require_once '../includes/auth.php';
require_login();
require_permission('sales.view');
$title = 'Receipts';
$active = 'receipts';

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$project_id = (int)($_GET['project_id'] ?? active_project_id());
$params = [];
$where = '';
if ($from) {
    $where .= " AND r.receipt_date >= ?";
    $params[] = $from;
}
if ($to) {
    $where .= " AND r.receipt_date <= ?";
    $params[] = $to;
}
if ($project_id > 0) {
    $where .= " AND r.project_id = ?";
    $params[] = $project_id;
}

$records = db_all("SELECT r.*, p.name AS project_name, c.full_name AS customer_name, b.booking_no, pr.property_no, pm.name AS method_name, bk.name AS bank_name, u.full_name AS receiver
                   FROM receipts r
                   JOIN customers c ON c.id = r.customer_id
                   LEFT JOIN bookings b ON b.id = r.booking_id
                   LEFT JOIN properties pr ON pr.id = b.property_id
                   LEFT JOIN projects p ON p.id = r.project_id
                   LEFT JOIN payment_methods pm ON pm.id = r.payment_method_id
                   LEFT JOIN banks bk ON bk.id = r.bank_id
                   LEFT JOIN users u ON u.id = r.received_by
                   WHERE 1=1$where
                   ORDER BY r.receipt_date DESC, r.id DESC", $params);
$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
include '../includes/header.php';
?>

<form method="get" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="project_id" class="form-select form-select-sm">
                    <option value="">All Projects</option>
                    <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>"></div>
            <div class="col-md-2"><input type="date" name="to" class="form-control form-control-sm" value="<?= e($to) ?>"></div>
            <div class="col-md-2"><button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i>Filter</button></div>
            <div class="col-md-3 text-end small text-muted">Receipts: <strong><?= count($records) ?></strong> &bull; Total: <strong><?= fmt_money(array_sum(array_map(function ($r) { return (float)$r['amount']; }, $records))) ?></strong></div>
        </div>
    </div>
</form>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search receipts...">
    </div>
    <span class="ms-auto small text-muted"><i class="bi bi-info-circle me-1"></i>Read-only register - receipts booking/installments se banti hain</span>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Receipt</th><th>Date</th><th>Project</th><th>Customer</th><th>Booking</th><th>Property</th><th>Amount</th><th>Method</th><th class="text-end"></th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-medium"><?= e($r['receipt_no']) ?></td>
                        <td><?= fmt_date($r['receipt_date']) ?></td>
                        <td class="small"><?= $r['project_name'] ? '<span class="badge bg-light text-dark border">' . e($r['project_name']) . '</span>' : '<span class="text-muted">General</span>' ?></td>
                        <td><?= e($r['customer_name']) ?></td>
                        <td><?= $r['booking_id'] ? '<a href="booking_view.php?id=' . $r['booking_id'] . '">' . e($r['booking_no']) . '</a>' : '-' ?></td>
                        <td><?= e($r['property_no'] ?? '-') ?></td>
                        <td><?= fmt_money($r['amount']) ?></td>
                        <td class="small"><?= e($r['method_name'] ?? '-') ?><?= $r['reference'] ? ' / ' . e($r['reference']) : '' ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="receipt_print.php?id=<?= $r['id'] ?>" target="_blank"><i class="bi bi-printer"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$records): ?>
                    <tr><td colspan="10"><div class="empty-state"><i class="bi bi-cash-coin"></i><p>No receipts found</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

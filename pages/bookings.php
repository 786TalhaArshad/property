<?php
require_once '../includes/auth.php';
require_login();
require_permission('sales.view');
$title = 'Bookings';
$active = 'bookings';
$canEdit = has_permission('sales.manage');

$status = $_GET['status'] ?? '';
$records = db_all("SELECT b.*, c.full_name AS customer_name, p.property_no, d.full_name AS dealer_name,
                   (SELECT COALESCE(SUM(amount),0) FROM receipts r WHERE r.booking_id = b.id) AS total_paid
                   FROM bookings b
                   JOIN customers c ON c.id = b.customer_id
                   JOIN properties p ON p.id = b.property_id
                   LEFT JOIN dealers d ON d.id = b.dealer_id
                   WHERE (? = '' OR b.status = ?)
                   ORDER BY b.booking_date DESC", [$status, $status]);
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search bookings...">
    </div>
    <select class="form-select form-select-sm" style="max-width:160px" onchange="location.href='?status='+this.value">
        <option value="">All Status</option>
        <?php foreach (['booking', 'active', 'completed', 'cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($canEdit): ?>
    <a class="btn btn-primary ms-auto" href="booking_form.php"><i class="bi bi-plus-lg me-1"></i>New Booking</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Booking</th><th>Customer</th><th>Property</th><th>Date</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <?php $paid = (float)$r['total_paid']; $total = (float)$r['total_price']; ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-medium"><?= e($r['booking_no']) ?><?= $r['dealer_name'] ? '<div class="small text-muted">Dealer: ' . e($r['dealer_name']) . '</div>' : '' ?></td>
                        <td><?= e($r['customer_name']) ?></td>
                        <td><?= e($r['property_no']) ?></td>
                        <td><?= fmt_date($r['booking_date']) ?></td>
                        <td><?= fmt_money($total) ?></td>
                        <td><?= fmt_money($paid) ?></td>
                        <td><span class="fw-medium <?= ($total - $paid) > 0 ? 'text-danger' : 'text-success' ?>"><?= fmt_money($total - $paid) ?></span></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-info" href="booking_view.php?id=<?= $r['id'] ?>"><i class="bi bi-eye"></i></a>
                            <?php if ($canEdit && $r['status'] === 'booking'): ?>
                            <a class="btn btn-sm btn-outline-primary" href="booking_form.php?id=<?= $r['id'] ?>"><i class="bi bi-pencil"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$records): ?>
                    <tr><td colspan="10"><div class="empty-state"><i class="bi bi-journal-check"></i><p>No bookings yet</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

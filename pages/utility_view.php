<?php
require_once '../includes/auth.php';
require_login();
require_permission('utilities.view');
$title = 'Utility Details';
$active = 'utilities';
$canEdit = has_permission('utilities.manage');

$id = (int)($_GET['id'] ?? 0);
$utility = db_get("SELECT u.*, p.property_no, t.full_name AS tenant_name
                   FROM utilities u
                   JOIN properties p ON p.id = u.property_id
                   LEFT JOIN tenants t ON t.id = u.tenant_id
                   WHERE u.id = ?", [$id]);
if (!$utility) {
    flash('danger', 'Utility not found.');
    redirect('utilities.php');
}

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'reading_add') {
        $reading_date = $_POST['reading_date'] ?? date('Y-m-d');
        $prev = (float)($_POST['previous_reading'] ?? 0);
        $curr = (float)($_POST['current_reading'] ?? 0);
        $units = max(0, $curr - $prev);
        $rate = (float)($_POST['rate'] ?? $utility['rate']);
        $amount = $units * $rate;
        db_exec("INSERT INTO meter_readings (utility_id, reading_date, previous_reading, current_reading, units, rate, amount, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$id, $reading_date, $prev, $curr, $units, $rate, $amount]);
        db_exec("UPDATE utilities SET rate = ?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$rate, $id]);
        flash('success', 'Meter reading saved. Amount: ' . fmt_money($amount));
    } elseif ($action === 'reading_delete') {
        db_exec("DELETE FROM meter_readings WHERE id = ? AND utility_id = ?", [(int)($_POST['id'] ?? 0), $id]);
        flash('success', 'Reading deleted.');
    } elseif ($action === 'bill_add') {
        $billing_month = trim($_POST['billing_month'] ?? '');
        $bill_date = $_POST['bill_date'] ?? date('Y-m-d');
        $due_date = $_POST['due_date'] ?? date('Y-m-d');
        $amount = (float)($_POST['amount'] ?? 0);
        $penalty = (float)($_POST['penalty'] ?? 0);
        if ($billing_month === '' || $amount <= 0) {
            flash('danger', 'Billing month and amount are required.');
        } else {
            db_exec("INSERT INTO utility_bills (utility_id, property_id, tenant_id, billing_month, bill_date, due_date, amount, penalty, paid_amount, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,0,'pending',CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$id, $utility['property_id'], $utility['tenant_id'], $billing_month, $bill_date, $due_date, $amount, $penalty]);
            flash('success', 'Utility bill added.');
        }
    } elseif ($action === 'bill_pay') {
        $bid = (int)($_POST['id'] ?? 0);
        $bill = db_get("SELECT * FROM utility_bills WHERE id = ? AND utility_id = ?", [$bid, $id]);
        if ($bill) {
            db_exec("UPDATE utility_bills SET paid_amount = amount + penalty, status = 'paid', paid_date = CURDATE(), updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$bid]);
            flash('success', 'Bill marked as paid.');
        }
    } elseif ($action === 'bill_delete') {
        db_exec("DELETE FROM utility_bills WHERE id = ? AND utility_id = ?", [(int)($_POST['id'] ?? 0), $id]);
        flash('success', 'Bill deleted.');
    }
    redirect('utility_view.php?id=' . $id);
}

$readings = db_all("SELECT * FROM meter_readings WHERE utility_id = ? ORDER BY reading_date DESC", [$id]);
$bills = db_all("SELECT * FROM utility_bills WHERE utility_id = ? ORDER BY bill_date DESC", [$id]);
$last = $readings ? $readings[0] : null;
include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="utilities.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= ucfirst(e($utility['utility_type'])) ?> - <?= e($utility['property_no']) ?></h5>
    <?= $utility['status'] === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-pencil-square"></i></div><div><div class="stat-label">READINGS</div><div class="stat-value"><?= count($readings) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-receipt"></i></div><div><div class="stat-label">BILLS</div><div class="stat-value"><?= count($bills) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">BILLED</div><div class="stat-value"><?= fmt_money(array_sum(array_map(function ($b) { return (float)$b['amount'] + (float)$b['penalty']; }, $bills))) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-label">UNPAID</div><div class="stat-value"><?= fmt_money(array_sum(array_map(function ($b) { return (float)$b['amount'] + (float)$b['penalty'] - (float)$b['paid_amount']; }, array_filter($bills, function ($b) { return $b['status'] !== 'paid'; })))) ?></div></div></div></div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-2"><div class="text-muted small">Meter No</div><div class="fw-medium"><?= e($utility['meter_no'] ?? '-') ?></div></div>
            <div class="col-md-2"><div class="text-muted small">Connection No</div><div class="fw-medium"><?= e($utility['connection_no'] ?? '-') ?></div></div>
            <div class="col-md-2"><div class="text-muted small">Consumer No</div><div class="fw-medium"><?= e($utility['consumer_no'] ?? '-') ?></div></div>
            <div class="col-md-2"><div class="text-muted small">Rate</div><div class="fw-medium"><?= fmt_money($utility['rate']) ?></div></div>
            <div class="col-md-2"><div class="text-muted small">Tenant</div><div class="fw-medium"><?= e($utility['tenant_name'] ?? '-') ?></div></div>
            <div class="col-md-2"><div class="text-muted small">Last Reading</div><div class="fw-medium"><?= $last ? fmt_num($last['current_reading']) : '-' ?></div></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-pencil-square me-2"></i>Meter Readings</span>
                <?php if ($canEdit): ?><button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#readingModal"><i class="bi bi-plus-lg"></i></button><?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Previous</th><th>Current</th><th>Units</th><th>Rate</th><th>Amount</th><?php if ($canEdit): ?><th></th><?php endif; ?></tr></thead>
                        <tbody>
                        <?php foreach ($readings as $rd): ?>
                            <tr>
                                <td><?= fmt_date($rd['reading_date']) ?></td>
                                <td><?= fmt_num($rd['previous_reading']) ?></td>
                                <td><?= fmt_num($rd['current_reading']) ?></td>
                                <td><?= fmt_num($rd['units']) ?></td>
                                <td><?= fmt_money($rd['rate']) ?></td>
                                <td><?= fmt_money($rd['amount']) ?></td>
                                <?php if ($canEdit): ?>
                                <td class="text-end">
                                    <form method="post" class="d-inline" data-confirm="Delete this reading?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="reading_delete">
                                        <input type="hidden" name="id" value="<?= $rd['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$readings): ?><tr><td colspan="7" class="text-center text-muted py-4">No readings yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-receipt me-2"></i>Bills</span>
                <?php if ($canEdit): ?><button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#billModal"><i class="bi bi-plus-lg"></i></button><?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Month</th><th>Bill Date</th><th>Due</th><th>Amount</th><th>Penalty</th><th>Status</th><?php if ($canEdit): ?><th class="text-end">Action</th><?php endif; ?></tr></thead>
                        <tbody>
                        <?php foreach ($bills as $bl): ?>
                            <tr>
                                <td><?= e($bl['billing_month']) ?></td>
                                <td><?= fmt_date($bl['bill_date']) ?></td>
                                <td><?= fmt_date($bl['due_date']) ?></td>
                                <td><?= fmt_money($bl['amount']) ?></td>
                                <td><?= fmt_money($bl['penalty']) ?></td>
                                <td><?= status_badge($bl['status']) ?></td>
                                <?php if ($canEdit): ?>
                                <td class="text-end">
                                    <?php if ($bl['status'] !== 'paid'): ?>
                                    <form method="post" class="d-inline" data-confirm="Mark this bill as paid?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="bill_pay">
                                        <input type="hidden" name="id" value="<?= $bl['id'] ?>">
                                        <button class="btn btn-sm btn-outline-success" title="Mark Paid"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="post" class="d-inline" data-confirm="Delete this bill?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="bill_delete">
                                        <input type="hidden" name="id" value="<?= $bl['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$bills): ?><tr><td colspan="7" class="text-center text-muted py-4">No bills yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="readingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reading_add">
                <div class="modal-header"><h5 class="modal-title">Add Meter Reading</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Reading Date</label>
                            <input type="date" name="reading_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Previous Reading</label>
                            <input type="number" step="0.01" name="previous_reading" class="form-control" value="<?= $last ? $last['current_reading'] : 0 ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Current Reading</label>
                            <input type="number" step="0.01" name="current_reading" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rate</label>
                            <input type="number" step="0.01" name="rate" class="form-control" value="<?= $utility['rate'] ?>">
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0 py-2 small"><i class="bi bi-info-circle me-1"></i>Units and amount are calculated automatically.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="billModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="bill_add">
                <div class="modal-header"><h5 class="modal-title">Add Utility Bill</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Billing Month</label>
                            <input type="month" name="billing_month" class="form-control" value="<?= date('Y-m') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bill Date</label>
                            <input type="date" name="bill_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+10 days')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required data-mask-money>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Penalty</label>
                            <input type="number" step="0.01" name="penalty" class="form-control" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

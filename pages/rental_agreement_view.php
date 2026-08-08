<?php
require_once '../includes/auth.php';
require_login();
require_permission('rentals.view');
$title = 'Rental Agreement Details';
$active = 'rental_agreements';
$canEdit = has_permission('rentals.manage');

$id = (int)($_GET['id'] ?? 0);
$agreement = db_get("SELECT ra.*, p.property_no, p.plot_no AS property_address, t.full_name AS tenant_name, t.cnic AS tenant_cnic, o.full_name AS owner_name, d.full_name AS dealer_name
                     FROM rental_agreements ra
                     JOIN properties p ON p.id = ra.property_id
                     JOIN tenants t ON t.id = ra.tenant_id
                     LEFT JOIN owners o ON o.id = ra.owner_id
                     LEFT JOIN dealers d ON d.id = ra.dealer_id
                     WHERE ra.id = ?", [$id]);
if (!$agreement) {
    flash('danger', 'Rental agreement not found.');
    redirect('rental_agreements.php');
}

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'collect') {
        $schedule_id = (int)($_POST['schedule_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $collection_date = $_POST['collection_date'] ?? date('Y-m-d');
        $payment_method_id = (int)($_POST['payment_method_id'] ?? 0) ?: null;
        $bank_id = (int)($_POST['bank_id'] ?? 0) ?: null;
        $reference = trim($_POST['reference'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        $sch = db_get("SELECT * FROM rent_schedule WHERE id = ? AND agreement_id = ?", [$schedule_id, $id]);
        if (!$sch) {
            flash('danger', 'Schedule entry not found.');
        } elseif ($amount <= 0) {
            flash('danger', 'Enter a valid amount.');
        } else {
            db_exec("INSERT INTO rent_collections (schedule_id, agreement_id, collection_date, amount, payment_method_id, bank_id, reference, remarks, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                [$schedule_id, $id, $collection_date, $amount, $payment_method_id, $bank_id, $reference, $remarks]);
            $newPaid = (float)$sch['paid_amount'] + $amount;
            $newStatus = $newPaid >= ((float)$sch['rent_amount'] + (float)$sch['late_charges']) ? 'paid' : 'partial';
            db_exec("UPDATE rent_schedule SET paid_amount = ?, status = ?, paid_date = ?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$newPaid, $newStatus, $collection_date, $schedule_id]);
            $bal = db_get("SELECT COALESCE(MAX(balance),0) b FROM tenant_ledger WHERE tenant_id = ?", [$agreement['tenant_id']])['b'];
            db_exec("INSERT INTO tenant_ledger (tenant_id, entry_date, description, debit, credit, balance, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,0,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$agreement['tenant_id'], $collection_date, 'Rent ' . $agreement['agreement_no'] . ' ' . $sch['period'], $amount, $bal + $amount]);
            flash('success', 'Rent collected successfully.');
        }
    } elseif ($action === 'terminate') {
        db_exec("UPDATE rental_agreements SET status = 'terminated', updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$id]);
        db_exec("UPDATE properties SET status = 'vacant', updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$agreement['property_id']]);
        flash('success', 'Agreement terminated and property marked vacant.');
    }
    redirect('rental_agreement_view.php?id=' . $id);
}

$schedule = db_all("SELECT * FROM rent_schedule WHERE agreement_id = ? ORDER BY due_date", [$id]);
$collections = db_all("SELECT rc.*, rs.period, pm.name AS method_name, bk.name AS bank_name FROM rent_collections rc JOIN rent_schedule rs ON rs.id = rc.schedule_id LEFT JOIN payment_methods pm ON pm.id = rc.payment_method_id LEFT JOIN banks bk ON bk.id = rc.bank_id WHERE rc.agreement_id = ? ORDER BY rc.collection_date DESC", [$id]);
$settlements = db_all("SELECT * FROM owner_settlements WHERE agreement_id = ? ORDER BY settlement_date DESC", [$id]);
$paymentMethods = db_all("SELECT * FROM payment_methods ORDER BY name");
$banks = db_all("SELECT * FROM banks ORDER BY name");

$totalDue = 0.0; $totalPaid = 0.0; $totalLate = 0.0;
foreach ($schedule as $s) {
    $totalDue += (float)$s['rent_amount'] + (float)$s['late_charges'];
    $totalLate += (float)$s['late_charges'];
    $totalPaid += (float)$s['paid_amount'];
}
include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="rental_agreements.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= e($agreement['agreement_no']) ?></h5>
    <?= status_badge($agreement['status']) ?>
    <?php if ($canEdit && in_array($agreement['status'], ['active', 'renewed'])): ?>
    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#terminateModal"><i class="bi bi-x-circle me-1"></i>Terminate</button>
    <?php endif; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">TOTAL RENT</div><div class="stat-value"><?= fmt_money($totalDue) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-coin"></i></div><div><div class="stat-label">COLLECTED</div><div class="stat-value"><?= fmt_money($totalPaid) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card <?= ($totalDue - $totalPaid) > 0 ? 'bg-grad-orange' : 'bg-grad-cyan' ?>"><div class="stat-body"><div class="stat-icon"><i class="bi bi-wallet2"></i></div><div><div class="stat-label">BALANCE</div><div class="stat-value"><?= fmt_money($totalDue - $totalPaid) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-calendar-month"></i></div><div><div class="stat-label">MONTHS</div><div class="stat-value"><?= count($schedule) ?></div></div></div></div></div>
</div>

<ul class="nav nav-pills mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#rOverview">Overview</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rSchedule">Schedule (<?= count($schedule) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rCollections">Collections (<?= count($collections) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#rSettlements">Owner Settlements</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="rOverview">
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3"><div class="text-muted small">Property</div><div class="fw-medium"><a href="property_view.php?id=<?= $agreement['property_id'] ?>"><?= e($agreement['property_no']) ?></a></div></div>
                    <div class="col-md-3"><div class="text-muted small">Property Address</div><div class="fw-medium"><?= e($agreement['property_address'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Tenant</div><div class="fw-medium"><a href="tenant_view.php?id=<?= $agreement['tenant_id'] ?>"><?= e($agreement['tenant_name']) ?></a></div></div>
                    <div class="col-md-3"><div class="text-muted small">Tenant CNIC</div><div class="fw-medium"><?= e($agreement['tenant_cnic'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Owner</div><div class="fw-medium"><?= e($agreement['owner_name'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Dealer</div><div class="fw-medium"><?= e($agreement['dealer_name'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Period</div><div class="fw-medium"><?= fmt_date($agreement['start_date']) ?> - <?= fmt_date($agreement['end_date']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Notice Period</div><div class="fw-medium"><?= (int)$agreement['notice_period_days'] ?> days</div></div>
                    <div class="col-md-3"><div class="text-muted small">Monthly Rent</div><div class="fw-medium"><?= fmt_money($agreement['monthly_rent']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Security Deposit</div><div class="fw-medium"><?= fmt_money($agreement['security_deposit']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Advance Rent</div><div class="fw-medium"><?= fmt_money($agreement['advance_rent']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Increase / Year</div><div class="fw-medium"><?= fmt_num($agreement['rent_increase_percent']) ?>%</div></div>
                    <div class="col-md-3"><div class="text-muted small">Parking Charges</div><div class="fw-medium"><?= fmt_money($agreement['parking_charges']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Maintenance Charges</div><div class="fw-medium"><?= fmt_money($agreement['maintenance_charges']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Utilities Included</div><div class="fw-medium"><?= $agreement['utility_included'] ? 'Yes' : 'No' ?></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="rSchedule">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Period</th><th>Due Date</th><th>Rent</th><th>Late Charges</th><th>Paid</th><th>Balance</th><th>Status</th><?php if ($canEdit): ?><th class="text-end">Action</th><?php endif; ?></tr></thead>
                        <tbody>
                        <?php foreach ($schedule as $s): ?>
                            <tr>
                                <td><?= e($s['period']) ?></td>
                                <td><?= fmt_date($s['due_date']) ?></td>
                                <td><?= fmt_money($s['rent_amount']) ?></td>
                                <td><?= fmt_money($s['late_charges']) ?></td>
                                <td><?= fmt_money($s['paid_amount']) ?></td>
                                <td><span class="<?= ((float)$s['rent_amount'] + (float)$s['late_charges'] - (float)$s['paid_amount']) > 0 ? 'text-danger fw-medium' : 'text-success' ?>"><?= fmt_money((float)$s['rent_amount'] + (float)$s['late_charges'] - (float)$s['paid_amount']) ?></span></td>
                                <td><?= status_badge($s['status']) ?></td>
                                <?php if ($canEdit): ?>
                                <td class="text-end">
                                    <?php if (in_array($s['status'], ['pending', 'partial'])): ?>
                                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#collectModal" data-schedule="<?= $s['id'] ?>" data-due="<?= (float)$s['rent_amount'] + (float)$s['late_charges'] - (float)$s['paid_amount'] ?>"><i class="bi bi-cash-coin me-1"></i>Collect</button>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$schedule): ?><tr><td colspan="8" class="text-center text-muted py-4">No schedule</td></tr><?php endif; ?>
                        </tbody>
                        <tfoot>
                        <tr class="table-light">
                            <td colspan="3" class="text-end fw-bold">Total</td>
                            <td class="fw-bold"><?= fmt_money($totalLate) ?></td>
                            <td class="fw-bold"><?= fmt_money($totalPaid) ?></td>
                            <td class="fw-bold"><?= fmt_money($totalDue - $totalPaid) ?></td>
                            <td colspan="2"></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="rCollections">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Period</th><th>Amount</th><th>Method</th><th>Bank</th><th>Reference</th></tr></thead>
                        <tbody>
                        <?php foreach ($collections as $c): ?>
                            <tr>
                                <td><?= fmt_date($c['collection_date']) ?></td>
                                <td><?= e($c['period']) ?></td>
                                <td><?= fmt_money($c['amount']) ?></td>
                                <td><?= e($c['method_name'] ?? '-') ?></td>
                                <td><?= e($c['bank_name'] ?? '-') ?></td>
                                <td class="small"><?= e($c['reference'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$collections): ?><tr><td colspan="6" class="text-center text-muted py-4">No collections yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="rSettlements">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Rent Income</th><th>Deductions</th><th>Amount</th><th>Status</th><th>Remarks</th></tr></thead>
                        <tbody>
                        <?php foreach ($settlements as $s): ?>
                            <tr>
                                <td><?= fmt_date($s['settlement_date']) ?></td>
                                <td><?= fmt_money($s['rent_income']) ?></td>
                                <td><?= fmt_money($s['deductions']) ?></td>
                                <td class="fw-medium"><?= fmt_money($s['settlement_amount']) ?></td>
                                <td><?= status_badge($s['status']) ?></td>
                                <td class="small"><?= e($s['remarks'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$settlements): ?><tr><td colspan="6" class="text-center text-muted py-4">No settlements</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="collectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="collect">
                <input type="hidden" name="schedule_id" id="colSchedule">
                <div class="modal-header"><h5 class="modal-title">Collect Rent</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="small text-muted mb-3"><?= e($agreement['agreement_no']) ?> - <?= e($agreement['tenant_name']) ?></p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input type="date" name="collection_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" name="amount" id="colAmount" class="form-control" required data-mask-money>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Method</label>
                            <select name="payment_method_id" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($paymentMethods as $pm): ?><option value="<?= $pm['id'] ?>"><?= e($pm['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank</label>
                            <select name="bank_id" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($banks as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference</label>
                            <input type="text" name="reference" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Remarks</label>
                            <input type="text" name="remarks" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Collect</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="terminateModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="terminate">
                <div class="modal-header"><h5 class="modal-title">Terminate Agreement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">Terminate this agreement? The property will be marked as vacant.</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">No</button>
                    <button class="btn btn-danger">Yes, Terminate</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($canEdit): ?>
<script>
(function () {
    var modal = document.getElementById('collectModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            document.getElementById('colSchedule').value = btn.dataset.schedule;
            document.getElementById('colAmount').value = btn.dataset.due;
        });
    }
})();
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

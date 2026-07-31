<?php
require_once '../includes/auth.php';
require_login();
require_permission('rentals.view');
$title = 'Owner Settlements';
$active = 'owner_settlements';
$canEdit = has_permission('rentals.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $owner_id = (int)($_POST['owner_id'] ?? 0);
        $agreement_id = (int)($_POST['agreement_id'] ?? 0) ?: null;
        $settlement_date = $_POST['settlement_date'] ?? date('Y-m-d');
        $rent_income = (float)($_POST['rent_income'] ?? 0);
        $deductions = (float)($_POST['deductions'] ?? 0);
        $settlement_amount = $rent_income - $deductions;
        $status = $_POST['status'] ?? 'pending';
        $payment_method_id = (int)($_POST['payment_method_id'] ?? 0) ?: null;
        $bank_id = (int)($_POST['bank_id'] ?? 0) ?: null;
        $remarks = trim($_POST['remarks'] ?? '');

        if ($owner_id <= 0 || $settlement_amount < 0) {
            flash('danger', 'Select an owner and ensure the settlement amount is valid.');
        } elseif ($id > 0) {
            db_exec("UPDATE owner_settlements SET owner_id=?, agreement_id=?, settlement_date=?, rent_income=?, deductions=?, settlement_amount=?, status=?, payment_method_id=?, bank_id=?, remarks=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$owner_id, $agreement_id, $settlement_date, $rent_income, $deductions, $settlement_amount, $status, $payment_method_id, $bank_id, $remarks, $id]);
            flash('success', 'Settlement updated successfully.');
        } else {
            db_exec("INSERT INTO owner_settlements (owner_id, agreement_id, settlement_date, rent_income, deductions, settlement_amount, status, payment_method_id, bank_id, remarks, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$owner_id, $agreement_id, $settlement_date, $rent_income, $deductions, $settlement_amount, $status, $payment_method_id, $bank_id, $remarks]);
            if ($status === 'paid') {
                $bal = db_get("SELECT COALESCE(MAX(balance),0) b FROM owner_ledger WHERE owner_id = ?", [$owner_id])['b'];
                db_exec("INSERT INTO owner_ledger (owner_id, entry_date, description, debit, credit, balance, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,0,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$owner_id, $settlement_date, 'Owner settlement', $settlement_amount, $bal + $settlement_amount]);
            }
            flash('success', 'Settlement recorded successfully.');
        }
    } elseif ($action === 'mark_paid') {
        $sid = (int)($_POST['id'] ?? 0);
        $s = db_get("SELECT * FROM owner_settlements WHERE id = ?", [$sid]);
        if ($s) {
            db_exec("UPDATE owner_settlements SET status = 'paid', updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$sid]);
            $bal = db_get("SELECT COALESCE(MAX(balance),0) b FROM owner_ledger WHERE owner_id = ?", [$s['owner_id']])['b'];
            db_exec("INSERT INTO owner_ledger (owner_id, entry_date, description, debit, credit, balance, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,0,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$s['owner_id'], date('Y-m-d'), 'Owner settlement', $s['settlement_amount'], $bal + $s['settlement_amount']]);
            flash('success', 'Settlement marked as paid.');
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM owner_settlements WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Settlement deleted successfully.');
    }
    redirect('owner_settlements.php');
}

$records = db_all("SELECT os.*, o.full_name AS owner_name, ra.agreement_no, pm.name AS method_name, bk.name AS bank_name
                   FROM owner_settlements os
                   JOIN owners o ON o.id = os.owner_id
                   LEFT JOIN rental_agreements ra ON ra.id = os.agreement_id
                   LEFT JOIN payment_methods pm ON pm.id = os.payment_method_id
                   LEFT JOIN banks bk ON bk.id = os.bank_id
                   ORDER BY os.settlement_date DESC");
$owners = db_all("SELECT o.*,
                  (SELECT COALESCE(SUM(rc.amount),0) FROM rent_collections rc JOIN rental_agreements ra ON ra.id = rc.agreement_id WHERE ra.owner_id = o.id) AS rent_total,
                  (SELECT COALESCE(SUM(os.settlement_amount),0) FROM owner_settlements os WHERE os.owner_id = o.id) AS settled_total
                  FROM owners o WHERE o.status = 1 ORDER BY o.full_name");
$agreements = db_all("SELECT ra.id, ra.agreement_no, p.property_no, t.full_name AS tenant_name FROM rental_agreements ra JOIN properties p ON p.id = ra.property_id JOIN tenants t ON t.id = ra.tenant_id WHERE ra.status IN ('active','renewed') ORDER BY ra.agreement_no DESC");
$paymentMethods = db_all("SELECT * FROM payment_methods ORDER BY name");
$banks = db_all("SELECT * FROM banks ORDER BY name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search settlements...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="New Settlement"><i class="bi bi-plus-lg me-1"></i>New Settlement</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Date</th><th>Owner</th><th>Agreement</th><th>Rent Income</th><th>Deductions</th><th>Amount</th><th>Status</th><th>Method</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= fmt_date($r['settlement_date']) ?></td>
                        <td><a href="owner_view.php?id=<?= $r['owner_id'] ?>"><?= e($r['owner_name']) ?></a></td>
                        <td><?= $r['agreement_id'] ? '<a href="rental_agreement_view.php?id=' . $r['agreement_id'] . '">' . e($r['agreement_no']) . '</a>' : '-' ?></td>
                        <td><?= fmt_money($r['rent_income']) ?></td>
                        <td><?= fmt_money($r['deductions']) ?></td>
                        <td class="fw-medium"><?= fmt_money($r['settlement_amount']) ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td class="small"><?= e($r['method_name'] ?? '-') ?></td>
                        <td class="text-end">
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'owner_id' => $r['owner_id'], 'agreement_id' => $r['agreement_id'], 'settlement_date' => $r['settlement_date'], 'rent_income' => $r['rent_income'], 'deductions' => $r['deductions'], 'settlement_amount' => $r['settlement_amount'], 'status' => $r['status'], 'payment_method_id' => $r['payment_method_id'], 'bank_id' => $r['bank_id'], 'remarks' => $r['remarks']])) ?>'><i class="bi bi-pencil"></i></button>
                            <?php if ($r['status'] === 'pending'): ?>
                            <form method="post" class="d-inline" data-confirm="Mark this settlement as paid?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="mark_paid">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="btn btn-sm btn-outline-success" title="Mark Paid"><i class="bi bi-check-lg"></i></button>
                            </form>
                            <?php endif; ?>
                            <form method="post" class="d-inline" data-confirm="Delete this settlement?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$records): ?>
                    <tr><td colspan="10"><div class="empty-state"><i class="bi bi-wallet2"></i><p>No settlements yet</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="recordModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header"><h5 class="modal-title">New Settlement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Owner</label>
                            <select name="owner_id" id="selOwner" class="form-select" required>
                                <option value="">Select Owner</option>
                                <?php foreach ($owners as $o): ?>
                                    <option value="<?= $o['id'] ?>" data-unsettled="<?= (float)$o['rent_total'] - (float)$o['settled_total'] ?>"><?= e($o['full_name']) ?> (Unsettled: <?= fmt_money((float)$o['rent_total'] - (float)$o['settled_total']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Agreement (optional)</label>
                            <select name="agreement_id" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($agreements as $a): ?><option value="<?= $a['id'] ?>"><?= e($a['agreement_no']) ?> - <?= e($a['property_no']) ?> (<?= e($a['tenant_name']) ?>)</option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date</label>
                            <input type="date" name="settlement_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Rent Income</label>
                            <input type="number" step="0.01" name="rent_income" id="fIncome" class="form-control" required data-mask-money>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Deductions</label>
                            <input type="number" step="0.01" name="deductions" id="fDeductions" class="form-control" data-mask-money value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Settlement Amount</label>
                            <input type="text" id="fAmount" class="form-control" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Method (if paid)</label>
                            <select name="payment_method_id" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($paymentMethods as $pm): ?><option value="<?= $pm['id'] ?>"><?= e($pm['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bank</label>
                            <select name="bank_id" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($banks as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Remarks</label>
                            <input type="text" name="remarks" class="form-control">
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

<?php if ($canEdit): ?>
<script>
(function () {
    var modal = document.getElementById('recordModal');
    var income = document.getElementById('fIncome');
    var deductions = document.getElementById('fDeductions');
    var amount = document.getElementById('fAmount');
    var ownerSel = document.getElementById('selOwner');
    function calc() {
        var v = (parseFloat(income.value) || 0) - (parseFloat(deductions.value) || 0);
        amount.value = parseFloat(v).toFixed(2);
    }
    income.addEventListener('input', calc);
    deductions.addEventListener('input', calc);
    if (modal) {
        modal.addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            modal.querySelector('.modal-title').textContent = btn.dataset.add || 'Edit Settlement';
            modal.querySelector('form').reset();
            document.getElementById('recordId').value = '';
            if (btn.dataset.edit) {
                var d = JSON.parse(btn.dataset.edit);
                document.getElementById('recordId').value = d.id;
                ownerSel.value = d.owner_id;
                modal.querySelector('[name=agreement_id]').value = d.agreement_id || '';
                modal.querySelector('[name=settlement_date]').value = d.settlement_date;
                income.value = d.rent_income;
                deductions.value = d.deductions;
                modal.querySelector('[name=status]').value = d.status;
                modal.querySelector('[name=payment_method_id]').value = d.payment_method_id || '';
                modal.querySelector('[name=bank_id]').value = d.bank_id || '';
                modal.querySelector('[name=remarks]').value = d.remarks || '';
            }
            calc();
        });
    }
})();
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

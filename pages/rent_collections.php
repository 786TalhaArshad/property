<?php
require_once '../includes/auth.php';
require_login();
require_permission('rentals.view');
$title = 'Rent Collections';
$active = 'rent_collections';
$canEdit = has_permission('rentals.manage');

function rent_collection_recalc_schedule($scheduleId) {
    $s = db_get("SELECT * FROM rent_schedule WHERE id = ?", [$scheduleId]);
    if (!$s) return;
    $agg = db_get("SELECT COALESCE(SUM(amount),0) amt, MAX(collection_date) d FROM rent_collections WHERE schedule_id = ?", [$scheduleId]);
    $paid = (float)$agg['amt'];
    $total = (float)$s['rent_amount'] + (float)$s['late_charges'];
    $status = $total > 0 && $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'pending');
    $paidDate = $paid > 0 ? $agg['d'] : null;
    db_exec("UPDATE rent_schedule SET paid_amount=?, status=?, paid_date=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$paid, $status, $paidDate, $scheduleId]);
}

$agreement_id = (int)($_GET['agreement_id'] ?? 0);

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $edit_id = (int)($_POST['edit_id'] ?? 0);
        $old = db_get("SELECT * FROM rent_collections WHERE id = ?", [$edit_id]);
        if (!$old) {
            flash('danger', 'Collection not found.');
        } else {
            $amount = (float)($_POST['amount'] ?? 0);
            $collection_date = $_POST['collection_date'] ?? date('Y-m-d');
            $payment_method_id = (int)($_POST['payment_method_id'] ?? 0) ?: null;
            $bank_id = (int)($_POST['bank_id'] ?? 0) ?: null;
            $reference = trim($_POST['reference'] ?? '');
            $remarks = trim($_POST['remarks'] ?? '');
            if ($amount <= 0) {
                flash('danger', 'Enter a valid amount.');
            } else {
                db_exec("UPDATE rent_collections SET collection_date=?, amount=?, payment_method_id=?, bank_id=?, reference=?, remarks=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?",
                    [$collection_date, $amount, $payment_method_id, $bank_id, $reference, $remarks, $edit_id]);
                rent_collection_recalc_schedule($old['schedule_id']);
                flash('success', 'Rent collection updated.');
            }
        }
    } elseif ($action === 'delete') {
        $cid = (int)($_POST['id'] ?? 0);
        $rc = db_get("SELECT * FROM rent_collections WHERE id = ?", [$cid]);
        if ($rc) {
            db_exec("DELETE FROM rent_collections WHERE id = ?", [$cid]);
            rent_collection_recalc_schedule($rc['schedule_id']);
            flash('success', 'Rent collection deleted.');
        }
    }
    redirect('rent_collections.php' . ($agreement_id ? '?agreement_id=' . $agreement_id : ''));
}

$records = db_all("SELECT rc.*, rs.period, ra.agreement_no, ra.tenant_id, p.property_no, t.full_name AS tenant_name, pm.name AS method_name, bk.name AS bank_name
                   FROM rent_collections rc
                   JOIN rent_schedule rs ON rs.id = rc.schedule_id
                   JOIN rental_agreements ra ON ra.id = rc.agreement_id
                   JOIN properties p ON p.id = ra.property_id
                   JOIN tenants t ON t.id = ra.tenant_id
                   LEFT JOIN payment_methods pm ON pm.id = rc.payment_method_id
                   LEFT JOIN banks bk ON bk.id = rc.bank_id
                   WHERE (? = 0 OR rc.agreement_id = ?)
                   ORDER BY rc.collection_date DESC", [$agreement_id, $agreement_id]);
$paymentMethods = db_all("SELECT * FROM payment_methods ORDER BY name");
$banks = db_all("SELECT * FROM banks ORDER BY name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search collections...">
    </div>
    <?php if ($agreement_id): ?><a class="btn btn-sm btn-light" href="rent_collections.php"><i class="bi bi-x-lg me-1"></i>Clear Filter</a><?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Date</th><th>Agreement</th><th>Property</th><th>Tenant</th><th>Period</th><th>Amount</th><th>Method</th><th>Bank</th><?php if ($canEdit): ?><th class="text-end">Actions</th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= fmt_date($r['collection_date']) ?></td>
                        <td><a href="rental_agreement_view.php?id=<?= $r['agreement_id'] ?>"><?= e($r['agreement_no']) ?></a></td>
                        <td><?= e($r['property_no']) ?></td>
                        <td><a href="tenant_view.php?id=<?= $r['tenant_id'] ?>"><?= e($r['tenant_name']) ?></a></td>
                        <td><?= e($r['period']) ?></td>
                        <td><?= fmt_money($r['amount']) ?></td>
                        <td><?= e($r['method_name'] ?? '-') ?></td>
                        <td><?= e($r['bank_name'] ?? '-') ?></td>
                        <?php if ($canEdit): ?>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'collection_date' => $r['collection_date'], 'period' => $r['period'], 'amount' => $r['amount'], 'payment_method_id' => $r['payment_method_id'], 'bank_id' => $r['bank_id'], 'reference' => $r['reference'], 'remarks' => $r['remarks']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this rent collection? The schedule will be adjusted.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$records): ?>
                    <tr><td colspan="<?= $canEdit ? 10 : 9 ?>"><div class="empty-state"><i class="bi bi-cash"></i><p>No rent collections yet</p></div></td></tr>
                <?php endif; ?>
                </tbody>
                <tfoot>
                <?php if ($records): ?>
                <tr class="table-light">
                    <td colspan="6" class="text-end fw-bold">Total</td>
                    <td class="fw-bold"><?= fmt_money(array_sum(array_map(function ($r) { return (float)$r['amount']; }, $records))) ?></td>
                    <td colspan="<?= $canEdit ? 3 : 2 ?>"></td>
                </tr>
                <?php endif; ?>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="recordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="edit_id" id="editId">
                <div class="modal-header"><h5 class="modal-title">Edit Rent Collection</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="small text-muted mb-3" id="rcvInfo"></p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input type="date" name="collection_date" id="rcvDate" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" name="amount" id="rcvAmount" class="form-control" required data-mask-money>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Method</label>
                            <select name="payment_method_id" id="rcvMethod" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($paymentMethods as $pm): ?><option value="<?= $pm['id'] ?>"><?= e($pm['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bank</label>
                            <select name="bank_id" id="rcvBank" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($banks as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference</label>
                            <input type="text" name="reference" id="rcvRef" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Remarks</label>
                            <input type="text" name="remarks" id="rcvRemarks" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Save Collection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('recordModal');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        var edit = btn.getAttribute('data-edit') ? JSON.parse(btn.getAttribute('data-edit')) : null;
        document.getElementById('editId').value = edit ? edit.id : '';
        document.getElementById('rcvDate').value = edit ? edit.collection_date : '';
        document.getElementById('rcvAmount').value = edit ? edit.amount : '';
        document.getElementById('rcvMethod').value = edit && edit.payment_method_id ? edit.payment_method_id : '';
        document.getElementById('rcvBank').value = edit && edit.bank_id ? edit.bank_id : '';
        document.getElementById('rcvRef').value = edit ? edit.reference : '';
        document.getElementById('rcvRemarks').value = edit ? edit.remarks : '';
        document.getElementById('rcvInfo').textContent = edit ? ('Period: ' + edit.period) : '';
    });
})();
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

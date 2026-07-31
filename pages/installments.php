<?php
require_once '../includes/auth.php';
require_login();
require_permission('sales.view');
$title = 'Installments';
$active = 'installments';
$canEdit = has_permission('sales.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'receive') {
        $installment_id = (int)($_POST['installment_id'] ?? 0);
        $inst = db_get("SELECT * FROM installments WHERE id = ?", [$installment_id]);
        if (!$inst) {
            flash('danger', 'Installment not found.');
            redirect('installments.php');
        }
        $booking = db_get("SELECT * FROM bookings WHERE id = ?", [$inst['booking_id']]);
        if (!$booking) {
            flash('danger', 'Booking not found.');
            redirect('installments.php');
        }
        $receipt_no = next_number('RCT', 'receipts', 'receipt_no');
        $receipt_date = $_POST['receipt_date'] ?? date('Y-m-d');
        $amount = (float)($_POST['amount'] ?? 0);
        $payment_method_id = (int)($_POST['payment_method_id'] ?? 0) ?: null;
        $bank_id = (int)($_POST['bank_id'] ?? 0) ?: null;
        $reference = trim($_POST['reference'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        if ($amount <= 0) {
            flash('danger', 'Enter a valid amount.');
        } else {
            db_exec("INSERT INTO receipts (receipt_no, receipt_date, customer_id, booking_id, installment_id, amount, payment_method_id, bank_id, reference, remarks, received_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                [$receipt_no, $receipt_date, $booking['customer_id'], $booking['id'], $installment_id, $amount, $payment_method_id, $bank_id, $reference, $remarks, $user['id']]);
            $newPaid = (float)$inst['paid_amount'] + $amount;
            $newStatus = $newPaid >= (float)$inst['amount'] ? 'paid' : 'partial';
            db_exec("UPDATE installments SET paid_amount = ?, status = ?, paid_date = ?, received_by = ?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$newPaid, $newStatus, $receipt_date, $user['id'], $installment_id]);
            flash('success', 'Payment received. Receipt No: ' . $receipt_no);
        }
    }
    redirect('installments.php');
}

$status = $_GET['status'] ?? '';
$records = db_all("SELECT i.*, b.booking_no, b.total_price, c.full_name AS customer_name, p.property_no,
                   (i.amount - i.paid_amount) AS balance
                   FROM installments i
                   JOIN bookings b ON b.id = i.booking_id
                   JOIN customers c ON c.id = b.customer_id
                   JOIN properties p ON p.id = b.property_id
                   WHERE (? = '' OR i.status = ?)
                   ORDER BY i.due_date ASC", [$status, $status]);
$paymentMethods = db_all("SELECT * FROM payment_methods ORDER BY name");
$banks = db_all("SELECT * FROM banks ORDER BY name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search installments...">
    </div>
    <select class="form-select form-select-sm" style="max-width:160px" onchange="location.href='?status='+this.value">
        <option value="">All Status</option>
        <?php foreach (['pending', 'partial', 'paid', 'overdue', 'waived'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Booking</th><th>Customer</th><th>Property</th><th>Type</th><th>Due Date</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Status</th><?php if ($canEdit): ?><th class="text-end">Action</th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><a class="fw-medium text-decoration-none" href="booking_view.php?id=<?= $r['booking_id'] ?>"><?= e($r['booking_no']) ?></a></td>
                        <td><?= e($r['customer_name']) ?></td>
                        <td><?= e($r['property_no']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= ucfirst($r['installment_type']) ?></span></td>
                        <td><?= fmt_date($r['due_date']) ?></td>
                        <td><?= fmt_money($r['amount']) ?></td>
                        <td><?= fmt_money($r['paid_amount']) ?></td>
                        <td><span class="<?= (float)$r['balance'] > 0 ? 'text-danger fw-medium' : 'text-success' ?>"><?= fmt_money($r['balance']) ?></span></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <?php if ($canEdit): ?>
                        <td class="text-end">
                            <?php if (in_array($r['status'], ['pending', 'partial'])): ?>
                            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#receiveModal" data-installment="<?= $r['id'] ?>" data-due="<?= (float)$r['balance'] ?>" data-info="<?= h($r['booking_no'] . ' - ' . $r['customer_name']) ?>"><i class="bi bi-cash-coin me-1"></i>Receive</button>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$records): ?>
                    <tr><td colspan="<?= $canEdit ? 12 : 11 ?>"><div class="empty-state"><i class="bi bi-calendar2-check"></i><p>No installments found</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="receiveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="receive">
                <input type="hidden" name="installment_id" id="rcvInstallment">
                <div class="modal-header"><h5 class="modal-title">Receive Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="small text-muted mb-3" id="rcvInfo"></p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input type="date" name="receipt_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" name="amount" id="rcvAmount" class="form-control" required data-mask-money>
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
                    <button class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Receive</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    var modal = document.getElementById('receiveModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            document.getElementById('rcvInstallment').value = btn.dataset.installment;
            document.getElementById('rcvAmount').value = btn.dataset.due;
            document.getElementById('rcvInfo').textContent = btn.dataset.info;
        });
    }
})();
</script>

<?php include '../includes/footer.php'; ?>

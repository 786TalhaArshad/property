<?php
require_once '../includes/auth.php';
require_login();
require_permission('sales.view');
$title = 'Receipts';
$active = 'receipts';
$canEdit = has_permission('sales.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $receipt_no = trim($_POST['receipt_no'] ?? '');
        if ($receipt_no === '') {
            $receipt_no = next_number('RCT', 'receipts', 'receipt_no');
        }
        $receipt_date = $_POST['receipt_date'] ?? date('Y-m-d');
        $customer_id = (int)($_POST['customer_id'] ?? 0);
        $booking_id = (int)($_POST['booking_id'] ?? 0) ?: null;
        $installment_id = (int)($_POST['installment_id'] ?? 0) ?: null;
        $amount = (float)($_POST['amount'] ?? 0);
        $payment_method_id = (int)($_POST['payment_method_id'] ?? 0) ?: null;
        $bank_id = (int)($_POST['bank_id'] ?? 0) ?: null;
        $reference = trim($_POST['reference'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        if ($customer_id <= 0) {
            flash('danger', 'Please select a customer.');
        } elseif ($amount <= 0) {
            flash('danger', 'Enter a valid amount.');
        } else {
            db_exec("INSERT INTO receipts (receipt_no, receipt_date, customer_id, booking_id, installment_id, amount, payment_method_id, bank_id, reference, remarks, received_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                [$receipt_no, $receipt_date, $customer_id, $booking_id, $installment_id, $amount, $payment_method_id, $bank_id, $reference, $remarks, $user['id']]);
            if ($installment_id) {
                $inst = db_get("SELECT * FROM installments WHERE id = ?", [$installment_id]);
                if ($inst) {
                    $newPaid = (float)$inst['paid_amount'] + $amount;
                    $newStatus = $newPaid >= (float)$inst['amount'] ? 'paid' : 'partial';
                    db_exec("UPDATE installments SET paid_amount = ?, status = ?, paid_date = ?, received_by = ?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$newPaid, $newStatus, $receipt_date, $user['id'], $installment_id]);
                }
            }
            flash('success', 'Receipt saved. Receipt No: ' . $receipt_no);
        }
    } elseif ($action === 'delete') {
        $rid = (int)($_POST['id'] ?? 0);
        $rec = db_get("SELECT * FROM receipts WHERE id = ?", [$rid]);
        if ($rec) {
            if ($rec['installment_id']) {
                $inst = db_get("SELECT * FROM installments WHERE id = ?", [$rec['installment_id']]);
                if ($inst) {
                    $newPaid = max(0, (float)$inst['paid_amount'] - (float)$rec['amount']);
                    $newStatus = $newPaid >= (float)$inst['amount'] ? 'paid' : ($newPaid > 0 ? 'partial' : 'pending');
                    db_exec("UPDATE installments SET paid_amount = ?, status = ?, paid_date = NULL, updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$newPaid, $newStatus, $rec['installment_id']]);
                }
            }
            db_exec("DELETE FROM receipts WHERE id = ?", [$rid]);
            flash('success', 'Receipt deleted.');
        }
    }
    redirect('receipts.php');
}

$records = db_all("SELECT r.*, c.full_name AS customer_name, b.booking_no, p.property_no, pm.name AS method_name, bk.name AS bank_name, u.full_name AS receiver
                   FROM receipts r
                   JOIN customers c ON c.id = r.customer_id
                   LEFT JOIN bookings b ON b.id = r.booking_id
                   LEFT JOIN properties p ON p.id = b.property_id
                   LEFT JOIN payment_methods pm ON pm.id = r.payment_method_id
                   LEFT JOIN banks bk ON bk.id = r.bank_id
                   LEFT JOIN users u ON u.id = r.received_by
                   ORDER BY r.receipt_date DESC, r.id DESC");
$customers = db_all("SELECT * FROM customers ORDER BY full_name");
$paymentMethods = db_all("SELECT * FROM payment_methods ORDER BY name");
$banks = db_all("SELECT * FROM banks ORDER BY name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search receipts...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal"><i class="bi bi-plus-lg me-1"></i>New Receipt</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Receipt</th><th>Date</th><th>Customer</th><th>Booking</th><th>Property</th><th>Amount</th><th>Method</th><th>Receiver</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-medium"><?= e($r['receipt_no']) ?></td>
                        <td><?= fmt_date($r['receipt_date']) ?></td>
                        <td><?= e($r['customer_name']) ?></td>
                        <td><?= $r['booking_id'] ? '<a href="booking_view.php?id=' . $r['booking_id'] . '">' . e($r['booking_no']) . '</a>' : '-' ?></td>
                        <td><?= e($r['property_no'] ?? '-') ?></td>
                        <td><?= fmt_money($r['amount']) ?></td>
                        <td class="small"><?= e($r['method_name'] ?? '-') ?><?= $r['reference'] ? ' / ' . e($r['reference']) : '' ?></td>
                        <td class="small"><?= e($r['receiver'] ?? '-') ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="receipt_print.php?id=<?= $r['id'] ?>" target="_blank"><i class="bi bi-printer"></i></a>
                            <?php if ($canEdit): ?>
                            <form method="post" class="d-inline" data-confirm="Delete this receipt? Installment will be adjusted.">
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
                    <tr><td colspan="10"><div class="empty-state"><i class="bi bi-cash-coin"></i><p>No receipts yet</p></div></td></tr>
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
                <div class="modal-header"><h5 class="modal-title">New Receipt</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Receipt No</label>
                            <input type="text" name="receipt_no" class="form-control" placeholder="Auto if blank">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input type="date" name="receipt_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" id="rcvCustomer" class="form-select" required>
                                <option value="">Select Customer</option>
                                <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['full_name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Booking</label>
                            <select name="booking_id" id="rcvBooking" class="form-select">
                                <option value="">None</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Installment (optional)</label>
                            <select name="installment_id" id="rcvInstallment" class="form-select">
                                <option value="">None</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required data-mask-money>
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
                    <button class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Save Receipt</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($canEdit): ?>
<script>
(function () {
    var customer = document.getElementById('rcvCustomer');
    var booking = document.getElementById('rcvBooking');
    var installment = document.getElementById('rcvInstallment');
    if (!customer) return;
    customer.addEventListener('change', function () {
        var id = customer.value;
        booking.innerHTML = '<option value="">Loading...</option>';
        installment.innerHTML = '<option value="">None</option>';
        if (!id) { booking.innerHTML = '<option value="">None</option>'; return; }
        fetch('ajax.php?action=bookings&id=' + id).then(function (r) { return r.json(); }).then(function (rows) {
            booking.innerHTML = '<option value="">None</option>' + rows.map(function (r) { return '<option value="' + r.id + '">' + r.name + '</option>'; }).join('');
        });
    });
    booking.addEventListener('change', function () {
        var id = booking.value;
        installment.innerHTML = '<option value="">None</option>';
        if (!id) return;
        fetch('ajax.php?action=installments&id=' + id).then(function (r) { return r.json(); }).then(function (rows) {
            installment.innerHTML = '<option value="">None</option>' + rows.map(function (r) { return '<option value="' + r.id + '">' + r.name + '</option>'; }).join('');
        });
    });
})();
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

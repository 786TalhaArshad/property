<?php
require_once '../includes/auth.php';
require_login();
require_permission('sales.view');
$title = 'Booking Details';
$active = 'bookings';
$canEdit = has_permission('sales.manage');

$id = (int)($_GET['id'] ?? 0);
$booking = db_get("SELECT b.*, c.full_name AS customer_name, c.customer_no, c.cnic AS customer_cnic, c.phone AS customer_phone, p.property_no, p.plot_no AS property_address, p.size_value, p.size_unit, d.full_name AS dealer_name
                   FROM bookings b
                   JOIN customers c ON c.id = b.customer_id
                   JOIN properties p ON p.id = b.property_id
                   LEFT JOIN dealers d ON d.id = b.dealer_id
                   WHERE b.id = ?", [$id]);
if (!$booking) {
    flash('danger', 'Booking not found.');
    redirect('bookings.php');
}

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'receive') {
        $receipt_no = next_number('RCT', 'receipts', 'receipt_no');
        $receipt_date = $_POST['receipt_date'] ?? date('Y-m-d');
        $installment_id = (int)($_POST['installment_id'] ?? 0) ?: null;
        $amount = (float)($_POST['amount'] ?? 0);
        $payment_method_id = (int)($_POST['payment_method_id'] ?? 0) ?: null;
        $bank_id = (int)($_POST['bank_id'] ?? 0) ?: null;
        $reference = trim($_POST['reference'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        if ($amount <= 0) {
            flash('danger', 'Enter a valid amount.');
        } else {
            db_exec("INSERT INTO receipts (receipt_no, receipt_date, customer_id, booking_id, installment_id, amount, payment_method_id, bank_id, reference, remarks, received_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                [$receipt_no, $receipt_date, $booking['customer_id'], $id, $installment_id, $amount, $payment_method_id, $bank_id, $reference, $remarks, $user['id']]);
            if ($installment_id) {
                $inst = db_get("SELECT * FROM installments WHERE id = ? AND booking_id = ?", [$installment_id, $id]);
                if ($inst) {
                    $newPaid = (float)$inst['paid_amount'] + $amount;
                    $newStatus = $newPaid >= (float)$inst['amount'] ? 'paid' : 'partial';
                    db_exec("UPDATE installments SET paid_amount = ?, status = ?, paid_date = ?, received_by = ?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$newPaid, $newStatus, $receipt_date, $user['id'], $installment_id]);
                }
            }
            flash('success', 'Payment received. Receipt No: ' . $receipt_no);
        }
    }
    redirect('booking_view.php?id=' . $id);
}

$installments = db_all("SELECT * FROM installments WHERE booking_id = ? ORDER BY installment_no", [$id]);
$receipts = db_all("SELECT r.*, pm.name AS method_name, b.name AS bank_name FROM receipts r LEFT JOIN payment_methods pm ON pm.id = r.payment_method_id LEFT JOIN banks b ON b.id = r.bank_id WHERE r.booking_id = ? ORDER BY r.receipt_date DESC", [$id]);
$agreements = db_all("SELECT * FROM sale_agreements WHERE booking_id = ? ORDER BY agreement_date DESC", [$id]);
$paymentMethods = db_all("SELECT * FROM payment_methods ORDER BY name");
$banks = db_all("SELECT * FROM banks ORDER BY name");

$totalDue = 0.0; $totalPaid = 0.0;
foreach ($installments as $i) {
    $totalDue += (float)$i['amount'];
    $totalPaid += (float)$i['paid_amount'];
}
$grandTotal = (float)$booking['total_price'] - (float)$booking['discount'];
include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="bookings.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= e($booking['booking_no']) ?></h5>
    <?php $saleTypeBadge = ['cash' => 'success', 'installment' => 'info', 'cash_installment' => 'warning'][$booking['sale_type']] ?? 'secondary'; ?>
    <span class="badge bg-<?= $saleTypeBadge ?>"><?= e(ucfirst(str_replace('_', ' ', $booking['sale_type']))) ?></span>
    <?= status_badge($booking['status']) ?>
    <?php if ($canEdit): ?><a class="btn btn-sm btn-outline-primary" href="booking_form.php?id=<?= $id ?>"><i class="bi bi-pencil"></i></a><?php endif; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-bank"></i></div><div><div class="stat-label">TOTAL (AFTER DISCOUNT)</div><div class="stat-value"><?= fmt_money($grandTotal) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-coin"></i></div><div><div class="stat-label">RECEIVED</div><div class="stat-value"><?= fmt_money($totalPaid) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card <?= ($grandTotal - $totalPaid) > 0 ? 'bg-grad-orange' : 'bg-grad-cyan' ?>"><div class="stat-body"><div class="stat-icon"><i class="bi bi-wallet2"></i></div><div><div class="stat-label">BALANCE</div><div class="stat-value"><?= fmt_money($grandTotal - $totalPaid) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-calendar2-check"></i></div><div><div class="stat-label">INSTALLMENTS</div><div class="stat-value"><?= count($installments) ?></div></div></div></div></div>
</div>

<ul class="nav nav-pills mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#bOverview">Overview</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#bInst">Installments (<?= count($installments) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#bReceipts">Receipts (<?= count($receipts) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#bAgreement">Sale Agreement</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="bOverview">
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3"><div class="text-muted small">Customer</div><div class="fw-medium"><a href="customer_view.php?id=<?= $booking['customer_id'] ?>"><?= e($booking['customer_name']) ?></a></div></div>
                    <div class="col-md-3"><div class="text-muted small">Customer No</div><div class="fw-medium"><?= e($booking['customer_no']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">CNIC</div><div class="fw-medium"><?= e($booking['customer_cnic'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Phone</div><div class="fw-medium"><?= e($booking['customer_phone'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Property</div><div class="fw-medium"><a href="property_view.php?id=<?= $booking['property_id'] ?>"><?= e($booking['property_no']) ?></a></div></div>
                    <div class="col-md-3"><div class="text-muted small">Property Address</div><div class="fw-medium"><?= e($booking['property_address'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Size</div><div class="fw-medium"><?= fmt_num($booking['size_value']) ?> <?= e($booking['size_unit']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Dealer</div><div class="fw-medium"><?= e($booking['dealer_name'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Booking Date</div><div class="fw-medium"><?= fmt_date($booking['booking_date']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Installment Plan</div><div class="fw-medium"><?= ucfirst(str_replace('_', ' ', e($booking['installment_plan']))) ?> / <?= (int)$booking['installment_years'] ?> yr</div></div>
                </div>
                <hr>
                <div class="row g-2">
                    <div class="col-md-3"><div class="text-muted small">Total Price</div><div class="fw-medium"><?= fmt_money($booking['total_price']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Discount</div><div class="fw-medium">- <?= fmt_money($booking['discount']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Token Amount</div><div class="fw-medium"><?= fmt_money($booking['token_amount']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Booking Amount</div><div class="fw-medium"><?= fmt_money($booking['booking_amount']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Possession Charges</div><div class="fw-medium"><?= fmt_money($booking['possession_charges']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Transfer Charges</div><div class="fw-medium"><?= fmt_money($booking['transfer_charges']) ?></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="bInst">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>No</th><th>Type</th><th>Due Date</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Status</th><?php if ($canEdit): ?><th class="text-end">Action</th><?php endif; ?></tr></thead>
                        <tbody>
                        <?php foreach ($installments as $inst): ?>
                            <tr>
                                <td><?= $inst['installment_no'] ?></td>
                                <td><span class="badge bg-light text-dark border"><?= ucfirst($inst['installment_type']) ?></span></td>
                                <td><?= fmt_date($inst['due_date']) ?></td>
                                <td><?= fmt_money($inst['amount']) ?></td>
                                <td><?= fmt_money($inst['paid_amount']) ?></td>
                                <td><span class="<?= ((float)$inst['amount'] - (float)$inst['paid_amount']) > 0 ? 'text-danger fw-medium' : 'text-success' ?>"><?= fmt_money((float)$inst['amount'] - (float)$inst['paid_amount']) ?></span></td>
                                <td><?= status_badge($inst['status']) ?></td>
                                <?php if ($canEdit): ?>
                                <td class="text-end">
                                    <?php if (in_array($inst['status'], ['pending', 'partial'])): ?>
                                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#receiveModal" data-installment="<?= $inst['id'] ?>" data-due="<?= (float)$inst['amount'] - (float)$inst['paid_amount'] ?>"><i class="bi bi-cash-coin me-1"></i>Receive</button>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$installments): ?><tr><td colspan="8" class="text-center text-muted py-4">No installments</td></tr><?php endif; ?>
                        </tbody>
                        <tfoot>
                        <tr class="table-light">
                            <td colspan="3" class="text-end fw-bold">Total</td>
                            <td class="fw-bold"><?= fmt_money($totalDue) ?></td>
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

    <div class="tab-pane fade" id="bReceipts">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Receipt</th><th>Date</th><th>Amount</th><th>Method</th><th>Bank</th><th>Reference</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($receipts as $r): ?>
                            <tr>
                                <td class="fw-medium"><?= e($r['receipt_no']) ?></td>
                                <td><?= fmt_date($r['receipt_date']) ?></td>
                                <td><?= fmt_money($r['amount']) ?></td>
                                <td><?= e($r['method_name'] ?? '-') ?></td>
                                <td><?= e($r['bank_name'] ?? '-') ?></td>
                                <td class="small"><?= e($r['reference'] ?? '-') ?></td>
                                <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="receipt_print.php?id=<?= $r['id'] ?>" target="_blank"><i class="bi bi-printer"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$receipts): ?><tr><td colspan="7" class="text-center text-muted py-4">No receipts yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="bAgreement">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Sale Agreements</h6>
                    <?php if ($canEdit): ?><a class="btn btn-sm btn-primary" href="agreements.php?booking_id=<?= $id ?>"><i class="bi bi-plus-lg me-1"></i>Add Agreement</a><?php endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Agreement No</th><th>Date</th><th>Status</th><th>File</th></tr></thead>
                        <tbody>
                        <?php foreach ($agreements as $ag): ?>
                            <tr>
                                <td class="fw-medium"><?= e($ag['agreement_no']) ?></td>
                                <td><?= fmt_date($ag['agreement_date']) ?></td>
                                <td><?= status_badge($ag['status']) ?></td>
                                <td><?= $ag['file_path'] ? '<a class="btn btn-sm btn-outline-secondary" target="_blank" href="' . BASE_URL . '/assets/' . e($ag['file_path']) . '"><i class="bi bi-download"></i></a>' : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$agreements): ?><tr><td colspan="4" class="text-center text-muted py-4">No agreement yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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
                    <p class="small text-muted mb-3"><?= e($booking['booking_no']) ?> - <?= e($booking['customer_name']) ?></p>
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
        });
    }
})();
</script>

<?php include '../includes/footer.php'; ?>

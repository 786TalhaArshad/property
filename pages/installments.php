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
            $project = db_get("SELECT p.project_id FROM properties p WHERE p.id = ?", [$booking['property_id']]);
            $projectId = $project ? (int)$project['project_id'] : (active_project_id() ?: null);
            $receiptId = db_exec("INSERT INTO receipts (receipt_no, receipt_date, project_id, customer_id, booking_id, installment_id, amount, payment_method_id, bank_id, reference, remarks, received_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                [$receipt_no, $receipt_date, $projectId, $booking['customer_id'], $booking['id'], $installment_id, $amount, $payment_method_id, $bank_id, $reference, $remarks, $user['id']]);
            $voucherId = null;
            $cashAcc = cash_bank_account_id($bank_id);
            $incomeAcc = coa_id_by_code('4000');
            if ($cashAcc && $incomeAcc) {
                $bankLabel = $bank_id ? (db_get("SELECT name FROM banks WHERE id = ?", [$bank_id])['name'] ?? 'Bank') : 'Cash';
                $voucherId = post_cash_voucher($receipt_date, 'cash_receipt', 'Installment received - ' . $booking['booking_no'] . ' (' . $bankLabel . ')', $projectId, $cashAcc, $incomeAcc, $amount, 'Receipt ' . $receipt_no, 'Sale income - ' . $booking['booking_no']);
            }
            if ($voucherId) {
                db_exec("UPDATE receipts SET voucher_id = ?, project_id = COALESCE(project_id, ?) WHERE id = ?", [$voucherId, $projectId, $receiptId]);
            }
            $newPaid = (float)$inst['paid_amount'] + $amount;
            $newStatus = $newPaid >= (float)$inst['amount'] ? 'paid' : 'partial';
            db_exec("UPDATE installments SET paid_amount = ?, status = ?, paid_date = ?, received_by = ?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$newPaid, $newStatus, $receipt_date, $user['id'], $installment_id]);
            flash('success', 'Payment received. Receipt No: ' . $receipt_no);
        }
    }
    redirect('installments.php');
}

$status = $_GET['status'] ?? '';
$sale_type = $_GET['sale_type'] ?? 'installment';
if (!in_array($sale_type, ['installment', 'cash', ''], true)) $sale_type = 'installment';
$project_id = (int)($_GET['project_id'] ?? active_project_id());

$records = db_all("SELECT i.*, b.booking_no, b.total_price, b.sale_type, b.installment_months,
                   c.full_name AS customer_name, p.property_no, p.project_id, pr.name AS project_name,
                   (i.amount - i.paid_amount) AS balance
                   FROM installments i
                   JOIN bookings b ON b.id = i.booking_id
                   JOIN customers c ON c.id = b.customer_id
                   JOIN properties p ON p.id = b.property_id
                   LEFT JOIN projects pr ON pr.id = p.project_id
                   WHERE (? = '' OR i.status = ?)
                     AND (? = '' OR b.sale_type = ?)
                     AND (? = 0 OR p.project_id = ?)
                   ORDER BY i.due_date ASC", [$status, $status, $sale_type, $sale_type, $project_id, $project_id]);

$totalAmount = 0.0; $totalPaid = 0.0; $totalBalance = 0.0;
foreach ($records as $r) {
    $totalAmount += (float)$r['amount'];
    $totalPaid += (float)$r['paid_amount'];
    $totalBalance += (float)$r['balance'];
}
$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
$paymentMethods = db_all("SELECT * FROM payment_methods ORDER BY name");
$banks = db_all("SELECT * FROM banks ORDER BY name");
include '../includes/header.php';
?>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-layers"></i></div><div><div class="stat-label">QISTEEN</div><div class="stat-value"><?= $totalCount = count($records) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-coin"></i></div><div><div class="stat-label">RECEIVED</div><div class="stat-value"><?= fmt_money($totalPaid) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-wallet2"></i></div><div><div class="stat-label">OUTSTANDING</div><div class="stat-value"><?= fmt_money($totalBalance) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-collection"></i></div><div><div class="stat-label">TOTAL AMOUNT</div><div class="stat-value"><?= fmt_money($totalAmount) ?></div></div></div></div></div>
</div>

<form method="get" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="project_id" class="form-select form-select-sm">
                    <option value="0">All Projects</option>
                    <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="sale_type" class="form-select form-select-sm">
                    <option value="installment" <?= $sale_type === 'installment' ? 'selected' : '' ?>>Installment Sales</option>
                    <option value="" <?= $sale_type === '' ? 'selected' : '' ?>>All (incl. Cash)</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <?php foreach (['pending', 'partial', 'paid', 'overdue', 'waived'] as $s): ?>
                        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i>Filter</button></div>
            <div class="col-md-3 text-end small text-muted">Qisteen: <strong><?= count($records) ?></strong></div>
        </div>
    </div>
</form>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search installments...">
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Booking</th><th>Customer</th><th>Property</th><th>Project</th><th>Plan</th><th>Type</th><th>Due Date</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Status</th><?php if ($canEdit): ?><th class="text-end">Action</th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><a class="fw-medium text-decoration-none" href="booking_view.php?id=<?= $r['booking_id'] ?>"><?= e($r['booking_no']) ?></a></td>
                        <td><?= e($r['customer_name']) ?></td>
                        <td><?= e($r['property_no']) ?></td>
                        <td class="small"><?= $r['project_name'] ? '<span class="badge bg-light text-dark border">' . e($r['project_name']) . '</span>' : '-' ?></td>
                        <td class="small"><?= $r['sale_type'] === 'installment' ? (int)$r['installment_months'] . ' monthly' : '<span class="badge bg-success">Cash</span>' ?></td>
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
                    <tr><td colspan="<?= $canEdit ? 14 : 13 ?>"><div class="empty-state"><i class="bi bi-calendar2-check"></i><p>No installments found</p></div></td></tr>
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

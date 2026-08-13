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
        $edit_id = (int)($_POST['edit_id'] ?? 0);
        $receipt_no = trim($_POST['receipt_no'] ?? '');
        if ($receipt_no === '') {
            $receipt_no = $edit_id > 0 ? ($_POST['current_no'] ?? '') : next_number('RCT', 'receipts', 'receipt_no');
        }
        $receipt_date = $_POST['receipt_date'] ?? date('Y-m-d');
        $project_id = $edit_id > 0 ? (int)(db_get("SELECT project_id FROM receipts WHERE id = ?", [$edit_id])['project_id'] ?? 0) : active_project_id();
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
            if ($edit_id > 0) {
                $old = db_get("SELECT * FROM receipts WHERE id = ?", [$edit_id]);
                if (!$old) {
                    flash('danger', 'Receipt not found.');
                } else {
                    if ($old['installment_id']) {
                        $inst = db_get("SELECT * FROM installments WHERE id = ?", [$old['installment_id']]);
                        if ($inst) {
                            $newPaid = max(0, (float)$inst['paid_amount'] - (float)$old['amount']);
                            $newStatus = $newPaid >= (float)$inst['amount'] ? 'paid' : ($newPaid > 0 ? 'partial' : 'pending');
                            db_exec("UPDATE installments SET paid_amount = ?, status = ?, paid_date = NULL, updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$newPaid, $newStatus, $old['installment_id']]);
                        }
                    }
                    db_exec("UPDATE receipts SET receipt_no=?, receipt_date=?, project_id=?, customer_id=?, booking_id=?, installment_id=?, amount=?, payment_method_id=?, bank_id=?, reference=?, remarks=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?",
                        [$receipt_no, $receipt_date, $project_id, $customer_id, $booking_id, $installment_id, $amount, $payment_method_id, $bank_id, $reference, $remarks, $edit_id]);
                    if ($installment_id) {
                        $inst = db_get("SELECT * FROM installments WHERE id = ?", [$installment_id]);
                        if ($inst) {
                            $newPaid = (float)$inst['paid_amount'] + $amount;
                            $newStatus = $newPaid >= (float)$inst['amount'] ? 'paid' : 'partial';
                            db_exec("UPDATE installments SET paid_amount = ?, status = ?, paid_date = ?, received_by = ?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$newPaid, $newStatus, $receipt_date, $user['id'], $installment_id]);
                        }
                    }
                    flash('success', 'Receipt updated. Receipt No: ' . $receipt_no);
                }
            } else {
                db_exec("INSERT INTO receipts (receipt_no, receipt_date, project_id, customer_id, booking_id, installment_id, amount, payment_method_id, bank_id, reference, remarks, received_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                    [$receipt_no, $receipt_date, $project_id, $customer_id, $booking_id, $installment_id, $amount, $payment_method_id, $bank_id, $reference, $remarks, $user['id']]);
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
$customers = db_all("SELECT * FROM customers ORDER BY full_name");
$paymentMethods = db_all("SELECT * FROM payment_methods ORDER BY name");
$banks = db_all("SELECT * FROM banks ORDER BY name");
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
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal"><i class="bi bi-plus-lg me-1"></i>New Receipt</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Receipt</th><th>Date</th><th>Project</th><th>Customer</th><th>Booking</th><th>Property</th><th>Amount</th><th>Method</th><th class="text-end">Actions</th></tr></thead>
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
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'receipt_no' => $r['receipt_no'], 'receipt_date' => $r['receipt_date'], 'project_id' => $r['project_id'], 'customer_id' => $r['customer_id'], 'booking_id' => $r['booking_id'], 'installment_id' => $r['installment_id'], 'amount' => $r['amount'], 'payment_method_id' => $r['payment_method_id'], 'bank_id' => $r['bank_id'], 'reference' => $r['reference'], 'remarks' => $r['remarks']])) ?>'><i class="bi bi-pencil"></i></button>
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
                    <tr><td colspan="10"><div class="empty-state"><i class="bi bi-cash-coin"></i><p>No receipts found</p></div></td></tr>
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
                    <input type="hidden" name="edit_id" id="editId">
                    <input type="hidden" name="current_no" id="currentNo">
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
                            <label class="form-label">Project</label>
                            <select name="project_id" class="form-select" disabled>
                                <option value="">-- General / No Project --</option>
                                <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= active_project_id() === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                            </select>
                            <div class="form-text">Locked to the active project from the header.</div>
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
    var modal = document.getElementById('recordModal');
    var customer = document.getElementById('rcvCustomer');
    var booking = document.getElementById('rcvBooking');
    var installment = document.getElementById('rcvInstallment');
    if (!customer || !modal) return;

    function fillBookings(id, selected, cb) {
        if (!id) { booking.innerHTML = '<option value="">None</option>'; installment.innerHTML = '<option value="">None</option>'; return cb && cb(); }
        booking.innerHTML = '<option value="">Loading...</option>';
        installment.innerHTML = '<option value="">None</option>';
        fetch('ajax.php?action=bookings&id=' + id).then(function (r) { return r.json(); }).then(function (rows) {
            booking.innerHTML = '<option value="">None</option>' + rows.map(function (r) { return '<option value="' + r.id + '"' + (String(r.id) === String(selected) ? ' selected' : '') + '>' + r.name + '</option>'; }).join('');
            cb && cb();
        });
    }
    function fillInstallments(id, selected) {
        if (!id) { installment.innerHTML = '<option value="">None</option>'; return; }
        installment.innerHTML = '<option value="">Loading...</option>';
        fetch('ajax.php?action=installments&id=' + id).then(function (r) { return r.json(); }).then(function (rows) {
            installment.innerHTML = '<option value="">None</option>' + rows.map(function (r) { return '<option value="' + r.id + '"' + (String(r.id) === String(selected) ? ' selected' : '') + '>' + r.name + '</option>'; }).join('');
        });
    }

    customer.addEventListener('change', function () {
        fillBookings(customer.value, null);
    });
    booking.addEventListener('change', function () {
        fillInstallments(booking.value, null);
    });

    modal.addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        var edit = btn && btn.getAttribute('data-edit') ? JSON.parse(btn.getAttribute('data-edit')) : null;
        modal.querySelector('#editId').value = edit ? edit.id : '';
        modal.querySelector('#currentNo').value = edit ? edit.receipt_no : '';
        modal.querySelector('.modal-title').textContent = edit ? 'Edit Receipt' : 'New Receipt';
        modal.querySelector('input[name="receipt_no"]').value = edit ? edit.receipt_no : '';
        modal.querySelector('input[name="receipt_date"]').value = edit ? edit.receipt_date : new Date().toISOString().slice(0, 10);
        modal.querySelector('select[name="project_id"]').value = edit && edit.project_id ? edit.project_id : '';
        customer.value = edit ? edit.customer_id : '';
        modal.querySelector('input[name="amount"]').value = edit ? edit.amount : '';
        modal.querySelector('select[name="payment_method_id"]').value = edit && edit.payment_method_id ? edit.payment_method_id : '';
        modal.querySelector('select[name="bank_id"]').value = edit && edit.bank_id ? edit.bank_id : '';
        modal.querySelector('input[name="reference"]').value = edit ? edit.reference : '';
        modal.querySelector('input[name="remarks"]').value = edit ? edit.remarks : '';
        if (edit) {
            fillBookings(edit.customer_id, edit.booking_id, function () {
                fillInstallments(edit.booking_id, edit.installment_id);
            });
        } else {
            booking.innerHTML = '<option value="">None</option>';
            installment.innerHTML = '<option value="">None</option>';
        }
    });
})();
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

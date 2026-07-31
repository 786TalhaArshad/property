<?php
require_once '../includes/auth.php';
require_login();
require_permission('sales.view');
$title = 'Quotations';
$active = 'quotations';
$canEdit = has_permission('sales.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $quotation_no = trim($_POST['quotation_no'] ?? '');
        if ($quotation_no === '') {
            $quotation_no = next_number('QUO', 'quotations', 'quotation_no');
        }
        $customer_id = (int)($_POST['customer_id'] ?? 0);
        $property_id = (int)($_POST['property_id'] ?? 0) ?: null;
        $dealer_id = (int)($_POST['dealer_id'] ?? 0) ?: null;
        $quotation_date = $_POST['quotation_date'] ?? date('Y-m-d');
        $amount = (float)($_POST['amount'] ?? 0);
        $status = $_POST['status'] ?? 'draft';
        $remarks = trim($_POST['remarks'] ?? '');

        if ($customer_id <= 0) {
            flash('danger', 'Please select a customer.');
        } elseif ($id > 0) {
            db_exec("UPDATE quotations SET quotation_no=?, customer_id=?, property_id=?, dealer_id=?, quotation_date=?, amount=?, status=?, remarks=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$quotation_no, $customer_id, $property_id, $dealer_id, $quotation_date, $amount, $status, $remarks, $id]);
            flash('success', 'Quotation updated successfully.');
        } else {
            db_exec("INSERT INTO quotations (quotation_no, customer_id, property_id, dealer_id, quotation_date, amount, status, remarks, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$quotation_no, $customer_id, $property_id, $dealer_id, $quotation_date, $amount, $status, $remarks]);
            flash('success', 'Quotation added successfully.');
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM quotations WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Quotation deleted successfully.');
    }
    redirect('quotations.php');
}

$status = $_GET['status'] ?? '';
$records = db_all("SELECT q.*, c.full_name AS customer_name, p.property_no, d.full_name AS dealer_name
                   FROM quotations q
                   JOIN customers c ON c.id = q.customer_id
                   LEFT JOIN properties p ON p.id = q.property_id
                   LEFT JOIN dealers d ON d.id = q.dealer_id
                   WHERE (? = '' OR q.status = ?)
                   ORDER BY q.quotation_date DESC", [$status, $status]);
$customers = db_all("SELECT * FROM customers WHERE status = 1 ORDER BY full_name");
$properties = db_all("SELECT * FROM properties WHERE status = 'available' ORDER BY property_no");
$dealers = db_all("SELECT * FROM dealers WHERE status = 1 ORDER BY full_name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search quotations...">
    </div>
    <select class="form-select form-select-sm" style="max-width:180px" onchange="location.href='?status='+this.value">
        <option value="">All Status</option>
        <?php foreach (['draft', 'sent', 'accepted', 'rejected'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Quotation"><i class="bi bi-plus-lg me-1"></i>Add Quotation</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Quotation</th><th>Customer</th><th>Property</th><th>Date</th><th>Amount</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-medium"><?= e($r['quotation_no']) ?><?= $r['dealer_name'] ? '<div class="small text-muted">Dealer: ' . e($r['dealer_name']) . '</div>' : '' ?></td>
                        <td><?= e($r['customer_name']) ?></td>
                        <td><?= e($r['property_no'] ?? '-') ?></td>
                        <td><?= fmt_date($r['quotation_date']) ?></td>
                        <td><?= fmt_money($r['amount']) ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td class="text-end">
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'quotation_no' => $r['quotation_no'], 'customer_id' => $r['customer_id'], 'property_id' => $r['property_id'], 'dealer_id' => $r['dealer_id'], 'quotation_date' => $r['quotation_date'], 'amount' => $r['amount'], 'status' => $r['status'], 'remarks' => $r['remarks']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this quotation?">
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
                    <tr><td colspan="8"><div class="empty-state"><i class="bi bi-file-earmark-text"></i><p>No quotations yet</p></div></td></tr>
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
                <div class="modal-header"><h5 class="modal-title">Add Quotation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Quotation No</label>
                            <input type="text" name="quotation_no" class="form-control" placeholder="Auto if blank">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input type="date" name="quotation_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" class="form-select" required>
                                <option value="">Select Customer</option>
                                <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['full_name']) ?> (<?= e($c['customer_no']) ?>)</option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Property</label>
                            <select name="property_id" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($properties as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['property_no']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Dealer / Agent</label>
                            <select name="dealer_id" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($dealers as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['full_name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required data-mask-money>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach (['draft', 'sent', 'accepted', 'rejected'] as $s): ?>
                                    <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
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

<?php include '../includes/footer.php'; ?>

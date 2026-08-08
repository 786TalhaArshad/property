<?php
require_once '../includes/auth.php';
require_login();
require_permission('vendors.view');
$title = 'Vendor Details';
$active = 'vendors';
$canEdit = has_permission('vendors.manage');

$id = (int)($_GET['id'] ?? 0);
$vendor = db_get("SELECT v.*, ci.name AS city_name FROM vendors v LEFT JOIN cities ci ON ci.id = v.city_id WHERE v.id = ?", [$id]);
if (!$vendor) {
    flash('danger', 'Vendor not found.');
    redirect('vendors.php');
}

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'payment_add') {
        $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
        $amount = (float)($_POST['amount'] ?? 0);
        $payment_method_id = (int)($_POST['payment_method_id'] ?? 0) ?: null;
        $bank_id = (int)($_POST['bank_id'] ?? 0) ?: null;
        $reference = trim($_POST['reference'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');
        if ($amount <= 0) {
            flash('danger', 'Enter a valid amount.');
        } else {
            db_exec("INSERT INTO vendor_payments (vendor_id, payment_date, amount, payment_method_id, bank_id, reference, remarks, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$id, $payment_date, $amount, $payment_method_id, $bank_id, $reference, $remarks]);
            flash('success', 'Vendor payment recorded.');
        }
    } elseif ($action === 'payment_delete') {
        db_exec("DELETE FROM vendor_payments WHERE id = ? AND vendor_id = ?", [(int)($_POST['id'] ?? 0), $id]);
        flash('success', 'Payment deleted.');
    }
    redirect('vendor_view.php?id=' . $id);
}

$payments = db_all("SELECT vp.*, pm.name AS method_name FROM vendor_payments vp LEFT JOIN payment_methods pm ON pm.id = vp.payment_method_id WHERE vp.vendor_id = ? ORDER BY vp.payment_date DESC", [$id]);
$paymentMethods = db_all("SELECT * FROM payment_methods ORDER BY name");
$banks = db_all("SELECT * FROM banks ORDER BY name");

$total = 0.0;
foreach ($payments as $p) {
    $total += (float)$p['amount'];
}

include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="vendors.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= e($vendor['business_name']) ?></h5>
    <span class="badge bg-light text-dark border"><?= e($vendor['vendor_no']) ?></span>
    <?= $vendor['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-truck"></i></div><div><div class="stat-label">TOTAL PAYMENTS</div><div class="stat-value"><?= count($payments) ?></div></div></div></div></div>
    <div class="col-md-4"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-coin"></i></div><div><div class="stat-label">TOTAL PAID</div><div class="stat-value"><?= fmt_money($total) ?></div></div></div></div></div>
    <div class="col-md-4"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-person-badge"></i></div><div><div class="stat-label">CONTACT</div><div class="stat-value small"><?= e($vendor['contact_person'] ?: '-') ?></div></div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-xl-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Vendor Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Business</dt><dd class="col-sm-8"><?= e($vendor['business_name']) ?></dd>
                    <dt class="col-sm-4">Contact Person</dt><dd class="col-sm-8"><?= e($vendor['contact_person'] ?: '-') ?></dd>
                    <dt class="col-sm-4">CNIC</dt><dd class="col-sm-8"><?= e($vendor['cnic'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Phone</dt><dd class="col-sm-8"><?= e($vendor['phone'] ?: '-') ?></dd>
                    <dt class="col-sm-4">WhatsApp</dt><dd class="col-sm-8"><?= e($vendor['whatsapp'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8"><?= e($vendor['email'] ?: '-') ?></dd>
                    <dt class="col-sm-4">City</dt><dd class="col-sm-8"><?= e($vendor['city_name'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Address</dt><dd class="col-sm-8"><?= e($vendor['address'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Bank</dt><dd class="col-sm-8"><?= e($vendor['bank_account_title'] ?: '-') ?> <?= $vendor['bank_account_no'] ? '· ' . e($vendor['bank_account_no']) : '' ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-cash-coin me-2"></i>Payments</div>
            <div class="card-body">
                <?php if ($canEdit): ?>
                <form method="post" class="row g-2 mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="payment_add">
                    <div class="col-md-4"><input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                    <div class="col-md-4"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required data-mask-money></div>
                    <div class="col-md-4"><button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Pay</button></div>
                    <div class="col-md-4">
                        <select name="payment_method_id" class="form-select">
                            <option value="">Method</option>
                            <?php foreach ($paymentMethods as $pm): ?><option value="<?= $pm['id'] ?>"><?= e($pm['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="bank_id" class="form-select">
                            <option value="">Bank</option>
                            <?php foreach ($banks as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4"><input type="text" name="reference" class="form-control" placeholder="Reference"></div>
                    <div class="col-12"><input type="text" name="remarks" class="form-control" placeholder="Remarks"></div>
                </form>
                <?php endif; ?>
                <div class="table-responsive" style="max-height:400px">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><?= fmt_date($p['payment_date']) ?></td>
                                <td><?= fmt_money($p['amount']) ?></td>
                                <td class="small"><?= e($p['method_name'] ?? '-') ?><?= $p['reference'] ? ' / ' . e($p['reference']) : '' ?></td>
                                <td class="text-end">
                                    <?php if ($canEdit): ?>
                                    <form method="post" class="d-inline" data-confirm="Delete this payment?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="payment_delete">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$payments): ?><tr><td colspan="4" class="text-center text-muted py-4">No payments yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

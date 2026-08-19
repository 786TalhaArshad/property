<?php
require_once '../includes/auth.php';
require_login();
require_permission('purchases.view');
$title = 'Purchases';
$active = 'purchases';
$canEdit = has_permission('purchases.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $pid = (int)($_POST['id'] ?? 0);
        $purchase = db_get("SELECT voucher_id, payment_voucher_id FROM purchases WHERE id = ?", [$pid]);
        if ($purchase) {
            $oldItems = db_all("SELECT product_id, quantity, unit_cost FROM purchase_items WHERE purchase_id = ?", [$pid]);
            foreach ($oldItems as $oi) {
                if ($oi['product_id']) stock_adjust($oi['product_id'], 'issue', (float)$oi['quantity'], (float)$oi['unit_cost'], 'purchase', $pid);
            }
            if ($purchase['voucher_id']) {
                db_exec("DELETE FROM voucher_items WHERE voucher_id = ?", [$purchase['voucher_id']]);
                db_exec("DELETE FROM vouchers WHERE id = ?", [$purchase['voucher_id']]);
            }
            if ($purchase['payment_voucher_id']) {
                $pvId = (int)$purchase['payment_voucher_id'];
                $linkedVendorPay = db_get("SELECT id FROM vendor_payments WHERE voucher_id = ?", [$pvId]);
                if ($linkedVendorPay) db_exec("DELETE FROM vendor_payments WHERE id = ?", [$linkedVendorPay['id']]);
                db_exec("DELETE FROM voucher_items WHERE voucher_id = ?", [$pvId]);
                db_exec("DELETE FROM vouchers WHERE id = ?", [$pvId]);
            }
            db_exec("DELETE FROM purchase_items WHERE purchase_id = ?", [$pid]);
            db_exec("DELETE FROM purchases WHERE id = ?", [$pid]);
            flash('success', 'Purchase deleted.');
        }
    }
    redirect('purchases.php');
}

$projectId = (int)($_GET['project_id'] ?? active_project_id());
$vendorFilter = (int)($_GET['vendor_id'] ?? 0);
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$sql = "SELECT p.*, v.business_name AS vendor_name,
        (SELECT COALESCE(SUM(pi.amount),0) FROM purchase_items pi WHERE pi.purchase_id = p.id) AS items_total
        FROM purchases p
        LEFT JOIN vendors v ON v.id = p.vendor_id
        WHERE 1=1";
$params = [];
if ($projectId > 0) { $sql .= " AND p.project_id = ?"; $params[] = $projectId; }
if ($vendorFilter > 0) { $sql .= " AND p.vendor_id = ?"; $params[] = $vendorFilter; }
if ($dateFrom !== '') { $sql .= " AND p.purchase_date >= ?"; $params[] = $dateFrom; }
if ($dateTo !== '') { $sql .= " AND p.purchase_date <= ?"; $params[] = $dateTo; }
$sql .= " ORDER BY p.purchase_date DESC, p.id DESC";
$records = db_all($sql, $params);

$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
$vendors = db_all("SELECT * FROM vendors ORDER BY business_name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2 mb-3">
    <form method="get" class="d-flex flex-wrap align-items-center gap-2">
        <select name="project_id" class="form-select form-select-sm" onchange="this.form.submit()" style="max-width:200px">
            <option value="0">All Projects</option>
            <?php foreach ($projects as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $projectId === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="vendor_id" class="form-select form-select-sm" onchange="this.form.submit()" style="max-width:200px">
            <option value="0">All Vendors</option>
            <?php foreach ($vendors as $v): ?>
            <option value="<?= $v['id'] ?>" <?= $vendorFilter === (int)$v['id'] ? 'selected' : '' ?>><?= e($v['business_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>" placeholder="From" style="max-width:150px">
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo) ?>" placeholder="To" style="max-width:150px">
        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-funnel"></i></button>
    </form>
    <div class="input-group input-group-sm ms-auto" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search purchases...">
    </div>
    <?php if ($canEdit): ?>
    <a href="purchase_form.php" class="btn btn-primary btn-sm ms-auto"><i class="bi bi-plus-lg me-1"></i>New Purchase</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Purchase No</th>
                        <th>Date</th>
                        <th>Vendor</th>
                        <th>Project</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <?php $bal = (float)$r['total_amount'] - (float)$r['paid_amount']; ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><a href="purchase_view.php?id=<?= $r['id'] ?>" class="fw-medium text-decoration-none"><?= e($r['purchase_no']) ?></a></td>
                        <td><?= fmt_date($r['purchase_date']) ?></td>
                        <td><?= e($r['vendor_name']) ?></td>
                        <td class="small"><?= e($r['project_id'] ? db_get("SELECT name FROM projects WHERE id = ?", [$r['project_id']])['name'] ?? '-' : '-') ?></td>
                        <td class="text-end"><?= fmt_money($r['total_amount']) ?></td>
                        <td class="text-end"><?= fmt_money($r['paid_amount']) ?></td>
                        <td class="text-end"><?= fmt_money($bal) ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td class="text-end">
                            <a href="purchase_view.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
                            <?php if ($canEdit): ?>
                            <a href="purchase_form.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form method="post" class="d-inline" data-confirm="Delete this purchase and its voucher?">
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
                    <tr><td colspan="10"><div class="empty-state"><i class="bi bi-bag"></i><p>No purchases yet</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_login();
require_permission('products.view');
$title = 'Product Details';
$active = 'products';

$id = (int)($_GET['id'] ?? 0);
$product = db_get("SELECT * FROM products WHERE id = ?", [$id]);
if (!$product) {
    flash('danger', 'Product not found.');
    redirect('products.php');
}

$items = db_all("SELECT pi.*, p.purchase_no, p.purchase_date, p.vendor_id, v.business_name AS vendor_name,
                 pr.name AS project_name
                 FROM purchase_items pi
                 JOIN purchases p ON p.id = pi.purchase_id
                 LEFT JOIN vendors v ON v.id = p.vendor_id
                 LEFT JOIN projects pr ON pr.id = p.project_id
                 WHERE pi.product_id = ?
                 ORDER BY p.purchase_date DESC, pi.id", [$id]);

$totalQty = 0.0;
$totalAmount = 0.0;
foreach ($items as $it) {
    $totalQty += (float)$it['quantity'];
    $totalAmount += (float)$it['amount'];
}

$vendors = db_all("SELECT DISTINCT v.id, v.business_name
                   FROM purchase_items pi
                   JOIN purchases p ON p.id = pi.purchase_id
                   JOIN vendors v ON v.id = p.vendor_id
                   WHERE pi.product_id = ?
                   ORDER BY v.business_name", [$id]);

$projects = db_all("SELECT DISTINCT pr.id, pr.name
                    FROM purchase_items pi
                    JOIN purchases p ON p.id = pi.purchase_id
                    JOIN projects pr ON pr.id = p.project_id
                    WHERE pi.product_id = ?
                    ORDER BY pr.name", [$id]);

$stockMovements = db_all("SELECT sm.*, p.name AS project_name, c.full_name AS contractor_name
                          FROM stock_movements sm
                          LEFT JOIN projects p ON p.id = sm.project_id
                          LEFT JOIN contractors c ON c.id = sm.contractor_id
                          WHERE sm.product_id = ?
                          ORDER BY sm.created_date DESC, sm.created_time DESC", [$id]);

include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="products.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= e($product['name']) ?></h5>
    <span class="badge bg-light text-dark border"><?= e($product['product_no']) ?></span>
    <?= $product['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-box-seam"></i></div><div><div class="stat-label">IN STOCK</div><div class="stat-value"><?= fmt_num($product['stock_qty']) ?> <?= e($product['unit'] ?? '') ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-bag"></i></div><div><div class="stat-label">TOTAL PURCHASED</div><div class="stat-value"><?= fmt_num($totalQty) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-currency-dollar"></i></div><div><div class="stat-label">AVG COST</div><div class="stat-value"><?= fmt_money($product['avg_cost']) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-shop"></i></div><div><div class="stat-label">TOTAL VALUE</div><div class="stat-value"><?= fmt_money((float)$product['stock_qty'] * (float)$product['avg_cost']) ?></div></div></div></div></div>
</div>

<ul class="nav nav-pills mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pProfile">Profile</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pPurchases">Purchase History (<?= count($items) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pStock">Stock Movements (<?= count($stockMovements) ?>)</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="pProfile">
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3"><div class="text-muted small">Product No</div><div class="fw-medium"><?= e($product['product_no']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Product Name</div><div class="fw-medium"><?= e($product['name']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Category</div><div class="fw-medium"><?= e($product['category'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Unit</div><div class="fw-medium"><?= e($product['unit'] ?? '-') ?></div></div>
                    <div class="col-md-6"><div class="text-muted small">Description</div><div class="fw-medium"><?= e($product['description'] ?? '-') ?></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="pPurchases">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Purchase</th><th>Vendor</th><th>Project</th><th class="text-end">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td><?= fmt_date($it['purchase_date']) ?></td>
                                <td><a href="purchase_view.php?id=<?= $it['purchase_id'] ?>"><?= e($it['purchase_no']) ?></a></td>
                                <td><?= $it['vendor_id'] ? '<a href="vendor_view.php?id=' . $it['vendor_id'] . '">' . e($it['vendor_name']) . '</a>' : '-' ?></td>
                                <td><?= e($it['project_name'] ?? '-') ?></td>
                                <td class="text-end"><?= fmt_num($it['quantity']) ?></td>
                                <td class="text-end"><?= fmt_money($it['unit_price']) ?></td>
                                <td class="text-end"><?= fmt_money($it['amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$items): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No purchase history yet</td></tr>
                        <?php endif; ?>
                        </tbody>
                        <tfoot>
                        <tr class="table-light">
                            <td colspan="4" class="text-end fw-bold">Total</td>
                            <td class="text-end fw-bold"><?= fmt_num($totalQty) ?></td>
                            <td></td>
                            <td class="text-end fw-bold"><?= fmt_money($totalAmount) ?></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="pStock">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Type</th><th class="text-end">Qty</th><th class="text-end">Unit Cost</th><th class="text-end">Total</th><th>Project</th><th>Contractor</th></tr></thead>
                        <tbody>
                        <?php foreach ($stockMovements as $sm): ?>
                            <tr>
                                <td><?= fmt_date($sm['created_date']) ?></td>
                                <td><?= $sm['movement_type'] === 'purchase' ? '<span class="badge bg-success">Purchase</span>' : '<span class="badge bg-danger">Issue</span>' ?></td>
                                <td class="text-end <?= (float)$sm['quantity'] < 0 ? 'text-danger' : '' ?>"><?= fmt_num($sm['quantity']) ?></td>
                                <td class="text-end"><?= fmt_money($sm['unit_cost']) ?></td>
                                <td class="text-end"><?= fmt_money($sm['total_cost']) ?></td>
                                <td><?= e($sm['project_name'] ?? '-') ?></td>
                                <td><?= e($sm['contractor_name'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$stockMovements): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No stock movements yet</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

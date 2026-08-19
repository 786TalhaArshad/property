<?php
require_once '../includes/auth.php';
require_login();
require_permission('products.view');
$title = 'Products';
$active = 'products';
$canEdit = has_permission('products.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $product_no = trim($_POST['product_no'] ?? '');
        if ($product_no === '') {
            $product_no = next_number('PRD', 'products', 'product_no');
        }
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $unit = trim($_POST['unit'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = (int)($_POST['status'] ?? 1);

        if ($name === '') {
            flash('danger', 'Product name is required.');
        } elseif ($id > 0) {
            db_exec("UPDATE products SET product_no=?, name=?, category=?, unit=?, description=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$product_no, $name, $category, $unit, $description, $status, $id]);
            flash('success', 'Product updated successfully.');
        } else {
            db_exec("INSERT INTO products (product_no, name, category, unit, description, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$product_no, $name, $category, $unit, $description, $status]);
            flash('success', 'Product added successfully.');
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM products WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Product deleted successfully.');
    }
    redirect('products.php');
}

$records = db_all("SELECT p.*,
                   (SELECT COUNT(*) FROM purchase_items pi WHERE pi.product_id = p.id) AS purchase_count,
                   (SELECT COALESCE(SUM(pi.quantity),0) FROM purchase_items pi WHERE pi.product_id = p.id) AS total_quantity,
                   (SELECT COALESCE(SUM(pi.amount),0) FROM purchase_items pi WHERE pi.product_id = p.id) AS total_purchased
                   FROM products p ORDER BY p.name");

$categories = db_all("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");

include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search products...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Product"><i class="bi bi-plus-lg me-1"></i>Add Product</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Product</th><th>Category</th><th>Unit</th><th class="text-end">In Stock</th><th class="text-end">Avg Cost</th><th class="text-end">Total Purchased</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <a class="fw-medium text-decoration-none" href="product_view.php?id=<?= $r['id'] ?>"><?= e($r['name']) ?></a>
                            <div class="small text-muted"><?= e($r['product_no']) ?></div>
                        </td>
                        <td><?= e($r['category'] ?? '-') ?></td>
                        <td><?= e($r['unit'] ?? '-') ?></td>
                        <td class="text-end"><?= (float)$r['stock_qty'] > 0 ? fmt_num($r['stock_qty']) : '<span class="text-muted">0</span>' ?></td>
                        <td class="text-end"><?= fmt_money($r['avg_cost']) ?></td>
                        <td class="text-end"><?= fmt_money($r['total_purchased']) ?></td>
                        <td><?= $r['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-info" href="product_view.php?id=<?= $r['id'] ?>"><i class="bi bi-eye"></i></a>
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode($r)) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this product? This cannot be undone.">
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
                    <tr><td colspan="9"><div class="empty-state"><i class="bi bi-box"></i><p>No products yet</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="recordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header"><h5 class="modal-title">Add Product</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Product No</label>
                            <input type="text" name="product_no" class="form-control" placeholder="Auto if blank">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Cement, Steel, Land">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" list="catList" placeholder="e.g. Material, Land, Equipment">
                            <datalist id="catList">
                                <?php foreach ($categories as $c): ?><option value="<?= e($c['category']) ?>"><?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit</label>
                            <input type="text" name="unit" class="form-control" list="unitList" placeholder="e.g. Kg, Bag, Sq Ft, Ton">
                            <datalist id="unitList">
                                <option value="Bag">
                                <option value="Kg">
                                <option value="Ton">
                                <option value="Sq Ft">
                                <option value="Sq Yard">
                                <option value="Meter">
                                <option value="Piece">
                                <option value="Roll">
                                <option value="Box">
                                <option value="Liter">
                            </datalist>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Optional"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
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

<script>
$(function () {
    var modal = document.getElementById('recordModal');
    modal.addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        var title = modal.querySelector('.modal-title');
        var form = modal.querySelector('form');
        form.reset();
        document.getElementById('recordId').value = '';
        if (btn && btn.dataset.edit) {
            title.textContent = 'Edit Product';
            var d = JSON.parse(btn.dataset.edit);
            document.getElementById('recordId').value = d.id || '';
            form.product_no.value = d.product_no || '';
            form.name.value = d.name || '';
            form.category.value = d.category || '';
            form.unit.value = d.unit || '';
            form.description.value = d.description || '';
            form.status.value = d.status ?? 1;
        } else {
            title.textContent = 'Add Product';
        }
    });
});
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

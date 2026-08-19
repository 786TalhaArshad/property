<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Material Issue Details';
$active = 'material_issues';

$id = (int)($_GET['id'] ?? 0);
$mi = db_get("SELECT mi.*, p.name AS project_name, c.full_name AS contractor_name, c.phone AS contractor_phone
              FROM material_issues mi
              LEFT JOIN projects p ON p.id = mi.project_id
              LEFT JOIN contractors c ON c.id = mi.contractor_id
              WHERE mi.id = ?", [$id]);
if (!$mi) {
    flash('danger', 'Material issue not found.');
    redirect('material_issues.php');
}

$items = db_all("SELECT mii.*, pr.name AS product_name, pr.product_no, pr.unit
                 FROM material_issue_items mii
                 JOIN products pr ON pr.id = mii.product_id
                 WHERE mii.material_issue_id = ?
                 ORDER BY mii.id", [$id]);

$voucher = null;
$voucherItems = [];
if ($mi['voucher_id']) {
    $voucher = db_get("SELECT * FROM vouchers WHERE id = ?", [$mi['voucher_id']]);
    if ($voucher) {
        $voucherItems = db_all("SELECT vi.*, coa.code, coa.name AS account_name
                               FROM voucher_items vi
                               JOIN chart_of_accounts coa ON coa.id = vi.account_id
                               WHERE vi.voucher_id = ?
                               ORDER BY vi.id", [$mi['voucher_id']]);
    }
}

include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="material_issues.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= e($mi['issue_no']) ?></h5>
    <span class="text-muted small"><?= fmt_date($mi['issue_date']) ?></span>
    <span class="ms-auto">
        <?php if (has_permission('accounting.manage')): ?>
        <a href="material_issue_form.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
        <?php endif; ?>
        <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    </span>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-box-seam"></i></div><div><div class="stat-label">ITEMS</div><div class="stat-value"><?= count($items) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-currency-dollar"></i></div><div><div class="stat-label">TOTAL AMOUNT</div><div class="stat-value"><?= fmt_money($mi['total_amount']) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-building"></i></div><div><div class="stat-label">PROJECT</div><div class="stat-value" style="font-size:.9rem"><?= e($mi['project_name']) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-person"></i></div><div><div class="stat-label">CONTRACTOR</div><div class="stat-value" style="font-size:.9rem"><?= e($mi['contractor_name']) ?></div></div></div></div></div>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-info-circle me-2"></i>Issue Information</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3"><div class="text-muted small">Issue No</div><div class="fw-medium"><?= e($mi['issue_no']) ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Date</div><div class="fw-medium"><?= fmt_date($mi['issue_date']) ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Project</div><div class="fw-medium"><?= e($mi['project_name']) ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Contractor</div><div class="fw-medium"><?= e($mi['contractor_name']) ?><?= $mi['contractor_phone'] ? '<div class="small text-muted">' . e($mi['contractor_phone']) . '</div>' : '' ?></div></div>
            <?php if ($mi['narration']): ?>
            <div class="col-12"><div class="text-muted small">Narration</div><div class="fw-medium"><?= e($mi['narration']) ?></div></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-list-ul me-2"></i>Items Issued</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>#</th><th>Product</th><th>Unit</th><th class="text-end">Qty</th><th class="text-end">Unit Cost</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                <?php foreach ($items as $i => $it): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><a href="product_view.php?id=<?= $it['product_id'] ?>"><?= e($it['product_name']) ?></a><div class="small text-muted"><?= e($it['product_no']) ?></div></td>
                        <td><?= e($it['unit'] ?? '-') ?></td>
                        <td class="text-end"><?= fmt_num($it['quantity']) ?></td>
                        <td class="text-end"><?= fmt_money($it['unit_cost']) ?></td>
                        <td class="text-end fw-bold"><?= fmt_money($it['total_cost']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-success">
                        <td colspan="4" class="text-end fw-bold">Total</td>
                        <td></td>
                        <td class="text-end fw-bold"><?= fmt_money($mi['total_amount']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php if ($voucher): ?>
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-journal-text me-2"></i>Journal Voucher: <?= e($voucher['voucher_no']) ?></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Account</th><th>Description</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
                <tbody>
                <?php foreach ($voucherItems as $vi): ?>
                    <tr>
                        <td><span class="badge bg-light text-dark border"><?= e($vi['code']) ?></span> <?= e($vi['account_name']) ?></td>
                        <td><?= e($vi['item_description']) ?></td>
                        <td class="text-end"><?= $vi['debit'] > 0 ? fmt_money($vi['debit']) : '' ?></td>
                        <td class="text-end"><?= $vi['credit'] > 0 ? fmt_money($vi['credit']) : '' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Vouchers';
$active = 'vouchers';
$canEdit = has_permission('accounting.manage');

if (is_post() && $canEdit) {
    csrf_check();
    if (($_POST['action'] ?? '') === 'delete') {
        db_exec("DELETE FROM vouchers WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Voucher deleted successfully.');
        redirect('vouchers.php');
    }
}

$records = db_all("SELECT v.*, u.full_name AS created_name,
                   (SELECT COALESCE(SUM(vi.debit),0) FROM voucher_items vi WHERE vi.voucher_id = v.id) AS total
                   FROM vouchers v
                   LEFT JOIN users u ON u.id = v.created_by
                   ORDER BY v.voucher_date DESC, v.id DESC");
$typeBadges = ['cash_payment' => 'danger', 'cash_receipt' => 'success', 'bank_payment' => 'warning', 'bank_receipt' => 'info', 'journal' => 'primary'];
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search vouchers...">
    </div>
    <?php if ($canEdit): ?>
    <a class="btn btn-primary ms-auto" href="voucher_form.php"><i class="bi bi-plus-lg me-1"></i>New Voucher</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Voucher</th><th>Date</th><th>Type</th><th>Narration</th><th>Amount</th><th>Status</th><th>Created By</th><?php if ($canEdit): ?><th class="text-end">Actions</th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-medium"><?= e($r['voucher_no']) ?></td>
                        <td><?= fmt_date($r['voucher_date']) ?></td>
                        <td><span class="badge bg-<?= $typeBadges[$r['voucher_type']] ?>"><?= ucfirst(str_replace('_', ' ', $r['voucher_type'])) ?></span></td>
                        <td class="small"><?= e($r['narration'] ?? '-') ?></td>
                        <td><?= fmt_money($r['total']) ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td class="small"><?= e($r['created_name'] ?? '-') ?></td>
                        <?php if ($canEdit): ?>
                        <td class="text-end">
                            <form method="post" class="d-inline" data-confirm="Delete this voucher? All entries will be removed.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$records): ?>
                    <tr><td colspan="9"><div class="empty-state"><i class="bi bi-receipt"></i><p>No vouchers yet</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

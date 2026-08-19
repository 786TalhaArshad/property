<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$active = 'vouchers';
$canEdit = has_permission('accounting.manage');

$id = (int)($_GET['id'] ?? 0);
$voucher = db_get("SELECT v.*, p.name AS project_name, u.full_name AS created_by_name
                   FROM vouchers v
                   LEFT JOIN projects p ON p.id = v.project_id
                   LEFT JOIN users u ON u.id = v.created_by
                   WHERE v.id = ?", [$id]);
if (!$voucher) {
    flash('danger', 'Voucher not found.');
    redirect('vouchers.php');
}

$lines = db_all("SELECT vi.*, coa.code AS account_code, coa.name AS account_name, coa.account_type
                 FROM voucher_items vi
                 JOIN chart_of_accounts coa ON coa.id = vi.account_id
                 WHERE vi.voucher_id = ?
                 ORDER BY vi.debit DESC, vi.id", [$id]);

$totalDebit = 0.0;
$totalCredit = 0.0;
foreach ($lines as $l) {
    $totalDebit += (float)$l['debit'];
    $totalCredit += (float)$l['credit'];
}

$typeBadges = ['cash_payment' => 'danger', 'cash_receipt' => 'success', 'bank_payment' => 'warning', 'bank_receipt' => 'info', 'journal' => 'primary'];
$title = 'Voucher ' . $voucher['voucher_no'];
include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="vouchers.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><i class="bi bi-receipt me-2"></i><?= e($voucher['voucher_no']) ?></h5>
    <span class="badge bg-<?= $typeBadges[$voucher['voucher_type']] ?? 'secondary' ?>"><?= ucfirst(str_replace('_', ' ', $voucher['voucher_type'])) ?></span>
    <?= status_badge($voucher['status']) ?>
    <div class="ms-auto d-flex gap-1">
        <a href="voucher_print.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm" target="_blank"><i class="bi bi-printer me-1"></i>Print</a>
        <?php if ($canEdit): ?>
        <a href="voucher_form.php?id=<?= $id ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-list-ol me-2"></i>Account Entries</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Account</th>
                            <th>Description</th>
                            <th class="text-end">Debit (Rs.)</th>
                            <th class="text-end">Credit (Rs.)</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($lines as $i => $l): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <span class="fw-medium"><?= e($l['account_code']) ?> - <?= e($l['account_name']) ?></span>
                                    <span class="badge bg-light text-dark border small ms-1"><?= e(ucfirst($l['account_type'])) ?></span>
                                </td>
                                <td class="small"><?= e($l['item_description'] ?? '-') ?></td>
                                <td class="text-end"><?= $l['debit'] > 0 ? fmt_money($l['debit']) : '-' ?></td>
                                <td class="text-end"><?= $l['credit'] > 0 ? fmt_money($l['credit']) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$lines): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No entries found</td></tr>
                        <?php endif; ?>
                        </tbody>
                        <tfoot>
                        <tr class="table-light">
                            <td colspan="3" class="text-end fw-bold">Total</td>
                            <td class="text-end fw-bold"><?= fmt_money($totalDebit) ?></td>
                            <td class="text-end fw-bold"><?= fmt_money($totalCredit) ?></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Voucher Info</div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted" style="width:40%">Voucher No</td><td class="fw-medium"><?= e($voucher['voucher_no']) ?></td></tr>
                    <tr><td class="text-muted">Date</td><td><?= fmt_date($voucher['voucher_date']) ?></td></tr>
                    <tr><td class="text-muted">Type</td><td><span class="badge bg-<?= $typeBadges[$voucher['voucher_type']] ?? 'secondary' ?>"><?= ucfirst(str_replace('_', ' ', $voucher['voucher_type'])) ?></span></td></tr>
                    <tr><td class="text-muted">Reference</td><td><?= e($voucher['reference_no'] ?? '-') ?></td></tr>
                    <tr><td class="text-muted">Project</td><td><?= $voucher['project_name'] ? e($voucher['project_name']) : '<span class="text-muted">General</span>' ?></td></tr>
                    <tr><td class="text-muted">Status</td><td><?= status_badge($voucher['status']) ?></td></tr>
                    <tr><td class="text-muted">Narration</td><td><?= e($voucher['narration'] ?? '-') ?></td></tr>
                    <tr><td class="text-muted">Remarks</td><td><?= e($voucher['remarks'] ?? '-') ?></td></tr>
                    <tr><td class="text-muted">Created By</td><td><?= e($voucher['created_by_name'] ?? '-') ?></td></tr>
                    <tr><td class="text-muted">Created</td><td><?= fmt_date($voucher['created_date']) ?></td></tr>
                </table>
            </div>
        </div>

        <?php if (abs($totalDebit - $totalCredit) < 0.01): ?>
        <div class="alert alert-success small mt-3 mb-0">
            <i class="bi bi-check-circle me-1"></i>Voucher is balanced (Debit = Credit = <?= fmt_money($totalDebit) ?>)
        </div>
        <?php else: ?>
        <div class="alert alert-danger small mt-3 mb-0">
            <i class="bi bi-exclamation-triangle me-1"></i>Voucher is NOT balanced! Difference: <?= fmt_money(abs($totalDebit - $totalCredit)) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

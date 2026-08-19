<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');

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

$companyInfo = [
    'name' => setting('company_name', APP_NAME),
    'tagline' => setting('company_tagline', ''),
    'address' => setting('company_address', ''),
    'phone' => setting('company_phone', ''),
    'email' => setting('company_email', ''),
    'logo' => setting('company_logo', ''),
];

$active = 'vouchers';
include '../includes/header.php';
?>
<style>
    body { background: #fff; }
    .print-head { border-bottom: 3px double #1c2b36; }
    .print-table { font-size: 13px; width: 100%; border-collapse: collapse; }
    .print-table th, .print-table td { border: 1px solid #444; padding: 6px 10px; }
    .print-table th { background: #e9ecef; text-align: left; }
    .total-row td { font-weight: bold; background: #f1f3f5; }
    .no-print { margin: 16px 0; }
    .info-grid { font-size: 13px; }
    .info-grid td { padding: 3px 10px; vertical-align: top; }
    .info-grid .label { color: #666; width: 130px; }
    .sign-area { margin-top: 40px; font-size: 12px; }
    .sign-area .sign-line { border-top: 1px solid #333; width: 180px; display: inline-block; padding-top: 4px; text-align: center; }
    @media print {
        body { background: #fff; }
        .no-print { display: none !important; }
        .print-head { border-bottom: 3px double #1c2b36 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>

<div class="no-print text-center">
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    <a class="btn btn-light" href="voucher_view.php?id=<?= $id ?>"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="print-head d-flex justify-content-between align-items-start pb-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <?php if ($companyInfo['logo']): ?>
            <img src="<?= BASE_URL ?>/assets/<?= e($companyInfo['logo']) ?>" style="max-height:60px" alt="logo">
        <?php endif; ?>
        <div>
            <h3 class="mb-0 fw-bold"><?= e($companyInfo['name']) ?></h3>
            <?php if ($companyInfo['tagline']): ?><div class="small"><?= e($companyInfo['tagline']) ?></div><?php endif; ?>
            <?php if ($companyInfo['address']): ?><div class="small text-muted"><?= e($companyInfo['address']) ?></div><?php endif; ?>
            <div class="small text-muted">
                <?= $companyInfo['phone'] ? 'Phone: ' . e($companyInfo['phone']) : '' ?>
                <?= $companyInfo['phone'] && $companyInfo['email'] ? ' &bull; ' : '' ?>
                <?= $companyInfo['email'] ? 'Email: ' . e($companyInfo['email']) : '' ?>
            </div>
        </div>
    </div>
    <div class="text-end">
        <h4 class="mb-1 fw-bold">JOURNAL VOUCHER</h4>
        <div class="small text-muted"><?= ucfirst(str_replace('_', ' ', $voucher['voucher_type'])) ?></div>
        <div class="small"><?= status_badge($voucher['status']) ?></div>
    </div>
</div>

<table class="info-grid mb-4" style="width:100%">
    <tr><td class="label fw-bold">Voucher No:</td><td class="fw-medium"><?= e($voucher['voucher_no']) ?></td>
        <td class="label fw-bold">Date:</td><td><?= fmt_date($voucher['voucher_date']) ?></td></tr>
    <tr><td class="label fw-bold">Reference No:</td><td><?= e($voucher['reference_no'] ?? '-') ?></td>
        <td class="label fw-bold">Project:</td><td><?= e($voucher['project_name'] ?? 'General') ?></td></tr>
    <tr><td class="label fw-bold">Narration:</td><td colspan="3"><?= e($voucher['narration'] ?? '-') ?></td></tr>
    <?php if ($voucher['remarks']): ?>
    <tr><td class="label fw-bold">Remarks:</td><td colspan="3"><?= e($voucher['remarks']) ?></td></tr>
    <?php endif; ?>
</table>

<table class="print-table">
    <thead>
    <tr>
        <th style="width:30px">#</th>
        <th>Account</th>
        <th>Description</th>
        <th class="text-end" style="width:130px">Debit (Rs.)</th>
        <th class="text-end" style="width:130px">Credit (Rs.)</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($lines as $i => $l): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td class="fw-medium"><?= e($l['account_code']) ?> - <?= e($l['account_name']) ?></td>
            <td><?= e($l['item_description'] ?? '-') ?></td>
            <td class="text-end"><?= $l['debit'] > 0 ? fmt_money($l['debit']) : '' ?></td>
            <td class="text-end"><?= $l['credit'] > 0 ? fmt_money($l['credit']) : '' ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
    <tr class="total-row">
        <td colspan="3" class="text-end">TOTAL</td>
        <td class="text-end"><?= fmt_money($totalDebit) ?></td>
        <td class="text-end"><?= fmt_money($totalCredit) ?></td>
    </tr>
    </tfoot>
</table>

<?php if (abs($totalDebit - $totalCredit) < 0.01): ?>
<div class="mt-2 mb-4 text-center">
    <span class="badge bg-success fs-6">BALANCED</span>
</div>
<?php else: ?>
<div class="mt-2 mb-4 text-center">
    <span class="badge bg-danger fs-6">UNBALANCED - Difference: <?= fmt_money(abs($totalDebit - $totalCredit)) ?></span>
</div>
<?php endif; ?>

<div class="sign-area d-flex justify-content-between">
    <div>
        <div class="sign-line">Prepared By</div>
    </div>
    <div>
        <div class="sign-line">Authorized By</div>
    </div>
</div>

<div class="mt-4 small text-muted text-center">
    Printed on <?= date('d/m/Y H:i') ?> &bull; <?= e($companyInfo['name']) ?>
</div>

<?php include '../includes/footer.php'; ?>

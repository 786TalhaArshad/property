<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Vouchers Print';

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$project_id = (int)($_GET['project_id'] ?? active_project_id());
$params = [];
$where = '';
if ($from) {
    $where .= " AND v.voucher_date >= ?";
    $params[] = $from;
}
if ($to) {
    $where .= " AND v.voucher_date <= ?";
    $params[] = $to;
}
if ($project_id > 0) {
    $where .= " AND v.project_id = ?";
    $params[] = $project_id;
}

$rows = db_all("SELECT v.voucher_no, v.voucher_date, v.voucher_type, v.narration, p.name AS project_name,
                vi.item_description, a.code AS acc_code, a.name AS acc_name, vi.debit, vi.credit
                FROM vouchers v
                JOIN voucher_items vi ON vi.voucher_id = v.id
                JOIN chart_of_accounts a ON a.id = vi.account_id
                LEFT JOIN projects p ON p.id = v.project_id
                WHERE v.status = 'posted'$where
                ORDER BY v.voucher_date, v.id, vi.id", $params);

$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
$projFilter = '';
foreach ($projects as $pj) {
    if ((int)$pj['id'] === $project_id) {
        $projFilter = $pj['name'];
        break;
    }
}

$companyInfo = [
    'name' => setting('company_name', 'Company'),
    'tagline' => setting('company_tagline', ''),
    'address' => setting('company_address', ''),
    'phone' => setting('company_phone', ''),
    'email' => setting('company_email', ''),
    'logo' => setting('company_logo', ''),
];
$totalDr = 0.0;
$totalCr = 0.0;
foreach ($rows as $r) {
    $totalDr += (float)$r['debit'];
    $totalCr += (float)$r['credit'];
}
include '../includes/header.php';
?>
<style>
    body { background: #fff; }
    .print-head { border-bottom: 3px double #1c2b36; }
    .print-table { font-size: 12px; width: 100%; border-collapse: collapse; }
    .print-table th, .print-table td { border: 1px solid #444; padding: 4px 6px; }
    .print-table th { background: #e9ecef; text-align: left; }
    .total-row td { font-weight: bold; background: #f1f3f5; }
    .no-print { margin: 16px 0; }
    @media print {
        body { background: #fff; }
        .no-print { display: none !important; }
    }
</style>

<div class="no-print text-center">
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    <a class="btn btn-light" href="vouchers.php"><i class="bi bi-arrow-left me-1"></i>Back to Vouchers</a>
</div>

<div class="print-head d-flex justify-content-between align-items-start pb-3 mb-3">
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
        <h5 class="mb-1">Vouchers Register</h5>
        <div class="small">Print Date: <?= fmt_date(date('Y-m-d')) ?></div>
        <div class="small">
            Project: <?= $projFilter ? e($projFilter) : 'All' ?>
            <?php if ($from): ?> &bull; From: <?= fmt_date($from) ?><?php endif; ?>
            <?php if ($to): ?> &bull; To: <?= fmt_date($to) ?><?php endif; ?>
        </div>
        <div class="small text-muted">Total Vouchers: <?= count(array_unique(array_column($rows, 'voucher_no'))) ?></div>
    </div>
</div>

<div class="table-responsive">
    <table class="print-table">
        <thead>
        <tr>
            <th style="width:30px">#</th>
            <th>Voucher</th>
            <th>Date</th>
            <th>Type</th>
            <th>Project</th>
            <th>Account</th>
            <th>Description</th>
            <th class="text-end">Debit</th>
            <th class="text-end">Credit</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $i => $r): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td class="fw-medium"><?= e($r['voucher_no']) ?></td>
                <td><?= fmt_date($r['voucher_date']) ?></td>
                <td><?= ucfirst(str_replace('_', ' ', $r['voucher_type'])) ?></td>
                <td class="small"><?= $r['project_name'] ? e($r['project_name']) : 'General' ?></td>
                <td class="small"><?= e($r['acc_code']) ?> - <?= e($r['acc_name']) ?></td>
                <td class="small"><?= e($r['item_description'] ?: ($r['narration'] ?? '')) ?></td>
                <td class="text-end"><?= (float)$r['debit'] > 0 ? fmt_money($r['debit']) : '-' ?></td>
                <td class="text-end"><?= (float)$r['credit'] > 0 ? fmt_money($r['credit']) : '-' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">No posted vouchers in the selected period</td></tr>
        <?php else: ?>
            <tr class="total-row">
                <td colspan="7" class="text-end">TOTAL</td>
                <td class="text-end"><?= fmt_money($totalDr) ?></td>
                <td class="text-end"><?= fmt_money($totalCr) ?></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>

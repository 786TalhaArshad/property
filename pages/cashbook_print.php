<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');

$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? date('Y-m-d'));
$project_id = (int)($_GET['project_id'] ?? active_project_id());

$cashAcc = db_get("SELECT id FROM chart_of_accounts WHERE code = '1000'");
if (!$cashAcc) {
    flash('danger', 'Cash account not found in chart of accounts.');
    redirect('index.php');
}
$cashAccId = (int)$cashAcc['id'];

$vouchers = db_all("SELECT vi.item_description, vi.debit, vi.credit, v.voucher_no, v.voucher_date, v.voucher_type, v.narration, v.project_id, p.name AS project_name
                    FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id
                    LEFT JOIN projects p ON p.id = v.project_id
                    WHERE vi.account_id = ? AND v.status = 'posted' AND v.narration NOT LIKE 'Cash sale %'", [$cashAccId]);
$receipts = db_all("SELECT r.receipt_no, r.receipt_date, r.amount, r.project_id, c.full_name AS customer_name, p.name AS project_name
                    FROM receipts r
                    LEFT JOIN customers c ON c.id = r.customer_id
                    LEFT JOIN projects p ON p.id = r.project_id
                    WHERE r.bank_id IS NULL");
$rents = db_all("SELECT rc.collection_date, rc.amount, rc.reference, rc.bank_id, t.full_name AS tenant_name, pr.project_id, p.name AS project_name
                 FROM rent_collections rc
                 JOIN rental_agreements ra ON ra.id = rc.agreement_id
                 JOIN tenants t ON t.id = ra.tenant_id
                 LEFT JOIN properties pr ON pr.id = ra.property_id
                 LEFT JOIN projects p ON p.id = pr.project_id
                 WHERE rc.bank_id IS NULL");

$rows = [];
foreach ($vouchers as $v) {
    $rows[] = ['date' => $v['voucher_date'], 'ref' => $v['voucher_no'], 'type' => 'Voucher',
        'desc' => trim($v['narration'] !== '' ? $v['narration'] : $v['item_description']),
        'project' => $v['project_name'], 'project_id' => $v['project_id'] ? (int)$v['project_id'] : 0,
        'in' => (float)$v['debit'], 'out' => (float)$v['credit']];
}
foreach ($receipts as $r) {
    $rows[] = ['date' => $r['receipt_date'], 'ref' => $r['receipt_no'], 'type' => 'Receipt',
        'desc' => 'Received - ' . trim($r['customer_name'] ?? ''),
        'project' => $r['project_name'], 'project_id' => $r['project_id'] ? (int)$r['project_id'] : 0,
        'in' => (float)$r['amount'], 'out' => 0.0];
}
foreach ($rents as $rc) {
    $rows[] = ['date' => $rc['collection_date'], 'ref' => $rc['reference'] !== '' ? 'RC-' . $rc['reference'] : 'RC-' . $rc['collection_date'], 'type' => 'Rent',
        'desc' => 'Rent - ' . trim($rc['tenant_name'] ?? ''),
        'project' => $rc['project_name'], 'project_id' => $rc['project_id'] ? (int)$rc['project_id'] : 0,
        'in' => (float)$rc['amount'], 'out' => 0.0];
}

$filtered = array_values(array_filter($rows, function ($r) use ($from, $to, $project_id) {
    if ($from !== '' && $r['date'] < $from) return false;
    if ($r['date'] > $to) return false;
    if ($project_id > 0 && $r['project_id'] !== $project_id) return false;
    return true;
}));
usort($filtered, function ($a, $b) {
    return strcmp($a['date'], $b['date']) ?: strcmp($a['ref'], $b['ref']);
});

$opening = 0.0;
if ($from !== '') {
    foreach ($rows as $r) {
        if ($r['date'] >= $from) continue;
        if ($project_id > 0 && $r['project_id'] !== $project_id) continue;
        $opening += $r['in'] - $r['out'];
    }
}

$totalIn = 0.0;
$totalOut = 0.0;
$running = [];
$bal = $opening;
foreach ($filtered as $i => $r) {
    $totalIn += $r['in'];
    $totalOut += $r['out'];
    $bal += $r['in'] - $r['out'];
    $running[$i] = $bal;
}
$closing = $bal;

$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
$projectName = '';
foreach ($projects as $p) {
    if ((int)$p['id'] === $project_id) {
        $projectName = $p['name'];
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
    <a class="btn btn-light" href="cashbook.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
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
        <h5 class="mb-1">Cash Book</h5>
        <div class="small">Print Date: <?= fmt_date(date('Y-m-d')) ?></div>
        <div class="small">
            Period: <?= $from ? fmt_date($from) . ' to ' . fmt_date($to) : 'As of ' . fmt_date($to) ?>
            &bull; Project: <?= $projectName ? e($projectName) : 'All' ?>
        </div>
        <div class="small">
            Opening: <?= fmt_money($opening) ?>
            &bull; Cash In: <?= fmt_money($totalIn) ?>
            &bull; Cash Out: <?= fmt_money($totalOut) ?>
            &bull; Closing: <?= fmt_money($closing) ?>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="print-table">
        <thead>
        <tr>
            <th>Date</th>
            <th>Ref / Voucher</th>
            <th>Type</th>
            <th>Description</th>
            <th>Project</th>
            <th class="text-end">Cash In</th>
            <th class="text-end">Cash Out</th>
            <th class="text-end">Balance</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($from !== ''): ?>
            <tr class="total-row">
                <td><?= fmt_date($from) ?></td>
                <td colspan="4">Opening Balance</td>
                <td>-</td><td>-</td>
                <td class="text-end"><?= fmt_money($opening) ?></td>
            </tr>
        <?php endif; ?>
        <?php foreach ($filtered as $i => $r): ?>
            <tr>
                <td><?= fmt_date($r['date']) ?></td>
                <td><?= e($r['ref']) ?></td>
                <td><?= e($r['type']) ?></td>
                <td><?= e($r['desc'] !== '' ? $r['desc'] : '-') ?></td>
                <td><?= $r['project'] ? e($r['project']) : '-' ?></td>
                <td class="text-end"><?= $r['in'] ? fmt_money($r['in']) : '-' ?></td>
                <td class="text-end"><?= $r['out'] ? fmt_money($r['out']) : '-' ?></td>
                <td class="text-end fw-medium"><?= fmt_money($running[$i]) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$filtered && $from === ''): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No cash entries yet</td></tr>
        <?php endif; ?>
        </tbody>
        <tfoot>
        <tr class="total-row">
            <td colspan="5" class="text-end fw-bold">Totals</td>
            <td class="text-end fw-bold"><?= fmt_money($totalIn) ?></td>
            <td class="text-end fw-bold"><?= fmt_money($totalOut) ?></td>
            <td class="text-end fw-bold"><?= fmt_money($closing) ?></td>
        </tr>
        </tfoot>
    </table>
</div>

<?php include '../includes/footer.php'; ?>

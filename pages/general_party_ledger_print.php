<?php
require_once '../includes/auth.php';
require_login();
require_permission('general_parties.view');

function gp_account_id($party_id, $party_name) {
    $code = '2000-' . str_pad((int)$party_id, 3, '0', STR_PAD_LEFT);
    $acc = db_get("SELECT id FROM chart_of_accounts WHERE code = ?", [$code]);
    return $acc ? (int)$acc['id'] : 0;
}

$id = (int)($_GET['id'] ?? 0);
$party = db_get("SELECT * FROM general_parties WHERE id = ?", [$id]);
if (!$party) {
    flash('danger', 'Party not found.');
    redirect('general_parties.php');
}

$entries = db_all("SELECT gpe.*, u.full_name AS created_name
                   FROM general_party_entries gpe
                   LEFT JOIN users u ON u.id = gpe.created_by
                   WHERE gpe.party_id = ?
                   ORDER BY gpe.entry_date, gpe.id", [$id]);

$totalPayable = 0.0;
$totalPaid = 0.0;
$totalReceiving = 0.0;
$running = [];
$bal = 0.0;
foreach ($entries as $e) {
    $amt = (float)$e['amount'];
    if ($e['entry_type'] === 'payable') {
        $totalPayable += $amt;
        $bal += $amt;
    } elseif ($e['entry_type'] === 'paid') {
        $totalPaid += $amt;
        $bal -= $amt;
    } else {
        $totalReceiving += $amt;
        $bal -= $amt;
    }
    $running[$e['id']] = $bal;
}
$balance = $totalPayable - $totalPaid - $totalReceiving;

$companyInfo = [
    'name' => setting('company_name', APP_NAME),
    'tagline' => setting('company_tagline', ''),
    'address' => setting('company_address', ''),
    'phone' => setting('company_phone', ''),
    'email' => setting('company_email', ''),
    'logo' => setting('company_logo', ''),
];
$active = 'general_parties';
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
    <a class="btn btn-light" href="general_party_view.php?id=<?= $id ?>#gLedger"><i class="bi bi-arrow-left me-1"></i>Back</a>
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
        <h5 class="mb-1">General Party Ledger</h5>
        <div class="fw-medium"><?= e($party['party_name']) ?> (<?= e($party['party_no']) ?>)</div>
        <div class="small text-muted">Phone: <?= e($party['phone'] ?? '-') ?></div>
        <div class="small">Print Date: <?= fmt_date(date('Y-m-d')) ?></div>
    </div>
</div>

<div class="table-responsive">
    <table class="print-table">
        <thead>
        <tr>
            <th>Date</th>
            <th>Entry No</th>
            <th>Type</th>
            <th>Narration</th>
            <th class="text-end">Payable</th>
            <th class="text-end">Paid</th>
            <th class="text-end">Receiving</th>
            <th class="text-end">Balance</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $typeLabels = [
            'payable' => 'Payable',
            'paid' => 'Paid',
            'receiving' => 'Receiving',
        ];
        foreach ($entries as $e):
        ?>
            <tr>
                <td><?= fmt_date($e['entry_date']) ?></td>
                <td><?= e($e['entry_no']) ?></td>
                <td><?= e($typeLabels[$e['entry_type']] ?? $e['entry_type']) ?></td>
                <td><?= e($e['narration'] ?? '-') ?></td>
                <td class="text-end"><?= $e['entry_type'] === 'payable' ? fmt_money($e['amount']) : '-' ?></td>
                <td class="text-end"><?= $e['entry_type'] === 'paid' ? fmt_money($e['amount']) : '-' ?></td>
                <td class="text-end"><?= $e['entry_type'] === 'receiving' ? fmt_money($e['amount']) : '-' ?></td>
                <td class="text-end fw-medium"><?= fmt_money($running[$e['id']]) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$entries): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No entries</td></tr>
        <?php endif; ?>
        </tbody>
        <tfoot>
        <tr class="total-row">
            <td colspan="4" class="text-end">Net Balance</td>
            <td class="text-end"><?= fmt_money($totalPayable) ?></td>
            <td class="text-end"><?= fmt_money($totalPaid) ?></td>
            <td class="text-end"><?= fmt_money($totalReceiving) ?></td>
            <td class="text-end"><?= fmt_money($balance) ?></td>
        </tr>
        </tfoot>
    </table>
</div>

<?php include '../includes/footer.php'; ?>

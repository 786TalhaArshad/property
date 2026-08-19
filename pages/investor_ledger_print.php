<?php
require_once '../includes/auth.php';
require_login();
require_permission('investors.view');

$id = (int)($_GET['id'] ?? 0);
$investor = db_get("SELECT * FROM investors WHERE id = ?", [$id]);
if (!$investor) {
    flash('danger', 'Investor not found.');
    redirect('investors.php');
}

$ledgerStart = trim($_GET['start_date'] ?? '');
$ledgerEnd = trim($_GET['end_date'] ?? '');

$allLedger = db_all("SELECT * FROM investor_ledger WHERE investor_id = ? ORDER BY entry_date, id", [$id]);
$filteredLedger = [];
foreach ($allLedger as $l) {
    if ($ledgerStart !== '' && $l['entry_date'] < $ledgerStart) continue;
    if ($ledgerEnd !== '' && $l['entry_date'] > $ledgerEnd) continue;
    $filteredLedger[] = $l;
}

$openingBalance = 0.0;
if ($ledgerStart !== '') {
    $ob = db_get("SELECT COALESCE(MAX(balance),0) b FROM investor_ledger WHERE investor_id = ? AND entry_date < ?", [$id, $ledgerStart]);
    $openingBalance = (float)$ob['b'];
}

$companyInfo = [
    'name' => setting('company_name', APP_NAME),
    'tagline' => setting('company_tagline', ''),
    'address' => setting('company_address', ''),
    'phone' => setting('company_phone', ''),
    'email' => setting('company_email', ''),
    'logo' => setting('company_logo', ''),
];
$active = 'investors';
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
    <a class="btn btn-light" href="investor_view.php?id=<?= $id ?>#iLedger"><i class="bi bi-arrow-left me-1"></i>Back</a>
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
        <h5 class="mb-1">Investor Ledger</h5>
        <div class="fw-medium"><?= e($investor['full_name']) ?> (<?= e($investor['investor_no']) ?>)</div>
        <div class="small text-muted">Phone: <?= e($investor['phone'] ?? '-') ?> &bull; Type: <?= e($investor['investment_type'] ?? '-') ?></div>
        <div class="small">Print Date: <?= fmt_date(date('Y-m-d')) ?></div>
        <div class="small">
            <?php if ($ledgerStart): ?>From: <?= fmt_date($ledgerStart) ?><?php endif; ?>
            <?php if ($ledgerEnd): ?> &bull; To: <?= fmt_date($ledgerEnd) ?><?php endif; ?>
            <?php if ($ledgerStart === '' && $ledgerEnd === ''): ?>All Entries<?php endif; ?>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="print-table">
        <thead>
        <tr>
            <th>Date</th>
            <th>Description</th>
            <th class="text-end">Credit (In)</th>
            <th class="text-end">Debit (Out)</th>
            <th class="text-end">Balance</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $bal = $openingBalance;
        if ($ledgerStart !== '' || $ledgerEnd !== '') {
            echo '<tr class="total-row"><td>' . fmt_date($ledgerStart) . '</td><td>Opening Balance</td><td>-</td><td>-</td><td class="text-end">' . fmt_money($bal) . '</td></tr>';
        }
        foreach ($filteredLedger as $l) {
            $bal += (float)$l['credit'] - (float)$l['debit'];
            $credit = (float)$l['credit'] > 0 ? fmt_money($l['credit']) : '-';
            $debit = (float)$l['debit'] > 0 ? fmt_money($l['debit']) : '-';
            echo '<tr><td>' . fmt_date($l['entry_date']) . '</td><td>' . e($l['description'] ?? '-') . '</td><td class="text-end">' . $credit . '</td><td class="text-end">' . $debit . '</td><td class="text-end fw-medium">' . fmt_money($bal) . '</td></tr>';
        }
        if (!$filteredLedger) {
            echo '<tr><td colspan="5" class="text-center text-muted py-4">No ledger entries</td></tr>';
        }
        ?>
        </tbody>
        <tfoot>
        <tr class="total-row">
            <td colspan="4" class="text-end fw-bold"><?= ($ledgerStart !== '' || $ledgerEnd !== '') ? 'Closing Balance' : 'Net Balance' ?></td>
            <td class="fw-bold text-end"><?= fmt_money($bal) ?></td>
        </tr>
        </tfoot>
    </table>
</div>

<?php include '../includes/footer.php'; ?>

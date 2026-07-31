<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Balance Sheet';
$active = 'balance_sheet';

$asof = $_GET['asof'] ?? date('Y-m-d');
$records = db_all("SELECT a.code, a.name, a.account_type, a.opening_balance,
                   (SELECT COALESCE(SUM(vi.debit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted' AND v.voucher_date <= ?) AS dr,
                   (SELECT COALESCE(SUM(vi.credit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted' AND v.voucher_date <= ?) AS cr
                   FROM chart_of_accounts a
                   WHERE a.account_type IN ('asset','liability','equity')
                   ORDER BY a.account_type, a.code", [$asof, $asof]);

$assets = [];
$liabilities = [];
$equity = [];
foreach ($records as $r) {
    $net = (float)$r['opening_balance'] + (float)$r['dr'] - (float)$r['cr'];
    if ($r['account_type'] === 'asset') {
        $assets[] = ['name' => $r['name'], 'net' => $net];
    } elseif ($r['account_type'] === 'liability') {
        $liabilities[] = ['name' => $r['name'], 'net' => -$net];
    } else {
        $equity[] = ['name' => $r['name'], 'net' => -$net];
    }
}
$totalAssets = array_sum(array_map(function ($a) { return $a['net']; }, $assets));
$totalLiab = array_sum(array_map(function ($a) { return $a['net']; }, $liabilities));
$totalEquity = array_sum(array_map(function ($a) { return $a['net']; }, $equity));

$netProfit = (float)db_get("SELECT
    (SELECT COALESCE(SUM(vi.credit),0) - COALESCE(SUM(vi.debit),0)
     FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id JOIN chart_of_accounts a ON a.id = vi.account_id
     WHERE a.account_type = 'income' AND v.status = 'posted' AND v.voucher_date <= ?) -
    (SELECT COALESCE(SUM(vi.debit),0) - COALESCE(SUM(vi.credit),0)
     FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id JOIN chart_of_accounts a ON a.id = vi.account_id
     WHERE a.account_type = 'expense' AND v.status = 'posted' AND v.voucher_date <= ?) AS np", [$asof, $asof])['np'];

$totalLiabEquity = $totalLiab + $totalEquity + $netProfit;
include '../includes/header.php';
?>

<form method="get" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-3"><input type="date" name="asof" class="form-control" value="<?= e($asof) ?>"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-boxes me-2"></i>Assets</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <tbody>
                    <?php foreach ($assets as $a): ?>
                        <tr><td><?= e($a['name']) ?></td><td class="text-end"><?= fmt_money($a['net']) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr class="table-light"><td class="fw-bold">Total Assets</td><td class="text-end fw-bold"><?= fmt_money($totalAssets) ?></td></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-bank me-2"></i>Liabilities &amp; Equity</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <tbody>
                    <?php foreach ($liabilities as $a): ?>
                        <tr><td><?= e($a['name']) ?></td><td class="text-end"><?= fmt_money($a['net']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php foreach ($equity as $a): ?>
                        <tr><td><?= e($a['name']) ?></td><td class="text-end"><?= fmt_money($a['net']) ?></td></tr>
                    <?php endforeach; ?>
                    <tr><td>Retained Earnings (Net Profit)</td><td class="text-end"><?= fmt_money($netProfit) ?></td></tr>
                    </tbody>
                    <tfoot><tr class="table-light"><td class="fw-bold">Total Liabilities &amp; Equity</td><td class="text-end fw-bold"><?= fmt_money($totalLiabEquity) ?></td></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-body text-center">
        <div class="text-muted small">BALANCE CHECK (Assets - Liab &amp; Equity)</div>
        <div class="fs-4 fw-bold <?= abs($totalAssets - $totalLiabEquity) < 0.01 ? 'text-success' : 'text-danger' ?>"><?= fmt_money($totalAssets - $totalLiabEquity) ?></div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

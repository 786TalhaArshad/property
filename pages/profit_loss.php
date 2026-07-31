<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Profit & Loss';
$active = 'profit_loss';

$from = $_GET['from'] ?? date('Y-01-01');
$to = $_GET['to'] ?? date('Y-m-d');

$income = db_all("SELECT a.code, a.name,
                  (SELECT COALESCE(SUM(vi.credit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted' AND v.voucher_date BETWEEN ? AND ?) AS cr,
                  (SELECT COALESCE(SUM(vi.debit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted' AND v.voucher_date BETWEEN ? AND ?) AS dr
                  FROM chart_of_accounts a WHERE a.account_type = 'income' ORDER BY a.code", [$from, $to, $from, $to]);
$expense = db_all("SELECT a.code, a.name,
                  (SELECT COALESCE(SUM(vi.debit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted' AND v.voucher_date BETWEEN ? AND ?) AS dr,
                  (SELECT COALESCE(SUM(vi.credit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted' AND v.voucher_date BETWEEN ? AND ?) AS cr
                  FROM chart_of_accounts a WHERE a.account_type = 'expense' ORDER BY a.code", [$from, $to, $from, $to]);

$totalIncome = 0.0;
foreach ($income as $r) {
    $totalIncome += (float)$r['cr'] - (float)$r['dr'];
}
$totalExpense = 0.0;
foreach ($expense as $r) {
    $totalExpense += (float)$r['dr'] - (float)$r['cr'];
}
$netProfit = $totalIncome - $totalExpense;
include '../includes/header.php';
?>

<form method="get" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-3"><input type="date" name="from" class="form-control" value="<?= e($from) ?>"></div>
            <div class="col-md-3"><input type="date" name="to" class="form-control" value="<?= e($to) ?>"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success-subtle"><i class="bi bi-graph-up-arrow me-2"></i>Income</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Account</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($income as $r): ?>
                        <?php $amt = (float)$r['cr'] - (float)$r['dr']; if ($amt == 0) continue; ?>
                        <tr><td><?= e($r['name']) ?></td><td class="text-end"><?= fmt_money($amt) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr class="table-light"><td class="fw-bold">Total Income</td><td class="text-end fw-bold"><?= fmt_money($totalIncome) ?></td></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-danger-subtle"><i class="bi bi-graph-down-arrow me-2"></i>Expenses</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Account</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                    <?php foreach ($expense as $r): ?>
                        <?php $amt = (float)$r['dr'] - (float)$r['cr']; if ($amt == 0) continue; ?>
                        <tr><td><?= e($r['name']) ?></td><td class="text-end"><?= fmt_money($amt) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr class="table-light"><td class="fw-bold">Total Expenses</td><td class="text-end fw-bold"><?= fmt_money($totalExpense) ?></td></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <div class="text-muted small">PERIOD</div>
            <div class="fw-medium"><?= fmt_date($from) ?> - <?= fmt_date($to) ?></div>
        </div>
        <div class="text-center">
            <div class="text-muted small">NET PROFIT</div>
            <div class="fs-3 fw-bold <?= $netProfit >= 0 ? 'text-success' : 'text-danger' ?>"><?= fmt_money($netProfit) ?></div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

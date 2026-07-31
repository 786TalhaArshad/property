<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Trial Balance';
$active = 'trial_balance';

$asof = $_GET['asof'] ?? date('Y-m-d');
$records = db_all("SELECT a.id, a.code, a.name, a.account_type, a.opening_balance,
                   (SELECT COALESCE(SUM(vi.debit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted' AND v.voucher_date <= ?) AS dr,
                   (SELECT COALESCE(SUM(vi.credit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted' AND v.voucher_date <= ?) AS cr
                   FROM chart_of_accounts a ORDER BY a.account_type, a.code", [$asof, $asof]);

$totalDr = 0.0;
$totalCr = 0.0;
$totalBalDr = 0.0;
$totalBalCr = 0.0;
include '../includes/header.php';
?>

<form method="get" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-0">As of</label>
            </div>
            <div class="col-md-3"><input type="date" name="asof" class="form-control" value="<?= e($asof) ?>"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-header"><i class="bi bi-columns-gap me-2"></i>Trial Balance as of <?= fmt_date($asof) ?></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th style="width:50px">#</th><th>Code</th><th>Account</th><th>Type</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <?php
                    $dr = (float)$r['dr'];
                    $cr = (float)$r['cr'];
                    $ob = (float)$r['opening_balance'];
                    $net = $ob + $dr - $cr;
                    $isDebit = in_array($r['account_type'], ['asset', 'expense']);
                    $balDr = $isDebit && $net > 0 ? $net : 0;
                    $balCr = !$isDebit && $net > 0 ? $net : ($isDebit && $net < 0 ? -$net : 0);
                    if (!$isDebit && $net < 0) { $balDr = -$net; }
                    $totalDr += $dr;
                    $totalCr += $cr;
                    $totalBalDr += $balDr;
                    $totalBalCr += $balCr;
                    ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-medium"><?= e($r['code']) ?></td>
                        <td><?= e($r['name']) ?></td>
                        <td><span class="badge bg-light text-dark border text-capitalize"><?= e($r['account_type']) ?></span></td>
                        <td class="text-end"><?= fmt_money($dr) ?></td>
                        <td class="text-end"><?= fmt_money($cr) ?></td>
                        <td class="text-end fw-medium"><?= fmt_money($net) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr class="table-light">
                    <td colspan="4" class="text-end fw-bold">Totals</td>
                    <td class="text-end fw-bold"><?= fmt_money($totalDr) ?></td>
                    <td class="text-end fw-bold"><?= fmt_money($totalCr) ?></td>
                    <td class="text-end fw-bold"><?= fmt_money($totalBalDr - $totalBalCr) ?></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

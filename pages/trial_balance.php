<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Trial Balance';
$active = 'trial_balance';

$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? date('Y-m-d'));
$project_id = (int)($_GET['project_id'] ?? active_project_id());

$projCond = $project_id > 0 ? ' AND v.project_id = ?' : '';
$fromCond = $from !== '' ? ' AND v.voucher_date < ?' : '';
$periodCond = $from !== '' ? ' AND v.voucher_date BETWEEN ? AND ?' : ' AND v.voucher_date <= ?';

$sql = "SELECT a.id, a.code, a.name, a.account_type, a.opening_balance,
        (SELECT COALESCE(SUM(vi.debit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted'" . $fromCond . $projCond . ") AS pre_dr,
        (SELECT COALESCE(SUM(vi.credit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted'" . $fromCond . $projCond . ") AS pre_cr,
        (SELECT COALESCE(SUM(vi.debit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted'" . $periodCond . $projCond . ") AS dr,
        (SELECT COALESCE(SUM(vi.credit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted'" . $periodCond . $projCond . ") AS cr
        FROM chart_of_accounts a ORDER BY a.account_type, a.code";
$params = [];
if ($from !== '') { $params[] = $from; }
if ($project_id > 0) { $params[] = $project_id; }
if ($from !== '') { $params[] = $from; }
if ($project_id > 0) { $params[] = $project_id; }
if ($from !== '') { $params[] = $from; $params[] = $to; } else { $params[] = $to; }
if ($project_id > 0) { $params[] = $project_id; }
if ($from !== '') { $params[] = $from; $params[] = $to; } else { $params[] = $to; }
if ($project_id > 0) { $params[] = $project_id; }
$records = db_all($sql, $params);

$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
$projectName = '';
foreach ($projects as $p) {
    if ((int)$p['id'] === $project_id) {
        $projectName = $p['name'];
        break;
    }
}

$totalDr = 0.0;
$totalCr = 0.0;
$totalBalDr = 0.0;
$totalBalCr = 0.0;
include '../includes/header.php';
?>

<form method="get" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-0">Project</label>
                <select name="project_id" class="form-select form-select-sm">
                    <option value="0">All Projects</option>
                    <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-0">Start Date</label>
                <input type="date" name="from" class="form-control" value="<?= e($from) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-0">End Date</label>
                <input type="date" name="to" class="form-control" value="<?= e($to) ?>">
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-header"><i class="bi bi-columns-gap me-2"></i>Trial Balance <?= $from ? 'from ' . fmt_date($from) . ' to ' . fmt_date($to) : 'as of ' . fmt_date($to) ?><?= $projectName ? ' &bull; ' . e($projectName) : '' ?></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th style="width:50px">#</th><th>Code</th><th>Account</th><th>Type</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <?php
                    $dr = (float)$r['dr'];
                    $cr = (float)$r['cr'];
                    $preDr = (float)$r['pre_dr'];
                    $preCr = (float)$r['pre_cr'];
                    $ob = $project_id > 0 ? 0 : (float)$r['opening_balance'];
                    $net = $ob + $preDr - $preCr + $dr - $cr;
                    if ($project_id > 0 && $net == 0) continue;
                    $balDr = $net > 0 ? $net : 0;
                    $balCr = $net < 0 ? -$net : 0;
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

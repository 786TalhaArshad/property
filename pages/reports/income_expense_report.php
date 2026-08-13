<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Income / Expense Report';
$active = 'reports';

$from = $_GET['from'] ?? date('Y-01-01');
$to = $_GET['to'] ?? date('Y-m-d');
$project_id = (int)($_GET['project_id'] ?? active_project_id());

$monthly = db_all("SELECT DATE_FORMAT(v.voucher_date, '%Y-%m') AS ym,
                   SUM(CASE WHEN a.account_type = 'income' THEN vi.credit ELSE 0 END) AS income,
                   SUM(CASE WHEN a.account_type = 'expense' THEN vi.debit ELSE 0 END) AS expense
                   FROM vouchers v
                   JOIN voucher_items vi ON vi.voucher_id = v.id
                   JOIN chart_of_accounts a ON a.id = vi.account_id
                   WHERE v.status = 'posted' AND v.voucher_date BETWEEN ? AND ?
                   AND (? = 0 OR v.project_id = ?)
                   GROUP BY ym ORDER BY ym", [$from, $to, $project_id, $project_id]);

$byAccount = db_all("SELECT a.code, a.name, a.account_type,
                     SUM(CASE WHEN a.account_type = 'income' THEN vi.credit - vi.debit ELSE 0 END) AS income,
                     SUM(CASE WHEN a.account_type = 'expense' THEN vi.debit - vi.credit ELSE 0 END) AS expense
                     FROM vouchers v
                     JOIN voucher_items vi ON vi.voucher_id = v.id
                     JOIN chart_of_accounts a ON a.id = vi.account_id
                     WHERE v.status = 'posted' AND v.voucher_date BETWEEN ? AND ?
                     AND (? = 0 OR v.project_id = ?)
                     GROUP BY a.id ORDER BY a.code", [$from, $to, $project_id, $project_id]);

$totIncome = 0.0; $totExpense = 0.0;
foreach ($monthly as $m) {
    $totIncome += (float)$m['income'];
    $totExpense += (float)$m['expense'];
}
$net = $totIncome - $totExpense;
$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
include '../../includes/header.php';
?>

<form method="get" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-2"><input type="date" name="from" class="form-control" value="<?= e($from) ?>"></div>
            <div class="col-md-2"><input type="date" name="to" class="form-control" value="<?= e($to) ?>"></div>
            <div class="col-md-3">
                <select name="project_id" class="form-select">
                    <option value="">All Projects</option>
                    <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div><div><div class="stat-label">INCOME</div><div class="stat-value"><?= fmt_money($totIncome) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-red"><div class="stat-body"><div class="stat-icon"><i class="bi bi-graph-down-arrow"></i></div><div><div class="stat-label">EXPENSE</div><div class="stat-value"><?= fmt_money($totExpense) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">NET PROFIT</div><div class="stat-value"><?= fmt_money($net) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-cyan"><div class="stat-body"><div class="stat-icon"><i class="bi bi-percent"></i></div><div><div class="stat-label">MARGIN</div><div class="stat-value"><?= $totIncome > 0 ? number_format($net / $totIncome * 100, 1) : '0.0' ?>%</div></div></div></div></div>
</div>

<div class="row g-3">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-calendar3 me-2"></i>Monthly Summary</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Month</th><th class="text-end">Income</th><th class="text-end">Expense</th><th class="text-end">Net</th></tr></thead>
                        <tbody>
                        <?php foreach ($monthly as $m): ?>
                            <?php $n = (float)$m['income'] - (float)$m['expense']; ?>
                            <tr><td><?= date('M Y', strtotime($m['ym'] . '-01')) ?></td><td class="text-end text-success"><?= fmt_money($m['income']) ?></td><td class="text-end text-danger"><?= fmt_money($m['expense']) ?></td><td class="text-end fw-bold"><?= fmt_money($n) ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (!$monthly): ?><tr><td colspan="4"><div class="empty-state"><i class="bi bi-inbox"></i><p>No posted vouchers in this period</p></div></td></tr><?php endif; ?>
                        </tbody>
                        <tfoot><tr class="table-light"><td class="fw-bold">Total</td><td class="text-end fw-bold"><?= fmt_money($totIncome) ?></td><td class="text-end fw-bold"><?= fmt_money($totExpense) ?></td><td class="text-end fw-bold"><?= fmt_money($net) ?></td></tr></tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-diagram-3 me-2"></i>By Account</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Account</th><th class="text-end">Income</th><th class="text-end">Expense</th></tr></thead>
                        <tbody>
                        <?php foreach ($byAccount as $a): ?>
                            <tr><td><span class="badge bg-light text-dark border me-1"><?= e($a['code']) ?></span><?= e($a['name']) ?></td>
                                <td class="text-end <?= (float)$a['income'] ? 'text-success' : '' ?>"><?= (float)$a['income'] ? fmt_money($a['income']) : '-' ?></td>
                                <td class="text-end <?= (float)$a['expense'] ? 'text-danger' : '' ?>"><?= (float)$a['expense'] ? fmt_money($a['expense']) : '-' ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (!$byAccount): ?><tr><td colspan="3"><div class="empty-state"><i class="bi bi-inbox"></i><p>No transactions</p></div></td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

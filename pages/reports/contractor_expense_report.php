<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Contractor Expense Report';
$active = 'reports';

$from = $_GET['from'] ?? date('Y-01-01');
$to = $_GET['to'] ?? date('Y-m-d');
$project_id = (int)($_GET['project_id'] ?? active_project_id());

$projects = db_all("SELECT id, name FROM projects ORDER BY name");
$projectName = '';
foreach ($projects as $p) { if ((int)$p['id'] === $project_id) { $projectName = $p['name']; break; } }

$whereExtra = "";
$extraParams = [];
if ($project_id) { $whereExtra = " AND ce.project_id = ?"; $extraParams[] = $project_id; }

$contractors = db_all("SELECT c.id, c.full_name, c.contractor_no, c.company,
                       (SELECT COALESCE(SUM(ce.amount),0) FROM contractor_entries ce WHERE ce.contractor_id = c.id AND ce.entry_type = 'payable' AND ce.entry_date BETWEEN ? AND ? $whereExtra) AS total_payable,
                       (SELECT COALESCE(SUM(ce.amount),0) FROM contractor_entries ce WHERE ce.contractor_id = c.id AND ce.entry_type = 'paid' AND ce.entry_date BETWEEN ? AND ? $whereExtra) AS total_paid
                       FROM contractors c WHERE c.status = 1
                       HAVING total_payable > 0 OR total_paid > 0
                       ORDER BY (total_payable - total_paid) DESC",
                       array_merge([$from, $to], $extraParams, [$from, $to], $extraParams));

$totalPayable = 0.0; $totalPaid = 0.0;
foreach ($contractors as $c) {
    $totalPayable += (float)$c['total_payable'];
    $totalPaid += (float)$c['total_paid'];
}
include '../../includes/header.php';
?>

<style>
@media print { .no-print,.sidebar,.main-header,.main-footer,.quick-action-btn{display:none!important}.main-content{margin:0!important;padding:10px!important}.card{border:none!important;box-shadow:none!important}body{font-size:11px}.table td,.table th{padding:4px 6px!important;font-size:11px} }
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-hammer me-2"></i>Contractor Expense Report</h5>
    <div class="ms-auto"><button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print</button></div>
</div>

<form method="get" class="card mb-3 no-print">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-2"><label class="form-label small text-muted mb-0">From</label><input type="date" name="from" class="form-control" value="<?= e($from) ?>"></div>
            <div class="col-md-2"><label class="form-label small text-muted mb-0">To</label><input type="date" name="to" class="form-control" value="<?= e($to) ?>"></div>
            <div class="col-md-3"><label class="form-label small text-muted mb-0">Project</label>
                <select name="project_id" class="form-select"><option value="">All Projects</option><?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?></select>
            </div>
            <div class="col-md-2"><label class="form-label mb-0">&nbsp;</label><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="card print-only" style="display:none"><div class="text-center mb-2"><h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5><div class="small text-muted">Contractor Expense Report from <?= fmt_date($from) ?> to <?= fmt_date($to) ?><?= $projectName ? ' | ' . e($projectName) : '' ?></div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-hammer"></i></div><div><div class="stat-label">CONTRACTORS</div><div class="stat-value"><?= count($contractors) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">TOTAL PAYABLE</div><div class="stat-value"><?= fmt_money($totalPayable) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-check-circle"></i></div><div><div class="stat-label">TOTAL PAID</div><div class="stat-value"><?= fmt_money($totalPaid) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-red"><div class="stat-body"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-label">BALANCE</div><div class="stat-value"><?= fmt_money($totalPayable - $totalPaid) ?></div></div></div></div></div>
</div>

<div class="card">
    <div class="card-header no-print"><i class="bi bi-table me-2"></i>Contractor Expenses<?= $projectName ? ' &bull; ' . e($projectName) : '' ?></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>#</th><th>Contractor</th><th>Code</th><th>Company</th><th class="text-end">Payable</th><th class="text-end">Paid</th><th class="text-end">Balance</th></tr></thead>
                <tbody>
                <?php foreach ($contractors as $i => $c): $bal = (float)$c['total_payable'] - (float)$c['total_paid']; ?>
                    <tr><td><?= $i + 1 ?></td><td class="fw-medium"><?= e($c['full_name']) ?></td><td class="small"><?= e($c['contractor_no']) ?></td><td><?= e($c['company'] ?? '-') ?></td><td class="text-end"><?= fmt_money($c['total_payable']) ?></td><td class="text-end text-success"><?= fmt_money($c['total_paid']) ?></td><td class="text-end fw-bold <?= $bal > 0 ? 'text-danger' : '' ?>"><?= fmt_money($bal) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$contractors): ?><tr><td colspan="7"><div class="empty-state"><i class="bi bi-inbox"></i><p>No contractor expenses</p></div></td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr class="table-light"><td colspan="4" class="fw-bold">Total</td><td class="text-end fw-bold"><?= fmt_money($totalPayable) ?></td><td class="text-end fw-bold text-success"><?= fmt_money($totalPaid) ?></td><td class="text-end fw-bold text-danger"><?= fmt_money($totalPayable - $totalPaid) ?></td></tr></tfoot>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

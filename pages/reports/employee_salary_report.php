<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Employee Salary Report';
$active = 'reports';

$from = $_GET['from'] ?? date('Y-01-01');
$to = $_GET['to'] ?? date('Y-m-d');

$employees = db_all("SELECT e.id, e.full_name, e.employee_no, e.designation, e.department, e.monthly_salary,
                     (SELECT COALESCE(SUM(ee.amount),0) FROM employee_entries ee WHERE ee.employee_id = e.id AND ee.entry_type = 'payable' AND ee.entry_date BETWEEN ? AND ?) AS total_payable,
                     (SELECT COALESCE(SUM(ee.amount),0) FROM employee_entries ee WHERE ee.employee_id = e.id AND ee.entry_type = 'paid' AND ee.entry_date BETWEEN ? AND ?) AS total_paid
                     FROM employees e WHERE e.status = 1
                     ORDER BY (total_payable - total_paid) DESC", [$from, $to, $from, $to]);

$totalPayable = 0.0; $totalPaid = 0.0; $byDept = [];
foreach ($employees as $e) {
    $totalPayable += (float)$e['total_payable'];
    $totalPaid += (float)$e['total_paid'];
    $dept = $e['department'] ?: 'Unassigned';
    if (!isset($byDept[$dept])) $byDept[$dept] = ['payable' => 0, 'paid' => 0];
    $byDept[$dept]['payable'] += (float)$e['total_payable'];
    $byDept[$dept]['paid'] += (float)$e['total_paid'];
}
include '../../includes/header.php';
?>

<style>
@media print { .no-print,.sidebar,.main-header,.main-footer,.quick-action-btn{display:none!important}.main-content{margin:0!important;padding:10px!important}.card{border:none!important;box-shadow:none!important}body{font-size:11px}.table td,.table th{padding:4px 6px!important;font-size:11px} }
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Employee Salary Report</h5>
    <div class="ms-auto"><button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print</button></div>
</div>

<form method="get" class="card mb-3 no-print">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-2"><label class="form-label small text-muted mb-0">From</label><input type="date" name="from" class="form-control" value="<?= e($from) ?>"></div>
            <div class="col-md-2"><label class="form-label small text-muted mb-0">To</label><input type="date" name="to" class="form-control" value="<?= e($to) ?>"></div>
            <div class="col-md-2"><label class="form-label mb-0">&nbsp;</label><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="card print-only" style="display:none"><div class="text-center mb-2"><h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5><div class="small text-muted">Employee Salary Report from <?= fmt_date($from) ?> to <?= fmt_date($to) ?></div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-people"></i></div><div><div class="stat-label">EMPLOYEES</div><div class="stat-value"><?= count($employees) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">TOTAL PAYABLE</div><div class="stat-value"><?= fmt_money($totalPayable) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-check-circle"></i></div><div><div class="stat-label">TOTAL PAID</div><div class="stat-value"><?= fmt_money($totalPaid) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-label">BALANCE</div><div class="stat-value"><?= fmt_money($totalPayable - $totalPaid) ?></div></div></div></div></div>
</div>

<div class="card">
    <div class="card-header no-print"><i class="bi bi-table me-2"></i>Employee Salaries</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>#</th><th>Employee</th><th>Code</th><th>Designation</th><th>Department</th><th class="text-end">Monthly Salary</th><th class="text-end">Payable</th><th class="text-end">Paid</th><th class="text-end">Balance</th></tr></thead>
                <tbody>
                <?php foreach ($employees as $i => $e): $bal = (float)$e['total_payable'] - (float)$e['total_paid']; ?>
                    <tr><td><?= $i + 1 ?></td><td class="fw-medium"><?= e($e['full_name']) ?></td><td class="small"><?= e($e['employee_no']) ?></td><td><?= e($e['designation'] ?? '-') ?></td><td class="small"><?= e($e['department'] ?? '-') ?></td><td class="text-end"><?= fmt_money($e['monthly_salary']) ?></td><td class="text-end"><?= fmt_money($e['total_payable']) ?></td><td class="text-end text-success"><?= fmt_money($e['total_paid']) ?></td><td class="text-end fw-bold <?= $bal > 0 ? 'text-danger' : '' ?>"><?= fmt_money($bal) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$employees): ?><tr><td colspan="9"><div class="empty-state"><i class="bi bi-inbox"></i><p>No employees</p></div></td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr class="table-light"><td colspan="6" class="fw-bold">Total</td><td class="text-end fw-bold"><?= fmt_money($totalPayable) ?></td><td class="text-end fw-bold text-success"><?= fmt_money($totalPaid) ?></td><td class="text-end fw-bold text-danger"><?= fmt_money($totalPayable - $totalPaid) ?></td></tr></tfoot>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

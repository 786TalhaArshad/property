<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Rent Roll Report';
$active = 'reports';

$project_id = (int)($_GET['project_id'] ?? active_project_id());
$asOf = $_GET['as_of'] ?? date('Y-m-d');

$where = "ra.status IN ('active','renewed')";
$params = [];
if ($project_id) { $where .= " AND p.project_id = ?"; $params[] = $project_id; }

$agreements = db_all("SELECT ra.*, p.property_no, t.full_name AS tenant_name, t.emergency_contact AS phone, o.full_name AS owner_name, pr.name AS project_name,
                      (SELECT COALESCE(SUM(rs.rent_amount),0) FROM rent_schedule rs WHERE rs.agreement_id = ra.id AND rs.due_date <= ?) AS total_billed,
                      (SELECT COALESCE(SUM(rs.paid_amount),0) FROM rent_schedule rs WHERE rs.agreement_id = ra.id AND rs.due_date <= ?) AS total_paid,
                      (SELECT COUNT(*) FROM rent_schedule rs WHERE rs.agreement_id = ra.id AND rs.status != 'paid' AND rs.due_date <= ?) AS overdue_months
                      FROM rental_agreements ra
                      JOIN properties p ON p.id = ra.property_id
                      JOIN tenants t ON t.id = ra.tenant_id
                      LEFT JOIN owners o ON o.id = ra.owner_id
                      LEFT JOIN projects pr ON pr.id = p.project_id
                      WHERE $where
                      ORDER BY pr.name, p.property_no", array_merge([$asOf, $asOf, $asOf], $params));

$totalRent = 0.0; $totalBilled = 0.0; $totalPaid = 0.0;
foreach ($agreements as $a) {
    $totalRent += (float)$a['monthly_rent'];
    $totalBilled += (float)$a['total_billed'];
    $totalPaid += (float)$a['total_paid'];
}
$projects = db_all("SELECT id, name FROM projects ORDER BY name");
$projectName = '';
foreach ($projects as $p) { if ((int)$p['id'] === $project_id) { $projectName = $p['name']; break; } }
include '../../includes/header.php';
?>

<style>
@media print { .no-print,.sidebar,.main-header,.main-footer,.quick-action-btn{display:none!important}.main-content{margin:0!important;padding:10px!important}.card{border:none!important;box-shadow:none!important}body{font-size:11px}.table td,.table th{padding:4px 6px!important;font-size:11px} }
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-house-door me-2"></i>Rent Roll Report</h5>
    <div class="ms-auto"><button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print</button></div>
</div>

<form method="get" class="card mb-3 no-print">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-2"><label class="form-label small text-muted mb-0">As of</label><input type="date" name="as_of" class="form-control" value="<?= e($asOf) ?>"></div>
            <div class="col-md-3"><label class="form-label small text-muted mb-0">Project</label>
                <select name="project_id" class="form-select"><option value="">All Projects</option><?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?></select>
            </div>
            <div class="col-md-2"><label class="form-label mb-0">&nbsp;</label><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="card print-only" style="display:none"><div class="text-center mb-2"><h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5><div class="small text-muted">Rent Roll Report as of <?= fmt_date($asOf) ?><?= $projectName ? ' | ' . e($projectName) : '' ?></div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-house-door"></i></div><div><div class="stat-label">TOTAL TENANTS</div><div class="stat-value"><?= count($agreements) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">MONTHLY RENT</div><div class="stat-value"><?= fmt_money($totalRent) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-check-circle"></i></div><div><div class="stat-label">TOTAL COLLECTED</div><div class="stat-value"><?= fmt_money($totalPaid) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-red"><div class="stat-body"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-label">TOTAL OUTSTANDING</div><div class="stat-value"><?= fmt_money($totalBilled - $totalPaid) ?></div></div></div></div></div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center no-print">
        <span><i class="bi bi-table me-2"></i>Rent Roll<?= $projectName ? ' &bull; ' . e($projectName) : '' ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Property</th><th>Tenant</th><th>Phone</th><th>Owner</th><th>Project</th><th class="text-end">Monthly Rent</th><th class="text-end">Billed</th><th class="text-end">Collected</th><th class="text-end">Outstanding</th><th>Overdue</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($agreements as $a):
                    $out = (float)$a['total_billed'] - (float)$a['total_paid'];
                ?>
                    <tr><td class="fw-medium"><?= e($a['property_no']) ?></td><td><?= e($a['tenant_name']) ?></td><td class="small"><?= e($a['phone']) ?></td><td class="small"><?= e($a['owner_name'] ?? '-') ?></td><td class="small"><?= e($a['project_name'] ?? '-') ?></td><td class="text-end"><?= fmt_money($a['monthly_rent']) ?></td><td class="text-end"><?= fmt_money($a['total_billed']) ?></td><td class="text-end"><?= fmt_money($a['total_paid']) ?></td><td class="text-end <?= $out > 0 ? 'text-danger fw-bold' : '' ?>"><?= fmt_money($out) ?></td><td class="text-center"><?= $a['overdue_months'] > 0 ? '<span class="badge bg-danger">' . $a['overdue_months'] . '</span>' : '<span class="badge bg-success">0</span>' ?></td><td><?= status_badge($a['status']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$agreements): ?><tr><td colspan="11"><div class="empty-state"><i class="bi bi-inbox"></i><p>No active rentals</p></div></td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr class="table-light"><td colspan="6" class="fw-bold">Totals</td><td class="text-end fw-bold"><?= fmt_money($totalBilled) ?></td><td class="text-end fw-bold"><?= fmt_money($totalPaid) ?></td><td class="text-end fw-bold text-danger"><?= fmt_money($totalBilled - $totalPaid) ?></td><td></td><td></td></tr></tfoot>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

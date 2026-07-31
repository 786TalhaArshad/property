<?php
require_once '../includes/auth.php';
require_login();
require_permission('tenants.view');
$title = 'Tenant Details';
$active = 'tenants';

$id = (int)($_GET['id'] ?? 0);
$tenant = db_get("SELECT * FROM tenants WHERE id = ?", [$id]);
if (!$tenant) {
    flash('danger', 'Tenant not found.');
    redirect('tenants.php');
}

$agreements = db_all("SELECT ra.*, p.property_no, o.full_name AS owner_name FROM rental_agreements ra JOIN properties p ON p.id = ra.property_id LEFT JOIN owners o ON o.id = ra.owner_id WHERE ra.tenant_id = ? ORDER BY ra.start_date DESC", [$id]);
$scheduleIds = [];
foreach ($agreements as $a) {
    $scheduleIds[] = (int)$a['id'];
}
$due = 0.0; $paid = 0.0;
$collections = [];
foreach ($agreements as $a) {
    $rs = db_all("SELECT COALESCE(SUM(rs.rent_amount + rs.late_charges),0) d FROM rent_schedule rs WHERE rs.agreement_id = ?", [$a['id']]);
    $due += (float)$rs[0]['d'];
    $cols = db_all("SELECT rc.*, rs.period, p.property_no FROM rent_collections rc JOIN rent_schedule rs ON rs.id = rc.schedule_id JOIN rental_agreements ra ON ra.id = rc.agreement_id JOIN properties p ON p.id = ra.property_id WHERE rc.agreement_id = ? ORDER BY rc.collection_date DESC", [$a['id']]);
    foreach ($cols as $c) {
        $paid += (float)$c['amount'];
        $collections[] = $c;
    }
}
include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="tenants.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= e($tenant['full_name']) ?></h5>
    <?php if ($tenant['status']): ?><span class="badge bg-success">Active</span><?php else: ?><span class="badge bg-secondary">Inactive</span><?php endif; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-house-heart"></i></div><div><div class="stat-label">AGREEMENTS</div><div class="stat-value"><?= count($agreements) ?></div></div></div></div></div>
    <div class="col-md-4"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">RENT PAID</div><div class="stat-value"><?= fmt_money($paid) ?></div></div></div></div></div>
    <div class="col-md-4"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div><div class="stat-label">TOTAL DUE</div><div class="stat-value"><?= fmt_money($due - $paid) ?></div></div></div></div></div>
</div>

<ul class="nav nav-pills mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tProfile">Profile</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tAgreements">Agreements (<?= count($agreements) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tLedger">Ledger</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tProfile">
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3"><div class="text-muted small">Tenant No</div><div class="fw-medium"><?= e($tenant['tenant_no']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">CNIC</div><div class="fw-medium"><?= e($tenant['cnic'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Police Verification</div><div class="fw-medium"><?= status_badge($tenant['police_verification']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Occupation</div><div class="fw-medium"><?= e($tenant['occupation'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Company</div><div class="fw-medium"><?= e($tenant['company'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Emergency Name</div><div class="fw-medium"><?= e($tenant['emergency_name'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Emergency Contact</div><div class="fw-medium"><?= e($tenant['emergency_contact'] ?? '-') ?></div></div>
                    <div class="col-md-6"><div class="text-muted small">Address</div><div class="fw-medium"><?= e($tenant['address'] ?? '-') ?></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tAgreements">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Agreement</th><th>Property</th><th>Owner</th><th>Period</th><th>Monthly Rent</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($agreements as $a): ?>
                            <tr>
                                <td><a href="rental_agreement_view.php?id=<?= $a['id'] ?>"><?= e($a['agreement_no']) ?></a></td>
                                <td><?= e($a['property_no']) ?></td>
                                <td><?= e($a['owner_name'] ?? '-') ?></td>
                                <td><?= fmt_date($a['start_date']) ?> - <?= fmt_date($a['end_date']) ?></td>
                                <td><?= fmt_money($a['monthly_rent']) ?></td>
                                <td><?= status_badge($a['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$agreements): ?><tr><td colspan="6" class="text-center text-muted py-4">No agreements</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tLedger">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Property</th><th>Period</th><th>Credit</th><th>Balance</th></tr></thead>
                        <tbody>
                        <?php
                        $bal = 0.0;
                        foreach ($collections as $c) {
                            $bal += (float)$c['amount'];
                            echo '<tr><td>' . fmt_date($c['collection_date']) . '</td><td>' . e($c['property_no']) . '</td><td>' . e($c['period']) . '</td><td>' . fmt_money($c['amount']) . '</td><td>' . fmt_money($bal) . '</td></tr>';
                        }
                        if (!$collections) {
                            echo '<tr><td colspan="5" class="text-center text-muted py-4">No collections yet</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_login();
require_permission('owners.view');
$title = 'Owner Details';
$active = 'owners';

$id = (int)($_GET['id'] ?? 0);
$owner = db_get("SELECT o.*, b.name AS bank_name FROM owners o LEFT JOIN banks b ON b.id = o.bank_id WHERE o.id = ?", [$id]);
if (!$owner) {
    flash('danger', 'Owner not found.');
    redirect('owners.php');
}

$properties = db_all("SELECT p.*, pt.name AS type_name, pr.name AS project_name FROM properties p LEFT JOIN property_types pt ON pt.id = p.property_type_id LEFT JOIN projects pr ON pr.id = p.project_id WHERE p.owner_id = ?", [$id]);
$agreements = db_all("SELECT ra.*, p.property_no, t.full_name AS tenant_name FROM rental_agreements ra JOIN properties p ON p.id = ra.property_id JOIN tenants t ON t.id = ra.tenant_id WHERE ra.owner_id = ?", [$id]);
$agreementIds = array_column($agreements, 'id');

$rentIncome = 0.0;
if ($agreementIds) {
    $in = implode(',', array_map('intval', $agreementIds));
    $rentIncome = (float)db_get("SELECT COALESCE(SUM(rc.amount),0) amt FROM rent_collections rc WHERE rc.agreement_id IN ($in)")['amt'];
}
$settlements = db_all("SELECT * FROM owner_settlements WHERE owner_id = ? ORDER BY settlement_date DESC", [$id]);
$settled = 0.0;
foreach ($settlements as $s) {
    $settled += (float)$s['settlement_amount'];
}
$balance = $rentIncome - $settled;

include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="owners.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= e($owner['full_name']) ?></h5>
    <?php if ($owner['status']): ?><span class="badge bg-success">Active</span><?php else: ?><span class="badge bg-secondary">Inactive</span><?php endif; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-house-door"></i></div><div><div class="stat-label">PROPERTIES</div><div class="stat-value"><?= count($properties) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">RENT INCOME</div><div class="stat-value"><?= fmt_money($rentIncome) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-wallet2"></i></div><div><div class="stat-label">SETTLED</div><div class="stat-value"><?= fmt_money($settled) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card <?= $balance > 0 ? 'bg-grad-orange' : 'bg-grad-cyan' ?>"><div class="stat-body"><div class="stat-icon"><i class="bi bi-arrow-repeat"></i></div><div><div class="stat-label">BALANCE DUE</div><div class="stat-value"><?= fmt_money($balance) ?></div></div></div></div></div>
</div>

<ul class="nav nav-pills mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#oProfile">Profile</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#oProps">Properties (<?= count($properties) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#oLedger">Ledger</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="oProfile">
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3"><div class="text-muted small">Owner No</div><div class="fw-medium"><?= e($owner['owner_no']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">CNIC</div><div class="fw-medium"><?= e($owner['cnic'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Phone</div><div class="fw-medium"><?= e($owner['phone'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">WhatsApp</div><div class="fw-medium"><?= e($owner['whatsapp'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Email</div><div class="fw-medium"><?= e($owner['email'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Commission Rate</div><div class="fw-medium"><?= fmt_num($owner['commission_rate']) ?>%</div></div>
                    <div class="col-md-3"><div class="text-muted small">Bank</div><div class="fw-medium"><?= e($owner['bank_name'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Account</div><div class="fw-medium"><?= e($owner['bank_account_title'] ?? '-') ?> <?= $owner['bank_account_no'] ? '(' . e($owner['bank_account_no']) . ')' : '' ?></div></div>
                    <div class="col-md-6"><div class="text-muted small">Address</div><div class="fw-medium"><?= e($owner['address'] ?? '-') ?></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="oProps">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Property</th><th>Type</th><th>Project</th><th>Size</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($properties as $p): ?>
                            <tr>
                                <td><a href="property_view.php?id=<?= $p['id'] ?>"><?= e($p['property_no']) ?></a></td>
                                <td><?= e($p['type_name'] ?? '-') ?></td>
                                <td><?= e($p['project_name'] ?? '-') ?></td>
                                <td><?= fmt_num($p['size_value']) ?> <?= e($p['size_unit']) ?></td>
                                <td><?= status_badge($p['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$properties): ?><tr><td colspan="5" class="text-center text-muted py-4">No properties assigned</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="oLedger">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Description</th><th>Credit</th><th>Debit</th><th>Balance</th></tr></thead>
                        <tbody>
                        <?php
                        $bal = 0.0;
                        foreach ($agreements as $a) {
                            $rc = db_all("SELECT rc.collection_date, rc.amount, rc.reference FROM rent_collections rc WHERE rc.agreement_id = ? ORDER BY rc.collection_date", [$a['id']]);
                            foreach ($rc as $r) {
                                $bal += (float)$r['amount'];
                                echo '<tr><td>' . fmt_date($r['collection_date']) . '</td><td>Rent ' . e($a['agreement_no']) . ' - ' . e($a['property_no']) . ' (' . e($a['tenant_name']) . ')</td><td>' . fmt_money((float)$r['amount']) . '</td><td>-</td><td>' . fmt_money($bal) . '</td></tr>';
                            }
                        }
                        foreach ($settlements as $s) {
                            $bal -= (float)$s['settlement_amount'];
                            echo '<tr><td>' . fmt_date($s['settlement_date']) . '</td><td>Owner settlement ' . e($s['remarks'] ?? '') . '</td><td>-</td><td>' . fmt_money((float)$s['settlement_amount']) . '</td><td>' . fmt_money($bal) . '</td></tr>';
                        }
                        if (!$agreements && !$settlements) {
                            echo '<tr><td colspan="5" class="text-center text-muted py-4">No ledger entries yet</td></tr>';
                        }
                        ?>
                        </tbody>
                        <tfoot>
                        <tr class="table-light">
                            <td colspan="4" class="text-end fw-bold">Balance Due</td>
                            <td class="fw-bold"><?= fmt_money($bal) ?></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_login();
require_permission('owners.view');
$active = 'owners';
$canEdit = has_permission('owners.manage');

$id = (int)($_GET['id'] ?? 0);
$owner = db_get("SELECT o.*, b.name AS bank_name FROM owners o LEFT JOIN banks b ON b.id = o.bank_id WHERE o.id = ?", [$id]);
if (!$owner) {
    flash('danger', 'Owner not found.');
    redirect('owners.php');
}

$ledgerStart = trim($_GET['start_date'] ?? '');
$ledgerEnd = trim($_GET['end_date'] ?? '');
$project_id = (int)($_GET['project_id'] ?? active_project_id());
$agreement_id = (int)($_GET['agreement_id'] ?? 0);

$title = 'Owner Details - ' . $owner['full_name'];

$properties = db_all("SELECT p.*, pt.name AS type_name, pr.name AS project_name,
                      pr.id AS proj_id
                      FROM properties p
                      LEFT JOIN property_types pt ON pt.id = p.property_type_id
                      LEFT JOIN projects pr ON pr.id = p.project_id
                      WHERE p.owner_id = ?", [$id]);
$agreements = db_all("SELECT ra.*, p.property_no, p.project_id, t.full_name AS tenant_name,
                      pj.name AS project_name
                      FROM rental_agreements ra
                      JOIN properties p ON p.id = ra.property_id
                      JOIN tenants t ON t.id = ra.tenant_id
                      LEFT JOIN projects pj ON pj.id = p.project_id
                      WHERE ra.owner_id = ?
                      ORDER BY ra.id DESC", [$id]);
$agreementIds = array_column($agreements, 'id');

$ownerProjects = db_all("SELECT pj.id, pj.name, pj.status,
                         COUNT(DISTINCT ra.id) AS agreements_count
                         FROM rental_agreements ra
                         JOIN properties p ON p.id = ra.property_id
                         JOIN projects pj ON pj.id = p.project_id
                         WHERE ra.owner_id = ?
                         GROUP BY pj.id, pj.name, pj.status
                         ORDER BY pj.name", [$id]);

$paidRows = db_all("SELECT rc.collection_date, rc.amount, rc.agreement_id, rc.reference,
                    ra.agreement_no, p.property_no, t.full_name AS tenant_name,
                    p.project_id,
                    COALESCE(p.project_id, 0) AS eff_project_id
                    FROM rent_collections rc
                    JOIN rental_agreements ra ON ra.id = rc.agreement_id
                    JOIN properties p ON p.id = ra.property_id
                    JOIN tenants t ON t.id = ra.tenant_id
                    WHERE ra.owner_id = ?
                    ORDER BY rc.collection_date, rc.id", [$id]);
$settlementRows = db_all("SELECT os.*, bk.name AS bank_name
                          FROM owner_settlements os
                          LEFT JOIN banks bk ON bk.id = os.bank_id
                          WHERE os.owner_id = ?
                          ORDER BY os.settlement_date, os.id", [$id]);

$ledRent = [];
foreach ($paidRows as $pr) {
    if ($ledgerStart !== '' && $pr['collection_date'] < $ledgerStart) continue;
    if ($ledgerEnd !== '' && $pr['collection_date'] > $ledgerEnd) continue;
    if ($project_id > 0 && (int)$pr['eff_project_id'] !== $project_id) continue;
    if ($agreement_id > 0 && (int)$pr['agreement_id'] !== $agreement_id) continue;
    $ledRent[] = $pr;
}
$ledSettlements = [];
foreach ($settlementRows as $s) {
    if ($ledgerStart !== '' && $s['settlement_date'] < $ledgerStart) continue;
    if ($ledgerEnd !== '' && $s['settlement_date'] > $ledgerEnd) continue;
    $ledSettlements[] = $s;
}

$openingBalance = 0.0;
if ($ledgerStart !== '') {
    $or = (float)db_get("SELECT COALESCE(SUM(rc.amount),0) amt
                         FROM rent_collections rc
                         JOIN rental_agreements ra ON ra.id = rc.agreement_id
                         JOIN properties p ON p.id = ra.property_id
                         WHERE ra.owner_id = ? AND rc.collection_date < ?
                         AND (? = 0 OR p.project_id = ?)",
        [$id, $ledgerStart, $project_id, $project_id])['amt'];
    $os = (float)db_get("SELECT COALESCE(SUM(os.settlement_amount),0) amt
                         FROM owner_settlements os
                         WHERE os.owner_id = ? AND os.settlement_date < ?",
        [$id, $ledgerStart])['amt'];
    $openingBalance = $or - $os;
}

$totalRent = 0.0;
$totalSettled = 0.0;
foreach ($ledRent as $r) {
    $totalRent += (float)$r['amount'];
}
foreach ($ledSettlements as $s) {
    $totalSettled += (float)$s['settlement_amount'];
}
$balance = $openingBalance + $totalRent - $totalSettled;

include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="owners.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= e($owner['full_name']) ?></h5>
    <?php if ($owner['status']): ?><span class="badge bg-success">Active</span><?php else: ?><span class="badge bg-secondary">Inactive</span><?php endif; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-house-door"></i></div><div><div class="stat-label">PROPERTIES</div><div class="stat-value"><?= count($properties) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">RENT INCOME</div><div class="stat-value"><?= fmt_money($totalRent) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-wallet2"></i></div><div><div class="stat-label">SETTLED / PAID</div><div class="stat-value"><?= fmt_money($totalSettled) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card <?= $balance > 0 ? 'bg-grad-orange' : 'bg-grad-cyan' ?>"><div class="stat-body"><div class="stat-icon"><i class="bi bi-arrow-repeat"></i></div><div><div class="stat-label">BALANCE DUE</div><div class="stat-value"><?= fmt_money($balance) ?></div></div></div></div></div>
</div>

<ul class="nav nav-pills mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#oProfile">Profile</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#oProps">Properties (<?= count($properties) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#oProjects">Projects (<?= count($ownerProjects) ?>)</button></li>
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

    <div class="tab-pane fade" id="oProjects">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Project</th><th>Agreements</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($ownerProjects as $pj): ?>
                            <tr>
                                <td><a href="project_view.php?id=<?= $pj['id'] ?>"><?= e($pj['name']) ?></a></td>
                                <td><?= (int)$pj['agreements_count'] ?></td>
                                <td class="text-end">
                                    <a href="owner_view.php?id=<?= $id ?>&project_id=<?= $pj['id'] ?>#oLedger" class="btn btn-sm btn-outline-primary"><i class="bi bi-book"></i> Ledger</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$ownerProjects): ?><tr><td colspan="3" class="text-center text-muted py-4">No projects yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="oLedger">
        <div class="card">
            <div class="card-body">
                <form method="get" action="owner_view.php#oLedger" class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <select name="project_id" class="form-select form-select-sm" style="max-width:220px">
                        <option value="0">All Projects</option>
                        <?php foreach ($ownerProjects as $pj): ?>
                            <option value="<?= $pj['id'] ?>" <?= $project_id === (int)$pj['id'] ? 'selected' : '' ?>><?= e($pj['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="agreement_id" class="form-select form-select-sm" style="max-width:220px">
                        <option value="0">All Agreements</option>
                        <?php foreach ($agreements as $a): ?>
                            <option value="<?= $a['id'] ?>" <?= $agreement_id === (int)$a['id'] ? 'selected' : '' ?>><?= e($a['agreement_no']) ?> - <?= e($a['property_no']) ?> (<?= e($a['tenant_name']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <div class="input-group input-group-sm" style="max-width:170px">
                        <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                        <input type="date" name="start_date" class="form-control" value="<?= e($ledgerStart) ?>">
                    </div>
                    <div class="input-group input-group-sm" style="max-width:170px">
                        <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                        <input type="date" name="end_date" class="form-control" value="<?= e($ledgerEnd) ?>">
                    </div>
                    <button class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="owner_view.php?id=<?= $id ?>#oLedger" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
                    <a href="owner_ledger_print.php?id=<?= $id ?>&project_id=<?= $project_id ?>&agreement_id=<?= $agreement_id ?>&start_date=<?= e($ledgerStart) ?>&end_date=<?= e($ledgerEnd) ?>" class="btn btn-outline-secondary btn-sm ms-auto" target="_blank"><i class="bi bi-printer me-1"></i> Print</a>
                </form>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Description</th><th>Agreement</th><th>Credit (Rent)</th><th>Debit (Paid)</th><th>Balance</th></tr></thead>
                        <tbody>
                        <?php
                        $bal = $openingBalance;
                        if ($ledgerStart !== '' || $ledgerEnd !== '') {
                            echo '<tr><td>' . fmt_date($ledgerStart) . '</td><td>Opening Balance</td><td>-</td><td>-</td><td>-</td><td>' . fmt_money($bal) . '</td></tr>';
                        }
                        foreach ($ledRent as $r) {
                            $bal += (float)$r['amount'];
                            echo '<tr><td>' . fmt_date($r['collection_date']) . '</td><td>Rent collected - ' . e($r['tenant_name']) . '</td><td>' . e($r['agreement_no']) . '</td><td>' . fmt_money((float)$r['amount']) . '</td><td>-</td><td>' . fmt_money($bal) . '</td></tr>';
                        }
                        foreach ($ledSettlements as $s) {
                            $bal -= (float)$s['settlement_amount'];
                            $desc = 'Owner settlement';
                            if ($s['remarks']) $desc .= ' - ' . e($s['remarks']);
                            if ($s['bank_name']) $desc .= ' (' . e($s['bank_name']) . ')';
                            echo '<tr><td>' . fmt_date($s['settlement_date']) . '</td><td>' . $desc . '</td><td>-</td><td>-</td><td>' . fmt_money((float)$s['settlement_amount']) . '</td><td>' . fmt_money($bal) . '</td></tr>';
                        }
                        if (!$ledRent && !$ledSettlements && $ledgerStart === '' && $ledgerEnd === '') {
                            echo '<tr><td colspan="6" class="text-center text-muted py-4">No ledger entries yet</td></tr>';
                        }
                        ?>
                        </tbody>
                        <tfoot>
                        <tr class="table-light">
                            <td colspan="5" class="text-end fw-bold"><?= ($ledgerStart !== '' || $ledgerEnd !== '') ? 'Closing Balance' : 'Balance Due' ?></td>
                            <td class="fw-bold"><?= fmt_money($bal) ?></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    if (location.hash) {
        $('.nav-pills .nav-link[data-bs-target="' + location.hash.replace(/[^a-zA-Z0-9_#]/g, '') + '"]').tab('show');
    }
});
</script>

<?php include '../includes/footer.php'; ?>

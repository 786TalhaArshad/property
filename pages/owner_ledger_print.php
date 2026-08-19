<?php
require_once '../includes/auth.php';
require_login();
require_permission('owners.view');

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

$agreements = db_all("SELECT ra.*, p.property_no, p.project_id, t.full_name AS tenant_name,
                      pj.name AS project_name
                      FROM rental_agreements ra
                      JOIN properties p ON p.id = ra.property_id
                      JOIN tenants t ON t.id = ra.tenant_id
                      LEFT JOIN projects pj ON pj.id = p.project_id
                      WHERE ra.owner_id = ?
                      ORDER BY ra.id DESC", [$id]);

$ownerProjects = db_all("SELECT pj.id, pj.name FROM rental_agreements ra
                         JOIN properties p ON p.id = ra.property_id
                         JOIN projects pj ON pj.id = p.project_id
                         WHERE ra.owner_id = ?
                         GROUP BY pj.id, pj.name ORDER BY pj.name", [$id]);

$paidRows = db_all("SELECT rc.collection_date, rc.amount, rc.agreement_id, rc.reference,
                    ra.agreement_no, p.property_no, t.full_name AS tenant_name,
                    p.project_id, COALESCE(p.project_id, 0) AS eff_project_id
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

$projFilter = '';
foreach ($ownerProjects as $pj) {
    if ((int)$pj['id'] === $project_id) {
        $projFilter = $pj['name'];
        break;
    }
}
$agrFilter = '';
foreach ($agreements as $a) {
    if ((int)$a['id'] === $agreement_id) {
        $agrFilter = $a['agreement_no'] . ' - ' . $a['property_no'] . ' (' . $a['tenant_name'] . ')';
        break;
    }
}

$companyInfo = [
    'name' => setting('company_name', APP_NAME),
    'tagline' => setting('company_tagline', ''),
    'address' => setting('company_address', ''),
    'phone' => setting('company_phone', ''),
    'email' => setting('company_email', ''),
    'logo' => setting('company_logo', ''),
];
$active = 'owners';
include '../includes/header.php';
?>
<style>
    body { background: #fff; }
    .print-head { border-bottom: 3px double #1c2b36; }
    .print-table { font-size: 12px; width: 100%; border-collapse: collapse; }
    .print-table th, .print-table td { border: 1px solid #444; padding: 4px 6px; }
    .print-table th { background: #e9ecef; text-align: left; }
    .total-row td { font-weight: bold; background: #f1f3f5; }
    .no-print { margin: 16px 0; }
    @media print {
        body { background: #fff; }
        .no-print { display: none !important; }
    }
</style>

<div class="no-print text-center">
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    <a class="btn btn-light" href="owner_view.php?id=<?= $id ?>#oLedger"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="print-head d-flex justify-content-between align-items-start pb-3 mb-3">
    <div class="d-flex align-items-center gap-3">
        <?php if ($companyInfo['logo']): ?>
            <img src="<?= BASE_URL ?>/assets/<?= e($companyInfo['logo']) ?>" style="max-height:60px" alt="logo">
        <?php endif; ?>
        <div>
            <h3 class="mb-0 fw-bold"><?= e($companyInfo['name']) ?></h3>
            <?php if ($companyInfo['tagline']): ?><div class="small"><?= e($companyInfo['tagline']) ?></div><?php endif; ?>
            <?php if ($companyInfo['address']): ?><div class="small text-muted"><?= e($companyInfo['address']) ?></div><?php endif; ?>
            <div class="small text-muted">
                <?= $companyInfo['phone'] ? 'Phone: ' . e($companyInfo['phone']) : '' ?>
                <?= $companyInfo['phone'] && $companyInfo['email'] ? ' &bull; ' : '' ?>
                <?= $companyInfo['email'] ? 'Email: ' . e($companyInfo['email']) : '' ?>
            </div>
        </div>
    </div>
    <div class="text-end">
        <h5 class="mb-1">Owner Ledger</h5>
        <div class="fw-medium"><?= e($owner['full_name']) ?> (<?= e($owner['owner_no']) ?>)</div>
        <div class="small text-muted">Phone: <?= e($owner['phone'] ?? '-') ?></div>
        <div class="small">Print Date: <?= fmt_date(date('Y-m-d')) ?></div>
        <div class="small">
            Project: <?= $projFilter ? e($projFilter) : 'All' ?>
            <?= $agrFilter ? ' &bull; Agreement: ' . e($agrFilter) : '' ?>
            <?php if ($ledgerStart): ?> &bull; From: <?= fmt_date($ledgerStart) ?><?php endif; ?>
            <?php if ($ledgerEnd): ?> &bull; To: <?= fmt_date($ledgerEnd) ?><?php endif; ?>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="print-table">
        <thead>
        <tr>
            <th>Date</th>
            <th>Description</th>
            <th>Agreement</th>
            <th class="text-end">Credit (Rent)</th>
            <th class="text-end">Debit (Paid)</th>
            <th class="text-end">Balance</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $bal = $openingBalance;
        if ($ledgerStart !== '' || $ledgerEnd !== '') {
            echo '<tr class="total-row"><td>' . fmt_date($ledgerStart) . '</td><td>Opening Balance</td><td>-</td><td>-</td><td>-</td><td class="text-end">' . fmt_money($bal) . '</td></tr>';
        }
        foreach ($ledRent as $r) {
            $bal += (float)$r['amount'];
            echo '<tr><td>' . fmt_date($r['collection_date']) . '</td><td>Rent collected - ' . e($r['tenant_name']) . '</td><td>' . e($r['agreement_no']) . '</td><td class="text-end">' . fmt_money((float)$r['amount']) . '</td><td>-</td><td class="text-end fw-medium">' . fmt_money($bal) . '</td></tr>';
        }
        foreach ($ledSettlements as $s) {
            $bal -= (float)$s['settlement_amount'];
            $desc = 'Owner settlement';
            if ($s['remarks']) $desc .= ' - ' . e($s['remarks']);
            if ($s['bank_name']) $desc .= ' (' . e($s['bank_name']) . ')';
            echo '<tr><td>' . fmt_date($s['settlement_date']) . '</td><td>' . $desc . '</td><td>-</td><td>-</td><td class="text-end">' . fmt_money((float)$s['settlement_amount']) . '</td><td class="text-end fw-medium">' . fmt_money($bal) . '</td></tr>';
        }
        if (!$ledRent && !$ledSettlements && $ledgerStart === '' && $ledgerEnd === '') {
            echo '<tr><td colspan="6" class="text-center text-muted py-4">No ledger entries yet</td></tr>';
        }
        ?>
        </tbody>
        <tfoot>
        <tr class="total-row">
            <td colspan="5" class="text-end fw-bold"><?= ($ledgerStart !== '' || $ledgerEnd !== '') ? 'Closing Balance' : 'Balance Due' ?></td>
            <td class="fw-bold text-end"><?= fmt_money($bal) ?></td>
        </tr>
        </tfoot>
    </table>
</div>

<?php include '../includes/footer.php'; ?>

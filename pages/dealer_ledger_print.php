<?php
require_once '../includes/auth.php';
require_login();
require_permission('dealers.view');

$id = (int)($_GET['id'] ?? 0);
$dealer = db_get("SELECT * FROM dealers WHERE id = ?", [$id]);
if (!$dealer) {
    flash('danger', 'Dealer not found.');
    redirect('dealers.php');
}

$ledgerStart = trim($_GET['start_date'] ?? '');
$ledgerEnd = trim($_GET['end_date'] ?? '');
$project_id = (int)($_GET['project_id'] ?? active_project_id());

$dealerProjects = db_all("SELECT pr.id, pr.name FROM bookings b
                          JOIN properties p ON p.id = b.property_id
                          JOIN projects pr ON pr.id = p.project_id
                          WHERE b.dealer_id = ? AND b.status <> 'cancelled'
                          GROUP BY pr.id, pr.name ORDER BY pr.name", [$id]);

$bookings = db_all("SELECT b.*, c.full_name AS customer_name, p.property_no,
                    p.project_id, COALESCE(p.project_id, 0) AS eff_project_id,
                    pr.name AS project_name
                    FROM bookings b
                    JOIN customers c ON c.id = b.customer_id
                    JOIN properties p ON p.id = b.property_id
                    LEFT JOIN projects pr ON pr.id = p.project_id
                    WHERE b.dealer_id = ? AND b.status <> 'cancelled'
                    ORDER BY b.id DESC", [$id]);
$payments = db_all("SELECT dp.*, pm.name AS method_name
                    FROM dealer_payments dp
                    LEFT JOIN payment_methods pm ON pm.id = dp.payment_method_id
                    WHERE dp.dealer_id = ?
                    ORDER BY dp.payment_date DESC", [$id]);

$ledBookings = [];
foreach ($bookings as $b) {
    if ($ledgerStart !== '' && $b['booking_date'] < $ledgerStart) continue;
    if ($ledgerEnd !== '' && $b['booking_date'] > $ledgerEnd) continue;
    if ($project_id > 0 && (int)$b['eff_project_id'] !== $project_id) continue;
    $ledBookings[] = $b;
}
$ledPayments = [];
foreach ($payments as $p) {
    if ($ledgerStart !== '' && $p['payment_date'] < $ledgerStart) continue;
    if ($ledgerEnd !== '' && $p['payment_date'] > $ledgerEnd) continue;
    $ledPayments[] = $p;
}

$openingBalance = 0.0;
if ($ledgerStart !== '') {
    $oc = (float)db_get("SELECT COALESCE(SUM(b.total_price * ? / 100), 0) amt
                         FROM bookings b
                         JOIN properties p ON p.id = b.property_id
                         WHERE b.dealer_id = ? AND b.status <> 'cancelled'
                         AND b.booking_date < ?
                         AND (? = 0 OR p.project_id = ?)",
        [$dealer['commission_rate'], $id, $ledgerStart, $project_id, $project_id])['amt'];
    $op = (float)db_get("SELECT COALESCE(SUM(amount), 0) amt
                         FROM dealer_payments
                         WHERE dealer_id = ? AND payment_date < ?",
        [$id, $ledgerStart])['amt'];
    $openingBalance = $oc - $op;
}

$projFilter = '';
foreach ($dealerProjects as $pj) {
    if ((int)$pj['id'] === $project_id) {
        $projFilter = $pj['name'];
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
$active = 'dealers';
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
    <a class="btn btn-light" href="dealer_view.php?id=<?= $id ?>#dLedger"><i class="bi bi-arrow-left me-1"></i>Back</a>
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
        <h5 class="mb-1">Dealer Ledger</h5>
        <div class="fw-medium"><?= e($dealer['full_name']) ?> (<?= e($dealer['dealer_no']) ?>)</div>
        <div class="small text-muted">Phone: <?= e($dealer['phone'] ?? '-') ?> &bull; Commission: <?= fmt_num($dealer['commission_rate']) ?>%</div>
        <div class="small">Print Date: <?= fmt_date(date('Y-m-d')) ?></div>
        <div class="small">
            Project: <?= $projFilter ? e($projFilter) : 'All' ?>
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
            <th>Project</th>
            <th class="text-end">Credit (Commission)</th>
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
        foreach ($ledBookings as $b) {
            $comm = (float)$b['total_price'] * (float)$dealer['commission_rate'] / 100;
            $bal += $comm;
            echo '<tr><td>' . fmt_date($b['booking_date']) . '</td><td>Commission - ' . e($b['booking_no']) . ' (' . e($b['customer_name']) . ')</td><td>' . e($b['project_name'] ?? '-') . '</td><td class="text-end">' . fmt_money($comm) . '</td><td>-</td><td class="text-end fw-medium">' . fmt_money($bal) . '</td></tr>';
        }
        foreach ($ledPayments as $p) {
            $bal -= (float)$p['amount'];
            $desc = 'Payment received';
            if ($p['method_name']) $desc .= ' - ' . e($p['method_name']);
            if ($p['reference']) $desc .= ' (' . e($p['reference']) . ')';
            if ($p['remarks']) $desc .= ' [' . e($p['remarks']) . ']';
            echo '<tr><td>' . fmt_date($p['payment_date']) . '</td><td>' . $desc . '</td><td>-</td><td>-</td><td class="text-end">' . fmt_money((float)$p['amount']) . '</td><td class="text-end fw-medium">' . fmt_money($bal) . '</td></tr>';
        }
        if (!$ledBookings && !$ledPayments && $ledgerStart === '' && $ledgerEnd === '') {
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

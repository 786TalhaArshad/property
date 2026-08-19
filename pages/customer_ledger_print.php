<?php
require_once '../includes/auth.php';
require_login();
require_permission('customers.view');

$id = (int)($_GET['id'] ?? 0);
$cust = db_get("SELECT c.*, ci.name AS city_name FROM customers c LEFT JOIN cities ci ON ci.id = c.city_id WHERE c.id = ?", [$id]);
if (!$cust) {
    flash('danger', 'Customer not found.');
    redirect('customers.php');
}

$ledgerStart = trim($_GET['start_date'] ?? '');
$ledgerEnd = trim($_GET['end_date'] ?? '');
$project_id = (int)($_GET['project_id'] ?? active_project_id());
$property_id = (int)($_GET['property_id'] ?? 0);

$bookings = db_all("SELECT b.*, p.property_no, p.project_id, pt.name AS type_name, pj.name AS project_name FROM bookings b JOIN properties p ON p.id = b.property_id LEFT JOIN property_types pt ON pt.id = p.property_type_id LEFT JOIN projects pj ON pj.id = p.project_id WHERE b.customer_id = ? AND b.status <> 'cancelled' ORDER BY b.id DESC", [$id]);
$custProjects = db_all("SELECT pj.id, pj.name, pj.status, COUNT(DISTINCT b.id) AS bookings_count, COALESCE(SUM(b.total_price - b.discount),0) AS booked_value
                        FROM bookings b
                        JOIN properties pr ON pr.id = b.property_id
                        JOIN projects pj ON pj.id = pr.project_id
                        WHERE b.customer_id = ? AND b.status <> 'cancelled'
                        GROUP BY pj.id, pj.name, pj.status
                        ORDER BY pj.name", [$id]);
$custProperties = db_all("SELECT pr.id, pr.property_no, pr.project_id, pt.name AS type_name, pj.name AS project_name
                          FROM bookings b
                          JOIN properties pr ON pr.id = b.property_id
                          LEFT JOIN property_types pt ON pt.id = pr.property_type_id
                          LEFT JOIN projects pj ON pj.id = pr.project_id
                          WHERE b.customer_id = ? AND b.status <> 'cancelled' AND (? = 0 OR pr.project_id = ?)
                          ORDER BY pj.name, pr.property_no", [$id, $project_id, $project_id]);
$custPropertyIds = array_map('intval', array_column($custProperties, 'id'));
if ($property_id > 0 && !in_array($property_id, $custPropertyIds)) {
    $property_id = 0;
}

$paidRows = db_all("SELECT r.receipt_date, r.receipt_no, r.amount, r.project_id,
                    COALESCE(pr.project_id, r.project_id, 0) AS eff_project_id,
                    COALESCE(b.property_id, 0) AS booking_property_id,
                    pj.name AS project_name
                    FROM receipts r
                    LEFT JOIN bookings b ON b.id = r.booking_id
                    LEFT JOIN properties pr ON pr.id = b.property_id
                    LEFT JOIN projects pj ON pj.id = COALESCE(pr.project_id, r.project_id)
                    WHERE r.customer_id = ?
                    ORDER BY r.receipt_date, r.id", [$id]);
$custPayRows = db_all("SELECT cp.payment_date, cp.amount, cp.narration, cp.bank_id, cp.project_id,
                       COALESCE(cp.project_id, 0) AS eff_project_id,
                       bk.name AS bank_name
                       FROM customer_payments cp
                       LEFT JOIN banks bk ON bk.id = cp.bank_id
                       WHERE cp.customer_id = ?
                       ORDER BY cp.payment_date, cp.id", [$id]);
$transfers = db_all("SELECT t.*, fc.full_name AS from_name, tc.full_name AS to_name,
                     COALESCE(pr.project_id, t.project_id, 0) AS eff_project_id,
                     COALESCE(b.property_id, 0) AS booking_property_id,
                     pj.name AS project_name
                     FROM transfers t
                     LEFT JOIN customers fc ON fc.id = t.from_customer_id
                     LEFT JOIN customers tc ON tc.id = t.to_customer_id
                     LEFT JOIN bookings b ON b.id = t.booking_id
                     LEFT JOIN properties pr ON pr.id = b.property_id
                     LEFT JOIN projects pj ON pj.id = COALESCE(pr.project_id, t.project_id)
                     WHERE t.transfer_type = 'customer_to_customer' AND (t.from_customer_id = ? OR t.to_customer_id = ?)
                     ORDER BY t.transfer_date, t.id", [$id, $id]);

$ledBookings = [];
foreach ($bookings as $b) {
    if ($ledgerStart !== '' && $b['booking_date'] < $ledgerStart) continue;
    if ($ledgerEnd !== '' && $b['booking_date'] > $ledgerEnd) continue;
    if ($project_id > 0 && (int)$b['project_id'] !== $project_id) continue;
    if ($property_id > 0 && (int)$b['property_id'] !== $property_id) continue;
    $ledBookings[] = $b;
}
$ledPayments = [];
foreach ($paidRows as $pr) {
    if ($ledgerStart !== '' && $pr['receipt_date'] < $ledgerStart) continue;
    if ($ledgerEnd !== '' && $pr['receipt_date'] > $ledgerEnd) continue;
    if ($project_id > 0 && (int)$pr['eff_project_id'] !== $project_id) continue;
    if ($property_id > 0 && (int)$pr['booking_property_id'] !== $property_id) continue;
    $ledPayments[] = $pr;
}
$ledTransfers = [];
foreach ($transfers as $t) {
    if ($ledgerStart !== '' && $t['transfer_date'] < $ledgerStart) continue;
    if ($ledgerEnd !== '' && $t['transfer_date'] > $ledgerEnd) continue;
    if ($project_id > 0 && (int)$t['eff_project_id'] !== $project_id) continue;
    if ($property_id > 0 && (int)$t['booking_property_id'] !== $property_id) continue;
    $ledTransfers[] = $t;
}
$ledCustPayments = [];
foreach ($custPayRows as $cp) {
    if ($ledgerStart !== '' && $cp['payment_date'] < $ledgerStart) continue;
    if ($ledgerEnd !== '' && $cp['payment_date'] > $ledgerEnd) continue;
    if ($project_id > 0 && (int)$cp['eff_project_id'] !== $project_id) continue;
    $ledCustPayments[] = $cp;
}
$storedOB = (float)$cust['opening_balance'];
if (($cust['balance_type'] ?? 'receivable') === 'payable') {
    $storedOB = -$storedOB;
}
$openingBalance = $storedOB;
if ($ledgerStart !== '') {
    $ob = (float)db_get("SELECT COALESCE(SUM(b.total_price - b.discount),0) amt FROM bookings b
                         JOIN properties pr ON pr.id = b.property_id
                         WHERE b.customer_id = ? AND b.status <> 'cancelled' AND b.booking_date < ?
                         AND (? = 0 OR pr.project_id = ?) AND (? = 0 OR b.property_id = ?)",
        [$id, $ledgerStart, $project_id, $project_id, $property_id, $property_id])['amt'];
    $op = (float)db_get("SELECT COALESCE(SUM(r.amount),0) amt FROM receipts r
                         LEFT JOIN bookings b ON b.id = r.booking_id
                         LEFT JOIN properties pr ON pr.id = b.property_id
                         WHERE r.customer_id = ? AND r.receipt_date < ?
                         AND (? = 0 OR COALESCE(pr.project_id, r.project_id) = ?)
                         AND (? = 0 OR COALESCE(b.property_id, 0) = ?)",
        [$id, $ledgerStart, $project_id, $project_id, $property_id, $property_id])['amt'];
    $ot = db_get("SELECT COALESCE(SUM(CASE WHEN to_customer_id = ? THEN amount WHEN from_customer_id = ? THEN -amount ELSE 0 END),0) amt
                  FROM transfers t
                  LEFT JOIN bookings b ON b.id = t.booking_id
                  LEFT JOIN properties pr ON pr.id = b.property_id
                  WHERE t.transfer_type = 'customer_to_customer' AND (t.from_customer_id = ? OR t.to_customer_id = ?) AND t.transfer_date < ?
                  AND (? = 0 OR COALESCE(pr.project_id, t.project_id) = ?)
                  AND (? = 0 OR COALESCE(b.property_id, 0) = ?)",
        [$id, $id, $id, $id, $ledgerStart, $project_id, $project_id, $property_id, $property_id]);
    $ocp = (float)db_get("SELECT COALESCE(SUM(cp.amount),0) amt FROM customer_payments cp
                          WHERE cp.customer_id = ? AND cp.payment_date < ?
                          AND (? = 0 OR cp.project_id = ?)",
        [$id, $ledgerStart, $project_id, $project_id])['amt'];
    $openingBalance = $storedOB + $ob - $op - $ocp + (float)$ot['amt'];
}

$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
$projFilter = '';
foreach ($projects as $pj) {
    if ((int)$pj['id'] === $project_id) {
        $projFilter = $pj['name'];
        break;
    }
}
$propFilter = '';
foreach ($custProperties as $cp) {
    if ((int)$cp['id'] === $property_id) {
        $propFilter = $cp['property_no'];
        break;
    }
}

$companyInfo = [
    'name' => setting('company_name', 'Company'),
    'tagline' => setting('company_tagline', ''),
    'address' => setting('company_address', ''),
    'phone' => setting('company_phone', ''),
    'email' => setting('company_email', ''),
    'logo' => setting('company_logo', ''),
];
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
    <a class="btn btn-light" href="customer_view.php?id=<?= $id ?>#cLedger"><i class="bi bi-arrow-left me-1"></i>Back</a>
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
        <h5 class="mb-1">Customer Ledger</h5>
        <div class="fw-medium"><?= e($cust['full_name']) ?> (<?= e($cust['customer_no']) ?>)</div>
        <div class="small text-muted">Phone: <?= e($cust['phone'] ?? '-') ?></div>
        <div class="small">Print Date: <?= fmt_date(date('Y-m-d')) ?></div>
        <div class="small">
            Project: <?= $projFilter ? e($projFilter) : 'All' ?>
            <?= $propFilter ? ' &bull; Property: ' . e($propFilter) : '' ?>
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
            <th class="text-end">Debit</th>
            <th class="text-end">Credit</th>
            <th class="text-end">Balance</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $balance = $openingBalance;
        if ($openingBalance != 0) {
            $obLabel = ($ledgerStart !== '') ? 'Opening Balance (' . fmt_date($ledgerStart) . ')' : 'Opening Balance';
            echo '<tr class="total-row"><td>' . $obLabel . '</td><td colspan="2">' . e($cust['full_name']) . ' — ' . (($cust['balance_type'] ?? 'receivable') === 'receivable' ? 'Receivable' : 'Payable') . '</td><td class="text-end">' . ($openingBalance > 0 ? fmt_money($openingBalance) : '-') . '</td><td class="text-end">' . ($openingBalance < 0 ? fmt_money(abs($openingBalance)) : '-') . '</td><td class="text-end fw-medium">' . fmt_money($balance) . '</td></tr>';
        }
        foreach ($ledBookings as $b) {
            $balance += (float)$b['total_price'] - (float)$b['discount'];
            echo '<tr><td>' . fmt_date($b['booking_date']) . '</td><td>Booking ' . e($b['booking_no']) . ' - ' . e($b['property_no']) . '</td><td>' . e($b['project_name'] ?? '-') . '</td><td class="text-end">' . fmt_money((float)$b['total_price'] - (float)$b['discount']) . '</td><td>-</td><td class="text-end fw-medium">' . fmt_money($balance) . '</td></tr>';
        }
        foreach ($ledTransfers as $t) {
            if ($t['to_customer_id'] == $id) {
                $balance += (float)$t['amount'];
                echo '<tr><td>' . fmt_date($t['transfer_date']) . '</td><td>Transfer from ' . e($t['from_name'] ?? '-') . ' (' . e($t['transfer_no']) . ')</td><td>' . e($t['project_name'] ?? '-') . '</td><td class="text-end">' . fmt_money((float)$t['amount']) . '</td><td>-</td><td class="text-end fw-medium">' . fmt_money($balance) . '</td></tr>';
            } else {
                $balance -= (float)$t['amount'];
                echo '<tr><td>' . fmt_date($t['transfer_date']) . '</td><td>Transfer to ' . e($t['to_name'] ?? '-') . ' (' . e($t['transfer_no']) . ')</td><td>' . e($t['project_name'] ?? '-') . '</td><td>-</td><td class="text-end">' . fmt_money((float)$t['amount']) . '</td><td class="text-end fw-medium">' . fmt_money($balance) . '</td></tr>';
            }
        }
        foreach ($ledPayments as $pr) {
            $balance -= (float)$pr['amount'];
            echo '<tr><td>' . fmt_date($pr['receipt_date']) . '</td><td>Payment ' . e($pr['receipt_no']) . '</td><td>' . e($pr['project_name'] ?? '-') . '</td><td>-</td><td class="text-end">' . fmt_money((float)$pr['amount']) . '</td><td class="text-end fw-medium">' . fmt_money($balance) . '</td></tr>';
        }
        foreach ($ledCustPayments as $cp) {
            $balance -= (float)$cp['amount'];
            $desc = 'Paid to customer';
            if ($cp['narration']) $desc .= ' - ' . e($cp['narration']);
            if ($cp['bank_name']) $desc .= ' (' . e($cp['bank_name']) . ')';
            echo '<tr><td>' . fmt_date($cp['payment_date']) . '</td><td>' . $desc . '</td><td>-</td><td>-</td><td class="text-end">' . fmt_money((float)$cp['amount']) . '</td><td class="text-end fw-medium">' . fmt_money($balance) . '</td></tr>';
        }
        if (!$ledBookings && !$ledTransfers && !$ledPayments && !$ledCustPayments && $ledgerStart === '' && $ledgerEnd === '') {
            echo '<tr><td colspan="6" class="text-center text-muted py-4">No ledger entries yet</td></tr>';
        }
        ?>
        </tbody>
        <tfoot>
        <tr class="total-row">
            <td colspan="5" class="text-end fw-bold"><?= ($ledgerStart !== '' || $ledgerEnd !== '') ? 'Closing Balance' : 'Outstanding Balance' ?></td>
            <td class="fw-bold text-end"><?= fmt_money($balance) ?></td>
        </tr>
        </tfoot>
    </table>
</div>

<?php include '../includes/footer.php'; ?>

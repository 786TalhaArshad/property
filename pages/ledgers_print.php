<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Ledgers Print';

$account_id = (int)($_GET['account_id'] ?? 0);
$account_type = $_GET['account_type'] ?? '';
$project_id = (int)($_GET['project_id'] ?? active_project_id());
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$include_children = isset($_GET['include_children']);

if ($account_id > 0) {
    $accounts = db_all("SELECT * FROM chart_of_accounts WHERE id = ? ORDER BY code", [$account_id]);
} elseif ($account_type !== '') {
    $accounts = db_all("SELECT * FROM chart_of_accounts WHERE account_type = ? ORDER BY code", [$account_type]);
} else {
    $accounts = db_all("SELECT * FROM chart_of_accounts ORDER BY code");
}

$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
$projFilter = '';
foreach ($projects as $pj) {
    if ((int)$pj['id'] === $project_id) {
        $projFilter = $pj['name'];
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
    .print-table { font-size: 11px; width: 100%; border-collapse: collapse; }
    .print-table th, .print-table td { border: 1px solid #444; padding: 3px 6px; }
    .print-table th { background: #e9ecef; text-align: left; }
    .ledger-section { page-break-before: always; }
    .ledger-section:first-of-type { page-break-before: avoid; }
    .ledger-title { background: #1c2b36; color: #fff; padding: 5px 10px; font-weight: bold; }
    .total-row td { font-weight: bold; background: #f1f3f5; }
    .no-print { margin: 16px 0; }
    @media print {
        body { background: #fff; }
        .no-print { display: none !important; }
    }
</style>

<div class="no-print text-center">
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    <a class="btn btn-light" href="ledger.php"><i class="bi bi-arrow-left me-1"></i>Back to Ledger</a>
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
        <h5 class="mb-1">General Ledger</h5>
        <div class="small">Print Date: <?= fmt_date(date('Y-m-d')) ?></div>
        <div class="small">
            Project: <?= $projFilter ? e($projFilter) : 'All' ?>
            <?php if ($from): ?> &bull; From: <?= fmt_date($from) ?><?php endif; ?>
            <?php if ($to): ?> &bull; To: <?= fmt_date($to) ?><?php endif; ?>
        </div>
        <div class="small text-muted">Accounts: <?= count($accounts) ?></div>
    </div>
</div>

<?php foreach ($accounts as $acc):
    $ids = [(int)$acc['id']];
    if ($include_children) {
        $children = db_all("SELECT id FROM chart_of_accounts WHERE parent_id = ?", [(int)$acc['id']]);
        foreach ($children as $c) {
            $ids[] = (int)$c['id'];
        }
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = $ids;
    $where = '';
    if ($from) {
        $where .= " AND v.voucher_date >= ?";
        $params[] = $from;
    }
    if ($to) {
        $where .= " AND v.voucher_date <= ?";
        $params[] = $to;
    }
    if ($project_id > 0) {
        $where .= " AND v.project_id = ?";
        $params[] = $project_id;
    }
    $entries = db_all("SELECT vi.*, v.voucher_no, v.voucher_date, v.voucher_type, v.narration
                       FROM voucher_items vi
                       JOIN vouchers v ON v.id = vi.voucher_id
                       WHERE vi.account_id IN ($placeholders) AND v.status = 'posted'$where
                       ORDER BY v.voucher_date, v.id", $params);
    $isCreditNormal = in_array($acc['account_type'], ['income', 'liability', 'equity'], true);
    $opening = (float)$acc['opening_balance'];
    if ($include_children) {
        $open = db_get("SELECT COALESCE(SUM(opening_balance),0) o FROM chart_of_accounts WHERE id IN ($placeholders)", $ids);
        $opening = (float)$open['o'];
    }
    $pparams = $ids;
    $pwhere = '';
    if ($from) {
        $pwhere .= " AND v.voucher_date < ?";
        $pparams[] = $from;
    }
    if ($project_id > 0) {
        $pwhere .= " AND v.project_id = ?";
        $pparams[] = $project_id;
    }
    $prior = db_get("SELECT COALESCE(SUM(vi.debit),0) AS dr, COALESCE(SUM(vi.credit),0) AS cr
                     FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id
                     WHERE vi.account_id IN ($placeholders) AND v.status = 'posted'$pwhere", $pparams);
    $priorNet = $isCreditNormal ? (float)$prior['cr'] - (float)$prior['dr'] : (float)$prior['dr'] - (float)$prior['cr'];
    if ($from) {
        $opening += $priorNet;
    }
    $bal = $opening;
    $totDr = 0.0;
    $totCr = 0.0;
    foreach ($entries as $en) {
        $totDr += (float)$en['debit'];
        $totCr += (float)$en['credit'];
        $bal += $isCreditNormal ? ((float)$en['credit'] - (float)$en['debit']) : ((float)$en['debit'] - (float)$en['credit']);
    }
    ?>
    <div class="ledger-section">
        <div class="ledger-title"><?= e($acc['code']) ?> - <?= e($acc['name']) ?> <span class="badge bg-light text-dark ms-2"><?= ucfirst($acc['account_type']) ?></span></div>
        <table class="print-table">
            <thead>
            <tr>
                <th style="width:70px">Date</th>
                <th style="width:80px">Voucher</th>
                <th>Description</th>
                <th class="text-end">Debit</th>
                <th class="text-end">Credit</th>
                <th class="text-end">Balance</th>
            </tr>
            </thead>
            <tbody>
            <tr class="total-row">
                <td colspan="3" class="text-end">Opening Balance</td>
                <td>-</td>
                <td>-</td>
                <td class="text-end"><?= fmt_money($opening) ?></td>
            </tr>
            <?php foreach ($entries as $en): ?>
                <tr>
                    <td><?= fmt_date($en['voucher_date']) ?></td>
                    <td class="fw-medium"><?= e($en['voucher_no']) ?></td>
                    <td class="small"><?= e($en['item_description'] ?: $en['narration']) ?></td>
                    <td class="text-end"><?= (float)$en['debit'] > 0 ? fmt_money($en['debit']) : '-' ?></td>
                    <td class="text-end"><?= (float)$en['credit'] > 0 ? fmt_money($en['credit']) : '-' ?></td>
                    <td class="text-end fw-medium"><?= fmt_money($bal) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$entries): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">No entries in the selected period</td></tr>
            <?php endif; ?>
            <tr class="total-row">
                <td colspan="3" class="text-end">Closing Balance</td>
                <td class="text-end"><?= fmt_money($totDr) ?></td>
                <td class="text-end"><?= fmt_money($totCr) ?></td>
                <td class="text-end"><?= fmt_money($bal) ?></td>
            </tr>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>

<?php if (!$accounts): ?>
    <div class="empty-state"><i class="bi bi-book"></i><p>No accounts found</p></div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

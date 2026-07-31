<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'General Ledger';
$active = 'ledger';

$account_id = (int)($_GET['account_id'] ?? 0);
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$accounts = db_all("SELECT * FROM chart_of_accounts ORDER BY code");

$account = null;
$entries = [];
$opening = 0.0;
if ($account_id > 0) {
    $account = db_get("SELECT * FROM chart_of_accounts WHERE id = ?", [$account_id]);
    $params = [$account_id, $account_id];
    $where = '';
    if ($from) {
        $where .= " AND v.voucher_date >= ?";
        $params[] = $from;
    }
    if ($to) {
        $where .= " AND v.voucher_date <= ?";
        $params[] = $to;
    }
    $entries = db_all("SELECT vi.*, v.voucher_no, v.voucher_date, v.voucher_type, v.narration
                       FROM voucher_items vi
                       JOIN vouchers v ON v.id = vi.voucher_id
                       WHERE vi.account_id = ? AND v.status = 'posted'$where
                       ORDER BY v.voucher_date, v.id", $params);
    $opening = (float)$account['opening_balance'];
    $prior = db_get("SELECT COALESCE(SUM(vi.debit),0) - COALESCE(SUM(vi.credit),0) AS net
                     FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id
                     WHERE vi.account_id = ? AND v.status = 'posted' AND v.voucher_date < ?", [$account_id, $from ?: date('Y-m-d', 0)]);
    if ($from) {
        $opening += (float)$prior['net'];
    }
}
include '../includes/header.php';
?>

<form method="get" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <select name="account_id" class="form-select" required>
                    <option value="">Select Account</option>
                    <?php foreach ($accounts as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= $account_id === (int)$a['id'] ? 'selected' : '' ?>><?= e($a['code']) ?> - <?= e($a['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><input type="date" name="from" class="form-control" value="<?= e($from) ?>" placeholder="From"></div>
            <div class="col-md-2"><input type="date" name="to" class="form-control" value="<?= e($to) ?>" placeholder="To"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
            <div class="col-md-2 text-end small text-muted">Balance: <strong><?= $account ? fmt_money($opening) : '-' ?></strong></div>
        </div>
    </div>
</form>

<?php if ($account): ?>
<div class="card">
    <div class="card-header"><i class="bi bi-book me-2"></i><?= e($account['name']) ?> (<?= e($account['code']) ?>)</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Date</th><th>Voucher</th><th>Type</th><th>Description</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead>
                <tbody>
                <tr class="table-light">
                    <td colspan="4" class="text-end fw-medium">Opening Balance</td>
                    <td>-</td><td>-</td><td class="fw-medium"><?= fmt_money($opening) ?></td>
                </tr>
                <?php
                $bal = $opening;
                foreach ($entries as $en) {
                    $bal += (float)$en['debit'] - (float)$en['credit'];
                    echo '<tr>';
                    echo '<td>' . fmt_date($en['voucher_date']) . '</td>';
                    echo '<td class="fw-medium">' . e($en['voucher_no']) . '</td>';
                    echo '<td><span class="badge bg-light text-dark border">' . ucfirst(str_replace('_', ' ', e($en['voucher_type']))) . '</span></td>';
                    echo '<td class="small">' . e($en['item_description'] ?: $en['narration']) . '</td>';
                    echo '<td>' . ((float)$en['debit'] > 0 ? fmt_money($en['debit']) : '-') . '</td>';
                    echo '<td>' . ((float)$en['credit'] > 0 ? fmt_money($en['credit']) : '-') . '</td>';
                    echo '<td class="fw-medium">' . fmt_money($bal) . '</td>';
                    echo '</tr>';
                }
                if (!$entries) {
                    echo '<tr><td colspan="7" class="text-center text-muted py-4">No entries in the selected period</td></tr>';
                }
                ?>
                </tbody>
                <tfoot>
                <tr class="table-light">
                    <td colspan="4" class="text-end fw-bold">Closing Balance</td>
                    <td class="fw-bold"><?= fmt_money(array_sum(array_map(function ($e) { return (float)$e['debit']; }, $entries))) ?></td>
                    <td class="fw-bold"><?= fmt_money(array_sum(array_map(function ($e) { return (float)$e['credit']; }, $entries))) ?></td>
                    <td class="fw-bold"><?= fmt_money($bal) ?></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
<div class="empty-state"><i class="bi bi-book"></i><p>Select an account to view its ledger</p></div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

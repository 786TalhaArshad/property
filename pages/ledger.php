<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'General Ledger';
$active = 'ledger';

$account_id = (int)($_GET['account_id'] ?? 0);
$project_id = (int)($_GET['project_id'] ?? active_project_id());
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$include_children = isset($_GET['include_children']);
$accounts = db_all("SELECT * FROM chart_of_accounts ORDER BY code");
$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");

$account = null;
$entries = [];
$opening = 0.0;
if ($account_id > 0) {
    $account = db_get("SELECT * FROM chart_of_accounts WHERE id = ?", [$account_id]);
    $ids = [$account_id];
    if ($include_children) {
        $children = db_all("SELECT id FROM chart_of_accounts WHERE parent_id = ?", [$account_id]);
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
    $entries = db_all("SELECT vi.*, v.voucher_no, v.voucher_date, v.voucher_type, v.narration, v.project_id
                       FROM voucher_items vi
                       JOIN vouchers v ON v.id = vi.voucher_id
                       WHERE vi.account_id IN ($placeholders) AND v.status = 'posted'$where
                       ORDER BY v.voucher_date, v.id", $params);
    $isCreditNormal = in_array($account['account_type'], ['income', 'liability', 'equity'], true);
    $opening = (float)$account['opening_balance'];
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
}
include '../includes/header.php';
?>

<form method="get" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="account_id" class="form-select" required>
                    <option value="">Select Account</option>
                    <?php foreach ($accounts as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= $account_id === (int)$a['id'] ? 'selected' : '' ?>><?= e($a['code']) ?> - <?= e($a['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="project_id" class="form-select">
                    <option value="">All Projects</option>
                    <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><input type="date" name="from" class="form-control" value="<?= e($from) ?>" placeholder="From"></div>
            <div class="col-md-2"><input type="date" name="to" class="form-control" value="<?= e($to) ?>" placeholder="To"></div>
            <div class="col-md-1"><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i></button></div>
            <div class="col-md-2 text-end small text-muted">Balance: <strong><?= $account ? fmt_money($opening) : '-' ?></strong></div>
        </div>
    </div>
</form>

<?php if ($account): ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div><i class="bi bi-book me-2"></i><?= e($account['name']) ?> (<?= e($account['code']) ?>)<?= $include_children ? ' <span class="badge bg-secondary ms-1">incl. sub-heads</span>' : '' ?></div>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="ledgers_print.php?account_id=<?= $account_id ?>&project_id=<?= $project_id ?>&from=<?= e($from) ?>&to=<?= e($to) ?><?= $include_children ? '&include_children=1' : '' ?>" target="_blank"><i class="bi bi-printer me-1"></i>Print This Ledger</a>
            <a class="btn btn-sm btn-outline-secondary" href="ledgers_print.php?project_id=<?= $project_id ?>&from=<?= e($from) ?>&to=<?= e($to) ?>" target="_blank"><i class="bi bi-printer me-1"></i>Print All Ledgers</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Date</th><th>Voucher</th><th>Type</th><th>Project</th><th>Description</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead>
                <tbody>
                <tr class="table-light">
                    <td colspan="5" class="text-end fw-medium">Opening Balance</td>
                    <td>-</td><td>-</td><td class="fw-medium"><?= fmt_money($opening) ?></td>
                </tr>
                <?php
                $bal = $opening;
                $projNames = [];
                foreach ($projects as $pj) {
                    $projNames[(int)$pj['id']] = $pj['name'];
                }
                foreach ($entries as $en) {
                    $bal += $isCreditNormal ? ((float)$en['credit'] - (float)$en['debit']) : ((float)$en['debit'] - (float)$en['credit']);
                    echo '<tr>';
                    echo '<td>' . fmt_date($en['voucher_date']) . '</td>';
                    echo '<td class="fw-medium">' . e($en['voucher_no']) . '</td>';
                    echo '<td><span class="badge bg-light text-dark border">' . ucfirst(str_replace('_', ' ', e($en['voucher_type']))) . '</span></td>';
                    echo '<td class="small">' . ($en['project_id'] ? e($projNames[(int)$en['project_id']] ?? '') : '<span class="text-muted">General</span>') . '</td>';
                    echo '<td class="small">' . e($en['item_description'] ?: $en['narration']) . '</td>';
                    echo '<td>' . ((float)$en['debit'] > 0 ? fmt_money($en['debit']) : '-') . '</td>';
                    echo '<td>' . ((float)$en['credit'] > 0 ? fmt_money($en['credit']) : '-') . '</td>';
                    echo '<td class="fw-medium">' . fmt_money($bal) . '</td>';
                    echo '</tr>';
                }
                if (!$entries) {
                    echo '<tr><td colspan="8" class="text-center text-muted py-4">No entries in the selected period</td></tr>';
                }
                ?>
                </tbody>
                <tfoot>
                <tr class="table-light">
                    <td colspan="5" class="text-end fw-bold">Closing Balance</td>
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
<div class="empty-state">
    <i class="bi bi-book"></i><p>Select an account to view its ledger</p>
    <a class="btn btn-outline-secondary btn-sm" href="ledgers_print.php?project_id=<?= $project_id ?>&from=<?= e($from) ?>&to=<?= e($to) ?>" target="_blank"><i class="bi bi-printer me-1"></i>Print All Ledgers</a>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Cash Book';
$active = 'cashbook';

$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? date('Y-m-d'));
$project_id = (int)($_GET['project_id'] ?? active_project_id());

$cashAcc = db_get("SELECT id, opening_balance FROM chart_of_accounts WHERE code = '1000'");
if (!$cashAcc) {
    flash('danger', 'Cash account not found in chart of accounts.');
    redirect('index.php');
}
$cashAccId = (int)$cashAcc['id'];

$vouchers = db_all("SELECT vi.item_description, vi.debit, vi.credit, v.voucher_no, v.voucher_date, v.voucher_type, v.narration, v.project_id, p.name AS project_name
                    FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id
                    LEFT JOIN projects p ON p.id = v.project_id
                    WHERE vi.account_id = ? AND v.status = 'posted' AND v.narration NOT LIKE 'Cash sale %'", [$cashAccId]);
$receipts = db_all("SELECT r.receipt_no, r.receipt_date, r.amount, r.project_id, c.full_name AS customer_name, p.name AS project_name
                    FROM receipts r
                    LEFT JOIN customers c ON c.id = r.customer_id
                    LEFT JOIN projects p ON p.id = r.project_id
                    WHERE r.bank_id IS NULL");
$rents = db_all("SELECT rc.collection_date, rc.amount, rc.reference, rc.bank_id, t.full_name AS tenant_name, pr.project_id, p.name AS project_name
                 FROM rent_collections rc
                 JOIN rental_agreements ra ON ra.id = rc.agreement_id
                 JOIN tenants t ON t.id = ra.tenant_id
                 LEFT JOIN properties pr ON pr.id = ra.property_id
                 LEFT JOIN projects p ON p.id = pr.project_id
                 WHERE rc.bank_id IS NULL");

$rows = [];
foreach ($vouchers as $v) {
    $rows[] = [
        'date' => $v['voucher_date'],
        'ref' => $v['voucher_no'],
        'type' => 'Voucher',
        'desc' => trim($v['narration'] !== '' ? $v['narration'] : $v['item_description']),
        'project' => $v['project_name'],
        'project_id' => $v['project_id'] ? (int)$v['project_id'] : 0,
        'in' => (float)$v['debit'],
        'out' => (float)$v['credit'],
    ];
}
foreach ($receipts as $r) {
    $rows[] = [
        'date' => $r['receipt_date'],
        'ref' => $r['receipt_no'],
        'type' => 'Receipt',
        'desc' => 'Received - ' . trim($r['customer_name'] ?? ''),
        'project' => $r['project_name'],
        'project_id' => $r['project_id'] ? (int)$r['project_id'] : 0,
        'in' => (float)$r['amount'],
        'out' => 0.0,
    ];
}
foreach ($rents as $rc) {
    $rows[] = [
        'date' => $rc['collection_date'],
        'ref' => $rc['reference'] !== '' ? 'RC-' . $rc['reference'] : 'RC-' . $rc['collection_date'],
        'type' => 'Rent',
        'desc' => 'Rent - ' . trim($rc['tenant_name'] ?? ''),
        'project' => $rc['project_name'],
        'project_id' => $rc['project_id'] ? (int)$rc['project_id'] : 0,
        'in' => (float)$rc['amount'],
        'out' => 0.0,
    ];
}

$filtered = array_values(array_filter($rows, function ($r) use ($from, $to, $project_id) {
    if ($from !== '' && $r['date'] < $from) return false;
    if ($r['date'] > $to) return false;
    if ($project_id > 0 && $r['project_id'] !== $project_id) return false;
    return true;
}));
usort($filtered, function ($a, $b) {
    return strcmp($a['date'], $b['date']) ?: strcmp($a['ref'], $b['ref']);
});

$opening = (float)$cashAcc['opening_balance'];
if ($from !== '') {
    foreach ($rows as $r) {
        if ($r['date'] >= $from) continue;
        if ($project_id > 0 && $r['project_id'] !== $project_id) continue;
        $opening += $r['in'] - $r['out'];
    }
}

$totalIn = 0.0;
$totalOut = 0.0;
$running = [];
$bal = $opening;
foreach ($filtered as $i => $r) {
    $totalIn += $r['in'];
    $totalOut += $r['out'];
    $bal += $r['in'] - $r['out'];
    $running[$i] = $bal;
}
$closing = $bal;

$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
$projectName = '';
foreach ($projects as $p) {
    if ((int)$p['id'] === $project_id) {
        $projectName = $p['name'];
        break;
    }
}
include '../includes/header.php';
?>

<form method="get" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-0">Project</label>
                <select name="project_id" class="form-select form-select-sm">
                    <option value="0">All Projects</option>
                    <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-0">Start Date</label>
                <input type="date" name="from" class="form-control" value="<?= e($from) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-0">End Date</label>
                <input type="date" name="to" class="form-control" value="<?= e($to) ?>">
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-journal-plus"></i></div><div><div class="stat-label">OPENING BALANCE</div><div class="stat-value"><?= fmt_money($opening) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-arrow-down-circle"></i></div><div><div class="stat-label">TOTAL CASH IN</div><div class="stat-value"><?= fmt_money($totalIn) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-arrow-up-circle"></i></div><div><div class="stat-label">TOTAL CASH OUT</div><div class="stat-value"><?= fmt_money($totalOut) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-red"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">CLOSING BALANCE</div><div class="stat-value"><?= fmt_money($closing) ?></div></div></div></div></div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div><i class="bi bi-cash-coin me-2"></i>Cash Book <?= $from ? 'from ' . fmt_date($from) . ' to ' . fmt_date($to) : 'as of ' . fmt_date($to) ?><?= $projectName ? ' &bull; ' . e($projectName) : '' ?></div>
        <a href="cashbook_print.php?<?= http_build_query(['from' => $from, 'to' => $to, 'project_id' => $project_id]) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer me-1"></i>Print</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Date</th><th>Ref / Voucher</th><th>Type</th><th>Description</th><th>Project</th><th class="text-end">Cash In</th><th class="text-end">Cash Out</th><th class="text-end">Balance</th></tr></thead>
                <tbody>
                <?php if ($from !== ''): ?>
                    <tr class="table-light"><td><?= fmt_date($from) ?></td><td colspan="4" class="fw-medium">Opening Balance</td><td>-</td><td>-</td><td class="text-end fw-medium"><?= fmt_money($opening) ?></td></tr>
                <?php endif; ?>
                <?php foreach ($filtered as $i => $r): ?>
                    <tr>
                        <td><?= fmt_date($r['date']) ?></td>
                        <td class="fw-medium"><?= e($r['ref']) ?></td>
                        <td><span class="badge bg-<?= $r['type'] === 'Voucher' ? 'info' : ($r['type'] === 'Receipt' ? 'success' : 'warning') ?> bg-opacity-25 text-dark"><?= e($r['type']) ?></span></td>
                        <td class="small"><?= e($r['desc'] !== '' ? $r['desc'] : '-') ?></td>
                        <td class="small"><?= $r['project'] ? e($r['project']) : '<span class="text-muted">General</span>' ?></td>
                        <td class="text-end text-success"><?= $r['in'] ? '+' . fmt_money($r['in']) : '-' ?></td>
                        <td class="text-end text-danger"><?= $r['out'] ? '-' . fmt_money($r['out']) : '-' ?></td>
                        <td class="text-end fw-medium"><?= fmt_money($running[$i]) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$filtered && $from === ''): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No cash entries yet</td></tr>
                <?php endif; ?>
                </tbody>
                <tfoot>
                <tr class="table-light">
                    <td colspan="5" class="text-end fw-bold">Totals</td>
                    <td class="text-end fw-bold"><?= fmt_money($totalIn) ?></td>
                    <td class="text-end fw-bold"><?= fmt_money($totalOut) ?></td>
                    <td class="text-end fw-bold"><?= fmt_money($closing) ?></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

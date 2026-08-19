<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Trial Balance';
$active = 'trial_balance';

$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? date('Y-m-d'));
$project_id = (int)($_GET['project_id'] ?? active_project_id());
$expand = (int)($_GET['expand'] ?? 0);

$projCond = $project_id > 0 ? ' AND v.project_id = ?' : '';
$fromCond = $from !== '' ? ' AND v.voucher_date < ?' : '';
$periodCond = $from !== '' ? ' AND v.voucher_date BETWEEN ? AND ?' : ' AND v.voucher_date <= ?';

$sql = "SELECT a.id, a.code, a.name, a.account_type, a.opening_balance,
        (SELECT COALESCE(SUM(vi.debit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted'" . $fromCond . $projCond . ") AS pre_dr,
        (SELECT COALESCE(SUM(vi.credit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted'" . $fromCond . $projCond . ") AS pre_cr,
        (SELECT COALESCE(SUM(vi.debit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted'" . $periodCond . $projCond . ") AS dr,
        (SELECT COALESCE(SUM(vi.credit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted'" . $periodCond . $projCond . ") AS cr,
        (SELECT COUNT(*) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted'" . $periodCond . $projCond . ") AS entry_count
        FROM chart_of_accounts a ORDER BY a.account_type, a.code";
$params = [];
if ($from !== '') { $params[] = $from; }
if ($project_id > 0) { $params[] = $project_id; }
if ($from !== '') { $params[] = $from; }
if ($project_id > 0) { $params[] = $project_id; }
if ($from !== '') { $params[] = $from; $params[] = $to; } else { $params[] = $to; }
if ($project_id > 0) { $params[] = $project_id; }
if ($from !== '') { $params[] = $from; $params[] = $to; } else { $params[] = $to; }
if ($project_id > 0) { $params[] = $project_id; }
if ($from !== '') { $params[] = $from; $params[] = $to; } else { $params[] = $to; }
if ($project_id > 0) { $params[] = $project_id; }
$records = db_all($sql, $params);

$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
$projectName = '';
foreach ($projects as $p) {
    if ((int)$p['id'] === $project_id) {
        $projectName = $p['name'];
        break;
    }
}

if ($expand > 0) {
    $detProjCond = $project_id > 0 ? ' AND v.project_id = ?' : '';
    $detPeriodCond = $from !== '' ? ' AND v.voucher_date BETWEEN ? AND ?' : ' AND v.voucher_date <= ?';
    $detSql = "SELECT vi.debit, vi.credit, vi.description, v.voucher_no, v.voucher_date, v.voucher_type,
               IFNULL(p.project_no, '-') AS project_no
               FROM voucher_items vi
               JOIN vouchers v ON v.id = vi.voucher_id
               LEFT JOIN projects p ON p.id = v.project_id
               WHERE vi.account_id = ? AND v.status = 'posted'" . $detPeriodCond . $detProjCond . "
               ORDER BY v.voucher_date, v.id";
    $detParams = [$expand];
    if ($from !== '') { $detParams[] = $from; $detParams[] = $to; } else { $detParams[] = $to; }
    if ($project_id > 0) { $detParams[] = $project_id; }
    $details = db_all($detSql, $detParams);
}

$totalDr = 0.0;
$totalCr = 0.0;
$totalBalDr = 0.0;
$totalBalCr = 0.0;
include '../includes/header.php';
?>

<style>
@media print {
    .no-print, .sidebar, .main-header, .main-footer, .quick-action-btn { display: none !important; }
    .main-content { margin: 0 !important; padding: 10px !important; }
    .card { border: none !important; box-shadow: none !important; }
    body { font-size: 11px; }
    .table td, .table th { padding: 4px 6px !important; font-size: 11px; }
}
.account-row { cursor: pointer; }
.account-row:hover { background: #f0f6ff !important; }
.account-row td:first-child::before { content: '\f2ea'; font-family: 'bootstrap-icons'; margin-right: 6px; color: #2d6cdf; font-size: 12px; }
.account-row.expanded td:first-child::before { content: '\f2f0'; }
.detail-row { display: none; }
.detail-row.show { display: table-row; }
.detail-row td { background: #f8fafc; padding-left: 30px !important; }
.detail-table { margin: 0; font-size: 13px; }
.detail-table th { background: #eef2f7; font-size: 11px; text-transform: uppercase; letter-spacing: .3px; }
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-columns-gap me-2"></i>Trial Balance</h5>
    <div class="ms-auto">
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print</button>
    </div>
</div>

<form method="get" class="card mb-3 no-print">
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
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary flex-fill"><i class="bi bi-funnel me-1"></i>View</button>
                <a href="trial_balance.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            </div>
        </div>
    </div>
</div>

<div class="card print-only" style="display:none">
    <div class="text-center mb-2">
        <h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5>
        <div class="small text-muted">Trial Balance <?= $from ? 'from ' . fmt_date($from) . ' to ' . fmt_date($to) : 'as of ' . fmt_date($to) ?><?= $projectName ? ' | ' . e($projectName) : '' ?></div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center no-print">
        <span><i class="bi bi-columns-gap me-2"></i>Trial Balance <?= $from ? 'from ' . fmt_date($from) . ' to ' . fmt_date($to) : 'as of ' . fmt_date($to) ?><?= $projectName ? ' &bull; ' . e($projectName) : '' ?></span>
        <span class="ms-auto small text-muted">Click account to view details</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tbTable">
                <thead><tr><th style="width:50px">#</th><th>Code</th><th>Account</th><th>Type</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th><th class="text-center no-print" style="width:60px">Entries</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <?php
                    $dr = (float)$r['dr'];
                    $cr = (float)$r['cr'];
                    $preDr = (float)$r['pre_dr'];
                    $preCr = (float)$r['pre_cr'];
                    $ob = $project_id > 0 ? 0 : (float)$r['opening_balance'];
                    $net = $ob + $preDr - $preCr + $dr - $cr;
                    if ($project_id > 0 && $net == 0) continue;
                    if ($dr == 0 && $cr == 0 && $net == 0) continue;
                    $balDr = $net > 0 ? $net : 0;
                    $balCr = $net < 0 ? -$net : 0;
                    $totalDr += $dr;
                    $totalCr += $cr;
                    $totalBalDr += $balDr;
                    $totalBalCr += $balCr;
                    $cnt = (int)$r['entry_count'];
                    $isExpanded = $expand === (int)$r['id'];
                    ?>
                    <tr class="account-row <?= $isExpanded ? 'expanded' : '' ?>" data-account-id="<?= $r['id'] ?>" onclick="toggleDetail(this, <?= $r['id'] ?>)">
                        <td><?= $i + 1 ?></td>
                        <td class="fw-medium"><?= e($r['code']) ?></td>
                        <td><?= e($r['name']) ?></td>
                        <td><span class="badge bg-light text-dark border text-capitalize"><?= e($r['account_type']) ?></span></td>
                        <td class="text-end"><?= fmt_money($dr) ?></td>
                        <td class="text-end"><?= fmt_money($cr) ?></td>
                        <td class="text-end fw-medium"><?= fmt_money($net) ?></td>
                        <td class="text-center no-print"><?= $cnt > 0 ? '<span class="badge bg-primary rounded-pill">' . $cnt . '</span>' : '-' ?></td>
                    </tr>
                    <tr class="detail-row <?= $isExpanded ? 'show' : '' ?>" id="detail-<?= $r['id'] ?>">
                        <td colspan="8" class="p-0 border-0">
                            <?php if ($isExpanded && !empty($details)): ?>
                            <table class="table table-sm detail-table mb-0">
                                <thead><tr><th>Date</th><th>Voucher</th><th>Type</th><th>Description</th><th>Project</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
                                <tbody>
                                <?php foreach ($details as $d): ?>
                                    <tr>
                                        <td class="text-nowrap"><?= fmt_date($d['voucher_date']) ?></td>
                                        <td><a href="voucher_print.php?id=<?= e($d['voucher_no']) ?>" target="_blank"><?= e($d['voucher_no']) ?></a></td>
                                        <td><span class="text-capitalize small"><?= e(str_replace('_',' ',$d['voucher_type'])) ?></span></td>
                                        <td><?= e($d['description'] ?? '-') ?></td>
                                        <td class="small"><?= e($d['project_no']) ?></td>
                                        <td class="text-end"><?= $d['debit'] > 0 ? fmt_money($d['debit']) : '-' ?></td>
                                        <td class="text-end"><?= $d['credit'] > 0 ? fmt_money($d['credit']) : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                <tr class="table-light">
                                    <td colspan="5" class="text-end fw-bold small">Total (<?= count($details) ?> entries)</td>
                                    <td class="text-end fw-bold small"><?= fmt_money($dr) ?></td>
                                    <td class="text-end fw-bold small"><?= fmt_money($cr) ?></td>
                                </tr>
                                </tfoot>
                            </table>
                            <?php elseif ($isExpanded): ?>
                            <div class="text-center text-muted py-3 small">No entries found for this period</div>
                            <?php else: ?>
                            <div class="text-center text-muted py-3 small">Loading...</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr class="table-light">
                    <td colspan="4" class="text-end fw-bold">Totals</td>
                    <td class="text-end fw-bold"><?= fmt_money($totalDr) ?></td>
                    <td class="text-end fw-bold"><?= fmt_money($totalCr) ?></td>
                    <td class="text-end fw-bold"><?= fmt_money($totalBalDr - $totalBalCr) ?></td>
                    <td class="no-print"></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
function toggleDetail(row, accountId) {
    var detail = document.getElementById('detail-' + accountId);
    if (!detail) return;
    if (detail.classList.contains('show')) {
        detail.classList.remove('show');
        row.classList.remove('expanded');
    } else {
        row.classList.add('expanded');
        if (detail.querySelector('.detail-table')) {
            detail.classList.add('show');
        } else {
            var params = new URLSearchParams(window.location.search);
            params.set('expand', accountId);
            window.location.search = params.toString();
        }
    }
}
</script>

<?php include '../includes/footer.php'; ?>

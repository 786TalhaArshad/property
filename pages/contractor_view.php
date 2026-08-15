<?php
require_once '../includes/auth.php';
require_login();
require_permission('contractors.view');
$title = 'Contractor Details';
$active = 'contractors';
$canEdit = has_permission('contractors.manage');

function post_contractor_voucher($date, $narration, $lines) {
    $voucher_no = next_number('JV', 'vouchers', 'voucher_no');
    $vid = db_exec("INSERT INTO vouchers (voucher_no, voucher_date, voucher_type, narration, status, created_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
        [$voucher_no, $date, 'journal', $narration, 'posted', $GLOBALS['user']['id']]);
    foreach ($lines as $l) {
        db_exec("INSERT INTO voucher_items (voucher_id, account_id, item_description, debit, credit, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$vid, $l[0], $l[1], $l[2], $l[3]]);
    }
    return $vid;
}

$id = (int)($_GET['id'] ?? 0);
$filterProject = (int)($_GET['project_id'] ?? 0);
$contractor = db_get("SELECT c.*, b.name AS bank_name FROM contractors c LEFT JOIN banks b ON b.id = c.bank_id WHERE c.id = ?", [$id]);
if (!$contractor) {
    flash('danger', 'Contractor not found.');
    redirect('contractors.php');
}
$filterProjectName = '';
if ($filterProject > 0) {
    $fp = db_get("SELECT name FROM projects WHERE id = ?", [$filterProject]);
    if ($fp) {
        $filterProjectName = $fp['name'];
    } else {
        $filterProject = 0;
    }
}

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'entry_add') {
        $type = $_POST['entry_type'] ?? '';
        $date = $_POST['entry_date'] ?? date('Y-m-d');
        $amount = (float)($_POST['amount'] ?? 0);
        $narration = trim($_POST['narration'] ?? '');
        $projectId = (int)($_POST['project_id'] ?? 0) ?: null;
        $entry_no = trim($_POST['entry_no'] ?? '');
        if ($entry_no === '') {
            $entry_no = next_number('CONE', 'contractor_entries', 'entry_no');
        }
        $valid = in_array($type, ['payable', 'paid'], true);
        if (!$valid) {
            flash('danger', 'Invalid entry type.');
            redirect('contractor_view.php?id=' . $id);
        }
        if ($amount <= 0) {
            flash('danger', 'Enter a valid amount.');
            redirect('contractor_view.php?id=' . $id);
        }

        $conAcc = contractor_payable_account_id($id, $contractor['full_name']);
        $voucherId = null;
        $narr = $narration !== '' ? $narration : ucfirst($type) . ' - ' . $contractor['full_name'];
        $accountId = null;

        if ($type === 'payable') {
            $accountId = (int)($_POST['account_id'] ?? 0);
            if (!$accountId) {
                flash('danger', 'Select the expense account to debit.');
                redirect('contractor_view.php?id=' . $id);
            }
            $voucherId = post_contractor_voucher($date, $narr, [
                [$accountId, 'Payable to ' . $contractor['full_name'], $amount, 0],
                [$conAcc, 'Payable to ' . $contractor['full_name'], 0, $amount],
            ]);
        } else {
            $payFrom = $_POST['pay_from'] ?? '';
            $creditAcc = 0;
            $creditName = 'Cash';
            if ($payFrom === 'cash') {
                $cashAcc = db_get("SELECT id FROM chart_of_accounts WHERE code = '1000'");
                if (!$cashAcc) {
                    flash('danger', 'Cash account not found in chart of accounts.');
                    redirect('contractor_view.php?id=' . $id);
                }
                $creditAcc = (int)$cashAcc['id'];
            } elseif (strpos($payFrom, 'bank:') === 0) {
                $bankId = (int)substr($payFrom, 5);
                if ($bankId <= 0) {
                    flash('danger', 'Select a bank.');
                    redirect('contractor_view.php?id=' . $id);
                }
                $bank = db_get("SELECT * FROM banks WHERE id = ?", [$bankId]);
                if (!$bank) {
                    flash('danger', 'Bank not found.');
                    redirect('contractor_view.php?id=' . $id);
                }
                $bankAcc = cash_bank_account_id($bankId);
                if (!$bankAcc) {
                    flash('danger', 'Bank account not found in chart of accounts.');
                    redirect('contractor_view.php?id=' . $id);
                }
                $creditAcc = (int)$bankAcc;
                $creditName = $bank['name'];
            } else {
                flash('danger', 'Select where the money comes from.');
                redirect('contractor_view.php?id=' . $id);
            }
            $voucherId = post_contractor_voucher($date, $narr, [
                [$conAcc, 'Paid to ' . $contractor['full_name'], $amount, 0],
                [$creditAcc, 'Contractor payment - ' . $creditName, 0, $amount],
            ]);
        }

        db_exec("INSERT INTO contractor_entries (contractor_id, entry_no, entry_date, entry_type, amount, narration, account_id, project_id, voucher_id, created_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$id, $entry_no, $date, $type, $amount, $narr, $accountId, $projectId, $voucherId, $user['id']]);
        flash('success', $type . ' entry ' . $entry_no . ' saved.');
    } elseif ($action === 'entry_delete') {
        $eid = (int)($_POST['id'] ?? 0);
        $ent = db_get("SELECT * FROM contractor_entries WHERE id = ? AND contractor_id = ?", [$eid, $id]);
        if ($ent) {
            if ($ent['voucher_id']) {
                db_exec("DELETE FROM vouchers WHERE id = ?", [$ent['voucher_id']]);
            }
            db_exec("DELETE FROM contractor_entries WHERE id = ?", [$eid]);
            flash('success', 'Entry deleted.');
        }
    } elseif ($action === 'project_add') {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $exists = db_get("SELECT id FROM projects WHERE id = ?", [$projectId]);
        if (!$exists) {
            flash('danger', 'Select a project.');
        } else {
            $linked = db_get("SELECT contractor_id FROM contractor_projects WHERE contractor_id = ? AND project_id = ?", [$id, $projectId]);
            if (!$linked) {
                db_exec("INSERT INTO contractor_projects (contractor_id, project_id, created_date, created_time) VALUES (?,?,CURDATE(),CURTIME())", [$id, $projectId]);
                flash('success', 'Project linked.');
            } else {
                flash('warning', 'Project already linked.');
            }
        }
    } elseif ($action === 'project_remove') {
        $projectId = (int)($_POST['project_id'] ?? 0);
        db_exec("DELETE FROM contractor_projects WHERE contractor_id = ? AND project_id = ?", [$id, $projectId]);
        flash('success', 'Project unlinked.');
    }
    redirect('contractor_view.php?id=' . $id);
}

$conAccId = contractor_payable_account_id($id, $contractor['full_name']);
$entries = db_all("SELECT ce.*, u.full_name AS created_name, v.voucher_no AS linked_voucher_no, p.name AS project_name
                   FROM contractor_entries ce
                   LEFT JOIN users u ON u.id = ce.created_by
                   LEFT JOIN vouchers v ON v.id = ce.voucher_id
                   LEFT JOIN projects p ON p.id = ce.project_id
                   WHERE ce.contractor_id = ? ORDER BY ce.entry_date, ce.id", [$id]);
$voucherLines = db_all("SELECT vi.item_description, vi.debit, vi.credit, v.voucher_no, v.voucher_date, v.narration AS voucher_narration, p.name AS project_name
                        FROM voucher_items vi
                        JOIN vouchers v ON v.id = vi.voucher_id
                        LEFT JOIN projects p ON p.id = v.project_id
                        WHERE vi.account_id = ? AND v.status = 'posted'
                          AND v.id NOT IN (SELECT ce2.voucher_id FROM contractor_entries ce2 WHERE ce2.contractor_id = ? AND ce2.voucher_id IS NOT NULL)
                        ORDER BY v.voucher_date, v.id", [$conAccId, $id]);
$banks = db_all("SELECT * FROM banks ORDER BY name");
$accounts = db_all("SELECT * FROM chart_of_accounts ORDER BY code");
$constructionAcc = db_get("SELECT id FROM chart_of_accounts WHERE code = '5600'");
$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
$contractorProjects = db_all("SELECT cp.project_id, p.name, p.location
                              FROM contractor_projects cp
                              JOIN projects p ON p.id = cp.project_id
                              WHERE cp.contractor_id = ? ORDER BY p.name", [$id]);

$extSql = "SELECT COALESCE(SUM(vi.debit),0) AS debit, COALESCE(SUM(vi.credit),0) AS credit
           FROM voucher_items vi JOIN vouchers v ON v.id=vi.voucher_id
           WHERE vi.account_id=? AND v.status='posted'
             AND v.id NOT IN (SELECT ce.voucher_id FROM contractor_entries ce WHERE ce.contractor_id=? AND ce.voucher_id IS NOT NULL)";
$projectSummary = [];
foreach ($contractorProjects as $cp) {
    $e = db_get("SELECT COALESCE(SUM(CASE WHEN entry_type='payable' THEN amount ELSE 0 END),0) AS payable,
                        COALESCE(SUM(CASE WHEN entry_type='paid' THEN amount ELSE 0 END),0) AS paid
                 FROM contractor_entries WHERE contractor_id=? AND project_id=?", [$id, $cp['project_id']]);
    $x = db_get($extSql . " AND v.project_id=?", [$conAccId, $id, $cp['project_id']]);
    $payable = (float)$e['payable'] + (float)$x['credit'];
    $paid = (float)$e['paid'] + (float)$x['debit'];
    $projectSummary[] = ['name' => $cp['name'], 'project_id' => $cp['project_id'], 'payable' => $payable, 'paid' => $paid, 'balance' => $payable - $paid];
}
$e0 = db_get("SELECT COALESCE(SUM(CASE WHEN entry_type='payable' THEN amount ELSE 0 END),0) AS payable,
                    COALESCE(SUM(CASE WHEN entry_type='paid' THEN amount ELSE 0 END),0) AS paid
             FROM contractor_entries WHERE contractor_id=? AND project_id IS NULL", [$id]);
$x0 = db_get($extSql . " AND v.project_id IS NULL", [$conAccId, $id]);
$unassignedPayable = (float)$e0['payable'] + (float)$x0['credit'];
$unassignedPaid = (float)$e0['paid'] + (float)$x0['debit'];

$ledger = [];
foreach ($entries as $e) {
    $ledger[] = [
        'date' => $e['entry_date'],
        'entry_no' => $e['entry_no'],
        'entry_type' => $e['entry_type'],
        'amount' => (float)$e['amount'],
        'narration' => $e['narration'],
        'entry_id' => (int)$e['id'],
        'voucher_no' => $e['linked_voucher_no'],
        'project' => $e['project_name'],
        'project_id' => $e['project_id'] ? (int)$e['project_id'] : 0,
        'is_entry' => true,
    ];
}
foreach ($voucherLines as $vl) {
    $amt = (float)$vl['debit'] > 0 ? (float)$vl['debit'] : (float)$vl['credit'];
    $narr = trim($vl['voucher_narration'] ?? '') !== '' ? $vl['voucher_narration'] : ($vl['item_description'] ?? '');
    $ledger[] = [
        'date' => $vl['voucher_date'],
        'entry_no' => $vl['voucher_no'],
        'entry_type' => (float)$vl['credit'] > 0 ? 'payable' : 'paid',
        'amount' => $amt,
        'narration' => $narr,
        'entry_id' => 0,
        'voucher_no' => $vl['voucher_no'],
        'project' => $vl['project_name'],
        'project_id' => $vl['project_id'] ? (int)$vl['project_id'] : 0,
        'is_entry' => false,
    ];
}
usort($ledger, function ($a, $b) {
    return strcmp($a['date'], $b['date']) ?: strcmp($a['entry_no'], $b['entry_no']);
});
if ($filterProject > 0) {
    $ledger = array_values(array_filter($ledger, function ($r) use ($filterProject) {
        return $r['project_id'] === $filterProject;
    }));
}

$totalPayable = 0.0;
$totalPaid = 0.0;
$running = [];
$bal = 0.0;
foreach ($ledger as $i => $row) {
    if ($row['entry_type'] === 'payable') {
        $totalPayable += $row['amount'];
        $bal += $row['amount'];
    } else {
        $totalPaid += $row['amount'];
        $bal -= $row['amount'];
    }
    $running[$i] = $bal;
}
$balance = $totalPayable - $totalPaid;

$typeLabels = [
    'payable' => ['Payable', 'danger'],
    'paid' => ['Paid', 'success'],
];

include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="contractors.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= e($contractor['full_name']) ?></h5>
    <span class="badge bg-light text-dark border"><?= e($contractor['contractor_no']) ?></span>
    <?= $contractor['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?>
    <?php foreach ($contractorProjects as $cp): ?>
    <a class="badge bg-info-subtle text-info-emphasis border text-decoration-none" href="projects.php?id=<?= $cp['project_id'] ?>"><i class="bi bi-building me-1"></i><?= e($cp['name']) ?></a>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-arrow-down-circle"></i></div><div><div class="stat-label">PAYABLE</div><div class="stat-value"><?= fmt_money($totalPayable) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-coin"></i></div><div><div class="stat-label">PAID</div><div class="stat-value"><?= fmt_money($totalPaid) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-red"><div class="stat-body"><div class="stat-icon"><i class="bi bi-wallet2"></i></div><div><div class="stat-label">BALANCE</div><div class="stat-value"><?= fmt_money($balance) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-people"></i></div><div><div class="stat-label">SPECIALTY</div><div class="stat-value small fw-medium"><?= e($contractor['specialty'] ?: '-') ?></div></div></div></div></div>
</div>

<?php if ($canEdit): ?>
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-pencil-square me-2"></i>Add Entry</div>
    <div class="card-body">
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="entry_add">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Entry Type</label>
                    <select name="entry_type" id="selType" class="form-select" required>
                        <option value="payable">Payable</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Entry No</label>
                    <input type="text" name="entry_no" class="form-control" placeholder="Auto if blank">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="entry_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required data-mask-money>
                </div>

                <div class="col-md-6" id="secPayable" data-sec="payable">
                    <label class="form-label">Debit Account (Expense)</label>
                    <select name="account_id" class="form-select">
                        <option value="">Select Account</option>
                        <?php foreach ($accounts as $a): ?><option value="<?= $a['id'] ?>" <?= $constructionAcc && (int)$constructionAcc['id'] === (int)$a['id'] ? 'selected' : '' ?>><?= e($a['code']) ?> - <?= e($a['name']) ?></option><?php endforeach; ?>
                    </select>
                    <div class="form-text">Contractor payable kis expense head par lagega (default Construction Expense).</div>
                </div>

                <div class="col-md-6 d-none" id="secPayment" data-sec="paid">
                    <label class="form-label">Paid From</label>
                    <select name="pay_from" class="form-select">
                        <option value="cash">Cash</option>
                        <?php foreach ($banks as $b): ?><option value="bank:<?= $b['id'] ?>"><?= e($b['name']) ?> - <?= e($b['account_no'] ?? '') ?></option><?php endforeach; ?>
                    </select>
                    <div class="form-text">Payment kis se ki gayi.</div>
                </div>

                <div class="col-12">
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Project-wise Summary</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Project</th><th class="text-end">Payable</th><th class="text-end">Paid</th><th class="text-end">Balance</th></tr></thead>
                <tbody>
                <?php foreach ($projectSummary as $ps): ?>
                    <tr>
                        <td class="fw-medium"><a class="text-decoration-none" href="projects.php?id=<?= $ps['project_id'] ?>"><?= e($ps['name']) ?></a></td>
                        <td class="text-end"><?= fmt_money($ps['payable']) ?></td>
                        <td class="text-end"><?= fmt_money($ps['paid']) ?></td>
                        <td class="text-end fw-medium <?= $ps['balance'] > 0 ? 'text-danger' : ($ps['balance'] < 0 ? 'text-success' : '') ?>"><?= fmt_money($ps['balance']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($unassignedPayable || $unassignedPaid): ?>
                    <tr>
                        <td class="text-muted">No Project</td>
                        <td class="text-end"><?= fmt_money($unassignedPayable) ?></td>
                        <td class="text-end"><?= fmt_money($unassignedPaid) ?></td>
                        <td class="text-end fw-medium <?= ($unassignedPayable - $unassignedPaid) > 0 ? 'text-danger' : (($unassignedPayable - $unassignedPaid) < 0 ? 'text-success' : '') ?>"><?= fmt_money($unassignedPayable - $unassignedPaid) ?></td>
                    </tr>
                <?php endif; ?>
                <?php if (!$projectSummary && !($unassignedPayable || $unassignedPaid)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">No entries yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Project</label>
                            <select name="project_id" class="form-select">
                                <option value="">No Project</option>
                                <?php foreach ($contractorProjects as $cp): ?><option value="<?= $cp['project_id'] ?>"><?= e($cp['name']) ?></option><?php endforeach; ?>
                            </select>
                            <div class="form-text">Entry kis project ke khilaf hai.</div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Narration</label>
                            <input type="text" name="narration" class="form-control" placeholder="Optional">
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Save Entry</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div><i class="bi bi-building me-2"></i>Projects (<?= count($contractorProjects) ?>)</div>
        <?php if ($canEdit): ?>
        <form method="post" class="d-flex gap-2 align-items-center">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="project_add">
            <select name="project_id" class="form-select form-select-sm" style="max-width:260px" required>
                <option value="">Link a project...</option>
                <?php $linkedIds = array_column($contractorProjects, 'project_id'); ?>
                <?php foreach ($projects as $p): if (in_array($p['id'], $linkedIds)) continue; ?>
                <option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg"></i></button>
        </form>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($contractorProjects): ?>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($contractorProjects as $cp): ?>
            <div class="border rounded px-3 py-2 d-flex align-items-center gap-3">
                <div>
                    <a class="fw-medium text-decoration-none" href="projects.php?id=<?= $cp['project_id'] ?>"><?= e($cp['name']) ?></a>
                    <div class="small text-muted"><?= e($cp['location'] ?: '-') ?></div>
                </div>
                <?php if ($canEdit): ?>
                <form method="post" class="m-0" data-confirm="Remove this project from the contractor?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="project_remove">
                    <input type="hidden" name="project_id" value="<?= $cp['project_id'] ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-muted small py-2">Is contractor se abhi koi project link nahi hai.</div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-journal-text"></i><span>Ledger</span>
                    <?php if ($filterProject > 0): ?><span class="badge bg-info"><?= e($filterProjectName) ?></span><?php endif; ?>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <form method="get" class="d-flex align-items-center gap-2">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <select name="project_id" class="form-select form-select-sm" style="max-width:220px" onchange="this.form.submit()">
                            <option value="0">All Projects</option>
                            <?php foreach ($contractorProjects as $cp): ?><option value="<?= $cp['project_id'] ?>" <?= $filterProject === (int)$cp['project_id'] ? 'selected' : '' ?>><?= e($cp['name']) ?></option><?php endforeach; ?>
                        </select>
                    </form>
                    <a class="btn btn-sm btn-outline-secondary" href="contractor_ledger_print.php?contractor_id=<?= $id ?><?= $filterProject > 0 ? '&amp;project_id=' . $filterProject : '' ?>" target="_blank"><i class="bi bi-printer me-1"></i>Print</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Entry No</th><th>Type</th><th>Project</th><th>Narration</th><th class="text-end">Payable</th><th class="text-end">Paid</th><th class="text-end">Balance</th><?php if ($canEdit): ?><th class="text-end"></th><?php endif; ?></tr></thead>
                        <tbody>
                        <?php foreach ($ledger as $i => $row): ?>
                            <tr>
                                <td><?= fmt_date($row['date']) ?></td>
                                <td class="fw-medium">
                                    <?= e($row['entry_no']) ?>
                                    <?php if ($row['is_entry']): ?>
                                        <?= $row['voucher_no'] ? ' <a class="small" href="vouchers.php" title="Journal voucher created"><i class="bi bi-journal-arrow-up text-muted"></i></a>' : '' ?>
                                    <?php else: ?>
                                        <a class="small" href="vouchers.php" title="Posted from journal voucher"><i class="bi bi-journal-check text-muted"></i></a>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-<?= $typeLabels[$row['entry_type']][1] ?? 'secondary' ?>"><?= e($typeLabels[$row['entry_type']][0] ?? $row['entry_type']) ?></span></td>
                                <td class="small"><?= $row['project'] ? e($row['project']) : '<span class="text-muted">-</span>' ?></td>
                                <td class="small"><?= e($row['narration'] !== '' ? $row['narration'] : '-') ?></td>
                                <td class="text-end"><?= $row['entry_type'] === 'payable' ? fmt_money($row['amount']) : '-' ?></td>
                                <td class="text-end"><?= $row['entry_type'] === 'paid' ? fmt_money($row['amount']) : '-' ?></td>
                                <td class="text-end fw-medium <?= $running[$i] > 0 ? 'text-danger' : ($running[$i] < 0 ? 'text-success' : '') ?>"><?= fmt_money($running[$i]) ?></td>
                                <?php if ($canEdit): ?>
                                <td class="text-end">
                                    <?php if ($row['is_entry']): ?>
                                    <form method="post" class="d-inline" data-confirm="Delete this entry?<?= $row['voucher_no'] ? ' The linked journal voucher will also be removed.' : '' ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="entry_delete">
                                        <input type="hidden" name="id" value="<?= $row['entry_id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$ledger): ?><tr><td colspan="<?= $canEdit ? 9 : 8 ?>" class="text-center text-muted py-4">No entries yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Contractor Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Company</dt><dd class="col-sm-8"><?= e($contractor['company'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Specialty</dt><dd class="col-sm-8"><?= e($contractor['specialty'] ?: '-') ?></dd>
                    <dt class="col-sm-4">CNIC</dt><dd class="col-sm-8"><?= e($contractor['cnic'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Phone</dt><dd class="col-sm-8"><?= e($contractor['phone'] ?: '-') ?></dd>
                    <dt class="col-sm-4">WhatsApp</dt><dd class="col-sm-8"><?= e($contractor['whatsapp'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8"><?= e($contractor['email'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Bank</dt><dd class="col-sm-8"><?= e($contractor['bank_name'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Bank Title</dt><dd class="col-sm-8"><?= e($contractor['bank_account_title'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Bank Account</dt><dd class="col-sm-8"><?= e($contractor['bank_account_no'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Address</dt><dd class="col-sm-8"><?= e($contractor['address'] ?: '-') ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<script>
(function () {
    var sel = document.getElementById('selType');
    var sections = document.querySelectorAll('[data-sec]');
    function show(sec) {
        sections.forEach(function (s) {
            s.classList.toggle('d-none', s.dataset.sec !== sec);
        });
    }
    sel.addEventListener('change', function () { show(sel.value); });
    show(sel.value);
})();
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

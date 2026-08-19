<?php
require_once '../includes/auth.php';
require_login();
require_permission('investors.view');
$title = 'Investor Details';
$active = 'investors';
$canManage = has_permission('investors.manage');

$id = (int)($_GET['id'] ?? 0);
$investor = db_get("SELECT * FROM investors WHERE id = ?", [$id]);
if (!$investor) {
    flash('danger', 'Investor not found.');
    redirect('investors.php');
}

if (is_post() && $canManage) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'add_entry') {
        $entryDate = $_POST['entry_date'] ?? date('Y-m-d');
        $entryType = $_POST['entry_type'] ?? 'credit';
        $amount = (float)($_POST['amount'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if ($amount <= 0) {
            flash('danger', 'Enter a valid amount.');
        } else {
            $lastBal = (float)db_get("SELECT COALESCE(MAX(balance),0) b FROM investor_ledger WHERE investor_id = ?", [$id])['b'];
            $debit = $entryType === 'debit' ? $amount : 0;
            $credit = $entryType === 'credit' ? $amount : 0;
            $newBal = $lastBal + $credit - $debit;

            db_exec("INSERT INTO investor_ledger (investor_id, entry_date, description, debit, credit, balance, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                [$id, $entryDate, $description, $debit, $credit, $newBal]);
            flash('success', 'Ledger entry added.');
        }
        redirect('investor_view.php?id=' . $id);
    } elseif ($action === 'delete_entry') {
        $entryId = (int)($_POST['entry_id'] ?? 0);
        $delEntry = db_get("SELECT * FROM investor_ledger WHERE id = ? AND investor_id = ?", [$entryId, $id]);
        if ($delEntry) {
            db_exec("DELETE FROM investor_ledger WHERE id = ?", [$entryId]);
            $allRows = db_all("SELECT id, debit, credit FROM investor_ledger WHERE investor_id = ? ORDER BY entry_date, id", [$id]);
            $runningBal = 0.0;
            foreach ($allRows as $row) {
                $runningBal += (float)$row['credit'] - (float)$row['debit'];
                db_exec("UPDATE investor_ledger SET balance = ? WHERE id = ?", [$runningBal, $row['id']]);
            }
            flash('success', 'Ledger entry deleted and balances recalculated.');
        }
        redirect('investor_view.php?id=' . $id);
    }
}

$ledgerStart = trim($_GET['start_date'] ?? '');
$ledgerEnd = trim($_GET['end_date'] ?? '');

$allLedger = db_all("SELECT * FROM investor_ledger WHERE investor_id = ? ORDER BY entry_date, id", [$id]);

$filteredLedger = [];
foreach ($allLedger as $l) {
    if ($ledgerStart !== '' && $l['entry_date'] < $ledgerStart) continue;
    if ($ledgerEnd !== '' && $l['entry_date'] > $ledgerEnd) continue;
    $filteredLedger[] = $l;
}

$totalCredit = 0;
$totalDebit = 0;
foreach ($filteredLedger as $l) {
    $totalCredit += (float)$l['credit'];
    $totalDebit += (float)$l['debit'];
}
$balance = $totalCredit - $totalDebit;

$openingBalance = 0.0;
if ($ledgerStart !== '') {
    $ob = db_get("SELECT COALESCE(MAX(balance),0) b FROM investor_ledger WHERE investor_id = ? AND entry_date < ?", [$id, $ledgerStart]);
    $openingBalance = (float)$ob['b'];
}

include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="investors.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= e($investor['full_name']) ?></h5>
    <span class="badge bg-info"><?= e($investor['investor_no']) ?></span>
    <?php if ($investor['status']): ?><span class="badge bg-success">Active</span><?php else: ?><span class="badge bg-secondary">Inactive</span><?php endif; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-arrow-down-circle"></i></div><div><div class="stat-label">TOTAL INVESTED</div><div class="stat-value"><?= fmt_money($totalCredit) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-arrow-up-circle"></i></div><div><div class="stat-label">TOTAL PAID OUT</div><div class="stat-value"><?= fmt_money($totalDebit) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card <?= $balance > 0 ? 'bg-grad-orange' : 'bg-grad-cyan' ?>"><div class="stat-body"><div class="stat-icon"><i class="bi bi-wallet2"></i></div><div><div class="stat-label">NET BALANCE</div><div class="stat-value"><?= fmt_money($balance) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-person-lines-fill"></i></div><div><div class="stat-label">TYPE</div><div class="stat-value fs-6"><?= e($investor['investment_type'] ?? '-') ?></div></div></div></div></div>
</div>

<ul class="nav nav-pills mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#iProfile">Profile</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#iLedger">Ledger (<?= count($allLedger) ?>)</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="iProfile">
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3"><div class="text-muted small">Investor No</div><div class="fw-medium"><?= e($investor['investor_no']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">CNIC</div><div class="fw-medium"><?= e($investor['cnic'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Phone</div><div class="fw-medium"><?= e($investor['phone'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">WhatsApp</div><div class="fw-medium"><?= e($investor['whatsapp'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Email</div><div class="fw-medium"><?= e($investor['email'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Investment Type</div><div class="fw-medium"><?= e($investor['investment_type'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Bank Account Title</div><div class="fw-medium"><?= e($investor['bank_account_title'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Bank Account No</div><div class="fw-medium"><?= e($investor['bank_account_no'] ?? '-') ?></div></div>
                    <div class="col-md-6"><div class="text-muted small">Address</div><div class="fw-medium"><?= e($investor['address'] ?? '-') ?></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="iLedger">
        <?php if ($canManage): ?>
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-plus-circle me-1"></i>Add Ledger Entry</div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_entry">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label">Date</label>
                            <input type="date" name="entry_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Type</label>
                            <select name="entry_type" class="form-select" required>
                                <option value="credit">Credit (Investment In)</option>
                                <option value="debit">Debit (Payout)</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control" placeholder="Optional">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-check-lg me-1"></i>Add</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="get" action="investor_view.php#iLedger" class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <div class="input-group input-group-sm" style="max-width:170px">
                        <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                        <input type="date" name="start_date" class="form-control" value="<?= e($ledgerStart) ?>">
                    </div>
                    <div class="input-group input-group-sm" style="max-width:170px">
                        <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                        <input type="date" name="end_date" class="form-control" value="<?= e($ledgerEnd) ?>">
                    </div>
                    <button class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="investor_view.php?id=<?= $id ?>#iLedger" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
                    <a href="investor_ledger_print.php?id=<?= $id ?>&start_date=<?= e($ledgerStart) ?>&end_date=<?= e($ledgerEnd) ?>" class="btn btn-outline-secondary btn-sm ms-auto" target="_blank"><i class="bi bi-printer me-1"></i> Print</a>
                </form>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Description</th><th>Credit (In)</th><th>Debit (Out)</th><th>Balance</th><?php if ($canManage): ?><th class="text-end">Action</th><?php endif; ?></tr></thead>
                        <tbody>
                        <?php
                        $bal = $openingBalance;
                        if ($ledgerStart !== '' || $ledgerEnd !== '') {
                            echo '<tr><td>' . fmt_date($ledgerStart) . '</td><td>Opening Balance</td><td>-</td><td>-</td><td>' . fmt_money($bal) . '</td>' . ($canManage ? '<td></td>' : '') . '</tr>';
                        }
                        foreach ($filteredLedger as $l):
                            $bal += (float)$l['credit'] - (float)$l['debit'];
                        ?>
                            <tr>
                                <td><?= fmt_date($l['entry_date']) ?></td>
                                <td><?= e($l['description'] ?? '-') ?></td>
                                <td class="text-success"><?= (float)$l['credit'] > 0 ? fmt_money($l['credit']) : '-' ?></td>
                                <td class="text-danger"><?= (float)$l['debit'] > 0 ? fmt_money($l['debit']) : '-' ?></td>
                                <td class="fw-bold <?= $bal > 0 ? 'text-success' : ($bal < 0 ? 'text-danger' : '') ?>"><?= fmt_money($bal) ?></td>
                                <?php if ($canManage): ?>
                                <td class="text-end">
                                    <form method="post" class="d-inline" data-confirm="Delete this entry?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_entry">
                                        <input type="hidden" name="entry_id" value="<?= $l['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$filteredLedger && $ledgerStart === '' && $ledgerEnd === ''): ?>
                            <tr><td colspan="<?= $canManage ? 6 : 5 ?>" class="text-center text-muted py-4">No ledger entries yet</td></tr>
                        <?php elseif (!$filteredLedger): ?>
                            <tr><td colspan="<?= $canManage ? 6 : 5 ?>" class="text-center text-muted py-4">No entries in this date range</td></tr>
                        <?php endif; ?>
                        </tbody>
                        <tfoot>
                        <tr class="table-light">
                            <td colspan="<?= ($ledgerStart !== '' || $ledgerEnd !== '') ? 4 : 3 ?>" class="text-end fw-bold"><?= ($ledgerStart !== '' || $ledgerEnd !== '') ? 'Closing Balance' : 'Net Balance' ?></td>
                            <td class="fw-bold <?= $balance > 0 ? 'text-success' : ($balance < 0 ? 'text-danger' : '') ?>" colspan="<?= $canManage ? 2 : 1 ?>"><?= fmt_money($bal) ?></td>
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

<?php
require_once '../includes/auth.php';
require_login();
require_permission('general_parties.view');
$title = 'General Party';
$active = 'general_parties';
$canEdit = has_permission('general_parties.manage');

function general_party_account_id($party_id, $party_name) {
    $code = '2000-' . str_pad((int)$party_id, 3, '0', STR_PAD_LEFT);
    $acc = db_get("SELECT id FROM chart_of_accounts WHERE code = ?", [$code]);
    if ($acc) {
        return (int)$acc['id'];
    }
    $parent = db_get("SELECT id FROM chart_of_accounts WHERE code = '2000'");
    return (int)db_exec("INSERT INTO chart_of_accounts (code, name, account_type, parent_id, opening_balance, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,0,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
        [$code, $party_name, 'liability', $parent ? (int)$parent['id'] : null]);
}

function general_party_bank_account_id($bank_id) {
    $bank = db_get("SELECT * FROM banks WHERE id = ?", [$bank_id]);
    if (!$bank) {
        return 0;
    }
    $code = '1001-' . str_pad((int)$bank_id, 3, '0', STR_PAD_LEFT);
    $acc = db_get("SELECT id FROM chart_of_accounts WHERE code = ?", [$code]);
    if ($acc) {
        return (int)$acc['id'];
    }
    $parent = db_get("SELECT id FROM chart_of_accounts WHERE code = '1001'");
    return (int)db_exec("INSERT INTO chart_of_accounts (code, name, account_type, parent_id, opening_balance, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,0,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
        [$code, $bank['name'], 'asset', $parent ? (int)$parent['id'] : null]);
}

function post_party_voucher($date, $narration, $lines) {
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
$party = db_get("SELECT * FROM general_parties WHERE id = ?", [$id]);
if (!$party) {
    flash('danger', 'Party not found.');
    redirect('general_parties.php');
}

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'entry_add') {
        $type = $_POST['entry_type'] ?? '';
        $date = $_POST['entry_date'] ?? date('Y-m-d');
        $amount = (float)($_POST['amount'] ?? 0);
        $narration = trim($_POST['narration'] ?? '');
        $entry_no = trim($_POST['entry_no'] ?? '');
        if ($entry_no === '') {
            $entry_no = next_number('GPE', 'general_party_entries', 'entry_no');
        }
        $valid = in_array($type, ['payable', 'paid', 'receiving'], true);
        if (!$valid) {
            flash('danger', 'Invalid entry type.');
            redirect('general_party_view.php?id=' . $id);
        }
        if ($amount <= 0) {
            flash('danger', 'Enter a valid amount.');
            redirect('general_party_view.php?id=' . $id);
        }

        $partyAcc = general_party_account_id($id, $party['party_name']);
        $voucherId = null;
        $narr = $narration !== '' ? $narration : ucfirst($type) . ' - ' . $party['party_name'];
        $accountId = null;

        if ($type === 'payable') {
            $accountId = (int)($_POST['account_id'] ?? 0);
            if (!$accountId) {
                flash('danger', 'Select the account to debit.');
                redirect('general_party_view.php?id=' . $id);
            }
            $voucherId = post_party_voucher($date, $narr, [
                [$accountId, 'Payable to ' . $party['party_name'], $amount, 0],
                [$partyAcc, 'Payable to ' . $party['party_name'], 0, $amount],
            ]);
        } else {
            $payFrom = $_POST['pay_from'] ?? '';
            $creditAcc = 0;
            $creditName = 'Cash';
            if ($payFrom === 'cash') {
                $cashAcc = db_get("SELECT id FROM chart_of_accounts WHERE code = '1000'");
                if (!$cashAcc) {
                    flash('danger', 'Cash account not found in chart of accounts.');
                    redirect('general_party_view.php?id=' . $id);
                }
                $creditAcc = (int)$cashAcc['id'];
            } elseif (strpos($payFrom, 'bank:') === 0) {
                $bankId = (int)substr($payFrom, 5);
                if ($bankId <= 0) {
                    flash('danger', 'Select a bank.');
                    redirect('general_party_view.php?id=' . $id);
                }
                $bank = db_get("SELECT * FROM banks WHERE id = ?", [$bankId]);
                if (!$bank) {
                    flash('danger', 'Bank not found.');
                    redirect('general_party_view.php?id=' . $id);
                }
                $bankAcc = general_party_bank_account_id($bankId);
                if (!$bankAcc) {
                    flash('danger', 'Bank account not found in chart of accounts.');
                    redirect('general_party_view.php?id=' . $id);
                }
                $creditAcc = (int)$bankAcc;
                $creditName = $bank['name'];
            } else {
                flash('danger', 'Select where the money comes from.');
                redirect('general_party_view.php?id=' . $id);
            }
            $voucherId = post_party_voucher($date, $narr, [
                [$partyAcc, ucfirst($type) . ' to ' . $party['party_name'], $amount, 0],
                [$creditAcc, ucfirst($type) . ' - ' . $creditName, 0, $amount],
            ]);
        }

        db_exec("INSERT INTO general_party_entries (party_id, entry_no, entry_date, entry_type, amount, narration, account_id, voucher_id, created_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$id, $entry_no, $date, $type, $amount, $narr, $accountId, $voucherId, $user['id']]);
        flash('success', $type . ' entry ' . $entry_no . ' saved.');
    } elseif ($action === 'entry_delete') {
        $eid = (int)($_POST['id'] ?? 0);
        $ent = db_get("SELECT * FROM general_party_entries WHERE id = ? AND party_id = ?", [$eid, $id]);
        if ($ent) {
            if ($ent['voucher_id']) {
                db_exec("DELETE FROM vouchers WHERE id = ?", [$ent['voucher_id']]);
            }
            db_exec("DELETE FROM general_party_entries WHERE id = ?", [$eid]);
            flash('success', 'Entry deleted.');
        }
    }
    redirect('general_party_view.php?id=' . $id);
}

$entries = db_all("SELECT gpe.*, u.full_name AS created_name FROM general_party_entries gpe LEFT JOIN users u ON u.id = gpe.created_by WHERE gpe.party_id = ? ORDER BY gpe.entry_date, gpe.id", [$id]);
$banks = db_all("SELECT * FROM banks ORDER BY name");
$accounts = db_all("SELECT * FROM chart_of_accounts ORDER BY code");

$totalPayable = 0.0;
$totalPaid = 0.0;
$totalReceiving = 0.0;
$running = [];
$bal = 0.0;
foreach ($entries as $e) {
    $amt = (float)$e['amount'];
    if ($e['entry_type'] === 'payable') {
        $totalPayable += $amt;
        $bal += $amt;
    } elseif ($e['entry_type'] === 'paid') {
        $totalPaid += $amt;
        $bal -= $amt;
    } else {
        $totalReceiving += $amt;
        $bal -= $amt;
    }
    $running[$e['id']] = $bal;
}
$balance = $totalPayable - $totalPaid - $totalReceiving;

$typeLabels = [
    'payable' => ['Payable', 'danger'],
    'paid' => ['Paid', 'success'],
    'receiving' => ['Receiving', 'info'],
];

include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="general_parties.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= e($party['party_name']) ?></h5>
    <span class="badge bg-light text-dark border"><?= e($party['party_no']) ?></span>
    <?= $party['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-arrow-down-circle"></i></div><div><div class="stat-label">PAYABLE</div><div class="stat-value"><?= fmt_money($totalPayable) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-coin"></i></div><div><div class="stat-label">PAID</div><div class="stat-value"><?= fmt_money($totalPaid) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-arrow-up-circle"></i></div><div><div class="stat-label">RECEIVING</div><div class="stat-value"><?= fmt_money($totalReceiving) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card <?= $balance > 0 ? 'bg-grad-red' : ($balance < 0 ? 'bg-grad-cyan' : 'bg-grad-blue') ?>"><div class="stat-body"><div class="stat-icon"><i class="bi bi-wallet2"></i></div><div><div class="stat-label">BALANCE</div><div class="stat-value"><?= fmt_money($balance) ?></div></div></div></div></div>
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
                        <option value="receiving">Receiving</option>
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
                    <label class="form-label">Debit Account</label>
                    <select name="account_id" class="form-select">
                        <option value="">Select Account</option>
                        <?php foreach ($accounts as $a): ?><option value="<?= $a['id'] ?>"><?= e($a['code']) ?> - <?= e($a['name']) ?></option><?php endforeach; ?>
                    </select>
                    <div class="form-text">Jis account par ye payable lagega (maslan expense).</div>
                </div>

                <div class="col-md-6 d-none" id="secPayment" data-sec="paid">
                    <label class="form-label">Payment From</label>
                    <select name="pay_from" class="form-select">
                        <option value="cash">Cash</option>
                        <?php foreach ($banks as $b): ?><option value="bank:<?= $b['id'] ?>"><?= e($b['name']) ?> - <?= e($b['account_no'] ?? '') ?></option><?php endforeach; ?>
                    </select>
                    <div class="form-text">Paise kahaan se diye gaye.</div>
                </div>

                <div class="col-md-6 d-none" id="secReceiving" data-sec="receiving">
                    <label class="form-label">Payment From</label>
                    <select name="pay_from" class="form-select">
                        <option value="cash">Cash</option>
                        <?php foreach ($banks as $b): ?><option value="bank:<?= $b['id'] ?>"><?= e($b['name']) ?> - <?= e($b['account_no'] ?? '') ?></option><?php endforeach; ?>
                    </select>
                    <div class="form-text">Jahan se receiving / payment ho rahi hai.</div>
                </div>

                <div class="col-12">
                    <label class="form-label">Narration</label>
                    <input type="text" name="narration" class="form-control" placeholder="Optional">
                </div>
            </div>
            <div class="mt-3">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Save Entry</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-journal-text me-2"></i>Ledger</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Entry No</th><th>Type</th><th>Narration</th><th class="text-end">Payable</th><th class="text-end">Paid</th><th class="text-end">Receiving</th><th class="text-end">Balance</th><?php if ($canEdit): ?><th class="text-end"></th><?php endif; ?></tr></thead>
                        <tbody>
                        <?php foreach ($entries as $e): ?>
                            <tr>
                                <td><?= fmt_date($e['entry_date']) ?></td>
                                <td class="fw-medium"><?= e($e['entry_no']) ?><?= $e['voucher_id'] ? ' <a class="small" href="vouchers.php" title="Journal voucher created"><i class="bi bi-journal-arrow-up text-muted"></i></a>' : '' ?></td>
                                <td><span class="badge bg-<?= $typeLabels[$e['entry_type']][1] ?? 'secondary' ?>"><?= e($typeLabels[$e['entry_type']][0] ?? $e['entry_type']) ?></span></td>
                                <td class="small"><?= e($e['narration'] ?? '-') ?></td>
                                <td class="text-end"><?= $e['entry_type'] === 'payable' ? fmt_money($e['amount']) : '-' ?></td>
                                <td class="text-end"><?= $e['entry_type'] === 'paid' ? fmt_money($e['amount']) : '-' ?></td>
                                <td class="text-end"><?= $e['entry_type'] === 'receiving' ? fmt_money($e['amount']) : '-' ?></td>
                                <td class="text-end fw-medium <?= $running[$e['id']] > 0 ? 'text-danger' : ($running[$e['id']] < 0 ? 'text-success' : '') ?>"><?= fmt_money($running[$e['id']]) ?></td>
                                <?php if ($canEdit): ?>
                                <td class="text-end">
                                    <form method="post" class="d-inline" data-confirm="Delete this entry?<?= $e['voucher_id'] ? ' The linked journal voucher will also be removed.' : '' ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="entry_delete">
                                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$entries): ?><tr><td colspan="<?= $canEdit ? 9 : 8 ?>" class="text-center text-muted py-4">No entries yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Party Details</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Party No</dt><dd class="col-sm-8"><?= e($party['party_no']) ?></dd>
                    <dt class="col-sm-4">Contact Person</dt><dd class="col-sm-8"><?= e($party['contact_person'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Phone</dt><dd class="col-sm-8"><?= e($party['phone'] ?: '-') ?></dd>
                    <dt class="col-sm-4">WhatsApp</dt><dd class="col-sm-8"><?= e($party['whatsapp'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8"><?= e($party['email'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Address</dt><dd class="col-sm-8"><?= e($party['address'] ?: '-') ?></dd>
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

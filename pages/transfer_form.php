<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.manage');
$active = 'transfers';

$edit_id = (int)($_GET['id'] ?? 0);
$record = null;
if ($edit_id > 0) {
    $record = db_get("SELECT * FROM transfers WHERE id = ?", [$edit_id]);
    if (!$record) {
        flash('danger', 'Transfer not found.');
        redirect('transfers.php');
    }
    if ($record['transfer_type'] === 'customer_withdraw') {
        flash('danger', 'A booking withdraw cannot be edited.');
        redirect('transfers.php');
    }
    $title = 'Edit Transfer - ' . $record['transfer_no'];
} else {
    $title = 'New Transfer / Withdraw';
}

$wFrom = 'cash';
if ($record && $record['account_id']) {
    $wa = db_get("SELECT code FROM chart_of_accounts WHERE id = ?", [$record['account_id']]);
    if ($wa && preg_match('/^1001-(\d+)$/', $wa['code'], $wm)) {
        $wFrom = 'bank:' . (int)$wm[1];
    }
}

function bank_account_id($bank_id) {
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

function post_transfer_voucher($date, $narration, $lines, $project_id = null) {
    $voucher_no = next_number('JV', 'vouchers', 'voucher_no');
    $vid = db_exec("INSERT INTO vouchers (voucher_no, voucher_date, voucher_type, project_id, narration, status, created_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
        [$voucher_no, $date, 'journal', $project_id, $narration, 'posted', $GLOBALS['user']['id']]);
    foreach ($lines as $l) {
        db_exec("INSERT INTO voucher_items (voucher_id, account_id, item_description, debit, credit, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$vid, $l[0], $l[1], $l[2], $l[3]]);
    }
    return $vid;
}

$project_id = $edit_id > 0 ? (int)($record['project_id'] ?? 0) : active_project_id();
$project_id = $project_id ?: null;

if (is_post()) {
    csrf_check();
    $type = $_POST['transfer_type'] ?? '';
    $date = $_POST['transfer_date'] ?? date('Y-m-d');
    $amount = (float)($_POST['amount'] ?? 0);
    $narration = trim($_POST['narration'] ?? '');
    $transfer_no = trim($_POST['transfer_no'] ?? '');
    if ($transfer_no === '') {
        $transfer_no = $edit_id > 0 ? $record['transfer_no'] : next_number('TR', 'transfers', 'transfer_no');
    }
    $valid = in_array($type, ['customer_to_customer', 'bank_to_cash', 'bank_to_bank', 'customer_withdraw', 'owner_withdraw'], true);
    if (!$valid) {
        flash('danger', 'Invalid transfer type.');
        redirect('transfer_form.php');
    }

    $fromCust = (int)($_POST['from_customer_id'] ?? 0) ?: null;
    $toCust = (int)($_POST['to_customer_id'] ?? 0) ?: null;
    $fromBank = (int)($_POST['from_bank_id'] ?? 0) ?: null;
    $toBank = (int)($_POST['to_bank_id'] ?? 0) ?: null;
    $bookingId = (int)($_POST['booking_id'] ?? 0) ?: null;
    $withdrawFrom = $_POST['withdraw_from'] ?? 'cash';

    $voucherId = null;
    $ok = false;
    $narr = '';

    if ($type === 'customer_to_customer') {
        if (!$fromCust || !$toCust) {
            flash('danger', 'Select both customers.');
            redirect('transfer_form.php');
        }
        if ($fromCust === $toCust) {
            flash('danger', 'From and To customers cannot be the same.');
            redirect('transfer_form.php');
        }
        if ($amount <= 0) {
            flash('danger', 'Enter a valid amount.');
            redirect('transfer_form.php');
        }
        $narr = $narration !== '' ? $narration : 'Balance transfer';
        $ok = true;
    } elseif ($type === 'bank_to_cash') {
        if (!$fromBank) {
            flash('danger', 'Select the bank.');
            redirect('transfer_form.php');
        }
        if ($amount <= 0) {
            flash('danger', 'Enter a valid amount.');
            redirect('transfer_form.php');
        }
        $cashAcc = db_get("SELECT id FROM chart_of_accounts WHERE code = '1000'");
        $bankAcc = bank_account_id($fromBank);
        if (!$cashAcc || !$bankAcc) {
            flash('danger', 'Cash / bank account not found in chart of accounts.');
            redirect('transfer_form.php');
        }
        $fromBankName = db_get("SELECT name FROM banks WHERE id = ?", [$fromBank])['name'] ?? 'Bank';
        $narr = $narration !== '' ? $narration : 'Cash withdrawal from ' . $fromBankName;
        $voucherId = post_transfer_voucher($date, $narr, [
            [(int)$cashAcc['id'], 'Cash withdrawal from bank', $amount, 0],
            [$bankAcc, 'Cash withdrawal from bank', 0, $amount],
        ], $project_id);
        $ok = true;
    } elseif ($type === 'bank_to_bank') {
        if (!$fromBank || !$toBank) {
            flash('danger', 'Select both banks.');
            redirect('transfer_form.php');
        }
        if ($fromBank === $toBank) {
            flash('danger', 'From and To banks cannot be the same.');
            redirect('transfer_form.php');
        }
        if ($amount <= 0) {
            flash('danger', 'Enter a valid amount.');
            redirect('transfer_form.php');
        }
        $fromAcc = bank_account_id($fromBank);
        $toAcc = bank_account_id($toBank);
        if (!$fromAcc || !$toAcc) {
            flash('danger', 'Bank account not found in chart of accounts.');
            redirect('transfer_form.php');
        }
        $narr = $narration !== '' ? $narration : 'Bank to bank transfer';
        $voucherId = post_transfer_voucher($date, $narr, [
            [$toAcc, 'Bank to bank transfer', $amount, 0],
            [$fromAcc, 'Bank to bank transfer', 0, $amount],
        ], $project_id);
        $ok = true;
    } elseif ($type === 'customer_withdraw') {
        $custId = (int)($_POST['customer_id'] ?? 0);
        if (!$custId || !$bookingId) {
            flash('danger', 'Select customer and booking.');
            redirect('transfer_form.php');
        }
        $booking = db_get("SELECT * FROM bookings WHERE id = ? AND customer_id = ? AND status <> 'cancelled'", [$bookingId, $custId]);
        if (!$booking) {
            flash('danger', 'Booking not found for this customer.');
            redirect('transfer_form.php');
        }
        $paid = (float)db_get("SELECT COALESCE(SUM(amount),0) amt FROM receipts WHERE booking_id = ?", [$bookingId])['amt'];
        $narr = $narration !== '' ? $narration : 'Booking withdraw ' . $booking['booking_no'];
        db_exec("UPDATE bookings SET status = 'cancelled', updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$bookingId]);
        db_exec("UPDATE properties SET status = 'available', updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$booking['property_id']]);
        db_exec("INSERT INTO transfers (transfer_no, transfer_date, transfer_type, project_id, from_customer_id, booking_id, amount, narration, created_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$transfer_no, $date, $type, $project_id, $custId, $bookingId, $paid, $narr, $user['id']]);
        flash('success', 'Booking withdrawn and property released. Refundable amount: ' . fmt_money($paid));
        redirect('transfers.php');
    } elseif ($type === 'owner_withdraw') {
        if ($amount <= 0) {
            flash('danger', 'Enter a valid amount.');
            redirect('transfer_form.php');
        }
        $fromAcc = 0;
        $fromName = 'Cash';
        if ($withdrawFrom === 'cash') {
            $cashAcc = db_get("SELECT id FROM chart_of_accounts WHERE code = '1000'");
            if (!$cashAcc) {
                flash('danger', 'Cash account not found in chart of accounts.');
                redirect('transfer_form.php');
            }
            $fromAcc = (int)$cashAcc['id'];
        } elseif (strpos($withdrawFrom, 'bank:') === 0) {
            $bankId = (int)substr($withdrawFrom, 5);
            if ($bankId <= 0) {
                flash('danger', 'Select a bank.');
                redirect('transfer_form.php');
            }
            $bank = db_get("SELECT * FROM banks WHERE id = ?", [$bankId]);
            if (!$bank) {
                flash('danger', 'Bank not found.');
                redirect('transfer_form.php');
            }
            $fromAcc = bank_account_id($bankId);
            $fromName = $bank['name'];
        } else {
            flash('danger', 'Select where to withdraw from.');
            redirect('transfer_form.php');
        }
        $equity = db_get("SELECT id FROM chart_of_accounts WHERE code = '3000'");
        if (!$fromAcc || !$equity) {
            flash('danger', 'Withdrawal / equity account not found in chart of accounts.');
            redirect('transfer_form.php');
        }
        $narr = $narration !== '' ? $narration : 'Owner / partner withdrawal from ' . $fromName;
        $voucherId = post_transfer_voucher($date, $narr, [
            [(int)$equity['id'], 'Owner / partner withdrawal', $amount, 0],
            [$fromAcc, 'Owner / partner withdrawal', 0, $amount],
        ], $project_id);
        $ok = true;
    }

    if ($ok) {
        if ($edit_id > 0) {
            if ($record['voucher_id']) {
                db_exec("DELETE FROM vouchers WHERE id = ?", [$record['voucher_id']]);
            }
            db_exec("UPDATE transfers SET transfer_no=?, transfer_date=?, transfer_type=?, project_id=?, from_customer_id=?, to_customer_id=?, from_bank_id=?, to_bank_id=?, booking_id=?, account_id=?, amount=?, narration=?, voucher_id=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?",
                [$transfer_no, $date, $type, $project_id, $fromCust, $toCust, $fromBank, $toBank, $bookingId, $fromAcc ?? null, $amount, $narr, $voucherId, $edit_id]);
            flash('success', 'Transfer ' . $transfer_no . ' updated.');
        } else {
            db_exec("INSERT INTO transfers (transfer_no, transfer_date, transfer_type, project_id, from_customer_id, to_customer_id, from_bank_id, to_bank_id, booking_id, account_id, amount, narration, voucher_id, created_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                [$transfer_no, $date, $type, $project_id, $fromCust, $toCust, $fromBank, $toBank, $bookingId, $fromAcc ?? null, $amount, $narr, $voucherId, $user['id']]);
            flash('success', 'Transfer ' . $transfer_no . ' saved.');
        }
        redirect('transfers.php');
    }
}

$customers = db_all("SELECT * FROM customers WHERE status = 1 ORDER BY full_name");
$banks = db_all("SELECT * FROM banks ORDER BY name");
$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="transfers.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= $edit_id > 0 ? 'Edit Transfer' : 'New Transfer / Withdraw' ?></h5>
</div>

<form method="post">
    <?= csrf_field() ?>
    <div class="card">
        <div class="card-header"><i class="bi bi-arrow-left-right me-2"></i>Transfer Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Transfer Type</label>
                    <select name="transfer_type" id="selType" class="form-select" required>
                        <option value="customer_to_customer" <?= ($record['transfer_type'] ?? '') === 'customer_to_customer' ? 'selected' : '' ?>>Customer to Customer</option>
                        <option value="bank_to_cash" <?= ($record['transfer_type'] ?? '') === 'bank_to_cash' ? 'selected' : '' ?>>Bank to Cash (Withdraw)</option>
                        <option value="bank_to_bank" <?= ($record['transfer_type'] ?? '') === 'bank_to_bank' ? 'selected' : '' ?>>Bank to Bank</option>
                        <option value="customer_withdraw" <?= ($record['transfer_type'] ?? '') === 'customer_withdraw' ? 'selected' : '' ?>>Customer Booking Withdraw</option>
                        <option value="owner_withdraw" <?= ($record['transfer_type'] ?? '') === 'owner_withdraw' ? 'selected' : '' ?>>Owner / Partner Withdraw</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Transfer No</label>
                    <input type="text" name="transfer_no" class="form-control" placeholder="Auto if blank" value="<?= e($record['transfer_no'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date</label>
                    <input type="date" name="transfer_date" class="form-control" value="<?= e($record['transfer_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Project</label>
                    <select name="project_id" class="form-select" disabled>
                        <option value="">-- General / No Project --</option>
                        <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= (int)$project_id === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                    </select>
                    <div class="form-text">Locked to the active project selected in the header.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><i class="bi bi-diagram-3 me-2"></i>Details</div>
        <div class="card-body">

            <div class="row g-3" id="secCustomer" data-sec="customer_to_customer">
                <div class="col-md-6">
                    <label class="form-label">From Customer</label>
                    <select name="from_customer_id" class="form-select">
                        <option value="">Select Customer</option>
                        <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>" <?= (int)($record['from_customer_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['full_name']) ?> (<?= e($c['customer_no']) ?>)</option><?php endforeach; ?>
                    </select>
                    <div class="form-text">Is customer ke ledger me credit hoga (outstanding kam).</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">To Customer</label>
                    <select name="to_customer_id" class="form-select">
                        <option value="">Select Customer</option>
                        <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>" <?= (int)($record['to_customer_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['full_name']) ?> (<?= e($c['customer_no']) ?>)</option><?php endforeach; ?>
                    </select>
                    <div class="form-text">Is customer ke ledger me debit hoga (outstanding zyada).</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" data-mask-money value="<?= e($record['amount'] ?? '') ?>">
                </div>
            </div>

            <div class="row g-3 d-none" id="secBankCash" data-sec="bank_to_cash">
                <div class="col-md-6">
                    <label class="form-label">From Bank</label>
                    <select name="from_bank_id" class="form-select">
                        <option value="">Select Bank</option>
                        <?php foreach ($banks as $b): ?><option value="<?= $b['id'] ?>" <?= (int)($record['from_bank_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?> - <?= e($b['account_no'] ?? '') ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" data-mask-money value="<?= e($record['amount'] ?? '') ?>">
                </div>
            </div>

            <div class="row g-3 d-none" id="secBankBank" data-sec="bank_to_bank">
                <div class="col-md-6">
                    <label class="form-label">From Bank</label>
                    <select name="from_bank_id" class="form-select">
                        <option value="">Select Bank</option>
                        <?php foreach ($banks as $b): ?><option value="<?= $b['id'] ?>" <?= (int)($record['from_bank_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?> - <?= e($b['account_no'] ?? '') ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">To Bank</label>
                    <select name="to_bank_id" class="form-select">
                        <option value="">Select Bank</option>
                        <?php foreach ($banks as $b): ?><option value="<?= $b['id'] ?>" <?= (int)($record['to_bank_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?> - <?= e($b['account_no'] ?? '') ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" data-mask-money value="<?= e($record['amount'] ?? '') ?>">
                </div>
            </div>

            <div class="row g-3 d-none" id="secWithdraw" data-sec="customer_withdraw">
                <div class="col-md-6">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" id="wdCustomer" class="form-select">
                        <option value="">Select Customer</option>
                        <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['full_name']) ?> (<?= e($c['customer_no']) ?>)</option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Booking</label>
                    <select name="booking_id" id="wdBooking" class="form-select">
                        <option value="">Select Booking</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Refundable Amount (auto)</label>
                    <input type="number" step="0.01" name="amount" id="wdAmount" class="form-control" readonly>
                    <div class="form-text">Booking cancel hokar property available ho jayegi.</div>
                </div>
            </div>

            <div class="row g-3 d-none" id="secOwner" data-sec="owner_withdraw">
                <div class="col-md-6">
                    <label class="form-label">Withdraw From</label>
                    <select name="withdraw_from" class="form-select">
                        <option value="cash" <?= $wFrom === 'cash' ? 'selected' : '' ?>>Cash</option>
                        <?php foreach ($banks as $b): ?><option value="bank:<?= $b['id'] ?>" <?= $wFrom === ('bank:' . $b['id']) ? 'selected' : '' ?>><?= e($b['name']) ?> - <?= e($b['account_no'] ?? '') ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" data-mask-money value="<?= e($record['amount'] ?? '') ?>">
                </div>
            </div>

            <div class="row g-3 mt-0">
                <div class="col-md-8">
                    <label class="form-label">Narration</label>
                    <input type="text" name="narration" class="form-control" placeholder="Optional" value="<?= e($record['narration'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i><?= $edit_id > 0 ? 'Update Transfer' : 'Save Transfer' ?></button>
        <a href="transfers.php" class="btn btn-light">Cancel</a>
    </div>
</form>

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

    var wdCustomer = document.getElementById('wdCustomer');
    var wdBooking = document.getElementById('wdBooking');
    var wdAmount = document.getElementById('wdAmount');
    if (wdCustomer) {
        wdCustomer.addEventListener('change', function () {
            var id = wdCustomer.value;
            wdBooking.innerHTML = '<option value="">Loading...</option>';
            wdAmount.value = '';
            if (!id) { wdBooking.innerHTML = '<option value="">Select Booking</option>'; return; }
            fetch('ajax.php?action=bookings&id=' + id).then(function (r) { return r.json(); }).then(function (rows) {
                wdBooking.innerHTML = '<option value="">Select Booking</option>' + rows.map(function (r) { return '<option value="' + r.id + '">' + r.name + '</option>'; }).join('');
            });
        });
        wdBooking.addEventListener('change', function () {
            var id = wdBooking.value;
            wdAmount.value = '';
            if (!id) return;
            fetch('ajax.php?action=booking_info&id=' + id).then(function (r) { return r.json(); }).then(function (rows) {
                if (rows.length) wdAmount.value = rows[0].paid;
            });
        });
    }
})();
</script>

<?php include '../includes/footer.php'; ?>

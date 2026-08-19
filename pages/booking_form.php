<?php
require_once '../includes/auth.php';
require_login();
require_permission('sales.manage');
$title = 'Booking Form';
$active = 'bookings';

$id = (int)($_GET['id'] ?? 0);
$record = null;
if ($id > 0) {
    $record = db_get("SELECT b.*, p.property_no, p.sale_price AS prop_price, c.full_name AS customer_name, c.customer_no
                      FROM bookings b
                      JOIN properties p ON p.id = b.property_id
                      JOIN customers c ON c.id = b.customer_id
                      WHERE b.id = ?", [$id]);
    if (!$record) {
        flash('danger', 'Booking not found.');
        redirect('bookings.php');
    }
}

function booking_bank_account_id($bank_id) {
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

function post_booking_voucher($date, $narration, $lines, $project_id = null) {
    $voucher_no = next_number('JV', 'vouchers', 'voucher_no');
    $vid = db_exec("INSERT INTO vouchers (voucher_no, voucher_date, voucher_type, project_id, narration, status, created_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
        [$voucher_no, $date, 'journal', $project_id, $narration, 'posted', $GLOBALS['user']['id']]);
    foreach ($lines as $l) {
        db_exec("INSERT INTO voucher_items (voucher_id, account_id, item_description, debit, credit, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$vid, $l[0], $l[1], $l[2], $l[3]]);
    }
    return $vid;
}

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $booking_no = trim($_POST['booking_no'] ?? '');
        if ($booking_no === '') {
            $booking_no = next_number('BK', 'bookings', 'booking_no');
        }
        $quotation_id = (int)($_POST['quotation_id'] ?? 0) ?: null;
        $property_id = (int)($_POST['property_id'] ?? 0);
        $customer_id = (int)($_POST['customer_id'] ?? 0);
        $dealer_id = (int)($_POST['dealer_id'] ?? 0) ?: null;
        $booking_date = $_POST['booking_date'] ?? date('Y-m-d');
        $sale_type = $_POST['sale_type'] ?? 'installment';
        if (!in_array($sale_type, ['cash', 'installment'])) $sale_type = 'installment';
        $total_price = (float)($_POST['total_price'] ?? 0);
        $discount = (float)($_POST['discount'] ?? 0);
        $token_amount = (float)($_POST['token_amount'] ?? 0);
        $booking_amount = (float)($_POST['booking_amount'] ?? 0);
        $possession_charges = (float)($_POST['possession_charges'] ?? 0);
        $transfer_charges = (float)($_POST['transfer_charges'] ?? 0);
        $installment_plan = 'monthly';
        $installment_months = max(1, (int)($_POST['installment_months'] ?? 12));
        $installment_years = max(1, (int)ceil($installment_months / 12));
        $status = $_POST['status'] ?? 'booking';
        $payment_mode = $_POST['payment_mode'] ?? 'cash';
        if (!in_array($payment_mode, ['cash', 'bank'])) $payment_mode = 'cash';
        $payment_method_id = null;
        $bank_id = null;
        if ($sale_type === 'cash') {
            if ($payment_mode === 'bank') {
                $bank_id = (int)($_POST['cash_bank_id'] ?? 0) ?: null;
            }
        } else {
            $payment_method_id = (int)($_POST['payment_method_id'] ?? 0) ?: null;
            $bank_id = (int)($_POST['bank_id'] ?? 0) ?: null;
        }
        $reference = $sale_type === 'cash' ? trim($_POST['cash_reference'] ?? '') : trim($_POST['reference'] ?? '');

        if ($property_id <= 0 || $customer_id <= 0) {
            flash('danger', 'Property and customer are required.');
        } elseif ($total_price <= 0) {
            flash('danger', 'Total price must be greater than zero.');
        } elseif ($sale_type === 'cash' && ($token_amount + $booking_amount) < ($total_price - $discount)) {
            flash('danger', 'Cash sale requires full payment: Token + Booking amount must equal the total after discount.');
        } elseif ($sale_type === 'cash' && $payment_mode === 'bank' && !$bank_id) {
            flash('danger', 'Select the bank where the payment will be received.');
        } else {
            $current = db_get("SELECT status FROM properties WHERE id = ?", [$property_id]);
            if (!$current) {
                flash('danger', 'Property not found.');
            } elseif ($id <= 0 && !in_array($current['status'], ['available', 'reserved'])) {
                flash('danger', 'Selected property is not available for booking.');
            } else {
                if ($id > 0) {
                    db_exec("UPDATE bookings SET booking_no=?, quotation_id=?, property_id=?, customer_id=?, dealer_id=?, booking_date=?, sale_type=?, total_price=?, discount=?, token_amount=?, booking_amount=?, possession_charges=?, transfer_charges=?, installment_plan=?, installment_years=?, installment_months=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?",
                        [$booking_no, $quotation_id, $property_id, $customer_id, $dealer_id, $booking_date, $sale_type, $total_price, $discount, $token_amount, $booking_amount, $possession_charges, $transfer_charges, $installment_plan, $installment_years, $installment_months, $status, $id]);
                    flash('success', 'Booking updated successfully.');
                    redirect('booking_view.php?id=' . $id);
                }

                $booking_id = db_exec("INSERT INTO bookings (booking_no, quotation_id, property_id, customer_id, dealer_id, booking_date, sale_type, total_price, discount, token_amount, booking_amount, possession_charges, transfer_charges, installment_plan, installment_years, installment_months, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                    [$booking_no, $quotation_id, $property_id, $customer_id, $dealer_id, $booking_date, $sale_type, $total_price, $discount, $token_amount, $booking_amount, $possession_charges, $transfer_charges, $installment_plan, $installment_years, $installment_months, $status]);

                $no = 1;
                $book = $token_amount + $booking_amount;
                $bookingInstId = null;
                $project = db_get("SELECT project_id FROM properties WHERE id = ?", [$property_id]);
                $projectId = $project ? (int)$project['project_id'] : null;
                if ($book > 0) {
                    $bookingInstId = db_exec("INSERT INTO installments (booking_id, installment_no, installment_type, due_date, amount, penalty, paid_amount, status, paid_date, received_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,0,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                        [$booking_id, $no++, 'booking', $booking_date, $book, $book, 'paid', $booking_date, $user['id']]);

                    $receipt_no = next_number('RCT', 'receipts', 'receipt_no');
                    $remarks = $sale_type === 'cash' ? 'Full cash sale' : 'Booking amount';
                    db_exec("INSERT INTO receipts (receipt_no, receipt_date, project_id, customer_id, booking_id, installment_id, amount, payment_method_id, bank_id, reference, remarks, received_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                        [$receipt_no, $booking_date, $projectId, $customer_id, $booking_id, $bookingInstId, $book, $payment_method_id, $bank_id, $reference, $remarks, $user['id']]);
                }
                if ($sale_type === 'cash' && $book > 0) {
                    $cashAcc = db_get("SELECT id FROM chart_of_accounts WHERE code = '1000'");
                    $incomeAcc = db_get("SELECT id FROM chart_of_accounts WHERE code = '4000'");
                    $ledgerAccId = 0;
                    $ledgerLabel = 'Cash';
                    if ($payment_mode === 'bank' && $bank_id) {
                        $ledgerAccId = booking_bank_account_id($bank_id);
                        $ledgerLabel = db_get("SELECT name FROM banks WHERE id = ?", [$bank_id])['name'] ?? 'Bank';
                    } elseif ($cashAcc) {
                        $ledgerAccId = (int)$cashAcc['id'];
                    }
                    if ($ledgerAccId && $incomeAcc) {
                        post_booking_voucher($booking_date, 'Cash sale ' . $booking_no . ' received in ' . $ledgerLabel, [
                            [$ledgerAccId, 'Property sale receipt - ' . $booking_no, $book, 0],
                            [(int)$incomeAcc['id'], 'Property sale - ' . $booking_no, 0, $book],
                        ], $projectId);
                    }
                }
                if ($possession_charges > 0) {
                    db_exec("INSERT INTO installments (booking_id, installment_no, installment_type, due_date, amount, penalty, paid_amount, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,0,0,'pending',CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                        [$booking_id, $no++, 'possession', date('Y-m-d', strtotime("+{$installment_months} months", strtotime($booking_date))), $possession_charges]);
                }

                $remaining = $total_price - $discount - $book - $possession_charges - $transfer_charges;
                if ($remaining > 0) {
                    $each = round($remaining / $installment_months, 2);
                    for ($i = 1; $i <= $installment_months; $i++) {
                        $amount = $i === $installment_months ? round($remaining - $each * ($installment_months - 1), 2) : $each;
                        $due = date('Y-m-d', strtotime("+{$i} months", strtotime($booking_date)));
                        db_exec("INSERT INTO installments (booking_id, installment_no, installment_type, due_date, amount, penalty, paid_amount, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,0,0,'pending',CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                            [$booking_id, $no++, 'installment', $due, $amount]);
                    }
                }

                $propStatus = $sale_type === 'cash' ? 'sold' : 'booked';
                $bookStatus = $sale_type === 'cash' ? 'completed' : $status;
                db_exec("UPDATE bookings SET status = ?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$bookStatus, $booking_id]);
                db_exec("UPDATE properties SET status = ?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$propStatus, $property_id]);
                flash('success', 'Booking created. ' . ($sale_type === 'cash' ? 'Cash sale completed.' : 'Installment plan generated.'));
                redirect('booking_view.php?id=' . $booking_id);
            }
        }
    } elseif ($action === 'cancel') {
        $bookId = (int)$id;
        $linkedReceipts = db_all("SELECT * FROM receipts WHERE booking_id = ?", [$bookId]);
        foreach ($linkedReceipts as $lr) {
            if (!empty($lr['installment_id'])) {
                db_exec("UPDATE installments SET paid_amount = 0, status = 'pending', paid_date = NULL, updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$lr['installment_id']]);
            }
            if (!empty($lr['voucher_id'])) {
                db_exec("DELETE FROM voucher_items WHERE voucher_id = ?", [$lr['voucher_id']]);
                db_exec("DELETE FROM vouchers WHERE id = ?", [$lr['voucher_id']]);
            }
        }
        db_exec("DELETE FROM receipts WHERE booking_id = ?", [$bookId]);
        db_exec("DELETE FROM installments WHERE booking_id = ?", [$bookId]);
        db_exec("UPDATE bookings SET status = 'cancelled', updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$bookId]);
        db_exec("UPDATE properties SET status = 'available', updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$record['property_id']]);
        flash('success', 'Booking cancelled, all related entries removed, and property released.');
        redirect('bookings.php');
    }
}

$customers = db_all("SELECT * FROM customers WHERE status = 1 ORDER BY full_name");
$ap = active_project_id();
$properties = $ap
    ? db_all("SELECT p.*, pt.name AS type_name, pr.name AS project_name FROM properties p LEFT JOIN property_types pt ON pt.id = p.property_type_id LEFT JOIN projects pr ON pr.id = p.project_id WHERE p.status IN ('available','reserved') AND p.project_id = ? ORDER BY p.property_no", [$ap])
    : db_all("SELECT p.*, pt.name AS type_name, pr.name AS project_name FROM properties p LEFT JOIN property_types pt ON pt.id = p.property_type_id LEFT JOIN projects pr ON pr.id = p.project_id WHERE p.status IN ('available','reserved') ORDER BY p.property_no");
$dealers = db_all("SELECT * FROM dealers WHERE status = 1 ORDER BY full_name");
$quotations = db_all("SELECT * FROM quotations WHERE status IN ('sent','accepted') ORDER BY quotation_no DESC");
$paymentMethods = db_all("SELECT * FROM payment_methods ORDER BY name");
$banks = db_all("SELECT * FROM banks ORDER BY name");

$editMode = 'cash';
$editBankId = 0;
$editMonths = 12;
if ($record) {
    $editMonths = max(1, (int)($record['installment_months'] ?: $record['installment_years'] * 12));
    if ($record['sale_type'] === 'cash') {
        $br = db_get("SELECT bank_id FROM receipts WHERE booking_id = ? ORDER BY id LIMIT 1", [$record['id']]);
        if ($br && $br['bank_id']) {
            $editMode = 'bank';
            $editBankId = (int)$br['bank_id'];
        }
    }
}

$total_price = $record ? $record['total_price'] : 0;
include '../includes/header.php';
?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="bookings.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= $record ? 'Edit Booking ' . e($record['booking_no']) : 'New Booking' ?></h5>
</div>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="save">
    <div class="card">
        <div class="card-header"><i class="bi bi-journal-check me-2"></i>Booking Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Booking No</label>
                    <input type="text" name="booking_no" class="form-control" value="<?= e($record['booking_no'] ?? '') ?>" placeholder="Auto">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="booking_date" id="fDate" class="form-control" value="<?= e($record['booking_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sale Type</label>
                    <select name="sale_type" id="fSaleType" class="form-select">
                        <option value="cash" <?= $record && $record['sale_type'] === 'cash' ? 'selected' : '' ?>>Cash</option>
                        <option value="installment" <?= !$record || $record['sale_type'] !== 'cash' ? 'selected' : '' ?>>Installment</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Quotation (optional)</label>
                    <select name="quotation_id" class="form-select">
                        <option value="">None</option>
                        <?php foreach ($quotations as $q): ?><option value="<?= $q['id'] ?>" <?= $record && $record['quotation_id'] == $q['id'] ? 'selected' : '' ?>><?= e($q['quotation_no']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['booking', 'active', 'completed'] as $s): ?>
                            <option value="<?= $s ?>" <?= $record && $record['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Property</label>
                    <select name="property_id" class="form-select" id="selProperty" required <?= $id > 0 ? 'disabled' : '' ?>>
                        <option value="">Select Property</option>
                        <?php foreach ($properties as $p): ?>
                            <option value="<?= $p['id'] ?>" data-price="<?= (float)$p['sale_price'] ?>" <?= $record && $record['property_id'] == $p['id'] ? 'selected' : '' ?>><?= e($p['property_no']) ?> - <?= e($p['project_name'] ?? '-') ?> (<?= e($p['type_name'] ?? '-') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($id > 0): ?><input type="hidden" name="property_id" value="<?= $record['property_id'] ?>"><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-select" required>
                        <option value="">Select Customer</option>
                        <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>" <?= $record && $record['customer_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['full_name']) ?> (<?= e($c['customer_no']) ?>)</option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Dealer / Agent</label>
                    <select name="dealer_id" class="form-select">
                        <option value="">None</option>
                        <?php foreach ($dealers as $d): ?><option value="<?= $d['id'] ?>" <?= $record && $record['dealer_id'] == $d['id'] ? 'selected' : '' ?>><?= e($d['full_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><i class="bi bi-calculator me-2"></i>Pricing</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Total Price</label>
                    <input type="number" step="0.01" name="total_price" id="fTotal" class="form-control" required data-mask-money value="<?= e($record['total_price'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Discount</label>
                    <input type="number" step="0.01" name="discount" id="fDiscount" class="form-control" data-mask-money value="<?= e($record['discount'] ?? '0') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Token Amount</label>
                    <input type="number" step="0.01" name="token_amount" id="fToken" class="form-control" data-mask-money value="<?= e($record['token_amount'] ?? '0') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Booking Amount</label>
                    <input type="number" step="0.01" name="booking_amount" id="fBooking" class="form-control" data-mask-money value="<?= e($record['booking_amount'] ?? '0') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Possession Charges</label>
                    <input type="number" step="0.01" name="possession_charges" id="fPossession" class="form-control" data-mask-money value="<?= e($record['possession_charges'] ?? '0') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Transfer Charges</label>
                    <input type="number" step="0.01" name="transfer_charges" id="fTransfer" class="form-control" data-mask-money value="<?= e($record['transfer_charges'] ?? '0') ?>">
                </div>
                <div class="col-md-12">
                    <div class="alert alert-secondary d-flex justify-content-between align-items-center py-2 mb-1">
                        <span class="small text-muted">Remaining amount (jo lena hai)</span>
                        <strong id="remainVal" class="fs-5">-</strong>
                    </div>
                    <div id="remainHint" class="small text-muted"></div>
                </div>
                <div class="col-md-12" id="instBlock" style="display:none">
                    <div class="alert alert-light border mb-0">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <label class="form-label">Number of Months / Installments</label>
                                <input type="number" min="1" name="installment_months" id="fMonths" class="form-control" value="<?= e($editMonths) ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="alert alert-info w-100 mb-0 py-2">
                                    <span class="small text-muted">Installment amount:</span>
                                    <strong id="calcPer">-</strong>
                                    <span class="small text-muted" id="calcCount"></span>
                                </div>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="small text-muted">Equal monthly installments, possession due after <span id="possMonths"><?= e($editMonths) ?></span> months.</div>
                            </div>
                            <div class="col-md-12">
                                <div class="table-responsive" style="max-height:180px;overflow:auto">
                                    <table class="table table-sm table-bordered align-middle mb-0">
                                        <thead class="table-light"><tr><th style="width:70px">Qist</th><th>Due Date</th><th class="text-end">Amount</th></tr></thead>
                                        <tbody id="instPreview"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12" id="cashBlock" style="display:none">
                    <div class="alert alert-light border mb-0">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-3">
                                <label class="form-label">Payment Mode</label><br>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="payment_mode" id="pmCash" value="cash" <?= $editMode === 'cash' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="pmCash">Cash</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="payment_mode" id="pmBank" value="bank" <?= $editMode === 'bank' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="pmBank">Bank</label>
                                </div>
                            </div>
                            <div class="col-md-3" id="cashBankSel" style="<?= $editMode === 'bank' ? '' : 'display:none' ?>">
                                <label class="form-label">Bank</label>
                                <select name="cash_bank_id" id="fBank" class="form-select">
                                    <option value="">Select Bank</option>
                                    <?php foreach ($banks as $b): ?><option value="<?= $b['id'] ?>" <?= $editBankId === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Reference (optional)</label>
                                <input type="text" name="cash_reference" class="form-control" placeholder="Cheque no / ref">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="alert alert-info w-100 mb-0 py-2">
                                    <span class="small text-muted">Full amount to:</span>
                                    <strong id="cashLedger">Cash</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3" id="upfrontCard">
        <div class="card-header"><i class="bi bi-cash-coin me-2"></i>Upfront Payment</div>
        <div class="card-body">
            <p class="small text-muted mb-3">Booking amount ke liye auto-receipt bane gi. Cash sale me full payment ka receipt banta hai (payment mode ooper pricing section me select karein).</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method_id" class="form-select">
                        <option value="">Select</option>
                        <?php foreach ($paymentMethods as $pm): ?><option value="<?= $pm['id'] ?>"><?= e($pm['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Bank (optional)</label>
                    <select name="bank_id" class="form-select">
                        <option value="">Select</option>
                        <?php foreach ($banks as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reference (optional)</label>
                    <input type="text" name="reference" class="form-control" placeholder="Cheque no / ref">
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i><?= $record ? 'Update Booking' : 'Save Booking' ?></button>
        <a href="bookings.php" class="btn btn-light">Cancel</a>
        <?php if ($record && $record['status'] !== 'cancelled'): ?>
        <button type="button" class="btn btn-outline-danger ms-auto" data-bs-toggle="modal" data-bs-target="#cancelModal"><i class="bi bi-x-lg me-1"></i>Cancel Booking</button>
        <?php endif; ?>
    </div>
</form>

<?php if ($record): ?>
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="cancel">
                <div class="modal-header"><h5 class="modal-title">Cancel Booking</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">Are you sure? The property will be released back to available status.</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">No</button>
                    <button class="btn btn-danger">Yes, Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
(function () {
    var total = document.getElementById('fTotal'), discount = document.getElementById('fDiscount'),
        token = document.getElementById('fToken'), booking = document.getElementById('fBooking'),
        possess = document.getElementById('fPossession'), transfer = document.getElementById('fTransfer'),
        saleType = document.getElementById('fSaleType'),
        instBlock = document.getElementById('instBlock'),
        cashBlock = document.getElementById('cashBlock'),
        upfrontCard = document.getElementById('upfrontCard'),
        months = document.getElementById('fMonths'),
        per = document.getElementById('calcPer'), count = document.getElementById('calcCount'),
        pmCash = document.getElementById('pmCash'), pmBank = document.getElementById('pmBank'),
        cashBankSel = document.getElementById('cashBankSel'), fBank = document.getElementById('fBank'),
        cashLedger = document.getElementById('cashLedger'),
        remainVal = document.getElementById('remainVal'), remainHint = document.getElementById('remainHint'),
        instPreview = document.getElementById('instPreview'),
        bookingDate = document.getElementById('fDate'), possMonths = document.getElementById('possMonths'),
        sel = document.getElementById('selProperty');
    if (sel) {
        sel.addEventListener('change', function () {
            var opt = sel.options[sel.selectedIndex];
            if (opt && opt.dataset.price) total.value = opt.dataset.price;
            calc();
        });
    }
    function isCash() { return saleType && saleType.value === 'cash'; }
    function toggleBank() {
        var bank = pmBank && pmBank.checked;
        if (cashBankSel) cashBankSel.style.display = bank ? '' : 'none';
        if (cashLedger) {
            if (bank && fBank && fBank.value) {
                cashLedger.textContent = fBank.options[fBank.selectedIndex] ? fBank.options[fBank.selectedIndex].text : 'Bank';
            } else {
                cashLedger.textContent = 'Cash';
            }
        }
    }
    function scenario() {
        var cash = isCash();
        if (instBlock) instBlock.style.display = cash ? 'none' : '';
        if (cashBlock) cashBlock.style.display = cash ? '' : 'none';
        if (upfrontCard) upfrontCard.style.display = cash ? 'none' : '';
        calc();
    }
    function money(v) {
        return (Math.round(v * 100) / 100).toFixed(2);
    }
    function addMonths(dateStr, m) {
        if (!dateStr) return '';
        var d = new Date(dateStr + 'T00:00:00');
        d.setMonth(d.getMonth() + m);
        return d.toISOString().slice(0, 10);
    }
    function calc() {
        var t = parseFloat(total.value) || 0, d = parseFloat(discount.value) || 0,
            tok = parseFloat(token.value) || 0, bk = parseFloat(booking.value) || 0,
            po = parseFloat(possess.value) || 0, tr = parseFloat(transfer.value) || 0;
        var remain = t - d - tok - bk - po - tr;
        if (remainVal) remainVal.textContent = remain > 0 ? money(remain) : (remain < 0 ? '-' + money(-remain) : '0.00');
        if (remainVal) remainVal.style.color = isCash() && remain > 0 ? '#b02a37' : '';
        if (remainHint) {
            if (isCash()) {
                if (remain > 0) {
                    remainHint.className = 'small text-danger';
                    remainHint.textContent = 'Cash sale requires full payment - Token + Booking amount must equal total after discount (remaining should be 0).';
                } else {
                    remainHint.className = 'small text-muted';
                    remainHint.textContent = 'Full amount is received now: ' + (pmBank && pmBank.checked && fBank && fBank.value ? 'Bank' : 'Cash');
                }
            } else {
                var n = Math.max(1, parseInt(months.value) || 1);
                remainHint.className = 'small text-muted';
                remainHint.textContent = remain > 0
                    ? 'Remaining amount will be split into ' + n + ' equal monthly installment' + (n > 1 ? 's' : '') + '.'
                    : 'No remaining amount to install - full amount covered by upfront payments.';
            }
        }
        if (isCash()) {
            if (per) per.textContent = '-';
            if (count) count.textContent = 'Full payment on booking';
            if (instPreview) instPreview.innerHTML = '';
            return;
        }
        var n = Math.max(1, parseInt(months.value) || 1);
        if (possMonths) possMonths.textContent = n;
        if (remain <= 0) {
            if (per) per.textContent = '-';
            if (count) count.textContent = '';
        } else {
            var each = Math.round(remain / n * 100) / 100;
            if (per) per.textContent = money(each);
            if (count) count.textContent = ' x ' + n + ' monthly installment' + (n > 1 ? 's' : '');
        }
        if (instPreview) {
            var html = '', showRows = Math.min(n, 8);
            for (var i = 1; i <= showRows; i++) {
                var amt = i === n ? money(remain - each * (n - 1)) : money(each);
                html += '<tr><td>' + i + '</td><td>' + addMonths(bookingDate ? bookingDate.value : '', i) + '</td><td class="text-end">' + amt + '</td></tr>';
            }
            if (n > showRows) html += '<tr><td colspan="3" class="text-center text-muted">+ ' + (n - showRows) + ' more installment' + (n - showRows > 1 ? 's' : '') + ' of ' + money(each) + ' each</td></tr>';
            instPreview.innerHTML = html;
        }
    }
    [total, discount, token, booking, possess, transfer, months, bookingDate].forEach(function (el) {
        if (el) {
            el.addEventListener('input', calc);
            el.addEventListener('change', calc);
        }
    });
    if (saleType) saleType.addEventListener('change', scenario);
    if (pmCash) pmCash.addEventListener('change', function () { toggleBank(); calc(); });
    if (pmBank) pmBank.addEventListener('change', function () { toggleBank(); calc(); });
    if (fBank) fBank.addEventListener('change', toggleBank);
    scenario();
    toggleBank();
    calc();
})();
</script>

<?php include '../includes/footer.php'; ?>

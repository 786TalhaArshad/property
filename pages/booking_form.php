<?php
require_once '../includes/auth.php';
require_login();
require_permission('sales.manage');
$title = 'Booking Form';
$active = 'bookings';

$id = (int)($_GET['id'] ?? 0);
$record = null;
if ($id > 0) {
    $record = db_get("SELECT b.*, p.property_no, p.total_price AS prop_price, c.full_name AS customer_name, c.customer_no
                      FROM bookings b
                      JOIN properties p ON p.id = b.property_id
                      JOIN customers c ON c.id = b.customer_id
                      WHERE b.id = ?", [$id]);
    if (!$record) {
        flash('danger', 'Booking not found.');
        redirect('bookings.php');
    }
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
        $total_price = (float)($_POST['total_price'] ?? 0);
        $discount = (float)($_POST['discount'] ?? 0);
        $token_amount = (float)($_POST['token_amount'] ?? 0);
        $booking_amount = (float)($_POST['booking_amount'] ?? 0);
        $possession_charges = (float)($_POST['possession_charges'] ?? 0);
        $transfer_charges = (float)($_POST['transfer_charges'] ?? 0);
        $installment_plan = $_POST['installment_plan'] ?? 'monthly';
        $installment_years = (int)($_POST['installment_years'] ?? 1) ?: 1;
        $status = $_POST['status'] ?? 'booking';

        if ($property_id <= 0 || $customer_id <= 0) {
            flash('danger', 'Property and customer are required.');
        } elseif ($total_price <= 0) {
            flash('danger', 'Total price must be greater than zero.');
        } else {
            $current = db_get("SELECT status FROM properties WHERE id = ?", [$property_id]);
            if (!$current) {
                flash('danger', 'Property not found.');
            } elseif ($id <= 0 && !in_array($current['status'], ['available', 'reserved'])) {
                flash('danger', 'Selected property is not available for booking.');
            } else {
                if ($id > 0) {
                    db_exec("UPDATE bookings SET booking_no=?, quotation_id=?, property_id=?, customer_id=?, dealer_id=?, booking_date=?, total_price=?, discount=?, token_amount=?, booking_amount=?, possession_charges=?, transfer_charges=?, installment_plan=?, installment_years=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?",
                        [$booking_no, $quotation_id, $property_id, $customer_id, $dealer_id, $booking_date, $total_price, $discount, $token_amount, $booking_amount, $possession_charges, $transfer_charges, $installment_plan, $installment_years, $status, $id]);
                    flash('success', 'Booking updated successfully.');
                    redirect('booking_view.php?id=' . $id);
                }

                $booking_id = db_exec("INSERT INTO bookings (booking_no, quotation_id, property_id, customer_id, dealer_id, booking_date, total_price, discount, token_amount, booking_amount, possession_charges, transfer_charges, installment_plan, installment_years, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                    [$booking_no, $quotation_id, $property_id, $customer_id, $dealer_id, $booking_date, $total_price, $discount, $token_amount, $booking_amount, $possession_charges, $transfer_charges, $installment_plan, $installment_years, $status]);

                $no = 1;
                $book = $token_amount + $booking_amount;
                if ($book > 0) {
                    db_exec("INSERT INTO installments (booking_id, installment_no, installment_type, due_date, amount, penalty, paid_amount, status, paid_date, received_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,0,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                        [$booking_id, $no++, 'booking', $booking_date, $book, $book, 'paid', $booking_date, $user['id']]);
                }
                if ($possession_charges > 0) {
                    db_exec("INSERT INTO installments (booking_id, installment_no, installment_type, due_date, amount, penalty, paid_amount, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,0,0,'pending',CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                        [$booking_id, $no++, 'possession', date('Y-m-d', strtotime("+{$installment_years} years", strtotime($booking_date))), $possession_charges]);
                }

                $remaining = $total_price - $discount - $book - $possession_charges - $transfer_charges;
                if ($remaining > 0) {
                    $mult = ['monthly' => 12, 'quarterly' => 4, 'half_yearly' => 2, 'yearly' => 1, 'lump_sum' => 1][$installment_plan] ?? 12;
                    $count = max(1, $installment_years * $mult);
                    $each = round($remaining / $count, 2);
                    $months = ['monthly' => 1, 'quarterly' => 3, 'half_yearly' => 6, 'yearly' => 12, 'lump_sum' => $installment_years * 12][$installment_plan] ?? 1;
                    for ($i = 1; $i <= $count; $i++) {
                        $amount = $i === $count ? round($remaining - $each * ($count - 1), 2) : $each;
                        $due = date('Y-m-d', strtotime("+{$months} months", strtotime($booking_date)));
                        db_exec("INSERT INTO installments (booking_id, installment_no, installment_type, due_date, amount, penalty, paid_amount, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,0,0,'pending',CURDATE(),CURTIME(),CURDATE(),CURTIME())",
                            [$booking_id, $no++, 'installment', $due, $amount]);
                        $booking_date = $due;
                    }
                }

                db_exec("UPDATE properties SET status = 'booked', updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$property_id]);
                flash('success', 'Booking created and installment plan generated.');
                redirect('booking_view.php?id=' . $booking_id);
            }
        }
    } elseif ($action === 'cancel') {
        db_exec("UPDATE bookings SET status = 'cancelled', updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$id]);
        db_exec("UPDATE properties SET status = 'available', updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$record['property_id']]);
        flash('success', 'Booking cancelled and property released.');
        redirect('bookings.php');
    }
}

$customers = db_all("SELECT * FROM customers WHERE status = 1 ORDER BY full_name");
$properties = db_all("SELECT p.*, pt.name AS type_name, pr.name AS project_name FROM properties p LEFT JOIN property_types pt ON pt.id = p.property_type_id LEFT JOIN projects pr ON pr.id = p.project_id WHERE p.status IN ('available','reserved') ORDER BY p.property_no");
$dealers = db_all("SELECT * FROM dealers WHERE status = 1 ORDER BY full_name");
$quotations = db_all("SELECT * FROM quotations WHERE status IN ('sent','accepted') ORDER BY quotation_no DESC");

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
                    <input type="date" name="booking_date" class="form-control" value="<?= e($record['booking_date'] ?? date('Y-m-d')) ?>" required>
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
                            <option value="<?= $p['id'] ?>" data-price="<?= (float)$p['total_price'] ?>" <?= $record && $record['property_id'] == $p['id'] ? 'selected' : '' ?>><?= e($p['property_no']) ?> - <?= e($p['project_name'] ?? '-') ?> (<?= e($p['type_name'] ?? '-') ?>)</option>
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
                <div class="col-md-4">
                    <label class="form-label">Installment Plan</label>
                    <select name="installment_plan" id="fPlan" class="form-select">
                        <?php foreach (['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'half_yearly' => 'Half Yearly', 'yearly' => 'Yearly', 'lump_sum' => 'Lump Sum'] as $v => $lbl): ?>
                            <option value="<?= $v ?>" <?= $record && $record['installment_plan'] === $v ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Installment Years</label>
                    <input type="number" min="1" name="installment_years" id="fYears" class="form-control" value="<?= e($record['installment_years'] ?? '1') ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="alert alert-info w-100 mb-0 py-2">
                        <span class="small text-muted">Installment amount:</span>
                        <strong id="calcPer">-</strong>
                        <span class="small text-muted" id="calcCount"></span>
                    </div>
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
        plan = document.getElementById('fPlan'), years = document.getElementById('fYears'),
        per = document.getElementById('calcPer'), count = document.getElementById('calcCount'),
        sel = document.getElementById('selProperty');
    if (sel) {
        sel.addEventListener('change', function () {
            var opt = sel.options[sel.selectedIndex];
            if (opt && opt.dataset.price) total.value = opt.dataset.price;
            calc();
        });
    }
    function calc() {
        var t = parseFloat(total.value) || 0, d = parseFloat(discount.value) || 0,
            tok = parseFloat(token.value) || 0, bk = parseFloat(booking.value) || 0,
            po = parseFloat(possess.value) || 0, tr = parseFloat(transfer.value) || 0,
            mult = { monthly: 12, quarterly: 4, half_yearly: 2, yearly: 1, lump_sum: 1 }[plan.value] || 12,
            yrs = parseInt(years.value) || 1;
        var remain = t - d - tok - bk - po - tr;
        if (remain <= 0) {
            per.textContent = '-';
            count.textContent = '';
        } else {
            var n = Math.max(1, yrs * mult);
            per.textContent = parseFloat(remain / n).toFixed(2);
            count.textContent = ' x ' + n + ' ' + (n > 1 ? 'installments' : 'installment');
        }
    }
    [total, discount, token, booking, possess, transfer, plan, years].forEach(function (el) {
        el.addEventListener('input', calc);
        el.addEventListener('change', calc);
    });
    calc();
})();
</script>

<?php include '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_login();
require_permission('rentals.manage');
$title = 'Rental Agreement Form';
$active = 'rental_agreements';

if (is_post()) {
    csrf_check();
    $agreement_no = trim($_POST['agreement_no'] ?? '');
    if ($agreement_no === '') {
        $agreement_no = next_number('RA', 'rental_agreements', 'agreement_no');
    }
    $property_id = (int)$_POST['property_id'];
    $tenant_id = (int)$_POST['tenant_id'];
    $owner_id = (int)($_POST['owner_id'] ?? 0) ?: null;
    $dealer_id = (int)($_POST['dealer_id'] ?? 0) ?: null;
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $monthly_rent = (float)($_POST['monthly_rent'] ?? 0);
    $security_deposit = (float)($_POST['security_deposit'] ?? 0);
    $advance_rent = (float)($_POST['advance_rent'] ?? 0);
    $parking_charges = (float)($_POST['parking_charges'] ?? 0);
    $maintenance_charges = (float)($_POST['maintenance_charges'] ?? 0);
    $utility_included = (int)($_POST['utility_included'] ?? 0);
    $rent_increase_percent = (float)($_POST['rent_increase_percent'] ?? 0);
    $notice_period_days = (int)($_POST['notice_period_days'] ?? 30) ?: 30;
    $status = $_POST['status'] ?? 'active';

    if ($property_id <= 0 || $tenant_id <= 0 || $start_date === '' || $end_date === '' || $start_date > $end_date) {
        flash('danger', 'Property, tenant, and a valid date range are required.');
        redirect('rental_agreement_form.php');
    }
    $prop = db_get("SELECT status FROM properties WHERE id = ?", [$property_id]);
    if (!$prop) {
        flash('danger', 'Property not found.');
        redirect('rental_agreement_form.php');
    }
    $conflict = db_get("SELECT id FROM rental_agreements WHERE property_id = ? AND status IN ('active','renewed')", [$property_id]);
    if ($conflict) {
        flash('danger', 'Property already has an active rental agreement.');
        redirect('rental_agreement_form.php');
    }

    $id = db_exec("INSERT INTO rental_agreements (agreement_no, property_id, tenant_id, owner_id, dealer_id, start_date, end_date, monthly_rent, security_deposit, advance_rent, parking_charges, maintenance_charges, utility_included, rent_increase_percent, notice_period_days, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
        [$agreement_no, $property_id, $tenant_id, $owner_id, $dealer_id, $start_date, $end_date, $monthly_rent, $security_deposit, $advance_rent, $parking_charges, $maintenance_charges, $utility_included, $rent_increase_percent, $notice_period_days, $status]);

    $rent = $monthly_rent;
    $due = $start_date;
    $year = (int)date('Y', strtotime($start_date));
    while ($due <= $end_date) {
        $cy = (int)date('Y', strtotime($due));
        if ($cy > $year) {
            $year = $cy;
            $rent = round($rent * (1 + $rent_increase_percent / 100), 2);
        }
        $period = date('Y-M', strtotime($due));
        db_exec("INSERT INTO rent_schedule (agreement_id, period, due_date, rent_amount, late_charges, paid_amount, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,0,'pending',CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$id, $period, $due, $rent]);
        $due = date('Y-m-d', strtotime('+1 month', strtotime($due)));
    }

    if ($security_deposit > 0) {
        db_exec("INSERT INTO tenant_ledger (tenant_id, entry_date, description, debit, credit, balance, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,0,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$tenant_id, $start_date, 'Security deposit ' . $agreement_no, $security_deposit, $security_deposit]);
    }
    if ($advance_rent > 0) {
        $bal = db_get("SELECT COALESCE(MAX(balance),0) b FROM tenant_ledger WHERE tenant_id = ?", [$tenant_id])['b'];
        db_exec("INSERT INTO tenant_ledger (tenant_id, entry_date, description, debit, credit, balance, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,0,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$tenant_id, $start_date, 'Advance rent ' . $agreement_no, $advance_rent, $bal + $advance_rent]);
    }

    db_exec("UPDATE properties SET status = 'rental', updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$property_id]);
    flash('success', 'Rental agreement created with ' . $id . ' schedule.');
    redirect('rental_agreement_view.php?id=' . $id);
}

$properties = db_all("SELECT p.*, pr.name AS project_name FROM properties p LEFT JOIN projects pr ON pr.id = p.project_id WHERE p.status IN ('available','vacant') ORDER BY p.property_no");
$tenants = db_all("SELECT * FROM tenants WHERE status = 1 ORDER BY full_name");
$owners = db_all("SELECT * FROM owners WHERE status = 1 ORDER BY full_name");
$dealers = db_all("SELECT * FROM dealers WHERE status = 1 ORDER BY full_name");
include '../includes/header.php';
?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="rental_agreements.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0">New Rental Agreement</h5>
</div>

<form method="post">
    <?= csrf_field() ?>
    <div class="card">
        <div class="card-header"><i class="bi bi-house-heart me-2"></i>Agreement Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Agreement No</label>
                    <input type="text" name="agreement_no" class="form-control" placeholder="Auto">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d', strtotime('+1 year')) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Property</label>
                    <select name="property_id" class="form-select" required>
                        <option value="">Select Property</option>
                        <?php foreach ($properties as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['property_no']) ?> - <?= e($p['project_name'] ?? '-') ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tenant</label>
                    <select name="tenant_id" class="form-select" required>
                        <option value="">Select Tenant</option>
                        <?php foreach ($tenants as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['full_name']) ?> (<?= e($t['tenant_no']) ?>)</option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Owner</label>
                    <select name="owner_id" class="form-select">
                        <option value="">None</option>
                        <?php foreach ($owners as $o): ?><option value="<?= $o['id'] ?>"><?= e($o['full_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Dealer / Agent</label>
                    <select name="dealer_id" class="form-select">
                        <option value="">None</option>
                        <?php foreach ($dealers as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['full_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['active', 'renewed'] as $s): ?><option value="<?= $s ?>"><?= ucfirst($s) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><i class="bi bi-calculator me-2"></i>Financials</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Monthly Rent</label>
                    <input type="number" step="0.01" name="monthly_rent" class="form-control" required data-mask-money>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Security Deposit</label>
                    <input type="number" step="0.01" name="security_deposit" class="form-control" data-mask-money>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Advance Rent</label>
                    <input type="number" step="0.01" name="advance_rent" class="form-control" data-mask-money>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Parking Charges / Month</label>
                    <input type="number" step="0.01" name="parking_charges" class="form-control" data-mask-money>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Maintenance Charges / Month</label>
                    <input type="number" step="0.01" name="maintenance_charges" class="form-control" data-mask-money>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Rent Increase % / Year</label>
                    <input type="number" step="0.01" name="rent_increase_percent" class="form-control" value="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Notice Period (days)</label>
                    <input type="number" name="notice_period_days" class="form-control" value="30">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Utilities Included</label>
                    <select name="utility_included" class="form-select">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </div>
            </div>
            <div class="alert alert-info mt-3 mb-0 py-2 small">
                <i class="bi bi-info-circle me-1"></i>A monthly rent schedule will be generated automatically for the agreement period.
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Create Agreement</button>
        <a href="rental_agreements.php" class="btn btn-light">Cancel</a>
    </div>
</form>

<?php include '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Cash Received';
$active = 'cash_received';
$canEdit = has_permission('accounting.manage');

function rc_recalc_schedule($scheduleId) {
    $s = db_get("SELECT * FROM rent_schedule WHERE id = ?", [$scheduleId]);
    if (!$s) return;
    $agg = db_get("SELECT COALESCE(SUM(amount),0) amt, MAX(collection_date) d FROM rent_collections WHERE schedule_id = ?", [$scheduleId]);
    $paid = (float)$agg['amt'];
    $total = (float)$s['rent_amount'] + (float)$s['late_charges'];
    $status = $total > 0 && $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'pending');
    $paidDate = $paid > 0 ? $agg['d'] : null;
    db_exec("UPDATE rent_schedule SET paid_amount=?, status=?, paid_date=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$paid, $status, $paidDate, $scheduleId]);
}

$defaultCredit = [
    'customer' => '4000',
    'tenant' => '4100',
    'vendor' => '2000',
    'owner' => '3000',
    'dealer' => '2000',
    'other' => '',
];

$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
$banks = db_all("SELECT * FROM banks ORDER BY name");
$agreements = db_all("SELECT ra.id, ra.agreement_no, ra.tenant_id, p.property_no FROM rental_agreements ra JOIN properties p ON p.id = ra.property_id WHERE ra.status IN ('active','renewed') ORDER BY ra.agreement_no");
$accounts = db_all("SELECT id, code, name, account_type FROM chart_of_accounts ORDER BY code");

if (is_post() && $canEdit) {
    csrf_check();
    $partyType = $_POST['party_type'] ?? '';
    $date = $_POST['received_date'] ?? date('Y-m-d');
    $amount = (float)($_POST['amount'] ?? 0);
    $narration = trim($_POST['narration'] ?? '');
    $paymentMode = $_POST['payment_mode'] ?? 'cash';
    $bankId = (int)($_POST['bank_id'] ?? 0) ?: null;
    $reference = trim($_POST['reference'] ?? '');
    $accountId = (int)($_POST['account_id'] ?? 0);
    $projectId = (int)($_POST['project_id'] ?? 0) ?: null;

    $valid = in_array($partyType, ['customer', 'vendor', 'owner', 'dealer', 'tenant', 'other'], true);
    if (!$valid) {
        flash('danger', 'Invalid receive type.');
        redirect('cash_received.php');
    }
    if ($amount <= 0) {
        flash('danger', 'Enter a valid amount.');
        redirect('cash_received.php');
    }
    if ($paymentMode === 'bank' && !$bankId) {
        flash('danger', 'Select the bank.');
        redirect('cash_received.php');
    }

    $partyName = '';
    $customerId = $bookingId = $propertyId = 0;
    $installmentId = $scheduleId = $agreementId = $tenantId = 0;

    if ($partyType === 'customer') {
        $customerId = (int)($_POST['customer_id'] ?? 0);
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $propertyId = (int)($_POST['property_id'] ?? 0);
        $installmentId = (int)($_POST['installment_id'] ?? 0) ?: null;
        if (!$customerId) {
            flash('danger', 'Select the customer.');
            redirect('cash_received.php');
        }
        $cust = db_get("SELECT full_name FROM customers WHERE id = ?", [$customerId]);
        if (!$cust) {
            flash('danger', 'Customer not found.');
            redirect('cash_received.php');
        }
        $partyName = $cust['full_name'];
        if ($bookingId) {
            $bk = db_get("SELECT p.project_id FROM bookings b JOIN properties p ON p.id = b.property_id WHERE b.id = ? AND b.customer_id = ?", [$bookingId, $customerId]);
            if ($bk) $projectId = $bk['project_id'] ?: $projectId;
        }
    } elseif ($partyType === 'vendor') {
        $vendorId = (int)($_POST['vendor_id'] ?? 0);
        if (!$vendorId) {
            flash('danger', 'Select the vendor.');
            redirect('cash_received.php');
        }
        $v = db_get("SELECT business_name FROM vendors WHERE id = ?", [$vendorId]);
        if ($v) $partyName = $v['business_name'];
    } elseif ($partyType === 'owner') {
        $ownerId = (int)($_POST['owner_id'] ?? 0);
        if (!$ownerId) {
            flash('danger', 'Select the owner.');
            redirect('cash_received.php');
        }
        $o = db_get("SELECT full_name FROM owners WHERE id = ?", [$ownerId]);
        if ($o) $partyName = $o['full_name'];
    } elseif ($partyType === 'dealer') {
        $dealerId = (int)($_POST['dealer_id'] ?? 0);
        if (!$dealerId) {
            flash('danger', 'Select the dealer.');
            redirect('cash_received.php');
        }
        $d = db_get("SELECT full_name FROM dealers WHERE id = ?", [$dealerId]);
        if ($d) $partyName = $d['full_name'];
    } elseif ($partyType === 'tenant') {
        $tenantId = (int)($_POST['tenant_id'] ?? 0);
        $agreementId = (int)($_POST['agreement_id'] ?? 0);
        $scheduleId = (int)($_POST['schedule_id'] ?? 0);
        if (!$tenantId) {
            flash('danger', 'Select the tenant.');
            redirect('cash_received.php');
        }
        if (!$agreementId || !$scheduleId) {
            flash('danger', 'Select the agreement and rent schedule.');
            redirect('cash_received.php');
        }
        $t = db_get("SELECT full_name FROM tenants WHERE id = ?", [$tenantId]);
        if ($t) $partyName = $t['full_name'];
        $ra = db_get("SELECT p.project_id FROM rental_agreements ra JOIN properties p ON p.id = ra.property_id WHERE ra.id = ? AND ra.tenant_id = ?", [$agreementId, $tenantId]);
        if ($ra) $projectId = $ra['project_id'] ?: $projectId;
    }

    $defaultCode = $defaultCredit[$partyType] ?? '';
    if (!$accountId && $defaultCode) $accountId = coa_id_by_code($defaultCode);
    if (!$accountId) {
        flash('danger', 'Select the account to credit.');
        redirect('cash_received.php');
    }

    $cashAcc = cash_bank_account_id($bankId);
    if (!$cashAcc) {
        flash('danger', 'Cash / bank account not found in chart of accounts.');
        redirect('cash_received.php');
    }

    $bankName = $bankId ? (db_get("SELECT name FROM banks WHERE id = ?", [$bankId])['name'] ?? 'Bank') : 'Cash';
    $narr = $narration !== '' ? $narration : ($partyName !== '' ? 'Received from ' . $partyName : 'Cash received');
    $voucherType = $paymentMode === 'bank' ? 'bank_receipt' : 'cash_receipt';
    $vid = post_cash_voucher($date, $voucherType, $narr, $projectId, $cashAcc, $accountId, $amount,
        'Received from ' . ($partyName !== '' ? $partyName : 'N/A'), 'Received in ' . $bankName);

    if ($partyType === 'customer') {
        $receipt_no = next_number('RCT', 'receipts', 'receipt_no');
        db_exec("INSERT INTO receipts (receipt_no, receipt_date, project_id, customer_id, booking_id, installment_id, amount, payment_method_id, bank_id, reference, remarks, received_by, voucher_id, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$receipt_no, $date, $projectId, $customerId, $bookingId ?: null, $installmentId, $amount, null, $bankId, $reference, $narration, $user['id'], $vid]);
        if ($installmentId) {
            $inst = db_get("SELECT * FROM installments WHERE id = ? AND booking_id = ?", [$installmentId, $bookingId]);
            if ($inst) {
                $newPaid = (float)$inst['paid_amount'] + $amount;
                $newStatus = $newPaid >= (float)$inst['amount'] ? 'paid' : 'partial';
                db_exec("UPDATE installments SET paid_amount = ?, status = ?, paid_date = ?, received_by = ?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$newPaid, $newStatus, $date, $user['id'], $installmentId]);
            }
        }
        flash('success', 'Amount received. Receipt No: ' . $receipt_no . ', Voucher ' . $vid . ' posted.');
    } elseif ($partyType === 'tenant') {
        db_exec("INSERT INTO rent_collections (schedule_id, agreement_id, collection_date, amount, payment_method_id, bank_id, reference, remarks, voucher_id, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
            [$scheduleId, $agreementId, $date, $amount, null, $bankId, $reference, $narration, $vid]);
        rc_recalc_schedule($scheduleId);
        flash('success', 'Rent received. Voucher ' . $vid . ' posted.');
    } else {
        flash('success', 'Amount received. Voucher ' . $vid . ' posted.');
    }
    redirect('cash_received.php');
}

include '../includes/header.php';
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h5 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Cash Received</h5>
</div>

<form method="post" id="cashReceivedForm">
    <?= csrf_field() ?>
    <input type="hidden" name="party_type" id="partyType" value="">
    <input type="hidden" name="party_id" id="partyId" value="">
    <input type="hidden" name="customer_id" id="customerId" value="">
    <input type="hidden" name="vendor_id" id="vendorId" value="">
    <input type="hidden" name="owner_id" id="ownerId" value="">
    <input type="hidden" name="dealer_id" id="dealerId" value="">
    <input type="hidden" name="tenant_id" id="tenantId" value="">
    <input type="hidden" name="booking_id" id="bookingId" value="">
    <div class="card">
        <div class="card-header"><i class="bi bi-arrow-down-circle me-2"></i>Receive Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Search Party <small class="text-muted">(Name / CNIC / Phone / Code)</small></label>
                    <div class="position-relative">
                        <input type="text" id="partySearch" class="form-control form-control-lg" placeholder="Type customer name, tenant, vendor, CNIC..." autocomplete="off">
                        <div id="searchResults" class="list-group position-absolute w-100" style="z-index:1050;display:none;max-height:350px;overflow-y:auto"></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="received_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" data-mask-money required>
                </div>
            </div>

            <div class="row g-3 mt-0" id="partyInfoBox" style="display:none">
                <div class="col-md-6">
                    <label class="form-label">Selected Party</label>
                    <div class="p-3 bg-light rounded border d-flex align-items-start gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold fs-5" id="partyNameDisplay">-</span>
                                <span class="badge" id="partyTypeBadge">-</span>
                            </div>
                            <div class="small text-muted" id="partyDetailDisplay"></div>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">Outstanding Balance</div>
                            <div class="fs-5 fw-bold" id="partyBalanceDisplay">0.00</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="clearParty" title="Clear"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-0 d-none" id="secCustomer" data-sec="customer">
                <div class="col-md-4">
                    <label class="form-label">Property (Booking)</label>
                    <select name="property_id" id="selProperty" class="form-select">
                        <option value="">Select Property</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Installment (optional)</label>
                    <select name="installment_id" id="selInstallment" class="form-select">
                        <option value="0">-- No installment --</option>
                    </select>
                    <div class="form-text">Choose karo to installment mark ho jayegi.</div>
                </div>
            </div>

            <div class="row g-3 mt-0 d-none" id="secTenant" data-sec="tenant">
                <div class="col-md-4">
                    <label class="form-label">Rental Agreement</label>
                    <select name="agreement_id" id="selAgreement" class="form-select">
                        <option value="">Select Agreement</option>
                        <?php foreach ($agreements as $a): ?><option value="<?= $a['id'] ?>" data-tenant="<?= $a['tenant_id'] ?>"><?= e($a['agreement_no']) ?> - <?= e($a['property_no']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Rent Schedule</label>
                    <select name="schedule_id" id="selSchedule" class="form-select">
                        <option value="">Select Schedule</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mt-0">
                <div class="col-md-4">
                    <label class="form-label">Project</label>
                    <select name="project_id" id="selProject" class="form-select">
                        <option value="0">All Projects</option>
                        <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= (int)active_project_id() === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Payment Mode</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="payment_mode" id="pmCash" value="cash" checked>
                            <label class="form-check-label" for="pmCash">Cash</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="payment_mode" id="pmBank" value="bank">
                            <label class="form-check-label" for="pmBank">Bank</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 d-none" id="blockBank">
                    <label class="form-label">Bank</label>
                    <select name="bank_id" class="form-select">
                        <option value="">Select Bank</option>
                        <?php foreach ($banks as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?> - <?= e($b['account_no'] ?? '') ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reference</label>
                    <input type="text" name="reference" class="form-control" placeholder="Cheque no / Txn id (optional)">
                </div>
            </div>

            <div class="row g-3 mt-0 d-none" id="secOther" data-sec="other">
                <div class="col-md-6">
                    <label class="form-label">Account *</label>
                    <select name="account_id" class="form-select">
                        <option value="">Select Account</option>
                        <?php
                        $groups = ['asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity', 'income' => 'Income', 'expense' => 'Expenses'];
                        foreach ($groups as $g => $gname): ?>
                        <optgroup label="<?= $gname ?>">
                            <?php foreach ($accounts as $a): if ($a['account_type'] !== $g) continue; ?>
                            <option value="<?= $a['id'] ?>" data-code="<?= $a['code'] ?>"><?= e($a['code']) ?> - <?= e($a['name']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mt-0">
                <div class="col-md-6">
                    <label class="form-label">Narration</label>
                    <input type="text" name="narration" class="form-control" placeholder="Optional">
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex gap-2 flex-wrap">
        <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Save &amp; Post Voucher</button>
        <button type="button" class="btn btn-outline-secondary" id="btnOther"><i class="bi bi-journal-text me-1"></i>Receive Other</button>
        <a href="vouchers.php" class="btn btn-light ms-auto">View Vouchers</a>
    </div>
</form>

<script>
(function () {
    var partySearch = document.getElementById('partySearch');
    var partyId = document.getElementById('partyId');
    var partyType = document.getElementById('partyType');
    var customerId = document.getElementById('customerId');
    var vendorId = document.getElementById('vendorId');
    var ownerId = document.getElementById('ownerId');
    var dealerId = document.getElementById('dealerId');
    var tenantId = document.getElementById('tenantId');
    var bookingId = document.getElementById('bookingId');
    var searchResults = document.getElementById('searchResults');
    var partyInfoBox = document.getElementById('partyInfoBox');
    var secCustomer = document.getElementById('secCustomer');
    var secTenant = document.getElementById('secTenant');
    var project = document.getElementById('selProject');
    var property = document.getElementById('selProperty');
    var installment = document.getElementById('selInstallment');
    var agreement = document.getElementById('selAgreement');
    var schedule = document.getElementById('selSchedule');
    var secOther = document.getElementById('secOther');
    var pmCash = document.getElementById('pmCash');
    var pmBank = document.getElementById('pmBank');
    var blockBank = document.getElementById('blockBank');
    var debounceTimer = null;

    var typeLabels = {
        customer: { label: 'Customer', cls: 'bg-primary' },
        vendor: { label: 'Vendor', cls: 'bg-success' },
        owner: { label: 'Owner', cls: 'bg-warning text-dark' },
        dealer: { label: 'Dealer', cls: 'bg-info' },
        tenant: { label: 'Tenant', cls: 'bg-danger' },
        employee: { label: 'Employee', cls: 'bg-secondary' },
        contractor: { label: 'Contractor', cls: 'bg-dark' },
        investor: { label: 'Investor', cls: 'bg-purple' }
    };

    function clearSelection() {
        partyId.value = '';
        partyType.value = '';
        customerId.value = '';
        vendorId.value = '';
        ownerId.value = '';
        dealerId.value = '';
        tenantId.value = '';
        bookingId.value = '';
        partySearch.value = '';
        searchResults.style.display = 'none';
        searchResults.innerHTML = '';
        partyInfoBox.style.display = 'none';
        secCustomer.classList.add('d-none');
        secTenant.classList.add('d-none');
        secOther.classList.add('d-none');
        property.innerHTML = '<option value="">Select Property</option>';
        installment.innerHTML = '<option value="0">-- No installment --</option>';
        schedule.innerHTML = '<option value="">Select Schedule</option>';
    }

    document.getElementById('clearParty').addEventListener('click', clearSelection);

    document.getElementById('btnOther').addEventListener('click', function () {
        clearSelection();
        partyType.value = 'other';
        secOther.classList.remove('d-none');
        partyInfoBox.style.display = 'none';
    });

    partySearch.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); }
    });

    partySearch.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        var q = partySearch.value.trim();
        if (q.length < 2) { searchResults.style.display = 'none'; return; }
        debounceTimer = setTimeout(function () {
            fetch('<?= BASE_URL ?>/pages/ajax.php?action=party_search&q=' + encodeURIComponent(q) + '&_t=' + Date.now())
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    searchResults.innerHTML = '';
                    if (data && data.error) {
                        searchResults.innerHTML = '<div class="list-group-item text-danger">' + data.error + '</div>';
                        searchResults.style.display = 'block';
                        return;
                    }
                    if (!data || !Array.isArray(data) || data.length === 0) {
                        searchResults.innerHTML = '<div class="list-group-item text-muted">No results found</div>';
                        searchResults.style.display = 'block';
                        return;
                    }
                    data.forEach(function (item) {
                        var tl = typeLabels[item.type] || { label: item.type, cls: 'bg-secondary' };
                        var detail = item.code ? item.code : '';
                        if (item.cnic) detail += (detail ? ' | ' : '') + 'CNIC: ' + item.cnic;
                        if (item.phone) detail += (detail ? ' | ' : '') + item.phone;
                        var div = document.createElement('button');
                        div.type = 'button';
                        div.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                        div.innerHTML = '<div><div class="fw-medium">' + (item.name || '') + '</div>' +
                            (detail ? '<div class="small text-muted">' + detail + '</div>' : '') + '</div>' +
                            '<span class="badge ' + tl.cls + '">' + tl.label + '</span>';
                        div.addEventListener('click', function () {
                            partyId.value = item.id;
                            partyType.value = item.type;
                            partySearch.value = item.name || '';
                            searchResults.style.display = 'none';
                            customerId.value = '';
                            vendorId.value = '';
                            ownerId.value = '';
                            dealerId.value = '';
                            tenantId.value = '';
                            bookingId.value = '';
                            if (item.type === 'customer') customerId.value = item.id;
                            else if (item.type === 'vendor') vendorId.value = item.id;
                            else if (item.type === 'owner') ownerId.value = item.id;
                            else if (item.type === 'dealer') dealerId.value = item.id;
                            else if (item.type === 'tenant') tenantId.value = item.id;

                            secCustomer.classList.toggle('d-none', item.type !== 'customer');
                            secTenant.classList.toggle('d-none', item.type !== 'tenant');
                            secOther.classList.add('d-none');

                            if (item.type === 'customer') {
                                loadProperties();
                            } else {
                                property.innerHTML = '<option value="">Select Property</option>';
                                installment.innerHTML = '<option value="0">-- No installment --</option>';
                            }
                            if (item.type === 'tenant') {
                                schedule.innerHTML = '<option value="">Select Schedule</option>';
                                Array.prototype.forEach.call(agreement.options, function (o) {
                                    o.style.display = (o.value !== '' && o.dataset.tenant == item.id) ? '' : 'none';
                                });
                                agreement.value = '';
                            } else {
                                Array.prototype.forEach.call(agreement.options, function (o) {
                                    o.style.display = '';
                                });
                                agreement.value = '';
                                schedule.innerHTML = '<option value="">Select Schedule</option>';
                            }

                            partyInfoBox.style.display = '';
                            var tl2 = typeLabels[item.type] || { label: item.type, cls: 'bg-secondary' };
                            document.getElementById('partyNameDisplay').textContent = item.name || '';
                            document.getElementById('partyTypeBadge').textContent = tl2.label;
                            document.getElementById('partyTypeBadge').className = 'badge ' + tl2.cls;
                            document.getElementById('partyDetailDisplay').textContent = detail;
                            document.getElementById('partyBalanceDisplay').textContent = 'Loading...';
                            document.getElementById('partyBalanceDisplay').className = 'fs-5 fw-bold';

                            fetch('<?= BASE_URL ?>/pages/ajax.php?action=party_balance&type=' + item.type + '&id=' + item.id + '&_t=' + Date.now())
                                .then(function (r) { return r.json(); })
                                .then(function (b) {
                                    if (b && b.error) {
                                        document.getElementById('partyBalanceDisplay').textContent = b.error;
                                        document.getElementById('partyBalanceDisplay').className = 'fs-5 fw-bold text-danger';
                                        return;
                                    }
                                    var bal = parseFloat(b.balance) || 0;
                                    var color = bal > 0 ? 'text-danger' : (bal < 0 ? 'text-success' : 'text-muted');
                                    document.getElementById('partyBalanceDisplay').textContent = 'Rs. ' + bal.toFixed(2);
                                    document.getElementById('partyBalanceDisplay').className = 'fs-5 fw-bold ' + color;
                                })
                                .catch(function () {
                                    document.getElementById('partyBalanceDisplay').textContent = 'Error loading balance';
                                    document.getElementById('partyBalanceDisplay').className = 'fs-5 fw-bold text-danger';
                                });
                        });
                        searchResults.appendChild(div);
                    });
                    searchResults.style.display = 'block';
                })
                .catch(function () {
                    searchResults.innerHTML = '<div class="list-group-item text-danger">Search failed. Please try again.</div>';
                    searchResults.style.display = 'block';
                });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!partySearch.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });

    function loadProperties() {
        var cid = customerId.value;
        var pid = project.value;
        property.innerHTML = '<option value="">Loading...</option>';
        installment.innerHTML = '<option value="0">-- No installment --</option>';
        bookingId.value = '';
        if (!cid) { property.innerHTML = '<option value="">Select Property</option>'; return; }
        fetch('ajax.php?action=properties_by_customer&customer_id=' + cid + '&project_id=' + pid)
            .then(function (r) { return r.json(); })
            .then(function (rows) {
                property.innerHTML = '<option value="">Select Property</option>' +
                    rows.map(function (r) { return '<option value="' + r.property_id + '" data-booking="' + r.booking_id + '">' + r.name + '</option>'; }).join('');
            });
    }

    property.addEventListener('change', function () {
        var opt = property.options[property.selectedIndex];
        bookingId.value = opt ? (opt.dataset.booking || '') : '';
        installment.innerHTML = '<option value="0">-- No installment --</option>';
        if (!bookingId.value) return;
        fetch('ajax.php?action=installments&id=' + bookingId.value)
            .then(function (r) { return r.json(); })
            .then(function (rows) {
                installment.innerHTML = '<option value="0">-- No installment --</option>' +
                    rows.map(function (r) { return '<option value="' + r.id + '">' + r.name + '</option>'; }).join('');
            });
    });

    agreement.addEventListener('change', function () {
        var aid = agreement.value;
        schedule.innerHTML = '<option value="">Select Schedule</option>';
        if (!aid) return;
        fetch('ajax.php?action=rental_schedules&id=' + aid)
            .then(function (r) { return r.json(); })
            .then(function (rows) {
                schedule.innerHTML = '<option value="">Select Schedule</option>' +
                    rows.map(function (r) { return '<option value="' + r.id + '">' + r.name + '</option>'; }).join('');
            });
    });

    project.addEventListener('change', function () {
        if (partyType.value === 'customer' && customerId.value) loadProperties();
    });

    function toggleBank() { blockBank.classList.toggle('d-none', !pmBank.checked); }
    pmCash.addEventListener('change', toggleBank);
    pmBank.addEventListener('change', toggleBank);
})();
</script>
<?php include '../includes/footer.php'; ?>

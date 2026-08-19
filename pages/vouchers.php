<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Journal Vouchers';
$active = 'vouchers';
$canEdit = has_permission('accounting.manage');

if (is_post() && $canEdit) {
    csrf_check();
    if (($_POST['action'] ?? '') === 'delete') {
        $delId = (int)($_POST['id'] ?? 0);
        global $mysqli;
        $mysqli->begin_transaction();
        try {
            $linkedReceipt = db_get("SELECT * FROM receipts WHERE voucher_id = ?", [$delId]);
            if ($linkedReceipt) {
                if (!empty($linkedReceipt['installment_id'])) {
                    $inst = db_get("SELECT * FROM installments WHERE id = ?", [$linkedReceipt['installment_id']]);
                    if ($inst) {
                        $newPaid = max(0, (float)$inst['paid_amount'] - (float)$linkedReceipt['amount']);
                        $newStatus = $newPaid >= (float)$inst['amount'] ? 'paid' : ($newPaid > 0 ? 'partial' : 'pending');
                        db_exec("UPDATE installments SET paid_amount = ?, status = ?, paid_date = NULL, updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$newPaid, $newStatus, $linkedReceipt['installment_id']]);
                    }
                }
                db_exec("DELETE FROM receipts WHERE id = ?", [$linkedReceipt['id']]);
            }
            $linkedCustPay = db_get("SELECT id FROM customer_payments WHERE voucher_id = ?", [$delId]);
            if ($linkedCustPay) db_exec("DELETE FROM customer_payments WHERE id = ?", [$linkedCustPay['id']]);
            $linkedVendorPay = db_get("SELECT id FROM vendor_payments WHERE voucher_id = ?", [$delId]);
            if ($linkedVendorPay) db_exec("DELETE FROM vendor_payments WHERE id = ?", [$linkedVendorPay['id']]);
            $linkedDealerPay = db_get("SELECT id FROM dealer_payments WHERE voucher_id = ?", [$delId]);
            if ($linkedDealerPay) db_exec("DELETE FROM dealer_payments WHERE id = ?", [$linkedDealerPay['id']]);
            $linkedOwnerSettlement = db_get("SELECT id, owner_id FROM owner_settlements WHERE voucher_id = ?", [$delId]);
            if ($linkedOwnerSettlement) {
                db_exec("DELETE FROM owner_settlements WHERE id = ?", [$linkedOwnerSettlement['id']]);
                $osOwner = (int)$linkedOwnerSettlement['owner_id'];
                if ($osOwner) db_exec("DELETE FROM owner_ledger WHERE owner_id = ? AND description = 'Payment to owner' AND voucher_id = ?", [$osOwner, $delId]);
            }
            $linkedInvestorLedger = db_get("SELECT id, investor_id FROM investor_ledger WHERE voucher_id = ?", [$delId]);
            if ($linkedInvestorLedger) {
                db_exec("DELETE FROM investor_ledger WHERE id = ?", [$linkedInvestorLedger['id']]);
            }
            $linkedRentCollection = db_get("SELECT id, schedule_id FROM rent_collections WHERE voucher_id = ?", [$delId]);
            if ($linkedRentCollection) {
                db_exec("DELETE FROM rent_collections WHERE id = ?", [$linkedRentCollection['id']]);
                if (!empty($linkedRentCollection['schedule_id'])) {
                    $schedId = (int)$linkedRentCollection['schedule_id'];
                    $agg = db_get("SELECT COALESCE(SUM(amount),0) amt FROM rent_collections WHERE schedule_id = ?", [$schedId]);
                    $sched = db_get("SELECT rent_amount, late_charges FROM rent_schedule WHERE id = ?", [$schedId]);
                    if ($sched && $agg) {
                        $total = (float)$sched['rent_amount'] + (float)$sched['late_charges'];
                        $paid = (float)$agg['amt'];
                        $st = $total > 0 && $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'pending');
                        db_exec("UPDATE rent_schedule SET paid_amount=?, status=?, paid_date=NULL, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$paid, $st, $schedId]);
                    }
                }
            }
            $linkedEmpEntry = db_get("SELECT id FROM employee_entries WHERE voucher_id = ?", [$delId]);
            if ($linkedEmpEntry) db_exec("DELETE FROM employee_entries WHERE id = ?", [$linkedEmpEntry['id']]);
            $linkedConEntry = db_get("SELECT id FROM contractor_entries WHERE voucher_id = ?", [$delId]);
            if ($linkedConEntry) db_exec("DELETE FROM contractor_entries WHERE id = ?", [$linkedConEntry['id']]);
            db_exec("DELETE FROM voucher_items WHERE voucher_id = ?", [$delId]);
            db_exec("DELETE FROM vouchers WHERE id = ?", [$delId]);
            $mysqli->commit();
            flash('success', 'Voucher deleted successfully.');
        } catch (\Exception $e) {
            $mysqli->rollback();
            flash('danger', 'Failed to delete voucher.');
        }
        redirect('vouchers.php');
    }
}

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$voucher_no = trim($_GET['voucher_no'] ?? '');
$project_id = (int)($_GET['project_id'] ?? active_project_id());
$params = [];
$where = '';
if ($from) {
    $where .= " AND v.voucher_date >= ?";
    $params[] = $from;
}
if ($to) {
    $where .= " AND v.voucher_date <= ?";
    $params[] = $to;
}
if ($voucher_no !== '') {
    $where .= " AND v.voucher_no LIKE ?";
    $params[] = '%' . $voucher_no . '%';
}
if ($project_id > 0) {
    $where .= " AND v.project_id = ?";
    $params[] = $project_id;
}

$records = db_all("SELECT v.*, p.name AS project_name, u.full_name AS created_name,
                   (SELECT COALESCE(SUM(vi.debit),0) FROM voucher_items vi WHERE vi.voucher_id = v.id) AS total_debit,
                   (SELECT COALESCE(SUM(vi.credit),0) FROM voucher_items vi WHERE vi.voucher_id = v.id) AS total_credit
                   FROM vouchers v
                   LEFT JOIN projects p ON p.id = v.project_id
                   LEFT JOIN users u ON u.id = v.created_by
                   WHERE 1=1$where
                   ORDER BY v.voucher_date DESC, v.id DESC", $params);
$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
$typeBadges = ['cash_payment' => 'danger', 'cash_receipt' => 'success', 'bank_payment' => 'warning', 'bank_receipt' => 'info', 'journal' => 'primary'];
include '../includes/header.php';
?>

<form method="get" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-2">
                <select name="project_id" class="form-select form-select-sm">
                    <option value="">All Projects</option>
                    <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="voucher_no" class="form-control form-control-sm" placeholder="Voucher No" value="<?= e($voucher_no) ?>">
            </div>
            <div class="col-md-2"><input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>" placeholder="From"></div>
            <div class="col-md-2"><input type="date" name="to" class="form-control form-control-sm" value="<?= e($to) ?>" placeholder="To"></div>
            <div class="col-md-2"><button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i>Filter</button></div>
            <div class="col-md-2 text-end small text-muted">Vouchers: <strong><?= count($records) ?></strong></div>
        </div>
    </div>
</form>

<div class="toolbar d-flex flex-wrap align-items-center gap-2 mb-3">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search vouchers...">
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="vouchers_print.php?project_id=<?= $project_id ?>&from=<?= e($from) ?>&to=<?= e($to) ?>" target="_blank" title="Print register"><i class="bi bi-printer me-1"></i>Print Register</a>
    <?php if ($canEdit): ?>
    <a class="btn btn-primary ms-auto" href="voucher_form.php"><i class="bi bi-plus-lg me-1"></i>New Journal Voucher</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr>
                    <th style="width:40px">#</th>
                    <th>Voucher No</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Reference</th>
                    <th>Project</th>
                    <th>Narration</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Credit</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-medium"><a href="voucher_view.php?id=<?= $r['id'] ?>"><?= e($r['voucher_no']) ?></a></td>
                        <td><?= fmt_date($r['voucher_date']) ?></td>
                        <td><span class="badge bg-<?= $typeBadges[$r['voucher_type']] ?>"><?= ucfirst(str_replace('_', ' ', $r['voucher_type'])) ?></span></td>
                        <td class="small"><?= e($r['reference_no'] ?? '-') ?></td>
                        <td class="small"><?= $r['project_name'] ? '<span class="badge bg-light text-dark border">' . e($r['project_name']) . '</span>' : '<span class="text-muted">General</span>' ?></td>
                        <td class="small text-truncate" style="max-width:200px" title="<?= e($r['narration'] ?? '') ?>"><?= e($r['narration'] ?? '-') ?></td>
                        <td class="text-end"><?= fmt_money($r['total_debit']) ?></td>
                        <td class="text-end"><?= fmt_money($r['total_credit']) ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-info" href="voucher_view.php?id=<?= $r['id'] ?>" title="View"><i class="bi bi-eye"></i></a>
                            <a class="btn btn-sm btn-outline-secondary" href="voucher_print.php?id=<?= $r['id'] ?>" target="_blank" title="Print"><i class="bi bi-printer"></i></a>
                            <?php if ($canEdit): ?>
                            <a class="btn btn-sm btn-outline-primary" href="voucher_form.php?id=<?= $r['id'] ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form method="post" class="d-inline" data-confirm="Delete this voucher and all its entries?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$records): ?>
                    <tr><td colspan="11"><div class="empty-state"><i class="bi bi-journal-text"></i><p>No vouchers found</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

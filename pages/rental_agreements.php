<?php
require_once '../includes/auth.php';
require_login();
require_permission('rentals.view');
$title = 'Rental Agreements';
$active = 'rental_agreements';
$canEdit = has_permission('rentals.manage');

if (is_post() && $canEdit) {
    csrf_check();
    if (($_POST['action'] ?? '') === 'delete') {
        db_exec("DELETE FROM rental_agreements WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Rental agreement deleted successfully.');
        redirect('rental_agreements.php');
    }
}

$status = $_GET['status'] ?? '';
$records = db_all("SELECT ra.*, p.property_no, t.full_name AS tenant_name, o.full_name AS owner_name,
                   (SELECT COALESCE(SUM(rs.rent_amount + rs.late_charges),0) FROM rent_schedule rs WHERE rs.agreement_id = ra.id) AS total_due,
                   (SELECT COALESCE(SUM(rs.paid_amount),0) FROM rent_schedule rs WHERE rs.agreement_id = ra.id) AS total_paid
                   FROM rental_agreements ra
                   JOIN properties p ON p.id = ra.property_id
                   JOIN tenants t ON t.id = ra.tenant_id
                   LEFT JOIN owners o ON o.id = ra.owner_id
                   WHERE (? = '' OR ra.status = ?)
                   ORDER BY ra.start_date DESC", [$status, $status]);
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search agreements...">
    </div>
    <select class="form-select form-select-sm" style="max-width:160px" onchange="location.href='?status='+this.value">
        <option value="">All Status</option>
        <?php foreach (['active', 'renewed', 'expired', 'terminated', 'vacated'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($canEdit): ?>
    <a class="btn btn-primary ms-auto" href="rental_agreement_form.php"><i class="bi bi-plus-lg me-1"></i>New Rental Agreement</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Agreement</th><th>Property</th><th>Tenant</th><th>Owner</th><th>Period</th><th>Rent</th><th>Balance</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-medium"><?= e($r['agreement_no']) ?></td>
                        <td><?= e($r['property_no']) ?></td>
                        <td><a href="tenant_view.php?id=<?= $r['tenant_id'] ?>"><?= e($r['tenant_name']) ?></a></td>
                        <td><?= e($r['owner_name'] ?? '-') ?></td>
                        <td class="small"><?= fmt_date($r['start_date']) ?><br><?= fmt_date($r['end_date']) ?></td>
                        <td><?= fmt_money($r['monthly_rent']) ?></td>
                        <td><span class="<?= ((float)$r['total_due'] - (float)$r['total_paid']) > 0 ? 'text-danger fw-medium' : 'text-success' ?>"><?= fmt_money((float)$r['total_due'] - (float)$r['total_paid']) ?></span></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-info" href="rental_agreement_view.php?id=<?= $r['id'] ?>"><i class="bi bi-eye"></i></a>
                            <?php if ($canEdit): ?>
                            <a class="btn btn-sm btn-outline-primary" href="rental_agreement_form.php?id=<?= $r['id'] ?>"><i class="bi bi-pencil"></i></a>
                            <form method="post" class="d-inline" data-confirm="Delete this rental agreement? Schedule and collections will be removed.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$records): ?>
                    <tr><td colspan="10"><div class="empty-state"><i class="bi bi-house-heart"></i><p>No rental agreements yet</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

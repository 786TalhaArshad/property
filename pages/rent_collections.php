<?php
require_once '../includes/auth.php';
require_login();
require_permission('rentals.view');
$title = 'Rent Collections';
$active = 'rent_collections';

$agreement_id = (int)($_GET['agreement_id'] ?? 0);
$records = db_all("SELECT rc.*, rs.period, ra.agreement_no, p.property_no, t.full_name AS tenant_name, pm.name AS method_name, bk.name AS bank_name
                   FROM rent_collections rc
                   JOIN rent_schedule rs ON rs.id = rc.schedule_id
                   JOIN rental_agreements ra ON ra.id = rc.agreement_id
                   JOIN properties p ON p.id = ra.property_id
                   JOIN tenants t ON t.id = ra.tenant_id
                   LEFT JOIN payment_methods pm ON pm.id = rc.payment_method_id
                   LEFT JOIN banks bk ON bk.id = rc.bank_id
                   WHERE (? = 0 OR rc.agreement_id = ?)
                   ORDER BY rc.collection_date DESC", [$agreement_id, $agreement_id]);
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search collections...">
    </div>
    <?php if ($agreement_id): ?><a class="btn btn-sm btn-light" href="rent_collections.php"><i class="bi bi-x-lg me-1"></i>Clear Filter</a><?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Date</th><th>Agreement</th><th>Property</th><th>Tenant</th><th>Period</th><th>Amount</th><th>Method</th><th>Bank</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= fmt_date($r['collection_date']) ?></td>
                        <td><a href="rental_agreement_view.php?id=<?= $r['agreement_id'] ?>"><?= e($r['agreement_no']) ?></a></td>
                        <td><?= e($r['property_no']) ?></td>
                        <td><a href="tenant_view.php?id=<?= $r['tenant_id'] ?>"><?= e($r['tenant_name']) ?></a></td>
                        <td><?= e($r['period']) ?></td>
                        <td><?= fmt_money($r['amount']) ?></td>
                        <td><?= e($r['method_name'] ?? '-') ?></td>
                        <td><?= e($r['bank_name'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$records): ?>
                    <tr><td colspan="9"><div class="empty-state"><i class="bi bi-cash"></i><p>No rent collections yet</p></div></td></tr>
                <?php endif; ?>
                </tbody>
                <tfoot>
                <?php if ($records): ?>
                <tr class="table-light">
                    <td colspan="6" class="text-end fw-bold">Total</td>
                    <td class="fw-bold"><?= fmt_money(array_sum(array_map(function ($r) { return (float)$r['amount']; }, $records))) ?></td>
                    <td colspan="2"></td>
                </tr>
                <?php endif; ?>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

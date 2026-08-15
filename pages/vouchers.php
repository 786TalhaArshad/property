<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Vouchers';
$active = 'vouchers';
$canEdit = has_permission('accounting.manage');

if (is_post() && $canEdit) {
    csrf_check();
    if (($_POST['action'] ?? '') === 'delete') {
        db_exec("DELETE FROM vouchers WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Voucher deleted successfully.');
        redirect('vouchers.php');
    }
}

$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
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
if ($project_id > 0) {
    $where .= " AND v.project_id = ?";
    $params[] = $project_id;
}

$records = db_all("SELECT v.*, p.name AS project_name, u.full_name AS created_name,
                   (SELECT COALESCE(SUM(vi.debit),0) FROM voucher_items vi WHERE vi.voucher_id = v.id) AS total
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
            <div class="col-md-3">
                <select name="project_id" class="form-select form-select-sm">
                    <option value="">All Projects</option>
                    <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>"></div>
            <div class="col-md-2"><input type="date" name="to" class="form-control form-control-sm" value="<?= e($to) ?>"></div>
            <div class="col-md-2"><button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i>Filter</button></div>
            <div class="col-md-3 text-end small text-muted">Vouchers: <strong><?= count($records) ?></strong></div>
        </div>
    </div>
</form>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search vouchers...">
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="vouchers_print.php?project_id=<?= $project_id ?>&from=<?= e($from) ?>&to=<?= e($to) ?>" target="_blank" title="Print all vouchers"><i class="bi bi-printer me-1"></i>Print</a>
    <?php if ($canEdit): ?>
    <a class="btn btn-primary ms-auto" href="voucher_form.php"><i class="bi bi-plus-lg me-1"></i>New Voucher</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Voucher</th><th>Date</th><th>Type</th><th>Project</th><th>Narration</th><th>Amount</th><th>Status</th><?php if ($canEdit): ?><th class="text-end">Actions</th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-medium"><?= e($r['voucher_no']) ?></td>
                        <td><?= fmt_date($r['voucher_date']) ?></td>
                        <td><span class="badge bg-<?= $typeBadges[$r['voucher_type']] ?>"><?= ucfirst(str_replace('_', ' ', $r['voucher_type'])) ?></span></td>
                        <td class="small"><?= $r['project_name'] ? '<span class="badge bg-light text-dark border">' . e($r['project_name']) . '</span>' : '<span class="text-muted">General</span>' ?></td>
                        <td class="small"><?= e($r['narration'] ?? '-') ?></td>
                        <td><?= fmt_money($r['total']) ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <?php if ($canEdit): ?>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="voucher_form.php?id=<?= $r['id'] ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form method="post" class="d-inline" data-confirm="Delete this voucher? All entries will be removed.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$records): ?>
                    <tr><td colspan="9"><div class="empty-state"><i class="bi bi-receipt"></i><p>No vouchers found</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

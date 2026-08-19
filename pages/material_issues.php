<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Material Issues';
$active = 'material_issues';
$canEdit = has_permission('accounting.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $mid = (int)($_POST['id'] ?? 0);
        $mi = db_get("SELECT * FROM material_issues WHERE id = ?", [$mid]);
        if ($mi) {
            $items = db_all("SELECT product_id, quantity, unit_cost FROM material_issue_items WHERE material_issue_id = ?", [$mid]);
            foreach ($items as $it) {
                stock_adjust($it['product_id'], 'purchase', (float)$it['quantity'], (float)$it['unit_cost'], 'material_issue', $mid);
            }
            if ($mi['voucher_id']) {
                db_exec("DELETE FROM voucher_items WHERE voucher_id = ?", [$mi['voucher_id']]);
                db_exec("DELETE FROM vouchers WHERE id = ?", [$mi['voucher_id']]);
            }
            db_exec("DELETE FROM material_issue_items WHERE material_issue_id = ?", [$mid]);
            db_exec("DELETE FROM material_issues WHERE id = ?", [$mid]);
            flash('success', 'Material issue deleted. Stock restored.');
        }
    }
    redirect('material_issues.php');
}

$projectId = (int)($_GET['project_id'] ?? active_project_id());
$contractorFilter = (int)($_GET['contractor_id'] ?? 0);
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$sql = "SELECT mi.*, p.name AS project_name, c.full_name AS contractor_name,
        (SELECT COUNT(*) FROM material_issue_items mii WHERE mii.material_issue_id = mi.id) AS item_count
        FROM material_issues mi
        LEFT JOIN projects p ON p.id = mi.project_id
        LEFT JOIN contractors c ON c.id = mi.contractor_id
        WHERE 1=1";
$params = [];
if ($projectId > 0) { $sql .= " AND mi.project_id = ?"; $params[] = $projectId; }
if ($contractorFilter > 0) { $sql .= " AND mi.contractor_id = ?"; $params[] = $contractorFilter; }
if ($dateFrom !== '') { $sql .= " AND mi.issue_date >= ?"; $params[] = $dateFrom; }
if ($dateTo !== '') { $sql .= " AND mi.issue_date <= ?"; $params[] = $dateTo; }
$sql .= " ORDER BY mi.issue_date DESC, mi.id DESC";
$records = db_all($sql, $params);

$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
$contractors = db_all("SELECT * FROM contractors WHERE status = 1 ORDER BY full_name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2 mb-3">
    <form method="get" class="d-flex flex-wrap align-items-center gap-2">
        <select name="project_id" class="form-select form-select-sm" onchange="this.form.submit()" style="max-width:200px">
            <option value="0">All Projects</option>
            <?php foreach ($projects as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $projectId === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="contractor_id" class="form-select form-select-sm" onchange="this.form.submit()" style="max-width:200px">
            <option value="0">All Contractors</option>
            <?php foreach ($contractors as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $contractorFilter === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['full_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>" style="max-width:150px">
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo) ?>" style="max-width:150px">
        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-funnel"></i></button>
    </form>
    <div class="input-group input-group-sm ms-auto" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search issues...">
    </div>
    <?php if ($canEdit): ?>
    <a href="material_issue_form.php" class="btn btn-primary btn-sm ms-auto"><i class="bi bi-plus-lg me-1"></i>New Material Issue</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Issue No</th>
                        <th>Date</th>
                        <th>Project</th>
                        <th>Contractor</th>
                        <th class="text-end">Items</th>
                        <th class="text-end">Total Amount</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><a href="material_issue_view.php?id=<?= $r['id'] ?>" class="fw-medium text-decoration-none"><?= e($r['issue_no']) ?></a></td>
                        <td><?= fmt_date($r['issue_date']) ?></td>
                        <td><?= e($r['project_name']) ?></td>
                        <td><?= e($r['contractor_name']) ?></td>
                        <td class="text-end"><?= (int)$r['item_count'] ?></td>
                        <td class="text-end"><?= fmt_money($r['total_amount']) ?></td>
                        <td class="text-end">
                            <a href="material_issue_view.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
                            <?php if ($canEdit): ?>
                            <a href="material_issue_form.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form method="post" class="d-inline" data-confirm="Delete this issue? Stock will be restored.">
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
                    <tr><td colspan="8"><div class="empty-state"><i class="bi bi-box-seam"></i><p>No material issues yet</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

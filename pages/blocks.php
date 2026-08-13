<?php
require_once '../includes/auth.php';
require_login();
require_permission('projects.view');
$title = 'Blocks';
$active = 'projects';
$canEdit = has_permission('projects.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $project_id = $id > 0 ? (int)(db_get("SELECT project_id FROM blocks WHERE id = ?", [$id])['project_id'] ?? 0) : active_project_id();
        if ($name === '' || !$project_id) {
            flash('danger', 'Block name is required and an active project must be selected.');
        } elseif ($id > 0) {
            db_exec("UPDATE blocks SET name=?, project_id=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$name, $project_id, $id]);
            flash('success', 'Block updated successfully.');
        } else {
            db_exec("INSERT INTO blocks (project_id, name, created_date, created_time, updated_date, updated_time) VALUES (?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$project_id, $name]);
            flash('success', 'Block added successfully.');
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM blocks WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Block deleted successfully.');
    }
    redirect('blocks.php');
}

if (active_project_id()) {
    $records = db_all("SELECT b.*, p.name AS project_name FROM blocks b JOIN projects p ON p.id = b.project_id WHERE b.project_id = ? ORDER BY b.name", [active_project_id()]);
} else {
    $records = db_all("SELECT b.*, p.name AS project_name FROM blocks b JOIN projects p ON p.id = b.project_id ORDER BY p.name, b.name");
}
$projects = db_all("SELECT * FROM projects ORDER BY name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search blocks...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Block"><i class="bi bi-plus-lg me-1"></i>Add Block</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:60px">#</th><th>Block</th><th>Project</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-medium"><?= e($r['name']) ?></td>
                        <td><?= e($r['project_name']) ?></td>
                        <td class="text-end">
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'name' => $r['name'], 'project_id' => $r['project_id']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this block? This cannot be undone.">
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
                    <tr><td colspan="4"><div class="empty-state"><i class="bi bi-grid"></i><p>No blocks yet</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="recordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header"><h5 class="modal-title">Add Block</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="mb-3">
                        <label class="form-label">Project</label>
                        <select name="project_id" class="form-select" disabled>
                            <option value="">Select project</option>
                            <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= active_project_id() === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                        </select>
                        <div class="form-text">Project comes from the active project selected in the header.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Block Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_login();
require_permission('projects.view');
$title = 'Streets';
$active = 'projects';
$canEdit = has_permission('projects.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $project_id = (int)($_POST['project_id'] ?? 0);
        $block_id = (int)($_POST['block_id'] ?? 0) ?: null;
        if ($name === '' || !$project_id) {
            flash('danger', 'Street name and project are required.');
        } elseif ($id > 0) {
            db_exec("UPDATE streets SET name=?, project_id=?, block_id=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$name, $project_id, $block_id, $id]);
            flash('success', 'Street updated successfully.');
        } else {
            db_exec("INSERT INTO streets (project_id, block_id, name, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$project_id, $block_id, $name]);
            flash('success', 'Street added successfully.');
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM streets WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Street deleted successfully.');
    }
    redirect('streets.php');
}

$records = db_all("SELECT s.*, p.name AS project_name, b.name AS block_name FROM streets s JOIN projects p ON p.id = s.project_id LEFT JOIN blocks b ON b.id = s.block_id ORDER BY p.name, s.name");
$projects = db_all("SELECT * FROM projects ORDER BY name");
$blocks = db_all("SELECT * FROM blocks ORDER BY name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search streets...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Street"><i class="bi bi-plus-lg me-1"></i>Add Street</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:60px">#</th><th>Street</th><th>Block</th><th>Project</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-medium"><?= e($r['name']) ?></td>
                        <td><?= e($r['block_name'] ?? '-') ?></td>
                        <td><?= e($r['project_name']) ?></td>
                        <td class="text-end">
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'name' => $r['name'], 'project_id' => $r['project_id'], 'block_id' => $r['block_id']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this street? This cannot be undone.">
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
                    <tr><td colspan="5"><div class="empty-state"><i class="bi bi-signpost-split"></i><p>No streets yet</p></div></td></tr>
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
                <div class="modal-header"><h5 class="modal-title">Add Street</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="mb-3">
                        <label class="form-label">Project</label>
                        <select name="project_id" class="form-select" required>
                            <option value="">Select project</option>
                            <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Block</label>
                        <select name="block_id" class="form-select">
                            <option value="">None</option>
                            <?php foreach ($blocks as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Street Name</label>
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

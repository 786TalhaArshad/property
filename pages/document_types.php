<?php
require_once '../includes/auth.php';
require_login();
require_permission('master.view');
$title = 'Document Types';
$active = 'master';
$canEdit = has_permission('settings.manage');
$table = 'document_types';
$label = 'Document Type';

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            flash('danger', ucfirst($label) . ' name is required.');
        } else {
            $dup = db_get("SELECT id FROM $table WHERE name = ? AND id <> ?", [$name, $id]);
            if ($dup) {
                flash('danger', 'This record already exists.');
            } elseif ($id > 0) {
                db_exec("UPDATE $table SET name=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$name, $id]);
                flash('success', ucfirst($label) . ' updated successfully.');
            } else {
                db_exec("INSERT INTO $table (name, created_date, created_time, updated_date, updated_time) VALUES (?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$name]);
                flash('success', ucfirst($label) . ' added successfully.');
            }
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM $table WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', ucfirst($label) . ' deleted successfully.');
    }
    redirect('document_types.php');
}

$records = db_all("SELECT * FROM $table ORDER BY name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Document Type"><i class="bi bi-plus-lg me-1"></i>Add <?= $label ?></button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:60px">#</th><th>Name</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= e($r['name']) ?></td>
                        <td class="text-end">
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'name' => $r['name']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this record? This cannot be undone.">
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
                    <tr><td colspan="3"><div class="empty-state"><i class="bi bi-file-earmark"></i><p>No records yet</p></div></td></tr>
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
                <div class="modal-header"><h5 class="modal-title">Add <?= $label ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
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

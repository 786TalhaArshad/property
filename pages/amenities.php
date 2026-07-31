<?php
require_once '../includes/auth.php';
require_login();
require_permission('master.view');
$title = 'Amenities';
$active = 'master';
$canEdit = has_permission('settings.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        if ($name === '') {
            flash('danger', 'Amenity name is required.');
        } else {
            $dup = db_get("SELECT id FROM amenities WHERE name = ? AND id <> ?", [$name, $id]);
            if ($dup) {
                flash('danger', 'This amenity already exists.');
            } elseif ($id > 0) {
                db_exec("UPDATE amenities SET name=?, icon=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$name, $icon, $id]);
                flash('success', 'Amenity updated successfully.');
            } else {
                db_exec("INSERT INTO amenities (name, icon, created_date, created_time, updated_date, updated_time) VALUES (?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$name, $icon]);
                flash('success', 'Amenity added successfully.');
            }
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM amenities WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Amenity deleted successfully.');
    }
    redirect('amenities.php');
}

$records = db_all("SELECT * FROM amenities ORDER BY name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Amenity"><i class="bi bi-plus-lg me-1"></i>Add Amenity</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:60px">#</th><th>Icon</th><th>Name</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><i class="bi <?= e($r['icon']) ?>" style="font-size:20px"></i></td>
                        <td><?= e($r['name']) ?></td>
                        <td class="text-end">
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'name' => $r['name'], 'icon' => $r['icon']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this amenity? This cannot be undone.">
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
                    <tr><td colspan="4"><div class="empty-state"><i class="bi bi-star"></i><p>No amenities yet</p></div></td></tr>
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
                <div class="modal-header"><h5 class="modal-title">Add Amenity</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bootstrap Icon Class</label>
                        <input type="text" name="icon" class="form-control" placeholder="bi-lightning-charge">
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

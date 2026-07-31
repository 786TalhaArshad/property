<?php
require_once '../includes/auth.php';
require_login();
require_permission('master.view');
$title = 'Areas';
$active = 'master';
$canEdit = has_permission('settings.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $city_id = (int)($_POST['city_id'] ?? 0);
        if ($name === '' || !$city_id) {
            flash('danger', 'Area name and city are required.');
        } else {
            $dup = db_get("SELECT id FROM areas WHERE name = ? AND city_id = ? AND id <> ?", [$name, $city_id, $id]);
            if ($dup) {
                flash('danger', 'This area already exists in the selected city.');
            } elseif ($id > 0) {
                db_exec("UPDATE areas SET name=?, city_id=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$name, $city_id, $id]);
                flash('success', 'Area updated successfully.');
            } else {
                db_exec("INSERT INTO areas (name, city_id, created_date, created_time, updated_date, updated_time) VALUES (?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$name, $city_id]);
                flash('success', 'Area added successfully.');
            }
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM areas WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Area deleted successfully.');
    }
    redirect('areas.php');
}

$records = db_all("SELECT a.*, ci.name AS city_name FROM areas a JOIN cities ci ON ci.id = a.city_id ORDER BY a.name");
$cities = db_all("SELECT * FROM cities ORDER BY name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search areas...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Area"><i class="bi bi-plus-lg me-1"></i>Add Area</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:60px">#</th><th>Name</th><th>City</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= e($r['name']) ?></td>
                        <td><?= e($r['city_name']) ?></td>
                        <td class="text-end">
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'name' => $r['name'], 'city_id' => $r['city_id']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this area? This cannot be undone.">
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
                    <tr><td colspan="4"><div class="empty-state"><i class="bi bi-geo-alt"></i><p>No areas yet</p></div></td></tr>
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
                <div class="modal-header"><h5 class="modal-title">Add Area</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="mb-3">
                        <label class="form-label">City</label>
                        <select name="city_id" class="form-select" required>
                            <option value="">Select city</option>
                            <?php foreach ($cities as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Area Name</label>
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

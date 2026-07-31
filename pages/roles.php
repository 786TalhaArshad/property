<?php
require_once '../includes/auth.php';
require_login();
require_permission('settings.manage');
$title = 'Roles & Permissions';
$active = 'roles';
$canEdit = has_permission('settings.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($name === '') {
            flash('danger', 'Role name is required.');
        } else {
            $dup = db_get("SELECT id FROM roles WHERE name = ? AND id <> ?", [$name, $id]);
            if ($dup) {
                flash('danger', 'A role with this name already exists.');
            } elseif ($id > 0) {
                db_exec("UPDATE roles SET name=?, description=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$name, $description, $id]);
                flash('success', 'Role updated successfully.');
            } else {
                db_exec("INSERT INTO roles (name, description, is_super_admin, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,0,1,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$name, $description]);
                flash('success', 'Role added successfully.');
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $r = db_get("SELECT is_super_admin FROM roles WHERE id = ?", [$id]);
        if ($r && $r['is_super_admin']) {
            flash('danger', 'The Super Admin role cannot be deleted.');
        } elseif ($id === (int)$user['role_id']) {
            flash('danger', 'You cannot delete your own role.');
        } else {
            db_exec("DELETE FROM roles WHERE id=?", [$id]);
            flash('success', 'Role deleted successfully.');
        }
    }
    redirect('roles.php');
}

$records = db_all("SELECT r.*, (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) AS user_count FROM roles r ORDER BY r.id");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search roles...">
    </div>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Role"><i class="bi bi-plus-lg me-1"></i>Add Role</button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Role</th><th>Description</th><th>Users</th><th>Access</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-medium"><?= e($r['name']) ?></td>
                        <td><?= e($r['description']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= $r['user_count'] ?></span></td>
                        <td><?= $r['is_super_admin'] ? '<span class="badge bg-danger">Full Access</span>' : '<a class="btn btn-sm btn-outline-secondary" href="role_permissions.php?id=' . $r['id'] . '">Configure</a>' ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'name' => $r['name'], 'description' => $r['description']])) ?>'><i class="bi bi-pencil"></i></button>
                            <?php if (!$r['is_super_admin'] && (int)$r['id'] !== (int)$user['role_id']): ?>
                            <form method="post" class="d-inline" data-confirm="Delete this role? Users with this role will lose access.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="recordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header"><h5 class="modal-title">Add Role</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="mb-3">
                        <label class="form-label">Role Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" class="form-control">
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

<?php include '../includes/footer.php'; ?>

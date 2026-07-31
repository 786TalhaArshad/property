<?php
require_once '../includes/auth.php';
require_login();
require_permission('settings.manage');
$title = 'Role Permissions';
$active = 'roles';

$role_id = (int)($_GET['id'] ?? 0);
$role = db_get("SELECT * FROM roles WHERE id = ?", [$role_id]);
if (!$role) {
    flash('danger', 'Role not found.');
    redirect('roles.php');
}
if ($role['is_super_admin']) {
    flash('warning', 'Super Admin has full access by default.');
    redirect('roles.php');
}

if (is_post()) {
    csrf_check();
    $perms = $_POST['permissions'] ?? [];
    db_exec("DELETE FROM role_permissions WHERE role_id = ?", [$role_id]);
    foreach ($perms as $pid) {
        db_exec("INSERT INTO role_permissions (role_id, permission_id, created_date, created_time, updated_date, updated_time) VALUES (?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$role_id, (int)$pid]);
    }
    db_exec("UPDATE roles SET updated_date = CURDATE(), updated_time = CURTIME() WHERE id = ?", [$role_id]);
    flash('success', 'Permissions updated successfully.');
    redirect('role_permissions.php?id=' . $role_id);
}

$permissions = db_all("SELECT * FROM permissions ORDER BY module, name");
$granted = db_all("SELECT permission_id FROM role_permissions WHERE role_id = ?", [$role_id]);
$grantedMap = [];
foreach ($granted as $g) {
    $grantedMap[$g['permission_id']] = true;
}

$grouped = [];
foreach ($permissions as $p) {
    $grouped[$p['module']][] = $p;
}

include '../includes/header.php';
?>
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="roles.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Roles</a>
    <h5 class="mb-0">Permissions for <span class="badge bg-primary"><?= e($role['name']) ?></span></h5>
</div>

<form method="post">
    <?= csrf_field() ?>
    <div class="row g-3">
        <?php foreach ($grouped as $module => $items): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center">
                    <span><?= e($module) ?></span>
                    <button type="button" class="btn btn-sm btn-outline-primary ms-auto check-all">Select All</button>
                </div>
                <div class="card-body py-2">
                    <?php foreach ($items as $p): ?>
                    <div class="form-check my-2">
                        <input class="form-check-input perm-cb" type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" id="perm_<?= $p['id'] ?>" <?= isset($grantedMap[$p['id']]) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="perm_<?= $p['id'] ?>"><?= e($p['name']) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="mt-4">
        <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Permissions</button>
    </div>
</form>

<script>
$(function () {
    $('.check-all').on('click', function () {
        var card = $(this).closest('.card');
        var allChecked = card.find('.perm-cb:checked').length === card.find('.perm-cb').length;
        card.find('.perm-cb').prop('checked', !allChecked);
    });
});
</script>

<?php include '../includes/footer.php'; ?>

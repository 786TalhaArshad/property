<?php
require_once '../includes/auth.php';
require_login();
require_permission('settings.manage');
$title = 'Users';
$active = 'users';
$canEdit = has_permission('settings.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $role_id = (int)($_POST['role_id'] ?? 0);
        $branch_id = (int)($_POST['branch_id'] ?? 0) ?: null;
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $status = (int)($_POST['status'] ?? 1);

        if ($username === '' || $full_name === '' || !$role_id) {
            flash('danger', 'Username, full name and role are required.');
        } else {
            $dup = db_get("SELECT id FROM users WHERE username = ? AND id <> ?", [$username, $id]);
            if ($dup) {
                flash('danger', 'This username is already taken.');
            } elseif ($id > 0) {
                if ($password !== '') {
                    db_exec("UPDATE users SET username=?, password=?, full_name=?, role_id=?, branch_id=?, email=?, phone=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$username, $password, $full_name, $role_id, $branch_id, $email, $phone, $status, $id]);
                } else {
                    db_exec("UPDATE users SET username=?, full_name=?, role_id=?, branch_id=?, email=?, phone=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$username, $full_name, $role_id, $branch_id, $email, $phone, $status, $id]);
                }
                flash('success', 'User updated successfully.');
            } else {
                if ($password === '') {
                    flash('danger', 'Password is required for new users.');
                } else {
                    $photo = upload_file('photo', 'uploads/users');
                    db_exec("INSERT INTO users (role_id, branch_id, username, password, full_name, email, phone, photo, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$role_id, $branch_id, $username, $password, $full_name, $email, $phone, $photo ?: null, $status]);
                    flash('success', 'User added successfully.');
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$user['id']) {
            flash('danger', 'You cannot delete your own account.');
        } else {
            db_exec("DELETE FROM users WHERE id=?", [$id]);
            flash('success', 'User deleted successfully.');
        }
    }
    redirect('users.php');
}

$records = db_all("SELECT u.*, r.name AS role_name, b.name AS branch_name FROM users u JOIN roles r ON r.id = u.role_id LEFT JOIN branches b ON b.id = u.branch_id ORDER BY u.full_name");
$roles = db_all("SELECT * FROM roles ORDER BY name");
$branches = db_all("SELECT * FROM branches ORDER BY name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search users...">
    </div>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add User"><i class="bi bi-plus-lg me-1"></i>Add User</button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>User</th><th>Username</th><th>Role</th><th>Branch</th><th>Phone</th><th>Last Login</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($r['photo']): ?>
                                    <img src="<?= BASE_URL ?>/assets/<?= e($r['photo']) ?>" class="img-thumb">
                                <?php else: ?>
                                    <span class="avatar-sm"><?= e(strtoupper(substr($r['full_name'], 0, 1))) ?></span>
                                <?php endif; ?>
                                <div>
                                    <div class="fw-medium"><?= e($r['full_name']) ?><?= (int)$r['id'] === (int)$user['id'] ? ' <span class="badge bg-info">You</span>' : '' ?></div>
                                    <div class="small text-muted"><?= e($r['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?= e($r['username']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= e($r['role_name']) ?></span></td>
                        <td><?= e($r['branch_name'] ?? '-') ?></td>
                        <td><?= e($r['phone']) ?></td>
                        <td class="small text-muted"><?= $r['last_login'] ? date('d-M-Y H:i', strtotime($r['last_login'])) : '-' ?></td>
                        <td><?= $r['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'username' => $r['username'], 'full_name' => $r['full_name'], 'role_id' => $r['role_id'], 'branch_id' => $r['branch_id'], 'email' => $r['email'], 'phone' => $r['phone'], 'status' => $r['status']])) ?>'><i class="bi bi-pencil"></i></button>
                            <?php if ((int)$r['id'] !== (int)$user['id']): ?>
                            <form method="post" class="d-inline" data-confirm="Delete this user? This cannot be undone.">
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
                    <tr><td colspan="9"><div class="empty-state"><i class="bi bi-person-lock"></i><p>No users yet</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="recordModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header"><h5 class="modal-title">Add User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-muted small">(leave blank to keep)</span></label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select name="role_id" class="form-select" required>
                                <option value="">Select role</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select">
                                <option value="">Select branch</option>
                                <?php foreach ($branches as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Photo (new users only)</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                        </div>
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

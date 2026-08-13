<?php
require_once '../includes/auth.php';
require_login();
$title = 'Change Password';
$active = 'profile';

if (is_post()) {
    csrf_check();
    $old = (string)($_POST['old_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if (!hash_equals((string)$user['password'], $old)) {
        flash('danger', 'Current password is incorrect.');
    } elseif (strlen($new) < 6) {
        flash('danger', 'New password must be at least 6 characters.');
    } elseif ($new !== $confirm) {
        flash('danger', 'New password and confirmation do not match.');
    } else {
        db_exec("UPDATE users SET password=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$new, $user['id']]);
        flash('success', 'Password changed successfully.');
    }
    redirect('change_password.php');
}

include '../includes/header.php';
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="profile.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0">Change Password</h5>
</div>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-key me-2"></i>Change Password</div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="old_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>

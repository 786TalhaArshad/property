<?php
require_once '../includes/auth.php';
require_login();
$title = 'My Profile';
$active = 'profile';

if (is_post()) {
    csrf_check();
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if ($fullName === '') {
        flash('danger', 'Full name is required.');
    } else {
        $photo = $user['photo'];
        $up = upload_file('photo', 'uploads/users');
        if ($up === false) {
            flash('danger', 'Photo upload failed. Allowed: jpg, png, webp.');
        } else {
            if ($up !== null) $photo = $up;
            db_exec("UPDATE users SET full_name=?, email=?, phone=?, photo=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$fullName, $email, $phone, $photo, $user['id']]);
            flash('success', 'Profile updated successfully.');
        }
    }
    redirect('profile.php');
}

include '../includes/header.php';
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0">My Profile</h5>
</div>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <?php if ($user['photo']): ?>
                    <img src="<?= BASE_URL ?>/assets/<?= e($user['photo']) ?>" class="rounded-circle mb-3" style="width:110px;height:110px;object-fit:cover">
                <?php else: ?>
                    <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:110px;height:110px;background:#e6ecf8;color:#2d6cdf;font-size:42px;font-weight:600">
                        <?= e(strtoupper(substr($user['full_name'] ?? 'U', 0, 1))) ?>
                    </div>
                <?php endif; ?>
                <h5 class="mb-0"><?= e($user['full_name']) ?></h5>
                <span class="badge bg-primary mt-2"><?= e($user['role_name']) ?></span>
                <p class="text-muted small mt-3 mb-0"><i class="bi bi-person"></i> <?= e($user['username']) ?></p>
                <p class="text-muted small mb-0"><i class="bi bi-clock"></i> Last login: <?= $user['last_login'] ? date('d-M-Y H:i', strtotime($user['last_login'])) : 'Never' ?></p>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-pencil-square me-2"></i>Update Profile</div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?= e($user['username']) ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($user['phone']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Photo</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <button class="btn btn-primary mt-4"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_login();
require_permission('settings.manage');
$title = 'Company Settings';
$active = 'settings';

if (is_post()) {
    csrf_check();
    $fields = [
        'company_name', 'company_tagline', 'company_address',
        'company_phone', 'company_email', 'currency', 'session_timeout'
    ];
    foreach ($fields as $f) {
        $val = trim($_POST[$f] ?? '');
        $exists = db_get("SELECT id FROM settings WHERE setting_key = ?", [$f]);
        if ($exists) {
            db_exec("UPDATE settings SET setting_value = ?, updated_date = CURDATE(), updated_time = CURTIME() WHERE setting_key = ?", [$val, $f]);
        } else {
            db_exec("INSERT INTO settings (setting_key, setting_value, created_date, created_time, updated_date, updated_time) VALUES (?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$f, $val]);
        }
    }

    $logo = upload_file('company_logo', 'uploads');
    if ($logo === false) {
        flash('danger', 'Logo upload failed. Allowed: jpg, png, webp.');
    } else {
        if ($logo !== null) {
            $exists = db_get("SELECT id FROM settings WHERE setting_key = 'company_logo'");
            if ($exists) {
                db_exec("UPDATE settings SET setting_value = ?, updated_date = CURDATE(), updated_time = CURTIME() WHERE setting_key = 'company_logo'", [$logo]);
            } else {
                db_exec("INSERT INTO settings (setting_key, setting_value, created_date, created_time, updated_date, updated_time) VALUES ('company_logo',?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$logo]);
            }
        }
        flash('success', 'Settings saved successfully.');
    }
    redirect('settings.php');
}

include '../includes/header.php';
?>
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-building-gear me-2"></i>Company Settings</div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" class="form-control" value="<?= e(setting('company_name')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tagline</label>
                            <input type="text" name="company_tagline" class="form-control" value="<?= e(setting('company_tagline')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <input type="text" name="company_address" class="form-control" value="<?= e(setting('company_address')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="company_phone" class="form-control" value="<?= e(setting('company_phone')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="company_email" class="form-control" value="<?= e(setting('company_email')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Currency Symbol</label>
                            <input type="text" name="currency" class="form-control" value="<?= e(setting('currency', 'Rs.')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Session Timeout (minutes)</label>
                            <input type="number" name="session_timeout" class="form-control" value="<?= e(setting('session_timeout', '60')) ?>" min="5">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Company Logo</label>
                            <?php if (setting('company_logo')): ?>
                                <div class="mb-2">
                                    <img src="<?= BASE_URL ?>/assets/<?= e(setting('company_logo')) ?>" style="max-height:60px" alt="logo">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="company_logo" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <button class="btn btn-primary mt-4"><i class="bi bi-check-lg me-1"></i>Save Settings</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>System Info</div>
            <div class="card-body">
                <ul class="list-unstyled small mb-0">
                    <li class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">App</span><strong><?= e(APP_NAME) ?></strong></li>
                    <li class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">PHP</span><strong><?= e(PHP_VERSION) ?></strong></li>
                    <li class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">Database</span><strong><?= e(DB_NAME) ?></strong></li>
                    <li class="d-flex justify-content-between py-1"><span class="text-muted">Base URL</span><strong class="text-truncate" style="max-width:150px"><?= e(BASE_URL) ?></strong></li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect(BASE_URL . '/index.php');
}

$error = '';
if (is_post()) {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $remember = !empty($_POST['remember']);

    $row = db_get("SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id
                   WHERE u.username = ? AND u.status = 1", [$username]);

    if ($row && hash_equals((string)$row['password'], $password)) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$row['id'];
        $_SESSION['last_active'] = time();
        db_exec("UPDATE users SET last_login = NOW(), updated_date = CURDATE(), updated_time = CURTIME() WHERE id = ?", [$row['id']]);

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            db_exec("UPDATE users SET remember_token = ? WHERE id = ?", [$token, $row['id']]);
            setcookie('remember_me', $token, time() + 60 * 60 * 24 * 30, '/');
        }

        flash('success', 'Welcome back, ' . $row['full_name'] . '!');
        redirect(BASE_URL . '/index.php');
    }

    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login | <?= e(setting('company_name', APP_NAME)) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="login-wrapper">
    <div class="card login-card">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="mb-3"><i class="bi bi-building" style="font-size:46px;color:#2d6cdf"></i></div>
                <h4 class="fw-bold mb-1"><?= e(setting('company_name', APP_NAME)) ?></h4>
                <p class="text-muted mb-0"><?= e(setting('company_tagline', 'Real Estate ERP')) ?></p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle me-1"></i><?= e($error) ?></div>
            <?php endif; ?>

            <?= get_flash() ?>

            <form method="post" action="">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="Enter username" required autofocus>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                    </div>
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label" for="remember">Remember Me</label>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2"><i class="bi bi-box-arrow-in-right me-1"></i> Sign In</button>
            </form>

            <div class="text-center mt-4 text-muted small">
                Default login: <strong>admin</strong> / <strong>admin123</strong>
            </div>
            <div class="text-center mt-2">
                <a href="<?= BASE_URL ?>/tour_guide.php" class="small"><i class="bi bi-compass me-1"></i>Learn how to use this software</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>

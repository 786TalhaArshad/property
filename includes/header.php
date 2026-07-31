<?php
if (!isset($title)) {
    $title = 'Dashboard';
}
$company = setting('company_name', APP_NAME);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | <?= e($company) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<aside class="sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <a class="brand-link" href="<?= BASE_URL ?>/index.php">
            <i class="bi bi-building"></i>
            <span><?= e($company) ?></span>
        </a>
    </div>
    <?php include __DIR__ . '/sidebar.php'; ?>
</aside>

<div class="main-area">

    <header class="topbar">
        <div class="d-flex align-items-center gap-3 px-3 py-2">
            <button class="btn btn-outline-secondary d-lg-none" type="button" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="bi bi-list"></i>
            </button>
            <h5 class="mb-0 fw-semibold d-none d-sm-block"><?= e($title) ?></h5>
            <div class="ms-auto d-flex align-items-center gap-3">
                <a href="<?= BASE_URL ?>/pages/notifications.php" class="btn btn-light btn-sm position-relative" title="Notifications">
                    <i class="bi bi-bell"></i>
                </a>
                <div class="dropdown user-drop">
                    <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= e($user['full_name'] ?? 'User') ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted small"><?= e($user['role_name'] ?? '') ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/pages/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/pages/change_password.php"><i class="bi bi-key me-2"></i>Change Password</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <div class="page-content">
        <?= get_flash() ?>

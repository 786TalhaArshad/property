<?php
require_once __DIR__ . '/functions.php';

$user = null;

if (!is_logged_in() && isset($_COOKIE['remember_me'])) {
    $ru = db_get("SELECT id FROM users WHERE remember_token = ? AND status = 1", [$_COOKIE['remember_me']]);
    if ($ru) {
        $_SESSION['user_id'] = (int)$ru['id'];
    }
}

if (is_logged_in()) {
    $timeout = (int)setting('session_timeout', 60) * 60;
    if (isset($_SESSION['last_active']) && (time() - $_SESSION['last_active']) > $timeout) {
        unset($_SESSION['user_id'], $_SESSION['last_active']);
        redirect(BASE_URL . '/login.php');
    }
    $_SESSION['last_active'] = time();

    $user = db_get("SELECT u.*, r.name AS role_name, r.is_super_admin
                    FROM users u
                    JOIN roles r ON r.id = u.role_id
                    WHERE u.id = ?", [$_SESSION['user_id']]);
    if ($user) {
        $user['perms'] = [];
        $rows = db_all("SELECT p.slug FROM role_permissions rp
                        JOIN permissions p ON p.id = rp.permission_id
                        WHERE rp.role_id = ?", [$user['role_id']]);
        foreach ($rows as $r) {
            $user['perms'][] = $r['slug'];
        }
    }
}

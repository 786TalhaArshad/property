<?php
require_once '../includes/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$name = null;
if ($id > 0) {
    $name = db_get("SELECT name FROM projects WHERE id = ? AND status = 1", [$id])['name'] ?? null;
}
if ($id > 0 && !$name) {
    flash('danger', 'Invalid project selected.');
} else {
    set_active_project($id);
    flash('success', 'Active project: ' . ($name ? e($name) : 'All Projects'));
}

$back = $_GET['back'] ?? '';
if ($back === '' || preg_match('#^(https?:)?//#i', $back) || ($back[0] !== '/' && strpos($back, BASE_URL) !== 0)) {
    redirect(BASE_URL . '/index.php');
}
redirect($back);

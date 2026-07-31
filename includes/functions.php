<?php
require_once __DIR__ . '/database.php';

function db_prepare($sql, $params = []) {
    global $mysqli;
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        die('DB Error: ' . htmlspecialchars($mysqli->error) . ' in SQL: ' . htmlspecialchars($sql));
    }
    if ($params) {
        $types = '';
        foreach ($params as $p) {
            $types .= is_int($p) ? 'i' : (is_double($p) ? 'd' : 's');
        }
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

function db_all($sql, $params = []) {
    $stmt = db_prepare($sql, $params);
    $res = $stmt->get_result();
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function db_get($sql, $params = []) {
    $stmt = db_prepare($sql, $params);
    $res = $stmt->get_result();
    return $res ? $res->fetch_assoc() : null;
}

function db_exec($sql, $params = []) {
    global $mysqli;
    $stmt = db_prepare($sql, $params);
    $lastId = $mysqli->insert_id;
    $stmt->close();
    return $lastId;
}

function db_insert_id() {
    global $mysqli;
    return $mysqli->insert_id;
}

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function flash($type, $msg) {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function get_flash() {
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $out = '';
    foreach ($_SESSION['flash'] as $f) {
        $out .= '<div class="alert alert-' . e($f['type']) . ' alert-dismissible fade show" role="alert">'
              . e($f['msg'])
              . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_user() {
    global $user;
    return $user;
}

function has_permission($slug) {
    global $user;
    if (!isset($user)) {
        return false;
    }
    if (!empty($user['is_super_admin'])) {
        return true;
    }
    return in_array($slug, $user['perms'], true);
}

function require_login() {
    if (!is_logged_in()) {
        redirect(BASE_URL . '/login.php');
    }
}

function require_permission($slug) {
    if (!has_permission($slug)) {
        flash('danger', 'You do not have permission to access this page.');
        redirect(BASE_URL . '/index.php');
    }
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_check() {
    if (($_POST['csrf_token'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
        flash('danger', 'Invalid security token. Please try again.');
        redirect(BASE_URL . '/index.php');
    }
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function is_post() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function std_stamps($insert = true) {
    if ($insert) {
        return "CURDATE(), CURTIME()";
    }
    return "updated_date = CURDATE(), updated_time = CURTIME()";
}

function fmt_money($n) {
    return number_format((float)$n, 2);
}

function fmt_num($n) {
    return number_format((float)$n, 0);
}

function fmt_date($d) {
    return $d && $d !== '0000-00-00' ? date('d-M-Y', strtotime($d)) : '-';
}

function upload_file($field, $dir, $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx']) {
    if (empty($_FILES[$field]['name'])) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return false;
    }
    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = APP_ROOT . '/assets/' . $dir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return false;
    }
    return $dir . '/' . $name;
}

function next_number($prefix, $table, $column = 'id') {
    $row = db_get("SELECT MAX(CAST(SUBSTRING($column, LENGTH(?) + 1) AS UNSIGNED)) AS max_no FROM $table WHERE $column LIKE CONCAT(?, '%')", [$prefix, $prefix]);
    $n = (int)($row['max_no'] ?? 0) + 1;
    return $prefix . '-' . str_pad($n, 4, '0', STR_PAD_LEFT);
}

function active_menu($key) {
    global $active;
    return isset($active) && $active === $key ? 'active' : '';
}

function status_badge($status) {
    $map = [
        'available' => 'success', 'booked' => 'info', 'reserved' => 'warning',
        'sold' => 'secondary', 'transferred' => 'dark', 'rental' => 'primary',
        'occupied' => 'danger', 'vacant' => 'light',
        'pending' => 'warning', 'partial' => 'info', 'paid' => 'success',
        'overdue' => 'danger', 'waived' => 'secondary',
        'active' => 'success', 'expired' => 'secondary', 'terminated' => 'danger',
        'vacated' => 'dark', 'renewed' => 'info', 'new' => 'primary', 'contacted' => 'info',
        'qualified' => 'success', 'proposal' => 'warning', 'follow_up' => 'secondary',
        'converted' => 'success', 'lost' => 'danger', 'open' => 'warning', 'in_progress' => 'info',
        'completed' => 'success', 'cancelled' => 'danger', 'draft' => 'secondary',
        'sent' => 'info', 'accepted' => 'success', 'rejected' => 'danger',
        'signed' => 'success', 'registered' => 'primary', 'booking' => 'info',
        'cleared' => 'success', 'scheduled' => 'primary',
    ];
    $cls = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $cls . '">' . h(ucfirst(str_replace('_', ' ', $status))) . '</span>';
}

function paginate_link($page, $extra = '') {
    $url = $_SERVER['PHP_SELF'];
    $sep = strpos($url, '?') !== false ? '&' : '?';
    return $url . $sep . 'page=' . $page . ($extra ? '&' . $extra : '');
}

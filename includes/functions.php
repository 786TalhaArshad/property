<?php
require_once __DIR__ . '/database.php';

function db_prepare($sql, $params = []) {
    global $mysqli;
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        error_log('DB prepare error: ' . $mysqli->error . ' | SQL: ' . $sql);
        die('A database error occurred. Please try again later.');
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

function db_affected_rows() {
    global $mysqli;
    return $mysqli->affected_rows;
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

function active_project_id() {
    return (int)($_SESSION['active_project_id'] ?? 0);
}

function active_project() {
    $id = active_project_id();
    return $id ? db_get("SELECT * FROM projects WHERE id = ? AND status = 1", [$id]) : null;
}

function set_active_project($id) {
    $id = (int)$id;
    if ($id > 0) {
        $p = db_get("SELECT id FROM projects WHERE id = ? AND status = 1", [$id]);
        if (!$p) {
            return false;
        }
    }
    $_SESSION['active_project_id'] = $id;
    return true;
}

function active_project_field($record_project_id, $edit_id) {
    return $edit_id > 0 ? (int)$record_project_id : active_project_id();
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
    if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0 && strpos($url, '/') !== 0) {
        $url = BASE_URL . '/' . ltrim($url, '/');
    }
    if (headers_sent()) {
        echo '<script>window.location.href=' . json_encode($url) . ';</script>';
        exit;
    }
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
    return $d && $d !== '0000-00-00' ? date('d/m/y', strtotime($d)) : '-';
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
    $row = db_get("SELECT MAX(CAST(SUBSTRING($column, LENGTH(?) + 2) AS UNSIGNED)) AS max_no FROM $table WHERE $column LIKE CONCAT(?, '%')", [$prefix, $prefix]);
    $n = (int)($row['max_no'] ?? 0) + 1;
    return $prefix . '-' . str_pad($n, 4, '0', STR_PAD_LEFT);
}

function coa_id_by_code($code) {
    $a = db_get("SELECT id FROM chart_of_accounts WHERE code = ?", [$code]);
    return $a ? (int)$a['id'] : 0;
}

function cash_bank_account_id($bankId = 0) {
    if ($bankId > 0) {
        $code = '1001-' . str_pad((string)(int)$bankId, 3, '0', STR_PAD_LEFT);
        $acc = db_get("SELECT id FROM chart_of_accounts WHERE code = ?", [$code]);
        if ($acc) return (int)$acc['id'];
        $parentId = coa_id_by_code('1001');
        if (!$parentId) {
            $parentId = db_exec("INSERT INTO chart_of_accounts (code, name, account_type, parent_id, created_date, created_time, updated_date, updated_time) VALUES ('1001','Bank Accounts','asset',0,CURDATE(),CURTIME(),CURDATE(),CURTIME())");
        }
        return (int)db_exec("INSERT INTO chart_of_accounts (code, name, account_type, parent_id, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$code, 'Bank ' . (int)$bankId, 'asset', $parentId]);
    }
    return coa_id_by_code('1000');
}

function employee_payable_account_id($employee_id, $employee_name) {
    $parent = db_get("SELECT id FROM chart_of_accounts WHERE code = '2050'");
    if (!$parent) {
        $parentId = db_exec("INSERT INTO chart_of_accounts (code, name, account_type, parent_id, opening_balance, created_date, created_time, updated_date, updated_time) VALUES ('2050','Employee Payable','liability',NULL,0,CURDATE(),CURTIME(),CURDATE(),CURTIME())");
    } else {
        $parentId = (int)$parent['id'];
    }
    $code = '2050-' . str_pad((int)$employee_id, 3, '0', STR_PAD_LEFT);
    $acc = db_get("SELECT id FROM chart_of_accounts WHERE code = ?", [$code]);
    if ($acc) return (int)$acc['id'];
    return (int)db_exec("INSERT INTO chart_of_accounts (code, name, account_type, parent_id, opening_balance, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,0,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
        [$code, $employee_name, 'liability', $parentId]);
}

function contractor_payable_account_id($contractor_id, $contractor_name) {
    $parent = db_get("SELECT id FROM chart_of_accounts WHERE code = '2060'");
    if (!$parent) {
        $parentId = db_exec("INSERT INTO chart_of_accounts (code, name, account_type, parent_id, opening_balance, created_date, created_time, updated_date, updated_time) VALUES ('2060','Contractor Payable','liability',NULL,0,CURDATE(),CURTIME(),CURDATE(),CURTIME())");
    } else {
        $parentId = (int)$parent['id'];
    }
    $code = '2060-' . str_pad((int)$contractor_id, 3, '0', STR_PAD_LEFT);
    $acc = db_get("SELECT id FROM chart_of_accounts WHERE code = ?", [$code]);
    if ($acc) return (int)$acc['id'];
    return (int)db_exec("INSERT INTO chart_of_accounts (code, name, account_type, parent_id, opening_balance, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,0,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
        [$code, $contractor_name, 'liability', $parentId]);
}

function post_cash_voucher($date, $voucherType, $narration, $projectId, $debitAccId, $creditAccId, $amount, $debitDesc = '', $creditDesc = '') {
    $prefix = ['cash_payment' => 'CP', 'cash_receipt' => 'CR', 'bank_payment' => 'BP', 'bank_receipt' => 'BR', 'journal' => 'JV'][$voucherType] ?? 'JV';
    $voucher_no = next_number($prefix, 'vouchers', 'voucher_no');
    $vid = db_exec("INSERT INTO vouchers (voucher_no, voucher_date, voucher_type, project_id, narration, status, created_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
        [$voucher_no, $date, $voucherType, $projectId, $narration, 'posted', $GLOBALS['user']['id']]);
    db_exec("INSERT INTO voucher_items (voucher_id, account_id, item_description, debit, credit, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,0,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
        [$vid, $debitAccId, $debitDesc, $amount]);
    db_exec("INSERT INTO voucher_items (voucher_id, account_id, item_description, debit, credit, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,0,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())",
        [$vid, $creditAccId, $creditDesc, $amount]);
    return $vid;
}

function active_menu($key) {
    global $active;
    return isset($active) && $active === $key ? 'active' : '';
}

function submenu_state($keys) {
    global $active;
    return isset($active) && in_array($active, (array) $keys) ? 'active' : '';
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

function stock_adjust($productId, $type, $qty, $unitCost, $refType = null, $refId = null, $projectId = null, $contractorId = null) {
    $product = db_get("SELECT stock_qty, avg_cost FROM products WHERE id = ?", [$productId]);
    if (!$product) return;
    $oldStock = (float)$product['stock_qty'];
    $oldAvg = (float)$product['avg_cost'];
    if ($type === 'purchase') {
        $totalCost = round($qty * $unitCost, 2);
        $newTotal = ($oldStock * $oldAvg) + $totalCost;
        $newStock = $oldStock + $qty;
        $newAvg = $newStock > 0 ? round($newTotal / $newStock, 2) : 0;
        db_exec("UPDATE products SET stock_qty = ?, avg_cost = ? WHERE id = ?", [$newStock, $newAvg, $productId]);
    } elseif ($type === 'issue') {
        $newStock = $oldStock - $qty;
        if ($newStock < 0) $newStock = 0;
        db_exec("UPDATE products SET stock_qty = ? WHERE id = ?", [$newStock, $productId]);
    }
    db_exec("INSERT INTO stock_movements (product_id, movement_type, quantity, unit_cost, total_cost, reference_type, reference_id, project_id, contractor_id, created_date, created_time) VALUES (?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME())",
        [$productId, $type, $type === 'issue' ? -$qty : $qty, $unitCost, round(abs($qty) * $unitCost, 2), $refType, $refId, $projectId, $contractorId]);
}

function paginate_link($page, $extra = '') {
    $url = $_SERVER['PHP_SELF'];
    $sep = strpos($url, '?') !== false ? '&' : '?';
    return $url . $sep . 'page=' . $page . ($extra ? '&' . $extra : '');
}

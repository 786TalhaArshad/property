<?php
require_once '../includes/auth.php';
require_login();

$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$rows = [];

if ($action === 'blocks') {
    $rows = db_all("SELECT id, name FROM blocks WHERE project_id = ? ORDER BY name", [$id]);
} elseif ($action === 'roads') {
    $rows = db_all("SELECT id, name FROM roads WHERE project_id = ? ORDER BY name", [$id]);
} elseif ($action === 'streets') {
    $rows = db_all("SELECT id, name FROM streets WHERE project_id = ? ORDER BY name", [$id]);
} elseif ($action === 'areas') {
    $rows = db_all("SELECT id, name FROM areas WHERE city_id = ? ORDER BY name", [$id]);
} elseif ($action === 'societies') {
    $rows = db_all("SELECT id, name FROM societies WHERE city_id = ? ORDER BY name", [$id]);
} elseif ($action === 'installments') {
    $rows = db_all("SELECT i.id, CONCAT('No. ', i.installment_no, ' - ', i.due_date, ' (', FORMAT(i.amount - i.paid_amount, 0), ' due)') AS name
                    FROM installments i WHERE i.booking_id = ? AND i.status IN ('pending','partial') ORDER BY i.installment_no", [$id]);
} elseif ($action === 'bookings') {
    $rows = db_all("SELECT b.id, CONCAT(b.booking_no, ' - ', p.property_no) AS name FROM bookings b
                    JOIN properties p ON p.id = b.property_id
                    WHERE b.customer_id = ? AND b.status <> 'cancelled' ORDER BY b.id DESC", [$id]);
} elseif ($action === 'booking_info') {
    $rows = db_all("SELECT b.id, b.booking_no, b.status, COALESCE((SELECT SUM(r.amount) FROM receipts r WHERE r.booking_id = b.id),0) AS paid
                    FROM bookings b WHERE b.id = ?", [$id]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!has_permission('settings.manage')) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Permission denied.']);
        exit;
    }
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $city_id = (int)($_POST['city_id'] ?? 0);
    if ($action === 'add_society' || $action === 'add_area') {
        $table = $action === 'add_society' ? 'societies' : 'areas';
        if ($name === '' || !$city_id) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Name and city are required.']);
            exit;
        }
        $existing = db_get("SELECT id FROM $table WHERE name = ? AND city_id = ?", [$name, $city_id]);
        if ($existing) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'id' => (int)$existing['id'], 'name' => $name]);
            exit;
        }
        $id = db_exec("INSERT INTO $table (name, city_id, created_date, created_time, updated_date, updated_time) VALUES (?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$name, $city_id]);
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'id' => (int)$id, 'name' => $name]);
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Invalid action.']);
    exit;
}

header('Content-Type: application/json');
echo json_encode($rows);

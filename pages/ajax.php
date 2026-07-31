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
}

header('Content-Type: application/json');
echo json_encode($rows);

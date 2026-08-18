<?php
$_ajax_request = true;
require_once '../includes/auth.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

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
} elseif ($action === 'customers_by_project') {
    $rows = $id > 0
        ? db_all("SELECT DISTINCT c.id, c.full_name AS name FROM customers c JOIN bookings b ON b.customer_id = c.id JOIN properties p ON p.id = b.property_id WHERE p.project_id = ? AND b.status <> 'cancelled' ORDER BY c.full_name", [$id])
        : db_all("SELECT id, full_name AS name FROM customers ORDER BY full_name");
} elseif ($action === 'properties_by_customer') {
    $customer_id = (int)($_GET['customer_id'] ?? 0);
    $project_id = (int)($_GET['project_id'] ?? 0);
    $rows = db_all("SELECT p.id AS property_id, p.property_no AS name, b.id AS booking_id
                    FROM bookings b JOIN properties p ON p.id = b.property_id
                    WHERE b.customer_id = ? AND b.status <> 'cancelled' AND (? = 0 OR p.project_id = ?)
                    ORDER BY p.property_no", [$customer_id, $project_id, $project_id]);
} elseif ($action === 'rental_schedules') {
    $rows = db_all("SELECT s.id, CONCAT(s.period, ' - ', s.due_date, ' (', FORMAT((s.rent_amount + s.late_charges) - s.paid_amount, 0), ' due)') AS name
                    FROM rent_schedule s WHERE s.agreement_id = ? AND s.status IN ('pending','partial') ORDER BY s.due_date", [$id]);
} elseif ($action === 'employee_payable_account') {
    $emp = db_get("SELECT * FROM employees WHERE id = ?", [$id]);
    $rows = $emp ? ['id' => employee_payable_account_id($id, $emp['full_name']), 'name' => $emp['full_name']] : [];
} elseif ($action === 'party_account') {
    $type = $_GET['type'] ?? '';
    $accountId = 0;
    $accountName = '';
    if ($type === 'customer' && $id > 0) {
        $c = db_get("SELECT full_name FROM customers WHERE id = ?", [$id]);
        if ($c) { $accountId = coa_id_by_code('1100'); $accountName = $c['full_name'] . ' (AR)'; }
    } elseif ($type === 'vendor' && $id > 0) {
        $v = db_get("SELECT business_name FROM vendors WHERE id = ?", [$id]);
        if ($v) { $accountId = coa_id_by_code('2000'); $accountName = $v['business_name'] . ' (AP)'; }
    } elseif ($type === 'owner' && $id > 0) {
        $o = db_get("SELECT full_name FROM owners WHERE id = ?", [$id]);
        if ($o) { $accountId = coa_id_by_code('3000'); $accountName = $o['full_name'] . ' (Capital)'; }
    } elseif ($type === 'dealer' && $id > 0) {
        $d = db_get("SELECT full_name FROM dealers WHERE id = ?", [$id]);
        if ($d) { $accountId = coa_id_by_code('2000'); $accountName = $d['full_name'] . ' (AP)'; }
    } elseif ($type === 'employee' && $id > 0) {
        $emp = db_get("SELECT full_name FROM employees WHERE id = ?", [$id]);
        if ($emp) { $accountId = employee_payable_account_id($id, $emp['full_name']); $accountName = $emp['full_name'] . ' (Payable)'; }
    } elseif ($type === 'contractor' && $id > 0) {
        $con = db_get("SELECT full_name FROM contractors WHERE id = ?", [$id]);
        if ($con) { $accountId = contractor_payable_account_id($id, $con['full_name']); $accountName = $con['full_name'] . ' (Payable)'; }
    } elseif ($type === 'investor' && $id > 0) {
        $inv = db_get("SELECT full_name FROM investors WHERE id = ?", [$id]);
        if ($inv) { $accountId = coa_id_by_code('2070'); $accountName = $inv['full_name'] . ' (Payable)'; }
    } elseif ($type === 'tenant' && $id > 0) {
        $t = db_get("SELECT full_name FROM tenants WHERE id = ?", [$id]);
        if ($t) { $accountId = coa_id_by_code('4100'); $accountName = $t['full_name'] . ' (Rental Income)'; }
    }
    $rows = ['account_id' => $accountId, 'account_name' => $accountName];
} elseif ($action === 'vendor_balance') {
    $row = db_get("SELECT COALESCE(SUM(p.total_amount - p.paid_amount), 0) AS balance FROM purchases p WHERE p.vendor_id = ? AND p.status IN ('pending','partial')", [$id]);
    $rows = $row ? $row : ['balance' => 0];
} elseif ($action === 'booking_info') {
    $rows = db_all("SELECT b.id, b.booking_no, b.status, COALESCE((SELECT SUM(r.amount) FROM receipts r WHERE r.booking_id = b.id),0) AS paid
                    FROM bookings b WHERE b.id = ?", [$id]);
} elseif ($action === 'party_search') {
    $q = trim($_GET['q'] ?? '');
    $rows = [];
    if ($q !== '' && strlen($q) >= 2) {
        $like = '%' . $q . '%';
        $customers = db_all("SELECT id, full_name AS name, customer_no AS code, cnic, phone, 'customer' AS type FROM customers WHERE full_name LIKE ? OR cnic LIKE ? OR customer_no LIKE ? OR phone LIKE ? LIMIT 5", [$like, $like, $like, $like]);
        $vendors = db_all("SELECT id, business_name AS name, vendor_no AS code, cnic, phone, 'vendor' AS type FROM vendors WHERE business_name LIKE ? OR contact_person LIKE ? OR cnic LIKE ? OR vendor_no LIKE ? OR phone LIKE ? LIMIT 5", [$like, $like, $like, $like, $like]);
        $owners = db_all("SELECT id, full_name AS name, NULL AS code, cnic, phone, 'owner' AS type FROM owners WHERE full_name LIKE ? OR cnic LIKE ? OR phone LIKE ? LIMIT 5", [$like, $like, $like]);
        $dealers = db_all("SELECT id, full_name AS name, NULL AS code, cnic, phone, 'dealer' AS type FROM dealers WHERE full_name LIKE ? OR cnic LIKE ? OR phone LIKE ? LIMIT 5", [$like, $like, $like]);
        $employees = db_all("SELECT id, full_name AS name, employee_no AS code, cnic, phone, 'employee' AS type FROM employees WHERE full_name LIKE ? OR cnic LIKE ? OR employee_no LIKE ? OR phone LIKE ? LIMIT 5", [$like, $like, $like, $like]);
        $contractors = db_all("SELECT id, full_name AS name, contractor_no AS code, cnic, phone, 'contractor' AS type FROM contractors WHERE full_name LIKE ? OR cnic LIKE ? OR contractor_no LIKE ? OR phone LIKE ? LIMIT 5", [$like, $like, $like, $like]);
        $investors = db_all("SELECT id, full_name AS name, investor_no AS code, cnic, phone, 'investor' AS type FROM investors WHERE full_name LIKE ? OR cnic LIKE ? OR investor_no LIKE ? OR phone LIKE ? LIMIT 5", [$like, $like, $like, $like]);
        $tenants = db_all("SELECT id, full_name AS name, tenant_no AS code, cnic, NULL AS phone, 'tenant' AS type FROM tenants WHERE full_name LIKE ? OR cnic LIKE ? OR tenant_no LIKE ? LIMIT 5", [$like, $like, $like]);
        $rows = array_merge($customers, $vendors, $owners, $dealers, $employees, $contractors, $investors, $tenants);
        usort($rows, function ($a, $b) { return strcmp($a['name'], $b['name']); });
        $rows = array_slice($rows, 0, 15);
    }
} elseif ($action === 'party_balance') {
    $type = $_GET['type'] ?? '';
    $id = (int)($_GET['id'] ?? 0);
    $balance = 0;
    $name = '';
    $info = '';
    if ($type === 'customer' && $id > 0) {
        $row = db_get("SELECT full_name AS name FROM customers WHERE id = ?", [$id]);
        $name = $row['name'] ?? '';
        $debit = (float)(db_get("SELECT COALESCE(SUM(b.total_price - b.discount),0) AS total FROM bookings b WHERE b.customer_id = ? AND b.status <> 'cancelled'", [$id])['total'] ?? 0);
        $credit = (float)(db_get("SELECT COALESCE(SUM(r.amount),0) AS total FROM receipts r JOIN bookings b ON b.id = r.booking_id WHERE b.customer_id = ?", [$id])['total'] ?? 0);
        $transfer = (float)(db_get("SELECT COALESCE(SUM(amount),0) AS total FROM transfers WHERE from_customer_id = ?", [$id])['total'] ?? 0);
        $balance = $debit - $credit - $transfer;
    } elseif ($type === 'vendor' && $id > 0) {
        $row = db_get("SELECT business_name AS name FROM vendors WHERE id = ?", [$id]);
        $name = $row['name'] ?? '';
        $purchases = (float)(db_get("SELECT COALESCE(SUM(total_amount),0) AS total FROM purchases WHERE vendor_id = ? AND status <> 'cancelled'", [$id])['total'] ?? 0);
        $payments = (float)(db_get("SELECT COALESCE(SUM(amount),0) AS total FROM vendor_payments WHERE vendor_id = ?", [$id])['total'] ?? 0);
        $balance = $purchases - $payments;
    } elseif ($type === 'owner' && $id > 0) {
        $row = db_get("SELECT full_name AS name FROM owners WHERE id = ?", [$id]);
        $name = $row['name'] ?? '';
        $balRow = db_get("SELECT COALESCE(MAX(balance),0) AS balance FROM owner_ledger WHERE owner_id = ?", [$id]);
        $balance = $balRow ? (float)$balRow['balance'] : 0;
    } elseif ($type === 'dealer' && $id > 0) {
        $row = db_get("SELECT full_name AS name FROM dealers WHERE id = ?", [$id]);
        $name = $row['name'] ?? '';
        $balRow = db_get("SELECT COALESCE(MAX(balance),0) AS balance FROM dealer_ledger WHERE dealer_id = ?", [$id]);
        $balance = $balRow ? (float)$balRow['balance'] : 0;
    } elseif ($type === 'employee' && $id > 0) {
        $row = db_get("SELECT full_name AS name FROM employees WHERE id = ?", [$id]);
        $name = $row['name'] ?? '';
        $payable = (float)(db_get("SELECT COALESCE(SUM(amount),0) AS total FROM employee_entries WHERE employee_id = ? AND entry_type = 'payable'", [$id])['total'] ?? 0);
        $paid = (float)(db_get("SELECT COALESCE(SUM(amount),0) AS total FROM employee_entries WHERE employee_id = ? AND entry_type = 'paid'", [$id])['total'] ?? 0);
        $balance = $payable - $paid;
    } elseif ($type === 'contractor' && $id > 0) {
        $row = db_get("SELECT full_name AS name FROM contractors WHERE id = ?", [$id]);
        $name = $row['name'] ?? '';
        $payable = (float)(db_get("SELECT COALESCE(SUM(amount),0) AS total FROM contractor_entries WHERE contractor_id = ? AND entry_type = 'payable'", [$id])['total'] ?? 0);
        $paid = (float)(db_get("SELECT COALESCE(SUM(amount),0) AS total FROM contractor_entries WHERE contractor_id = ? AND entry_type = 'paid'", [$id])['total'] ?? 0);
        $balance = $payable - $paid;
    } elseif ($type === 'investor' && $id > 0) {
        $row = db_get("SELECT full_name AS name FROM investors WHERE id = ?", [$id]);
        $name = $row['name'] ?? '';
        $balRow = db_get("SELECT COALESCE(MAX(balance),0) AS balance FROM investor_ledger WHERE investor_id = ?", [$id]);
        $balance = $balRow ? (float)$balRow['balance'] : 0;
    } elseif ($type === 'tenant' && $id > 0) {
        $row = db_get("SELECT full_name AS name FROM tenants WHERE id = ?", [$id]);
        $name = $row['name'] ?? '';
        $balance = (float)(db_get("SELECT COALESCE(SUM(s.rent_amount + s.late_charges - s.paid_amount),0) AS total FROM rent_schedule s JOIN rental_agreements ra ON ra.id = s.agreement_id WHERE ra.tenant_id = ? AND s.status IN ('pending','partial')", [$id])['total'] ?? 0);
    }
    $rows = ['balance' => $balance, 'name' => $name];
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

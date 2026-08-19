<?php
require_once '../includes/auth.php';
require_login();
require_permission('customers.view');
$title = 'Customers';
$active = 'customers';
$canEdit = has_permission('customers.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $customer_no = trim($_POST['customer_no'] ?? '');
        if ($customer_no === '') {
            $customer_no = next_number('CUST', 'customers', 'customer_no');
        }
        $full_name = trim($_POST['full_name'] ?? '');
        $cnic = trim($_POST['cnic'] ?? '');
        $passport_no = trim($_POST['passport_no'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city_id = (int)($_POST['city_id'] ?? 0) ?: null;
        $nominee_name = trim($_POST['nominee_name'] ?? '');
        $nominee_cnic = trim($_POST['nominee_cnic'] ?? '');
        $nominee_relation = trim($_POST['nominee_relation'] ?? '');
        $opening_balance = (float)($_POST['opening_balance'] ?? 0);
        $balance_type = trim($_POST['balance_type'] ?? 'receivable');
        if (!in_array($balance_type, ['receivable', 'payable'])) $balance_type = 'receivable';
        $status = (int)($_POST['status'] ?? 1);

        if ($full_name === '') {
            flash('danger', 'Customer name is required.');
        } elseif ($id > 0) {
            db_exec("UPDATE customers SET customer_no=?, full_name=?, cnic=?, passport_no=?, phone=?, whatsapp=?, email=?, address=?, city_id=?, nominee_name=?, nominee_cnic=?, nominee_relation=?, opening_balance=?, balance_type=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$customer_no, $full_name, $cnic, $passport_no, $phone, $whatsapp, $email, $address, $city_id, $nominee_name, $nominee_cnic, $nominee_relation, $opening_balance, $balance_type, $status, $id]);
            flash('success', 'Customer updated successfully.');
        } else {
            db_exec("INSERT INTO customers (customer_no, full_name, cnic, passport_no, phone, whatsapp, email, address, city_id, nominee_name, nominee_cnic, nominee_relation, opening_balance, balance_type, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$customer_no, $full_name, $cnic, $passport_no, $phone, $whatsapp, $email, $address, $city_id, $nominee_name, $nominee_cnic, $nominee_relation, $opening_balance, $balance_type, $status]);
            flash('success', 'Customer added successfully.');
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM customers WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Customer deleted successfully.');
    }
    redirect('customers.php');
}

$records = db_all("SELECT c.*, ci.name AS city_name,
                   (SELECT COUNT(*) FROM bookings b WHERE b.customer_id = c.id AND b.status <> 'cancelled') AS booking_count,
                   (SELECT COALESCE(SUM(amount),0) FROM receipts r WHERE r.customer_id = c.id) AS total_paid
                   FROM customers c
                   LEFT JOIN cities ci ON ci.id = c.city_id
                   ORDER BY c.full_name");
$cities = db_all("SELECT * FROM cities ORDER BY name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search customers...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Customer"><i class="bi bi-plus-lg me-1"></i>Add Customer</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Customer</th><th>CNIC</th><th>Phone</th><th>WhatsApp</th><th>City</th><th>Bookings</th><th>Total Paid</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <a class="fw-medium text-decoration-none" href="customer_view.php?id=<?= $r['id'] ?>"><?= e($r['full_name']) ?></a>
                            <div class="small text-muted"><?= e($r['customer_no']) ?></div>
                        </td>
                        <td class="small"><?= e($r['cnic']) ?></td>
                        <td><?= e($r['phone']) ?></td>
                        <td><?= e($r['whatsapp']) ?></td>
                        <td><?= e($r['city_name'] ?? '-') ?></td>
                        <td><span class="badge bg-light text-dark border"><?= $r['booking_count'] ?></span></td>
                        <td><?= fmt_money($r['total_paid']) ?></td>
                        <td><?= $r['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-info" href="customer_view.php?id=<?= $r['id'] ?>"><i class="bi bi-eye"></i></a>
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'customer_no' => $r['customer_no'], 'full_name' => $r['full_name'], 'cnic' => $r['cnic'], 'passport_no' => $r['passport_no'], 'phone' => $r['phone'], 'whatsapp' => $r['whatsapp'], 'email' => $r['email'], 'address' => $r['address'], 'city_id' => $r['city_id'], 'nominee_name' => $r['nominee_name'], 'nominee_cnic' => $r['nominee_cnic'], 'nominee_relation' => $r['nominee_relation'], 'opening_balance' => $r['opening_balance'], 'balance_type' => $r['balance_type'], 'status' => $r['status']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this customer? This cannot be undone.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$records): ?>
                    <tr><td colspan="10"><div class="empty-state"><i class="bi bi-people"></i><p>No customers yet</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="recordModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header"><h5 class="modal-title">Add Customer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Customer No</label>
                            <input type="text" name="customer_no" class="form-control" placeholder="Auto if blank">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CNIC</label>
                            <input type="text" name="cnic" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Passport No</label>
                            <input type="text" name="passport_no" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">WhatsApp</label>
                            <input type="text" name="whatsapp" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <select name="city_id" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($cities as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nominee Name</label>
                            <input type="text" name="nominee_name" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nominee CNIC</label>
                            <input type="text" name="nominee_cnic" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nominee Relation</label>
                            <input type="text" name="nominee_relation" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Opening Balance</label>
                            <input type="number" step="0.01" name="opening_balance" class="form-control" placeholder="0.00" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Balance Type</label>
                            <select name="balance_type" class="form-select">
                                <option value="receivable">Receivable (Customer owes us)</option>
                                <option value="payable">Payable (We owe customer)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

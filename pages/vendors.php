<?php
require_once '../includes/auth.php';
require_login();
require_permission('vendors.view');
$title = 'Vendors';
$active = 'vendors';
$canEdit = has_permission('vendors.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $vendor_no = trim($_POST['vendor_no'] ?? '');
        if ($vendor_no === '') {
            $vendor_no = next_number('VEN', 'vendors', 'vendor_no');
        }
        $business_name = trim($_POST['business_name'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');
        $cnic = trim($_POST['cnic'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city_id = (int)($_POST['city_id'] ?? 0) ?: null;
        $bank_id = (int)($_POST['bank_id'] ?? 0) ?: null;
        $bank_account_title = trim($_POST['bank_account_title'] ?? '');
        $bank_account_no = trim($_POST['bank_account_no'] ?? '');
        $status = (int)($_POST['status'] ?? 1);

        if ($business_name === '') {
            flash('danger', 'Business name is required.');
        } elseif ($id > 0) {
            db_exec("UPDATE vendors SET vendor_no=?, business_name=?, contact_person=?, cnic=?, phone=?, whatsapp=?, email=?, address=?, city_id=?, bank_id=?, bank_account_title=?, bank_account_no=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$vendor_no, $business_name, $contact_person, $cnic, $phone, $whatsapp, $email, $address, $city_id, $bank_id, $bank_account_title, $bank_account_no, $status, $id]);
            flash('success', 'Vendor updated successfully.');
        } else {
            db_exec("INSERT INTO vendors (vendor_no, business_name, contact_person, cnic, phone, whatsapp, email, address, city_id, bank_id, bank_account_title, bank_account_no, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$vendor_no, $business_name, $contact_person, $cnic, $phone, $whatsapp, $email, $address, $city_id, $bank_id, $bank_account_title, $bank_account_no, $status]);
            flash('success', 'Vendor added successfully.');
        }
    } elseif ($action === 'delete') {
        $delId = (int)($_POST['id'] ?? 0);
        if ($delId > 0) {
            db_exec("DELETE FROM vendors WHERE id=?", [$delId]);
            if (db_affected_rows() > 0) {
                flash('success', 'Vendor deleted successfully.');
            } else {
                flash('danger', 'Vendor not found or could not be deleted.');
            }
        } else {
            flash('danger', 'Invalid vendor ID.');
        }
    }
    redirect('vendors.php');
}

$records = db_all("SELECT v.*, ci.name AS city_name,
                   (SELECT COALESCE(SUM(amount),0) FROM vendor_payments vp WHERE vp.vendor_id = v.id) AS total_paid
                   FROM vendors v
                   LEFT JOIN cities ci ON ci.id = v.city_id
                   ORDER BY v.business_name");
$cities = db_all("SELECT * FROM cities ORDER BY name");
$banks = db_all("SELECT * FROM banks ORDER BY name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search vendors...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Vendor"><i class="bi bi-plus-lg me-1"></i>Add Vendor</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Vendor</th><th>Contact Person</th><th>Phone</th><th>City</th><th>Total Paid</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <a class="fw-medium text-decoration-none" href="vendor_view.php?id=<?= $r['id'] ?>"><?= e($r['business_name']) ?></a>
                            <div class="small text-muted"><?= e($r['vendor_no']) ?></div>
                        </td>
                        <td class="small"><?= e($r['contact_person']) ?></td>
                        <td><?= e($r['phone']) ?></td>
                        <td><?= e($r['city_name'] ?? '-') ?></td>
                        <td><?= fmt_money($r['total_paid']) ?></td>
                        <td><?= $r['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-info" href="vendor_view.php?id=<?= $r['id'] ?>"><i class="bi bi-eye"></i></a>
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'vendor_no' => $r['vendor_no'], 'business_name' => $r['business_name'], 'contact_person' => $r['contact_person'], 'cnic' => $r['cnic'], 'phone' => $r['phone'], 'whatsapp' => $r['whatsapp'], 'email' => $r['email'], 'address' => $r['address'], 'city_id' => $r['city_id'], 'bank_id' => $r['bank_id'], 'bank_account_title' => $r['bank_account_title'], 'bank_account_no' => $r['bank_account_no'], 'status' => $r['status']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this vendor? This cannot be undone.">
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
                    <tr><td colspan="8"><div class="empty-state"><i class="bi bi-truck"></i><p>No vendors yet</p></div></td></tr>
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
                <div class="modal-header"><h5 class="modal-title">Add Vendor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Vendor No</label>
                            <input type="text" name="vendor_no" class="form-control" placeholder="Auto if blank">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Business Name</label>
                            <input type="text" name="business_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CNIC</label>
                            <input type="text" name="cnic" class="form-control">
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
                            <label class="form-label">Bank</label>
                            <select name="bank_id" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($banks as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bank Account Title</label>
                            <input type="text" name="bank_account_title" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bank Account No</label>
                            <input type="text" name="bank_account_no" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
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

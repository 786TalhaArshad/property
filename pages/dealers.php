<?php
require_once '../includes/auth.php';
require_login();
require_permission('dealers.view');
$title = 'Dealers / Agents';
$active = 'dealers';
$canEdit = has_permission('dealers.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $dealer_no = trim($_POST['dealer_no'] ?? '');
        if ($dealer_no === '') {
            $dealer_no = next_number('DLR', 'dealers', 'dealer_no');
        }
        $full_name = trim($_POST['full_name'] ?? '');
        $cnic = trim($_POST['cnic'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $dealer_type = $_POST['dealer_type'] ?? 'dealer';
        $commission_rate = (float)($_POST['commission_rate'] ?? 0);
        $status = (int)($_POST['status'] ?? 1);

        if ($full_name === '') {
            flash('danger', 'Dealer name is required.');
        } elseif ($id > 0) {
            db_exec("UPDATE dealers SET dealer_no=?, full_name=?, cnic=?, phone=?, whatsapp=?, email=?, address=?, dealer_type=?, commission_rate=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$dealer_no, $full_name, $cnic, $phone, $whatsapp, $email, $address, $dealer_type, $commission_rate, $status, $id]);
            flash('success', 'Dealer updated successfully.');
        } else {
            db_exec("INSERT INTO dealers (dealer_no, full_name, cnic, phone, whatsapp, email, address, dealer_type, commission_rate, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$dealer_no, $full_name, $cnic, $phone, $whatsapp, $email, $address, $dealer_type, $commission_rate, $status]);
            flash('success', 'Dealer added successfully.');
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM dealers WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Dealer deleted successfully.');
    }
    redirect('dealers.php');
}

$records = db_all("SELECT d.*,
                   (SELECT COUNT(*) FROM bookings b WHERE b.dealer_id = d.id AND b.status <> 'cancelled') AS sales_count,
                   (SELECT COALESCE(SUM(b.total_price * d2.commission_rate / 100),0) FROM bookings b JOIN dealers d2 ON d2.id = b.dealer_id WHERE b.dealer_id = d.id AND b.status <> 'cancelled') AS commission
                   FROM dealers d ORDER BY d.full_name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search dealers...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Dealer"><i class="bi bi-plus-lg me-1"></i>Add Dealer / Agent</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Dealer</th><th>Type</th><th>CNIC</th><th>Phone</th><th>Sales</th><th>Commission</th><th>Rate</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <a class="fw-medium text-decoration-none" href="dealer_view.php?id=<?= $r['id'] ?>"><?= e($r['full_name']) ?></a>
                            <div class="small text-muted"><?= e($r['dealer_no']) ?></div>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= ucfirst(e($r['dealer_type'])) ?></span></td>
                        <td class="small"><?= e($r['cnic']) ?></td>
                        <td><?= e($r['phone']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= $r['sales_count'] ?></span></td>
                        <td><?= fmt_money($r['commission']) ?></td>
                        <td><?= fmt_num($r['commission_rate']) ?>%</td>
                        <td><?= $r['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-info" href="dealer_view.php?id=<?= $r['id'] ?>"><i class="bi bi-eye"></i></a>
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'dealer_no' => $r['dealer_no'], 'full_name' => $r['full_name'], 'cnic' => $r['cnic'], 'phone' => $r['phone'], 'whatsapp' => $r['whatsapp'], 'email' => $r['email'], 'address' => $r['address'], 'dealer_type' => $r['dealer_type'], 'commission_rate' => $r['commission_rate'], 'status' => $r['status']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this dealer? This cannot be undone.">
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
                    <tr><td colspan="10"><div class="empty-state"><i class="bi bi-handshake"></i><p>No dealers yet</p></div></td></tr>
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
                <div class="modal-header"><h5 class="modal-title">Add Dealer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Dealer No</label>
                            <input type="text" name="dealer_no" class="form-control" placeholder="Auto if blank">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Dealer Type</label>
                            <select name="dealer_type" class="form-select">
                                <option value="dealer">Dealer</option>
                                <option value="agent">Agent</option>
                            </select>
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
                        <div class="col-md-6">
                            <label class="form-label">Commission Rate %</label>
                            <input type="number" step="0.01" name="commission_rate" class="form-control" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control">
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

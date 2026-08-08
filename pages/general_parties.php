<?php
require_once '../includes/auth.php';
require_login();
require_permission('general_parties.view');
$title = 'General Parties';
$active = 'general_parties';
$canEdit = has_permission('general_parties.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $party_no = trim($_POST['party_no'] ?? '');
        if ($party_no === '') {
            $party_no = next_number('GP', 'general_parties', 'party_no');
        }
        $party_name = trim($_POST['party_name'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $status = (int)($_POST['status'] ?? 1);

        if ($party_name === '') {
            flash('danger', 'Party name is required.');
        } elseif ($id > 0) {
            db_exec("UPDATE general_parties SET party_no=?, party_name=?, contact_person=?, phone=?, whatsapp=?, email=?, address=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$party_no, $party_name, $contact_person, $phone, $whatsapp, $email, $address, $status, $id]);
            flash('success', 'Party updated successfully.');
        } else {
            db_exec("INSERT INTO general_parties (party_no, party_name, contact_person, phone, whatsapp, email, address, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$party_no, $party_name, $contact_person, $phone, $whatsapp, $email, $address, $status]);
            flash('success', 'Party added successfully.');
        }
    } elseif ($action === 'delete') {
        $pid = (int)($_POST['id'] ?? 0);
        $cnt = (int)db_get("SELECT COUNT(*) AS c FROM general_party_entries WHERE party_id = ?", [$pid])['c'];
        if ($cnt > 0) {
            flash('danger', 'Cannot delete a party that has ledger entries.');
        } else {
            db_exec("DELETE FROM general_parties WHERE id=?", [$pid]);
            flash('success', 'Party deleted successfully.');
        }
    }
    redirect('general_parties.php');
}

$records = db_all("SELECT gp.*,
                   (SELECT COALESCE(SUM(CASE WHEN gpe.entry_type = 'payable' THEN gpe.amount ELSE 0 END),0) FROM general_party_entries gpe WHERE gpe.party_id = gp.id) AS total_payable,
                   (SELECT COALESCE(SUM(CASE WHEN gpe.entry_type = 'paid' THEN gpe.amount ELSE 0 END),0) FROM general_party_entries gpe WHERE gpe.party_id = gp.id) AS total_paid,
                   (SELECT COALESCE(SUM(CASE WHEN gpe.entry_type = 'receiving' THEN gpe.amount ELSE 0 END),0) FROM general_party_entries gpe WHERE gpe.party_id = gp.id) AS total_receiving
                   FROM general_parties gp
                   ORDER BY gp.party_name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search parties...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add General Party"><i class="bi bi-plus-lg me-1"></i>Add General Party</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Party</th><th>Contact Person</th><th>Phone</th><th>Payable</th><th>Paid</th><th>Receiving</th><th>Balance</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <?php $bal = (float)$r['total_payable'] - (float)$r['total_paid'] - (float)$r['total_receiving']; ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <a class="fw-medium text-decoration-none" href="general_party_view.php?id=<?= $r['id'] ?>"><?= e($r['party_name']) ?></a>
                            <div class="small text-muted"><?= e($r['party_no']) ?></div>
                        </td>
                        <td class="small"><?= e($r['contact_person']) ?></td>
                        <td><?= e($r['phone']) ?></td>
                        <td><?= fmt_money($r['total_payable']) ?></td>
                        <td><?= fmt_money($r['total_paid']) ?></td>
                        <td><?= fmt_money($r['total_receiving']) ?></td>
                        <td class="text-nowrap fw-medium <?= $bal > 0 ? 'text-danger' : ($bal < 0 ? 'text-success' : 'text-muted') ?>"><?= fmt_money($bal) ?></td>
                        <td><?= $r['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-info" href="general_party_view.php?id=<?= $r['id'] ?>"><i class="bi bi-eye"></i></a>
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'party_no' => $r['party_no'], 'party_name' => $r['party_name'], 'contact_person' => $r['contact_person'], 'phone' => $r['phone'], 'whatsapp' => $r['whatsapp'], 'email' => $r['email'], 'address' => $r['address'], 'status' => $r['status']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this party? This cannot be undone.">
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
                    <tr><td colspan="10"><div class="empty-state"><i class="bi bi-person-lines-fill"></i><p>No general parties yet</p></div></td></tr>
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
                <div class="modal-header"><h5 class="modal-title">Add General Party</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Party No</label>
                            <input type="text" name="party_no" class="form-control" placeholder="Auto if blank">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Party Name</label>
                            <input type="text" name="party_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">WhatsApp</label>
                            <input type="text" name="whatsapp" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-8">
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

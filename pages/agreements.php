<?php
require_once '../includes/auth.php';
require_login();
require_permission('sales.view');
$title = 'Sale Agreements';
$active = 'agreements';
$canEdit = has_permission('sales.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $agreement_no = trim($_POST['agreement_no'] ?? '');
        if ($agreement_no === '') {
            $agreement_no = next_number('AGR', 'sale_agreements', 'agreement_no');
        }
        $booking_id = (int)($_POST['booking_id'] ?? 0);
        $agreement_date = $_POST['agreement_date'] ?? date('Y-m-d');
        $status = $_POST['status'] ?? 'draft';

        if ($booking_id <= 0) {
            flash('danger', 'Please select a booking.');
        } else {
            if ($id > 0) {
                $file_path = $agreement_file = upload_file('agreement_file', 'uploads/agreements', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
                if ($agreement_file === false) {
                    flash('danger', 'File upload failed. Allowed: PDF, images, DOC.');
                    redirect('agreements.php');
                }
                if ($file_path) {
                    db_exec("UPDATE sale_agreements SET agreement_no=?, booking_id=?, agreement_date=?, status=?, file_path=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$agreement_no, $booking_id, $agreement_date, $status, $file_path, $id]);
                } else {
                    db_exec("UPDATE sale_agreements SET agreement_no=?, booking_id=?, agreement_date=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$agreement_no, $booking_id, $agreement_date, $status, $id]);
                }
                flash('success', 'Agreement updated successfully.');
            } else {
                $file_path = upload_file('agreement_file', 'uploads/agreements', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
                if ($file_path === false) {
                    flash('danger', 'File upload failed. Allowed: PDF, images, DOC.');
                    redirect('agreements.php');
                }
                db_exec("INSERT INTO sale_agreements (agreement_no, booking_id, agreement_date, file_path, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$agreement_no, $booking_id, $agreement_date, $file_path, $status]);
                db_exec("UPDATE bookings SET status = 'active', updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$booking_id]);
                flash('success', 'Agreement added and booking activated.');
            }
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM sale_agreements WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Agreement deleted successfully.');
    }
    $back = isset($_POST['back_to']) && $_POST['back_to'] ? 'booking_view.php?id=' . (int)$_POST['back_to'] : 'agreements.php';
    redirect($back);
}

$filterBooking = (int)($_GET['booking_id'] ?? 0);
$records = db_all("SELECT sa.*, b.booking_no, c.full_name AS customer_name, p.property_no
                   FROM sale_agreements sa
                   JOIN bookings b ON b.id = sa.booking_id
                   JOIN customers c ON c.id = b.customer_id
                   JOIN properties p ON p.id = b.property_id
                   WHERE (? = 0 OR sa.booking_id = ?)
                   ORDER BY sa.agreement_date DESC", [$filterBooking, $filterBooking]);
$bookings = db_all("SELECT b.id, b.booking_no, c.full_name AS customer_name, p.property_no FROM bookings b JOIN customers c ON c.id = b.customer_id JOIN properties p ON p.id = b.property_id WHERE b.status <> 'cancelled' ORDER BY b.id DESC");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search agreements...">
    </div>
    <?php if ($filterBooking): ?><a class="btn btn-sm btn-light" href="agreements.php"><i class="bi bi-x-lg me-1"></i>Clear Filter</a><?php endif; ?>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Agreement"<?= $filterBooking ? ' data-booking="' . $filterBooking . '"' : '' ?>><i class="bi bi-plus-lg me-1"></i>Add Agreement</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Agreement</th><th>Booking</th><th>Customer</th><th>Property</th><th>Date</th><th>Status</th><th>File</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-medium"><?= e($r['agreement_no']) ?></td>
                        <td><a href="booking_view.php?id=<?= $r['booking_id'] ?>"><?= e($r['booking_no']) ?></a></td>
                        <td><?= e($r['customer_name']) ?></td>
                        <td><?= e($r['property_no']) ?></td>
                        <td><?= fmt_date($r['agreement_date']) ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td><?= $r['file_path'] ? '<a class="btn btn-sm btn-outline-secondary" target="_blank" href="' . BASE_URL . '/assets/' . e($r['file_path']) . '"><i class="bi bi-download"></i></a>' : '-' ?></td>
                        <td class="text-end">
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'agreement_no' => $r['agreement_no'], 'booking_id' => $r['booking_id'], 'agreement_date' => $r['agreement_date'], 'status' => $r['status']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this agreement?">
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
                    <tr><td colspan="9"><div class="empty-state"><i class="bi bi-file-earmark-check"></i><p>No agreements yet</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="recordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header"><h5 class="modal-title">Add Agreement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <input type="hidden" name="back_to" id="backTo">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Agreement No</label>
                            <input type="text" name="agreement_no" class="form-control" placeholder="Auto if blank">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input type="date" name="agreement_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Booking</label>
                            <select name="booking_id" id="selBooking" class="form-select" required>
                                <option value="">Select Booking</option>
                                <?php foreach ($bookings as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['booking_no']) ?> - <?= e($b['customer_name']) ?> (<?= e($b['property_no']) ?>)</option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach (['draft', 'signed', 'registered'] as $s): ?>
                                    <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Agreement File</label>
                            <input type="file" name="agreement_file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <div class="form-text">PDF, DOC or image.</div>
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

<?php if ($canEdit): ?>
<script>
(function () {
    var modal = document.getElementById('recordModal');
    var booking = document.getElementById('selBooking');
    if (modal) {
        modal.addEventListener('show.bs.modal', function (e) {
            var btn = e.relatedTarget;
            document.getElementById('backTo').value = '';
            if (btn.dataset.add) {
                modal.querySelector('.modal-title').textContent = btn.dataset.add;
                modal.querySelector('form').reset();
                document.getElementById('recordId').value = '';
                if (btn.dataset.booking) booking.value = btn.dataset.booking;
            } else {
                var d = JSON.parse(btn.dataset.edit);
                modal.querySelector('.modal-title').textContent = 'Edit Agreement';
                document.getElementById('recordId').value = d.id;
                modal.querySelector('[name=agreement_no]').value = d.agreement_no;
                booking.value = d.booking_id;
                modal.querySelector('[name=agreement_date]').value = d.agreement_date;
                modal.querySelector('[name=status]').value = d.status;
                if (window.location.search.indexOf('booking_id=') !== -1) {
                    document.getElementById('backTo').value = booking.value;
                }
            }
        });
    }
})();
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

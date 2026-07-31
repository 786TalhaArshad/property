<?php
require_once '../includes/auth.php';
require_login();
require_permission('crm.view');
$title = 'Meetings';
$active = 'meetings';
$canEdit = has_permission('crm.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $lead_id = (int)($_POST['lead_id'] ?? 0) ?: null;
        $customer_id = (int)($_POST['customer_id'] ?? 0) ?: null;
        $meeting_date = str_replace('T', ' ', $_POST['meeting_date'] ?? date('Y-m-d H:i')) . ':00';
        $location = trim($_POST['location'] ?? '');
        $note = trim($_POST['note'] ?? '');
        $status = $_POST['status'] ?? 'scheduled';

        if ($id > 0) {
            db_exec("UPDATE meetings SET lead_id=?, customer_id=?, meeting_date=?, location=?, note=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$lead_id, $customer_id, $meeting_date, $location, $note, $status, $id]);
            flash('success', 'Meeting updated successfully.');
        } else {
            db_exec("INSERT INTO meetings (lead_id, customer_id, meeting_date, location, note, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$lead_id, $customer_id, $meeting_date, $location, $note, $status]);
            flash('success', 'Meeting scheduled successfully.');
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM meetings WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Meeting deleted successfully.');
    }
    redirect('meetings.php');
}

$records = db_all("SELECT m.*, l.name AS lead_name, c.full_name AS customer_name
                   FROM meetings m
                   LEFT JOIN leads l ON l.id = m.lead_id
                   LEFT JOIN customers c ON c.id = m.customer_id
                   ORDER BY m.meeting_date DESC");
$leads = db_all("SELECT * FROM leads WHERE status NOT IN ('converted','lost') ORDER BY name");
$customers = db_all("SELECT * FROM customers ORDER BY full_name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search meetings...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Schedule Meeting"><i class="bi bi-plus-lg me-1"></i>Schedule Meeting</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Date</th><th>Lead</th><th>Customer</th><th>Location</th><th>Note</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= fmt_date(substr($r['meeting_date'], 0, 10)) ?> <?= substr($r['meeting_date'], 11, 5) ?></td>
                        <td><?= $r['lead_id'] ? '<a href="lead_view.php?id=' . $r['lead_id'] . '">' . e($r['lead_name']) . '</a>' : '-' ?></td>
                        <td><?= e($r['customer_name'] ?? '-') ?></td>
                        <td><?= e($r['location'] ?? '-') ?></td>
                        <td class="small"><?= e($r['note'] ?? '-') ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td class="text-end">
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'lead_id' => $r['lead_id'], 'customer_id' => $r['customer_id'], 'meeting_date' => substr($r['meeting_date'], 0, 16), 'location' => $r['location'], 'note' => $r['note'], 'status' => $r['status']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this meeting?">
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
                    <tr><td colspan="8"><div class="empty-state"><i class="bi bi-calendar-event"></i><p>No meetings scheduled</p></div></td></tr>
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
            <form method="post">
                <div class="modal-header"><h5 class="modal-title">Schedule Meeting</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Lead</label>
                            <select name="lead_id" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($leads as $l): ?><option value="<?= $l['id'] ?>"><?= e($l['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['full_name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date &amp; Time</label>
                            <input type="datetime-local" name="meeting_date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="scheduled">Scheduled</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Note</label>
                            <input type="text" name="note" class="form-control">
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

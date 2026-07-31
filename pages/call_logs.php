<?php
require_once '../includes/auth.php';
require_login();
require_permission('crm.view');
$title = 'Call Logs';
$active = 'call_logs';
$canEdit = has_permission('crm.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $lead_id = (int)($_POST['lead_id'] ?? 0) ?: null;
        $call_date = str_replace('T', ' ', $_POST['call_date'] ?? date('Y-m-d H:i')) . ':00';
        $duration = (int)($_POST['duration'] ?? 0);
        $direction = $_POST['direction'] ?? 'outbound';
        $note = trim($_POST['note'] ?? '');

        if (!$lead_id) {
            flash('danger', 'Please select a lead.');
            redirect('call_logs.php');
        }
        if ($id > 0) {
            db_exec("UPDATE call_logs SET lead_id=?, call_date=?, duration=?, direction=?, note=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$lead_id, $call_date, $duration, $direction, $note, $id]);
            flash('success', 'Call log updated successfully.');
        } else {
            db_exec("INSERT INTO call_logs (lead_id, call_date, duration, direction, note, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$lead_id, $call_date, $duration, $direction, $note]);
            flash('success', 'Call log added successfully.');
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM call_logs WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Call log deleted successfully.');
    }
    redirect('call_logs.php');
}

$records = db_all("SELECT c.*, l.name AS lead_name, l.phone AS lead_phone
                   FROM call_logs c
                   LEFT JOIN leads l ON l.id = c.lead_id
                   ORDER BY c.call_date DESC");
$leads = db_all("SELECT * FROM leads ORDER BY name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search call logs...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Log Call"><i class="bi bi-plus-lg me-1"></i>Log Call</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Date &amp; Time</th><th>Lead</th><th>Direction</th><th>Duration</th><th>Note</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= fmt_date(substr($r['call_date'], 0, 10)) ?> <?= substr($r['call_date'], 11, 5) ?></td>
                        <td><?= $r['lead_id'] ? '<a href="lead_view.php?id=' . $r['lead_id'] . '">' . e($r['lead_name']) . '</a>' : '-' ?><div class="small text-muted"><?= e($r['lead_phone'] ?? '') ?></div></td>
                        <td><span class="badge <?= $r['direction'] === 'inbound' ? 'bg-success-subtle text-success' : 'bg-info-subtle text-info' ?>"><?= ucfirst($r['direction']) ?></span></td>
                        <td><?= (int)$r['duration'] ?> sec</td>
                        <td class="small"><?= e($r['note'] ?? '-') ?></td>
                        <td class="text-end">
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'lead_id' => $r['lead_id'], 'call_date' => substr($r['call_date'], 0, 16), 'duration' => $r['duration'], 'direction' => $r['direction'], 'note' => $r['note']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this call log?">
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
                    <tr><td colspan="7"><div class="empty-state"><i class="bi bi-telephone"></i><p>No call logs recorded</p></div></td></tr>
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
                <div class="modal-header"><h5 class="modal-title">Log Call</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Lead</label>
                            <select name="lead_id" class="form-select" required>
                                <option value="">Select Lead</option>
                                <?php foreach ($leads as $l): ?><option value="<?= $l['id'] ?>"><?= e($l['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date &amp; Time</label>
                            <input type="datetime-local" name="call_date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Direction</label>
                            <select name="direction" class="form-select">
                                <option value="outbound">Outbound</option>
                                <option value="inbound">Inbound</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Duration (seconds)</label>
                            <input type="number" name="duration" class="form-control" value="0" min="0">
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

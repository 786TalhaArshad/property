<?php
require_once '../includes/auth.php';
require_login();
require_permission('crm.view');
$title = 'Lead Details';
$active = 'leads';
$canEdit = has_permission('crm.manage');

$id = (int)($_GET['id'] ?? 0);
$lead = db_get("SELECT l.*, pt.name AS type_name, pr.name AS project_name, u.full_name AS assigned_name
                FROM leads l
                LEFT JOIN property_types pt ON pt.id = l.property_type_id
                LEFT JOIN projects pr ON pr.id = l.project_id
                LEFT JOIN users u ON u.id = l.assigned_to
                WHERE l.id = ?", [$id]);
if (!$lead) {
    flash('danger', 'Lead not found.');
    redirect('leads.php');
}

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'followup_add') {
        $followup_date = $_POST['followup_date'] ?? date('Y-m-d');
        $note = trim($_POST['note'] ?? '');
        $next = $_POST['next_follow_up'] ?? null ?: null;
        db_exec("INSERT INTO lead_followups (lead_id, followup_date, note, next_follow_up, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$id, $followup_date, $note, $next]);
        if ($next) {
            db_exec("UPDATE leads SET next_follow_up = ?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$next, $id]);
        }
        flash('success', 'Follow up recorded.');
    } elseif ($action === 'call_add') {
        $call_date = $_POST['call_date'] ?? date('Y-m-d H:i:s');
        $duration = (int)($_POST['duration'] ?? 0);
        $direction = $_POST['direction'] ?? 'outbound';
        $note = trim($_POST['note'] ?? '');
        db_exec("INSERT INTO call_logs (lead_id, call_date, duration, direction, note, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$id, $call_date, $duration, $direction, $note]);
        flash('success', 'Call logged.');
    } elseif ($action === 'meeting_add') {
        $meeting_date = $_POST['meeting_date'] ?? date('Y-m-d H:i:s');
        $location = trim($_POST['location'] ?? '');
        $note = trim($_POST['note'] ?? '');
        $status = $_POST['status'] ?? 'scheduled';
        db_exec("INSERT INTO meetings (lead_id, meeting_date, location, note, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$id, $meeting_date, $location, $note, $status]);
        flash('success', 'Meeting scheduled.');
    }
    redirect('lead_view.php?id=' . $id);
}

$followups = db_all("SELECT * FROM lead_followups WHERE lead_id = ? ORDER BY followup_date DESC", [$id]);
$calls = db_all("SELECT * FROM call_logs WHERE lead_id = ? ORDER BY call_date DESC", [$id]);
$meetings = db_all("SELECT * FROM meetings WHERE lead_id = ? ORDER BY meeting_date DESC", [$id]);
include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="leads.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= e($lead['name']) ?></h5>
    <?= status_badge($lead['status']) ?>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3"><div class="text-muted small">Lead No</div><div class="fw-medium"><?= e($lead['lead_no']) ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Phone</div><div class="fw-medium"><?= e($lead['phone'] ?? '-') ?></div></div>
            <div class="col-md-3"><div class="text-muted small">WhatsApp</div><div class="fw-medium"><?= e($lead['whatsapp'] ?? '-') ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Email</div><div class="fw-medium"><?= e($lead['email'] ?? '-') ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Source</div><div class="fw-medium"><?= ucfirst(e($lead['source'])) ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Interest</div><div class="fw-medium"><?= e($lead['type_name'] ?? 'Any') ?> <?= $lead['project_name'] ? '(' . e($lead['project_name']) . ')' : '' ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Budget</div><div class="fw-medium"><?= fmt_money($lead['budget']) ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Assigned To</div><div class="fw-medium"><?= e($lead['assigned_name'] ?? '-') ?></div></div>
            <div class="col-md-3"><div class="text-muted small">Next Follow Up</div><div class="fw-medium"><?= $lead['next_follow_up'] ? fmt_date($lead['next_follow_up']) : '-' ?></div></div>
            <div class="col-md-9"><div class="text-muted small">Remarks</div><div class="fw-medium"><?= e($lead['remarks'] ?? '-') ?></div></div>
        </div>
    </div>
</div>

<ul class="nav nav-pills mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#lFollowups">Follow Ups (<?= count($followups) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#lCalls">Call Logs (<?= count($calls) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#lMeetings">Meetings (<?= count($meetings) ?>)</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="lFollowups">
        <div class="card">
            <div class="card-body">
                <?php if ($canEdit): ?>
                <form method="post" class="row g-2 mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="followup_add">
                    <div class="col-md-3"><input type="date" name="followup_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
                    <div class="col-md-5"><input type="text" name="note" class="form-control" placeholder="Note" required></div>
                    <div class="col-md-2"><input type="date" name="next_follow_up" class="form-control" title="Next Follow Up"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i></button></div>
                </form>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Note</th><th>Next Follow Up</th></tr></thead>
                        <tbody>
                        <?php foreach ($followups as $f): ?>
                            <tr>
                                <td><?= fmt_date($f['followup_date']) ?></td>
                                <td><?= e($f['note'] ?? '-') ?></td>
                                <td><?= $f['next_follow_up'] ? fmt_date($f['next_follow_up']) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$followups): ?><tr><td colspan="3" class="text-center text-muted py-4">No follow ups yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="lCalls">
        <div class="card">
            <div class="card-body">
                <?php if ($canEdit): ?>
                <form method="post" class="row g-2 mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="call_add">
                    <div class="col-md-3"><input type="datetime-local" name="call_date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>"></div>
                    <div class="col-md-2">
                        <select name="direction" class="form-select">
                            <option value="outbound">Outbound</option>
                            <option value="inbound">Inbound</option>
                        </select>
                    </div>
                    <div class="col-md-2"><input type="number" name="duration" class="form-control" placeholder="Duration (min)"></div>
                    <div class="col-md-4"><input type="text" name="note" class="form-control" placeholder="Note"></div>
                    <div class="col-md-1"><button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i></button></div>
                </form>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Direction</th><th>Duration</th><th>Note</th></tr></thead>
                        <tbody>
                        <?php foreach ($calls as $c): ?>
                            <tr>
                                <td><?= fmt_date(substr($c['call_date'], 0, 10)) ?> <?= substr($c['call_date'], 11, 5) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= ucfirst(e($c['direction'])) ?></span></td>
                                <td><?= (int)$c['duration'] ?> min</td>
                                <td><?= e($c['note'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$calls): ?><tr><td colspan="4" class="text-center text-muted py-4">No calls logged</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="lMeetings">
        <div class="card">
            <div class="card-body">
                <?php if ($canEdit): ?>
                <form method="post" class="row g-2 mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="meeting_add">
                    <div class="col-md-3"><input type="datetime-local" name="meeting_date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>"></div>
                    <div class="col-md-3"><input type="text" name="location" class="form-control" placeholder="Location"></div>
                    <div class="col-md-3"><input type="text" name="note" class="form-control" placeholder="Note"></div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-1"><button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i></button></div>
                </form>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Location</th><th>Note</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($meetings as $m): ?>
                            <tr>
                                <td><?= fmt_date(substr($m['meeting_date'], 0, 10)) ?> <?= substr($m['meeting_date'], 11, 5) ?></td>
                                <td><?= e($m['location'] ?? '-') ?></td>
                                <td><?= e($m['note'] ?? '-') ?></td>
                                <td><?= status_badge($m['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$meetings): ?><tr><td colspan="4" class="text-center text-muted py-4">No meetings yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

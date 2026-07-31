<?php
require_once '../includes/auth.php';
require_login();
require_permission('maintenance.view');
$title = 'Complaint Details';
$active = 'maintenance';
$canEdit = has_permission('maintenance.manage');

$id = (int)($_GET['id'] ?? 0);
$complaint = db_get("SELECT mc.*, p.property_no, t.full_name AS tenant_name
                     FROM maintenance_complaints mc
                     LEFT JOIN properties p ON p.id = mc.property_id
                     LEFT JOIN tenants t ON t.id = mc.tenant_id
                     WHERE mc.id = ?", [$id]);
if (!$complaint) {
    flash('danger', 'Complaint not found.');
    redirect('maintenance.php');
}

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'task_add') {
        $technician_id = (int)($_POST['technician_id'] ?? 0) ?: null;
        $task_description = trim($_POST['task_description'] ?? '');
        $cost = (float)($_POST['cost'] ?? 0);
        $completion_date = $_POST['completion_date'] ?? null ?: null;
        $photos = upload_file('photos', 'uploads/maintenance', ['jpg', 'jpeg', 'png', 'webp']);
        if ($photos === false) {
            flash('danger', 'Photo upload failed.');
            redirect('maintenance_view.php?id=' . $id);
        }
        db_exec("INSERT INTO maintenance_tasks (complaint_id, technician_id, task_description, cost, completion_date, photos, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$id, $technician_id, $task_description, $cost, $completion_date, $photos]);
        if ($completion_date) {
            db_exec("UPDATE maintenance_complaints SET status = 'completed', updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$id]);
        }
        flash('success', 'Task added successfully.');
    } elseif ($action === 'task_delete') {
        db_exec("DELETE FROM maintenance_tasks WHERE id = ? AND complaint_id = ?", [(int)($_POST['id'] ?? 0), $id]);
        flash('success', 'Task deleted.');
    } elseif ($action === 'status') {
        $newStatus = $_POST['status'] ?? '';
        if (in_array($newStatus, ['open', 'in_progress', 'completed', 'cancelled'])) {
            db_exec("UPDATE maintenance_complaints SET status = ?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$newStatus, $id]);
            flash('success', 'Status updated.');
        }
    }
    redirect('maintenance_view.php?id=' . $id);
}

$tasks = db_all("SELECT mt.*, te.name AS technician_name FROM maintenance_tasks mt LEFT JOIN technicians te ON te.id = mt.technician_id WHERE mt.complaint_id = ? ORDER BY mt.id", [$id]);
$technicians = db_all("SELECT * FROM technicians WHERE status = 1 ORDER BY name");
$totalCost = 0.0;
foreach ($tasks as $tk) {
    $totalCost += (float)$tk['cost'];
}
include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="maintenance.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= e($complaint['complaint_no']) ?></h5>
    <?= status_badge($complaint['status']) ?>
    <?= status_badge($complaint['priority']) ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-list-task"></i></div><div><div class="stat-label">TASKS</div><div class="stat-value"><?= count($tasks) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">TOTAL COST</div><div class="stat-value"><?= fmt_money($totalCost) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-check2-circle"></i></div><div><div class="stat-label">DONE</div><div class="stat-value"><?= count(array_filter($tasks, function ($tk) { return $tk['completion_date']; })) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-calendar-event"></i></div><div><div class="stat-label">REPORTED</div><div class="stat-value small fw-medium"><?= fmt_date($complaint['reported_date']) ?></div></div></div></div></div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="text-muted small">Category: <strong class="text-capitalize"><?= e($complaint['category']) ?></strong></div>
                <p class="mb-1"><?= nl2br(e($complaint['description'])) ?></p>
                <div class="small text-muted">
                    Property: <?= e($complaint['property_no'] ?? '-') ?> &bull;
                    Tenant: <?= e($complaint['tenant_name'] ?? '-') ?> &bull;
                    Reported by: <?= e($complaint['reported_by'] ?? '-') ?>
                </div>
            </div>
            <?php if ($canEdit): ?>
            <form method="post" class="d-flex gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="status">
                <select name="status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    <?php foreach (['open', 'in_progress', 'completed', 'cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $complaint['status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-task me-2"></i>Tasks</span>
        <?php if ($canEdit): ?><button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#taskModal"><i class="bi bi-plus-lg me-1"></i>Add Task</button><?php endif; ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Technician</th><th>Task</th><th>Cost</th><th>Completed</th><th>Photo</th><?php if ($canEdit): ?><th></th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($tasks as $tk): ?>
                    <tr>
                        <td><?= e($tk['technician_name'] ?? '-') ?></td>
                        <td><?= e($tk['task_description'] ?? '-') ?></td>
                        <td><?= fmt_money($tk['cost']) ?></td>
                        <td><?= $tk['completion_date'] ? fmt_date($tk['completion_date']) : '-' ?></td>
                        <td><?= $tk['photos'] ? '<a class="btn btn-sm btn-outline-secondary" target="_blank" href="' . BASE_URL . '/assets/' . e($tk['photos']) . '"><i class="bi bi-image"></i></a>' : '-' ?></td>
                        <?php if ($canEdit): ?>
                        <td class="text-end">
                            <form method="post" class="d-inline" data-confirm="Delete this task?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="task_delete">
                                <input type="hidden" name="id" value="<?= $tk['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$tasks): ?><tr><td colspan="6" class="text-center text-muted py-4">No tasks assigned</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="taskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="task_add">
                <div class="modal-header"><h5 class="modal-title">Add Task</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Technician</label>
                            <select name="technician_id" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($technicians as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cost</label>
                            <input type="number" step="0.01" name="cost" class="form-control" value="0" data-mask-money>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Task Description</label>
                            <input type="text" name="task_description" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Completion Date</label>
                            <input type="date" name="completion_date" class="form-control">
                            <div class="form-text">Setting this marks the complaint completed.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Photo</label>
                            <input type="file" name="photos" class="form-control" accept=".jpg,.jpeg,.png,.webp">
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

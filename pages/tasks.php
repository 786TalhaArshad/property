<?php
require_once '../includes/auth.php';
require_login();
require_permission('crm.view');
$title = 'Tasks';
$active = 'tasks';
$canEdit = has_permission('crm.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $assigned_to = (int)($_POST['assigned_to'] ?? 0) ?: null;
        $due_date = $_POST['due_date'] ?? null ?: null;
        $priority = $_POST['priority'] ?? 'medium';
        $status = $_POST['status'] ?? 'pending';

        if ($title === '') {
            flash('danger', 'Task title is required.');
        } elseif ($id > 0) {
            db_exec("UPDATE tasks SET title=?, description=?, assigned_to=?, due_date=?, priority=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$title, $description, $assigned_to, $due_date, $priority, $status, $id]);
            flash('success', 'Task updated successfully.');
        } else {
            db_exec("INSERT INTO tasks (title, description, assigned_to, due_date, priority, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$title, $description, $assigned_to, $due_date, $priority, $status]);
            flash('success', 'Task added successfully.');
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM tasks WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Task deleted successfully.');
    }
    redirect('tasks.php');
}

$records = db_all("SELECT t.*, u.full_name AS assigned_name FROM tasks t LEFT JOIN users u ON u.id = t.assigned_to ORDER BY t.status = 'completed', t.due_date ASC");
$users = db_all("SELECT * FROM users WHERE status = 1 ORDER BY full_name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search tasks...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Task"><i class="bi bi-plus-lg me-1"></i>Add Task</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Task</th><th>Assigned To</th><th>Due Date</th><th>Priority</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr class="<?= $r['status'] === 'completed' ? 'text-muted' : '' ?>">
                        <td><?= $i + 1 ?></td>
                        <td>
                            <span class="fw-medium"><?= e($r['title']) ?></span>
                            <?php if ($r['description']): ?><div class="small text-muted"><?= e($r['description']) ?></div><?php endif; ?>
                        </td>
                        <td><?= e($r['assigned_name'] ?? '-') ?></td>
                        <td><?= $r['due_date'] ? fmt_date($r['due_date']) : '-' ?></td>
                        <td><?= status_badge($r['priority']) ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td class="text-end">
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'title' => $r['title'], 'description' => $r['description'], 'assigned_to' => $r['assigned_to'], 'due_date' => $r['due_date'], 'priority' => $r['priority'], 'status' => $r['status']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this task?">
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
                    <tr><td colspan="7"><div class="empty-state"><i class="bi bi-check2-square"></i><p>No tasks yet</p></div></td></tr>
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
                <div class="modal-header"><h5 class="modal-title">Add Task</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Assigned To</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($users as $u): ?><option value="<?= $u['id'] ?>"><?= e($u['full_name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                <?php foreach (['low', 'medium', 'high'] as $p): ?>
                                    <option value="<?= $p ?>"><?= ucfirst($p) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach (['pending', 'in_progress', 'completed'] as $s): ?>
                                    <option value="<?= $s ?>"><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
                                <?php endforeach; ?>
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

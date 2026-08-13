<?php
require_once '../includes/auth.php';
require_login();
require_permission('crm.view');
$title = 'Leads';
$active = 'leads';
$canEdit = has_permission('crm.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $lead_no = trim($_POST['lead_no'] ?? '');
        if ($lead_no === '') {
            $lead_no = next_number('LD', 'leads', 'lead_no');
        }
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $source = $_POST['source'] ?? 'other';
        $property_type_id = (int)($_POST['property_type_id'] ?? 0) ?: null;
        $project_id = $id > 0 ? (int)(db_get("SELECT project_id FROM leads WHERE id = ?", [$id])['project_id'] ?? 0) : active_project_id();
        $budget = (float)($_POST['budget'] ?? 0);
        $status = $_POST['status'] ?? 'new';
        $assigned_to = (int)($_POST['assigned_to'] ?? 0) ?: null;
        $next_follow_up = $_POST['next_follow_up'] ?? null ?: null;
        $remarks = trim($_POST['remarks'] ?? '');

        if ($name === '') {
            flash('danger', 'Lead name is required.');
        } elseif ($id > 0) {
            db_exec("UPDATE leads SET lead_no=?, name=?, phone=?, whatsapp=?, email=?, source=?, property_type_id=?, project_id=?, budget=?, status=?, assigned_to=?, next_follow_up=?, remarks=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$lead_no, $name, $phone, $whatsapp, $email, $source, $property_type_id, $project_id, $budget, $status, $assigned_to, $next_follow_up, $remarks, $id]);
            flash('success', 'Lead updated successfully.');
        } else {
            db_exec("INSERT INTO leads (lead_no, name, phone, whatsapp, email, source, property_type_id, project_id, budget, status, assigned_to, next_follow_up, remarks, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$lead_no, $name, $phone, $whatsapp, $email, $source, $property_type_id, $project_id, $budget, $status, $assigned_to, $next_follow_up, $remarks]);
            flash('success', 'Lead added successfully.');
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM leads WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Lead deleted successfully.');
    }
    redirect('leads.php');
}

$status = $_GET['status'] ?? '';
$ap = active_project_id();
$records = db_all("SELECT l.*, pt.name AS type_name, pr.name AS project_name, u.full_name AS assigned_name,
                   (SELECT COUNT(*) FROM lead_followups f WHERE f.lead_id = l.id) AS followup_count
                   FROM leads l
                   LEFT JOIN property_types pt ON pt.id = l.property_type_id
                   LEFT JOIN projects pr ON pr.id = l.project_id
                   LEFT JOIN users u ON u.id = l.assigned_to
                   WHERE (? = '' OR l.status = ?)
                   AND (? = 0 OR l.project_id = ?)
                   ORDER BY l.id DESC", [$status, $status, $ap, $ap]);
$users = db_all("SELECT * FROM users WHERE status = 1 ORDER BY full_name");
$propertyTypes = db_all("SELECT * FROM property_types ORDER BY name");
$projects = db_all("SELECT * FROM projects ORDER BY name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search leads...">
    </div>
    <select class="form-select form-select-sm" style="max-width:150px" onchange="location.href='?status='+this.value">
        <option value="">All Status</option>
        <?php foreach (['new', 'contacted', 'qualified', 'proposal', 'follow_up', 'converted', 'lost'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Lead"><i class="bi bi-plus-lg me-1"></i>Add Lead</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Lead</th><th>Contact</th><th>Source</th><th>Interest</th><th>Budget</th><th>Status</th><th>Assigned</th><th>Next Follow Up</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <a class="fw-medium text-decoration-none" href="lead_view.php?id=<?= $r['id'] ?>"><?= e($r['name']) ?></a>
                            <div class="small text-muted"><?= e($r['lead_no']) ?></div>
                        </td>
                        <td class="small"><?= e($r['phone'] ?? '-') ?><?= $r['whatsapp'] ? '<br>' . e($r['whatsapp']) : '' ?></td>
                        <td><span class="badge bg-light text-dark border"><?= ucfirst(e($r['source'])) ?></span></td>
                        <td class="small"><?= e($r['type_name'] ?? '-') ?><?= $r['project_name'] ? '<br>' . e($r['project_name']) : '' ?></td>
                        <td><?= $r['budget'] ? fmt_money($r['budget']) : '-' ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td class="small"><?= e($r['assigned_name'] ?? '-') ?></td>
                        <td><?= $r['next_follow_up'] ? fmt_date($r['next_follow_up']) : '-' ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-info" href="lead_view.php?id=<?= $r['id'] ?>"><i class="bi bi-eye"></i></a>
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'lead_no' => $r['lead_no'], 'name' => $r['name'], 'phone' => $r['phone'], 'whatsapp' => $r['whatsapp'], 'email' => $r['email'], 'source' => $r['source'], 'property_type_id' => $r['property_type_id'], 'project_id' => $r['project_id'], 'budget' => $r['budget'], 'status' => $r['status'], 'assigned_to' => $r['assigned_to'], 'next_follow_up' => $r['next_follow_up'], 'remarks' => $r['remarks']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this lead? Followups and logs will be removed.">
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
                    <tr><td colspan="10"><div class="empty-state"><i class="bi bi-funnel"></i><p>No leads yet</p></div></td></tr>
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
                <div class="modal-header"><h5 class="modal-title">Add Lead</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Lead No</label>
                            <input type="text" name="lead_no" class="form-control" placeholder="Auto if blank">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
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
                            <label class="form-label">Source</label>
                            <select name="source" class="form-select">
                                <?php foreach (['facebook', 'website', 'whatsapp', 'walk_in', 'referral', 'other'] as $s): ?>
                                    <option value="<?= $s ?>"><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Property Type</label>
                            <select name="property_type_id" class="form-select">
                                <option value="">Any</option>
                                <?php foreach ($propertyTypes as $pt): ?><option value="<?= $pt['id'] ?>"><?= e($pt['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Project</label>
                            <select name="project_id" class="form-select" disabled>
                                <option value="">Any</option>
                                <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= active_project_id() === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                            </select>
                            <div class="form-text">Uses the active project from the header.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Budget</label>
                            <input type="number" step="0.01" name="budget" class="form-control" value="0" data-mask-money>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach (['new', 'contacted', 'qualified', 'proposal', 'follow_up', 'converted', 'lost'] as $s): ?>
                                    <option value="<?= $s ?>"><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Assigned To</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($users as $u): ?><option value="<?= $u['id'] ?>"><?= e($u['full_name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Next Follow Up</label>
                            <input type="date" name="next_follow_up" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <input type="text" name="remarks" class="form-control">
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

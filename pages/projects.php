<?php
require_once '../includes/auth.php';
require_login();
require_permission('projects.view');
$title = 'Projects';
$active = 'projects';
$canEdit = has_permission('projects.manage');
$quickAdd = $canEdit && has_permission('settings.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $developer = trim($_POST['developer'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $country_id = (int)($_POST['country_id'] ?? 0) ?: null;
        $city_id = (int)($_POST['city_id'] ?? 0) ?: null;
        $area_id = (int)($_POST['area_id'] ?? 0) ?: null;
        $society_id = (int)($_POST['society_id'] ?? 0) ?: null;
        $noc = trim($_POST['noc'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = (int)($_POST['status'] ?? 1);

        if ($name === '') {
            flash('danger', 'Project name is required.');
        } else {
            $dup = db_get("SELECT id FROM projects WHERE name = ? AND id <> ?", [$name, $id]);
            if ($dup) {
                flash('danger', 'A project with this name already exists.');
            } elseif ($id > 0) {
                $uploadedNoc = upload_file('noc_file', 'uploads/projects');
                $uploadedMap = upload_file('map_file', 'uploads/projects');
                $uploadedPlan = upload_file('master_plan_file', 'uploads/projects');
                db_exec("UPDATE projects SET name=?, developer=?, location=?, country_id=?, city_id=?, area_id=?, society_id=?, noc=?, description=?, status=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$name, $developer, $location, $country_id, $city_id, $area_id, $society_id, $noc, $description, $status, $id]);
                if ($uploadedNoc) db_exec("UPDATE projects SET noc_file = ? WHERE id = ?", [$uploadedNoc, $id]);
                if ($uploadedMap) db_exec("UPDATE projects SET map_file = ? WHERE id = ?", [$uploadedMap, $id]);
                if ($uploadedPlan) db_exec("UPDATE projects SET master_plan_file = ? WHERE id = ?", [$uploadedPlan, $id]);
                flash('success', 'Project updated successfully.');
            } else {
                $uploadedNoc = upload_file('noc_file', 'uploads/projects');
                $uploadedMap = upload_file('map_file', 'uploads/projects');
                $uploadedPlan = upload_file('master_plan_file', 'uploads/projects');
                db_exec("INSERT INTO projects (name, developer, location, country_id, city_id, area_id, society_id, noc, noc_file, map_file, master_plan_file, description, status, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$name, $developer, $location, $country_id, $city_id, $area_id, $society_id, $noc, $uploadedNoc, $uploadedMap, $uploadedPlan, $description, $status]);
                flash('success', 'Project added successfully.');
            }
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM projects WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Project deleted successfully.');
    }
    redirect('projects.php');
}

$records = db_all("SELECT p.*, c.name AS city_name, a.name AS area_name, s.name AS society_name,
                   (SELECT COUNT(*) FROM properties pr WHERE pr.project_id = p.id) AS prop_count,
                   (SELECT COUNT(*) FROM blocks b WHERE b.project_id = p.id) AS block_count
                   FROM projects p
                   LEFT JOIN cities c ON c.id = p.city_id
                   LEFT JOIN areas a ON a.id = p.area_id
                   LEFT JOIN societies s ON s.id = p.society_id
                   ORDER BY p.name");
$countries = db_all("SELECT * FROM countries ORDER BY name");
$cities = db_all("SELECT * FROM cities ORDER BY name");
$areas = db_all("SELECT * FROM areas ORDER BY name");
$societies = db_all("SELECT * FROM societies ORDER BY name");
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search projects...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Project"><i class="bi bi-plus-lg me-1"></i>Add Project</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Project</th><th>Location</th><th>Blocks</th><th>Properties</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <div class="fw-medium"><?= e($r['name']) ?></div>
                            <div class="small text-muted">Developer: <?= e($r['developer'] ?? '-') ?></div>
                        </td>
                        <td class="small">
                            <?= e($r['city_name'] ?? '') ?>
                            <?= $r['area_name'] ? '<div class="text-muted">' . e($r['area_name']) . '</div>' : '' ?>
                            <?= $r['society_name'] ? '<div class="text-muted">' . e($r['society_name']) . '</div>' : '' ?>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= $r['block_count'] ?></span></td>
                        <td><span class="badge bg-light text-dark border"><?= $r['prop_count'] ?></span></td>
                        <td><?= $r['status'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-primary" href="project_dashboard.php?id=<?= $r['id'] ?>" title="Dashboard"><i class="bi bi-speedometer2"></i></a>
                            <a class="btn btn-sm btn-outline-info" href="project_view.php?id=<?= $r['id'] ?>" title="Manage"><i class="bi bi-diagram-2"></i></a>
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'name' => $r['name'], 'developer' => $r['developer'], 'location' => $r['location'], 'country_id' => $r['country_id'], 'city_id' => $r['city_id'], 'area_id' => $r['area_id'], 'society_id' => $r['society_id'], 'noc' => $r['noc'], 'description' => $r['description'], 'status' => $r['status']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this project and all its blocks/properties? This cannot be undone.">
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
                    <tr><td colspan="7"><div class="empty-state"><i class="bi bi-grid-1x2"></i><p>No projects yet</p></div></td></tr>
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
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header"><h5 class="modal-title">Add Project</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Project Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Developer</label>
                            <input type="text" name="developer" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NOC Number</label>
                            <input type="text" name="noc" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Country</label>
                            <select name="country_id" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($countries as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <select name="city_id" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($cities as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Society</label>
                            <div class="input-group">
                                <select name="society_id" class="form-select">
                                    <option value="">Select</option>
                                    <?php foreach ($societies as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
                                </select>
                                <?php if ($quickAdd): ?>
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#quickModal" data-quick="society" title="Add Society"><i class="bi bi-plus-lg"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Area</label>
                            <div class="input-group">
                                <select name="area_id" class="form-select">
                                    <option value="">Select</option>
                                    <?php foreach ($areas as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
                                </select>
                                <?php if ($quickAdd): ?>
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#quickModal" data-quick="area" title="Add Area"><i class="bi bi-plus-lg"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">NOC File</label>
                            <input type="file" name="noc_file" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Map File</label>
                            <input type="file" name="map_file" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Master Plan File</label>
                            <input type="file" name="master_plan_file" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
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

<?php if ($quickAdd): ?>
<div class="modal fade" id="quickModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="quickForm">
                <div class="modal-header"><h5 class="modal-title" id="quickTitle">Add</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" id="quickType">
                    <div class="mb-3">
                        <label class="form-label">City</label>
                        <select id="quickCity" class="form-select" required>
                            <option value="">Select city</option>
                            <?php foreach ($cities as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="quickNameLabel">Name</label>
                        <input type="text" id="quickName" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('quickModal');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        var type = btn ? btn.getAttribute('data-quick') : '';
        if (type !== 'society' && type !== 'area') return;
        modal.querySelector('#quickType').value = type;
        modal.querySelector('#quickTitle').textContent = 'Add ' + (type === 'society' ? 'Society' : 'Area');
        modal.querySelector('#quickNameLabel').textContent = (type === 'society' ? 'Society' : 'Area') + ' Name';
        var citySel = document.querySelector('#recordModal select[name="city_id"]');
        modal.querySelector('#quickCity').value = citySel ? citySel.value : '';
    });

    document.getElementById('quickForm').addEventListener('submit', function (e) {
        e.preventDefault();
        var form = this;
        var type = modal.querySelector('#quickType').value;
        var city = modal.querySelector('#quickCity').value;
        var name = modal.querySelector('#quickName').value.trim();
        if (!city || !name) {
            alert('Select a city and enter a name.');
            return;
        }
        var data = new URLSearchParams();
        data.set('action', type === 'society' ? 'add_society' : 'add_area');
        data.set('city_id', city);
        data.set('name', name);
        data.set('csrf_token', modal.querySelector('#quickForm input[name="csrf_token"]').value);
        fetch('ajax.php', { method: 'POST', body: data, headers: { 'Content-Type': 'application/x-www-form-urlencoded' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.ok) { alert(res.error || 'Failed to save.'); return; }
                var selName = type === 'society' ? 'select[name="society_id"]' : 'select[name="area_id"]';
                var $sel = $('#recordModal').find(selName);
                if (!$sel.find('option[value="' + res.id + '"]').length) {
                    $sel.append($('<option>', { value: res.id, text: res.name }));
                }
                $sel.val(String(res.id));
                $('#quickModal').modal('hide');
                form.reset();
            });
    });
})();
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

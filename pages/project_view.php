<?php
require_once '../includes/auth.php';
require_login();
require_permission('projects.view');
$title = 'Project Details';
$active = 'projects';
$canEdit = has_permission('projects.manage');

$id = (int)($_GET['id'] ?? 0);
$project = db_get("SELECT p.*, c.name AS city_name, a.name AS area_name, s.name AS society_name
                   FROM projects p
                   LEFT JOIN cities c ON c.id = p.city_id
                   LEFT JOIN areas a ON a.id = p.area_id
                   LEFT JOIN societies s ON s.id = p.society_id
                   WHERE p.id = ?", [$id]);
if (!$project) {
    flash('danger', 'Project not found.');
    redirect('projects.php');
}

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'block_save') {
        $bid = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            flash('danger', 'Block name is required.');
        } elseif ($bid > 0) {
            db_exec("UPDATE blocks SET name = ?, updated_date = CURDATE(), updated_time = CURTIME() WHERE id = ? AND project_id = ?", [$name, $bid, $id]);
            flash('success', 'Block updated.');
        } else {
            db_exec("INSERT INTO blocks (project_id, name, created_date, created_time, updated_date, updated_time) VALUES (?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$id, $name]);
            flash('success', 'Block added.');
        }
    } elseif ($action === 'block_delete') {
        db_exec("DELETE FROM blocks WHERE id = ? AND project_id = ?", [(int)($_POST['id'] ?? 0), $id]);
        flash('success', 'Block deleted.');
    } elseif ($action === 'road_save') {
        $rid = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            flash('danger', 'Road name is required.');
        } elseif ($rid > 0) {
            db_exec("UPDATE roads SET name = ?, updated_date = CURDATE(), updated_time = CURTIME() WHERE id = ? AND project_id = ?", [$name, $rid, $id]);
            flash('success', 'Road updated.');
        } else {
            db_exec("INSERT INTO roads (project_id, name, created_date, created_time, updated_date, updated_time) VALUES (?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$id, $name]);
            flash('success', 'Road added.');
        }
    } elseif ($action === 'road_delete') {
        db_exec("DELETE FROM roads WHERE id = ? AND project_id = ?", [(int)($_POST['id'] ?? 0), $id]);
        flash('success', 'Road deleted.');
    } elseif ($action === 'street_save') {
        $sid = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $block_id = (int)($_POST['block_id'] ?? 0) ?: null;
        if ($name === '') {
            flash('danger', 'Street name is required.');
        } elseif ($sid > 0) {
            db_exec("UPDATE streets SET name = ?, block_id = ?, updated_date = CURDATE(), updated_time = CURTIME() WHERE id = ? AND project_id = ?", [$name, $block_id, $sid, $id]);
            flash('success', 'Street updated.');
        } else {
            db_exec("INSERT INTO streets (project_id, block_id, name, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$id, $block_id, $name]);
            flash('success', 'Street added.');
        }
    } elseif ($action === 'street_delete') {
        db_exec("DELETE FROM streets WHERE id = ? AND project_id = ?", [(int)($_POST['id'] ?? 0), $id]);
        flash('success', 'Street deleted.');
    } elseif ($action === 'image_upload') {
        $title = trim($_POST['title'] ?? '');
        $img = upload_file('image_file', 'uploads/projects');
        if ($img === false) {
            flash('danger', 'Image upload failed.');
        } else {
            db_exec("INSERT INTO project_images (project_id, image_file, title, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$id, $img, $title]);
            flash('success', 'Image uploaded.');
        }
    } elseif ($action === 'image_delete') {
        db_exec("DELETE FROM project_images WHERE id = ? AND project_id = ?", [(int)($_POST['id'] ?? 0), $id]);
        flash('success', 'Image deleted.');
    } elseif ($action === 'doc_upload') {
        $title = trim($_POST['title'] ?? '');
        $doc = upload_file('doc_file', 'uploads/projects');
        if ($doc === false) {
            flash('danger', 'Document upload failed.');
        } else {
            db_exec("INSERT INTO project_documents (project_id, title, file_path, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$id, $title, $doc]);
            flash('success', 'Document uploaded.');
        }
    } elseif ($action === 'doc_delete') {
        db_exec("DELETE FROM project_documents WHERE id = ? AND project_id = ?", [(int)($_POST['id'] ?? 0), $id]);
        flash('success', 'Document deleted.');
    }
    redirect('project_view.php?id=' . $id);
}

$blocks = db_all("SELECT * FROM blocks WHERE project_id = ? ORDER BY name", [$id]);
$roads = db_all("SELECT * FROM roads WHERE project_id = ? ORDER BY name", [$id]);
$streets = db_all("SELECT s.*, b.name AS block_name FROM streets s LEFT JOIN blocks b ON b.id = s.block_id WHERE s.project_id = ? ORDER BY s.name", [$id]);
$images = db_all("SELECT * FROM project_images WHERE project_id = ? ORDER BY id DESC", [$id]);
$docs = db_all("SELECT * FROM project_documents WHERE project_id = ? ORDER BY id DESC", [$id]);

$hasMiTable = (bool)(db_get("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'material_issues'")['c'] ?? 0);
$materialCost = $hasMiTable ? (float)db_get("SELECT COALESCE(SUM(mi.total_amount),0) amt FROM material_issues mi WHERE mi.project_id = ?", [$id])['amt'] : 0.0;
$contractorPayable = (float)db_get("SELECT COALESCE(SUM(ce.amount),0) amt FROM contractor_entries ce WHERE ce.project_id = ? AND ce.entry_type = 'payable'", [$id])['amt'];
$contractorPaid = (float)db_get("SELECT COALESCE(SUM(ce.amount),0) amt FROM contractor_entries ce WHERE ce.project_id = ? AND ce.entry_type = 'paid'", [$id])['amt'];
$totalInvestment = $materialCost + $contractorPaid;
$recentIssues = $hasMiTable ? db_all("SELECT mi.*, c.full_name AS contractor_name FROM material_issues mi LEFT JOIN contractors c ON c.id = mi.contractor_id WHERE mi.project_id = ? ORDER BY mi.issue_date DESC, mi.id DESC LIMIT 10", [$id]) : [];

include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="projects.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    <h5 class="mb-0"><?= e($project['name']) ?></h5>
    <?php if ($project['status']): ?><span class="badge bg-success">Active</span><?php else: ?><span class="badge bg-secondary">Inactive</span><?php endif; ?>
    <span class="ms-auto text-muted small">Developer: <?= e($project['developer'] ?? '-') ?> &bull; NOC: <?= e($project['noc'] ?? '-') ?></span>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-grid"></i></div><div><div class="stat-label">BLOCKS</div><div class="stat-value"><?= count($blocks) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-signpost"></i></div><div><div class="stat-label">ROADS</div><div class="stat-value"><?= count($roads) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-signpost-split"></i></div><div><div class="stat-label">STREETS</div><div class="stat-value"><?= count($streets) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-camera"></i></div><div><div class="stat-label">IMAGES</div><div class="stat-value"><?= count($images) ?></div></div></div></div></div>
</div>

<ul class="nav nav-pills mb-3" id="projectTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabInfo">Overview</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabBlocks">Blocks</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabRoads">Roads</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabStreets">Streets</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabImages">Images</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabDocs">Documents</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabCosting">Costing</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tabInfo">
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><div class="text-muted small">Location</div><div class="fw-medium"><?= e($project['location'] ?? '-') ?></div></div>
                    <div class="col-md-4"><div class="text-muted small">City</div><div class="fw-medium"><?= e($project['city_name'] ?? '-') ?></div></div>
                    <div class="col-md-4"><div class="text-muted small">Area</div><div class="fw-medium"><?= e($project['area_name'] ?? '-') ?></div></div>
                    <div class="col-md-4"><div class="text-muted small">Society</div><div class="fw-medium"><?= e($project['society_name'] ?? '-') ?></div></div>
                    <div class="col-md-4"><div class="text-muted small">Description</div><div class="fw-medium"><?= e($project['description'] ?? '-') ?></div></div>
                    <div class="col-md-4">
                        <div class="text-muted small">Files</div>
                        <div>
                            <?php if ($project['map_file']): ?><a href="<?= BASE_URL ?>/assets/<?= e($project['map_file']) ?>" target="_blank" class="badge bg-info text-decoration-none me-1"><i class="bi bi-map"></i> Map</a><?php endif; ?>
                            <?php if ($project['master_plan_file']): ?><a href="<?= BASE_URL ?>/assets/<?= e($project['master_plan_file']) ?>" target="_blank" class="badge bg-info text-decoration-none me-1"><i class="bi bi-file-earmark"></i> Master Plan</a><?php endif; ?>
                            <?php if ($project['noc_file']): ?><a href="<?= BASE_URL ?>/assets/<?= e($project['noc_file']) ?>" target="_blank" class="badge bg-info text-decoration-none"><i class="bi bi-file-earmark-check"></i> NOC</a><?php endif; ?>
                            <?php if (!$project['map_file'] && !$project['master_plan_file'] && !$project['noc_file']): ?><span class="text-muted small">-</span><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tabBlocks">
        <div class="card">
            <div class="card-body">
                <?php if ($canEdit): ?>
                <form method="post" class="row g-2 align-items-center mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="block_save">
                    <input type="hidden" name="id" id="blockId" value="">
                    <div class="col-md-4"><input type="text" name="name" id="blockName" class="form-control" placeholder="Block name (e.g. A, B, Executive)" required></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Add Block</button></div>
                </form>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>#</th><th>Block Name</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($blocks as $i => $b): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= e($b['name']) ?></td>
                                <td class="text-end">
                                    <?php if ($canEdit): ?>
                                    <button class="btn btn-sm btn-outline-primary" onclick="$('#blockId').val('<?= $b['id'] ?>');$('#blockName').val('<?= e($b['name'], ENT_QUOTES) ?>')"><i class="bi bi-pencil"></i></button>
                                    <form method="post" class="d-inline" data-confirm="Delete this block?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="block_delete">
                                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$blocks): ?><tr><td colspan="3" class="text-center text-muted py-4">No blocks yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tabRoads">
        <div class="card">
            <div class="card-body">
                <?php if ($canEdit): ?>
                <form method="post" class="row g-2 align-items-center mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="road_save">
                    <input type="hidden" name="id" id="roadId" value="">
                    <div class="col-md-4"><input type="text" name="name" id="roadName" class="form-control" placeholder="Road name (e.g. 100 ft Road)" required></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Add Road</button></div>
                </form>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>#</th><th>Road Name</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($roads as $i => $b): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= e($b['name']) ?></td>
                                <td class="text-end">
                                    <?php if ($canEdit): ?>
                                    <button class="btn btn-sm btn-outline-primary" onclick="$('#roadId').val('<?= $b['id'] ?>');$('#roadName').val('<?= e($b['name'], ENT_QUOTES) ?>')"><i class="bi bi-pencil"></i></button>
                                    <form method="post" class="d-inline" data-confirm="Delete this road?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="road_delete">
                                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$roads): ?><tr><td colspan="3" class="text-center text-muted py-4">No roads yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tabStreets">
        <div class="card">
            <div class="card-body">
                <?php if ($canEdit): ?>
                <form method="post" class="row g-2 align-items-center mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="street_save">
                    <input type="hidden" name="id" id="streetId" value="">
                    <div class="col-md-3">
                        <select name="block_id" class="form-select" id="streetBlock">
                            <option value="">All blocks</option>
                            <?php foreach ($blocks as $b): ?><option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3"><input type="text" name="name" id="streetName" class="form-control" placeholder="Street name" required></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Add Street</button></div>
                </form>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>#</th><th>Street Name</th><th>Block</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($streets as $i => $b): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= e($b['name']) ?></td>
                                <td><?= e($b['block_name'] ?? '-') ?></td>
                                <td class="text-end">
                                    <?php if ($canEdit): ?>
                                    <button class="btn btn-sm btn-outline-primary" onclick="$('#streetId').val('<?= $b['id'] ?>');$('#streetName').val('<?= e($b['name'], ENT_QUOTES) ?>');$('#streetBlock').val('<?= (int)$b['block_id'] ?>')"><i class="bi bi-pencil"></i></button>
                                    <form method="post" class="d-inline" data-confirm="Delete this street?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="street_delete">
                                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$streets): ?><tr><td colspan="4" class="text-center text-muted py-4">No streets yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tabImages">
        <div class="card">
            <div class="card-body">
                <?php if ($canEdit): ?>
                <form method="post" enctype="multipart/form-data" class="row g-2 align-items-center mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="image_upload">
                    <div class="col-md-4"><input type="file" name="image_file" class="form-control" accept="image/*" required></div>
                    <div class="col-md-4"><input type="text" name="title" class="form-control" placeholder="Title (optional)"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-upload"></i> Upload</button></div>
                </form>
                <?php endif; ?>
                <div class="row g-3">
                    <?php foreach ($images as $img): ?>
                    <div class="col-md-3 col-sm-4">
                        <div class="card">
                            <img src="<?= BASE_URL ?>/assets/<?= e($img['image_file']) ?>" class="card-img-top" style="height:140px;object-fit:cover">
                            <div class="card-body py-2 d-flex align-items-center justify-content-between">
                                <span class="small text-truncate"><?= e($img['title'] ?? 'Image') ?></span>
                                <?php if ($canEdit): ?>
                                <form method="post" data-confirm="Delete this image?" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="image_delete">
                                    <input type="hidden" name="id" value="<?= $img['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (!$images): ?><div class="col-12"><div class="empty-state"><i class="bi bi-camera"></i><p>No images yet</p></div></div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tabDocs">
        <div class="card">
            <div class="card-body">
                <?php if ($canEdit): ?>
                <form method="post" enctype="multipart/form-data" class="row g-2 align-items-center mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="doc_upload">
                    <div class="col-md-4"><input type="file" name="doc_file" class="form-control" required></div>
                    <div class="col-md-4"><input type="text" name="title" class="form-control" placeholder="Title" required></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-upload"></i> Upload</button></div>
                </form>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>#</th><th>Title</th><th>File</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($docs as $i => $d): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= e($d['title']) ?></td>
                                <td><a href="<?= BASE_URL ?>/assets/<?= e($d['file_path']) ?>" target="_blank"><i class="bi bi-file-earmark-arrow-down"></i> Download</a></td>
                                <td class="text-end">
                                    <?php if ($canEdit): ?>
                                    <form method="post" class="d-inline" data-confirm="Delete this document?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="doc_delete">
                                        <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$docs): ?><tr><td colspan="4" class="text-center text-muted py-4">No documents yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tabCosting">
        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-box-seam"></i></div><div><div class="stat-label">MATERIAL COST</div><div class="stat-value"><?= fmt_money($materialCost) ?></div></div></div></div></div>
            <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-person-workspace"></i></div><div><div class="stat-label">CONTRACTOR PAYABLE</div><div class="stat-value"><?= fmt_money($contractorPayable) ?></div></div></div></div></div>
            <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">CONTRACTOR PAID</div><div class="stat-value"><?= fmt_money($contractorPaid) ?></div></div></div></div></div>
            <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div><div><div class="stat-label">TOTAL INVESTMENT</div><div class="stat-value"><?= fmt_money($totalInvestment) ?></div></div></div></div></div>
        </div>

        <?php if ($recentIssues): ?>
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-box-seam me-2"></i>Recent Material Issues</span>
                <a href="material_issues.php?project_id=<?= $id ?>" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Issue No</th><th>Contractor</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                        <?php foreach ($recentIssues as $mi): ?>
                            <tr>
                                <td><?= fmt_date($mi['issue_date']) ?></td>
                                <td><a href="material_issue_view.php?id=<?= $mi['id'] ?>"><?= e($mi['issue_no']) ?></a></td>
                                <td><?= e($mi['contractor_name']) ?></td>
                                <td class="text-end"><?= fmt_money($mi['total_amount']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

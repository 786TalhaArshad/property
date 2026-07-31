<?php
require_once '../includes/auth.php';
require_login();
require_permission('documents.view');
$title = 'Documents';
$active = 'documents';
$canEdit = has_permission('documents.view');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $related_type = $_POST['related_type'] ?? 'general';
        $related_id = (int)($_POST['related_id'] ?? 0);
        $document_type_id = (int)($_POST['document_type_id'] ?? 0) ?: null;
        $title = trim($_POST['title'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        if ($title === '') {
            flash('danger', 'Document title is required.');
        } elseif ($id > 0) {
            $file_path = upload_file('document_file', 'uploads/documents');
            if ($file_path === false) {
                flash('danger', 'File upload failed. Allowed: PDF, DOC, images.');
                redirect('documents.php');
            }
            if ($file_path) {
                db_exec("UPDATE documents SET related_type=?, related_id=?, document_type_id=?, title=?, file_path=?, remarks=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$related_type, $related_id, $document_type_id, $title, $file_path, $remarks, $id]);
            } else {
                db_exec("UPDATE documents SET related_type=?, related_id=?, document_type_id=?, title=?, remarks=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$related_type, $related_id, $document_type_id, $title, $remarks, $id]);
            }
            flash('success', 'Document updated successfully.');
        } else {
            $file_path = upload_file('document_file', 'uploads/documents');
            if ($file_path === false) {
                flash('danger', 'File upload failed. Allowed: PDF, DOC, images.');
                redirect('documents.php');
            }
            if (!$file_path) {
                flash('danger', 'Please select a file to upload.');
                redirect('documents.php');
            }
            db_exec("INSERT INTO documents (related_type, related_id, document_type_id, title, file_path, remarks, uploaded_by, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$related_type, $related_id, $document_type_id, $title, $file_path, $remarks, $user['id']]);
            flash('success', 'Document uploaded successfully.');
        }
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM documents WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Document deleted successfully.');
    }
    redirect('documents.php');
}

$related = $_GET['related_type'] ?? '';
$records = db_all("SELECT d.*, dt.name AS doc_type_name, u.full_name AS uploader_name FROM documents d LEFT JOIN document_types dt ON dt.id = d.document_type_id LEFT JOIN users u ON u.id = d.uploaded_by WHERE (? = '' OR d.related_type = ?) ORDER BY d.id DESC", [$related, $related]);
$documentTypes = db_all("SELECT * FROM document_types ORDER BY name");
$relTypes = ['customer' => 'Customer', 'owner' => 'Owner', 'dealer' => 'Dealer', 'property' => 'Property', 'project' => 'Project', 'booking' => 'Booking', 'tenant' => 'Tenant', 'agreement' => 'Agreement', 'lead' => 'Lead', 'general' => 'General'];
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search documents...">
    </div>
    <select class="form-select form-select-sm" style="max-width:170px" onchange="location.href='?related_type='+this.value">
        <option value="">All Types</option>
        <?php foreach ($relTypes as $v => $lbl): ?>
            <option value="<?= $v ?>" <?= $related === $v ? 'selected' : '' ?>><?= $lbl ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal"><i class="bi bi-plus-lg me-1"></i>Upload Document</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Title</th><th>Type</th><th>Related To</th><th>Doc Type</th><th>Remarks</th><th>Uploaded By</th><th>File</th><?php if ($canEdit): ?><th class="text-end">Actions</th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-medium"><?= e($r['title']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= e($relTypes[$r['related_type']] ?? $r['related_type']) ?></span></td>
                        <td class="small">ID: <?= (int)$r['related_id'] ?></td>
                        <td><?= e($r['doc_type_name'] ?? '-') ?></td>
                        <td class="small"><?= e($r['remarks'] ?? '-') ?></td>
                        <td class="small"><?= e($r['uploader_name'] ?? '-') ?></td>
                        <td><a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?= BASE_URL ?>/assets/<?= e($r['file_path']) ?>"><i class="bi bi-download"></i></a></td>
                        <?php if ($canEdit): ?>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'related_type' => $r['related_type'], 'related_id' => $r['related_id'], 'document_type_id' => $r['document_type_id'], 'title' => $r['title'], 'remarks' => $r['remarks']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this document?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$records): ?>
                    <tr><td colspan="9"><div class="empty-state"><i class="bi bi-folder2-open"></i><p>No documents uploaded</p></div></td></tr>
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
                <div class="modal-header"><h5 class="modal-title">Upload Document</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Related To</label>
                            <select name="related_type" class="form-select">
                                <?php foreach ($relTypes as $v => $lbl): ?><option value="<?= $v ?>"><?= $lbl ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Related ID</label>
                            <input type="number" name="related_id" class="form-control" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Document Type</label>
                            <select name="document_type_id" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($documentTypes as $dt): ?><option value="<?= $dt['id'] ?>"><?= e($dt['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">File</label>
                            <input type="file" name="document_file" class="form-control" required>
                            <div class="form-text">PDF, DOC, JPG, PNG.</div>
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

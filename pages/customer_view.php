<?php
require_once '../includes/auth.php';
require_login();
require_permission('customers.view');
$title = 'Customer Details';
$active = 'customers';
$canEdit = has_permission('customers.manage');

$id = (int)($_GET['id'] ?? 0);
$cust = db_get("SELECT c.*, ci.name AS city_name FROM customers c LEFT JOIN cities ci ON ci.id = c.city_id WHERE c.id = ?", [$id]);
if (!$cust) {
    flash('danger', 'Customer not found.');
    redirect('customers.php');
}

$ledgerStart = trim($_GET['start_date'] ?? '');
$ledgerEnd = trim($_GET['end_date'] ?? '');
$project_id = (int)($_GET['project_id'] ?? active_project_id());
$property_id = (int)($_GET['property_id'] ?? 0);

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'doc_upload') {
        $title = trim($_POST['title'] ?? '');
        $doc_type_id = (int)($_POST['document_type_id'] ?? 0) ?: null;
        $doc = upload_file('doc_file', 'uploads/documents');
        if ($doc === false) {
            flash('danger', 'Document upload failed.');
        } else {
            db_exec("INSERT INTO documents (related_type, related_id, document_type_id, title, file_path, uploaded_by, created_date, created_time, updated_date, updated_time) VALUES ('customer',?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$id, $doc_type_id, $title, $doc, $user['id']]);
            flash('success', 'Document uploaded.');
        }
    } elseif ($action === 'doc_delete') {
        db_exec("DELETE FROM documents WHERE id = ? AND related_type = 'customer' AND related_id = ?", [(int)($_POST['id'] ?? 0), $id]);
        flash('success', 'Document deleted.');
    }
    redirect('customer_view.php?id=' . $id);
}

$bookings = db_all("SELECT b.*, p.property_no, p.project_id, pt.name AS type_name, pj.name AS project_name FROM bookings b JOIN properties p ON p.id = b.property_id LEFT JOIN property_types pt ON pt.id = p.property_type_id LEFT JOIN projects pj ON pj.id = p.project_id WHERE b.customer_id = ? AND b.status <> 'cancelled' ORDER BY b.id DESC", [$id]);
$custProjects = db_all("SELECT pj.id, pj.name, pj.status, COUNT(DISTINCT b.id) AS bookings_count, COALESCE(SUM(b.total_price - b.discount),0) AS booked_value
                        FROM bookings b
                        JOIN properties pr ON pr.id = b.property_id
                        JOIN projects pj ON pj.id = pr.project_id
                        WHERE b.customer_id = ? AND b.status <> 'cancelled'
                        GROUP BY pj.id, pj.name, pj.status
                        ORDER BY pj.name", [$id]);
$custProperties = db_all("SELECT pr.id, pr.property_no, pr.project_id, pt.name AS type_name, pj.name AS project_name
                          FROM bookings b
                          JOIN properties pr ON pr.id = b.property_id
                          LEFT JOIN property_types pt ON pt.id = pr.property_type_id
                          LEFT JOIN projects pj ON pj.id = pr.project_id
                          WHERE b.customer_id = ? AND b.status <> 'cancelled' AND (? = 0 OR pr.project_id = ?)
                          ORDER BY pj.name, pr.property_no", [$id, $project_id, $project_id]);
$custPropertyIds = array_map('intval', array_column($custProperties, 'id'));
if ($property_id > 0 && !in_array($property_id, $custPropertyIds)) {
    $property_id = 0;
}
$docs = db_all("SELECT d.*, dt.name AS doc_type FROM documents d LEFT JOIN document_types dt ON dt.id = d.document_type_id WHERE d.related_type = 'customer' AND d.related_id = ? ORDER BY d.id DESC", [$id]);
$docTypes = db_all("SELECT * FROM document_types ORDER BY name");

$totalBooked = 0.0;
$totalPaid = 0.0;
foreach ($bookings as $b) {
    $totalBooked += (float)$b['total_price'] - (float)$b['discount'];
}
$paidRows = db_all("SELECT r.receipt_date, r.receipt_no, r.amount, r.project_id,
                    COALESCE(pr.project_id, r.project_id, 0) AS eff_project_id,
                    COALESCE(b.property_id, 0) AS booking_property_id,
                    pj.name AS project_name
                    FROM receipts r
                    LEFT JOIN bookings b ON b.id = r.booking_id
                    LEFT JOIN properties pr ON pr.id = b.property_id
                    LEFT JOIN projects pj ON pj.id = COALESCE(pr.project_id, r.project_id)
                    WHERE r.customer_id = ?
                    ORDER BY r.receipt_date, r.id", [$id]);
foreach ($paidRows as $pr) {
    $totalPaid += (float)$pr['amount'];
}
$transfers = db_all("SELECT t.*, fc.full_name AS from_name, tc.full_name AS to_name,
                     COALESCE(pr.project_id, t.project_id, 0) AS eff_project_id,
                     COALESCE(b.property_id, 0) AS booking_property_id,
                     pj.name AS project_name
                     FROM transfers t
                     LEFT JOIN customers fc ON fc.id = t.from_customer_id
                     LEFT JOIN customers tc ON tc.id = t.to_customer_id
                     LEFT JOIN bookings b ON b.id = t.booking_id
                     LEFT JOIN properties pr ON pr.id = b.property_id
                     LEFT JOIN projects pj ON pj.id = COALESCE(pr.project_id, t.project_id)
                     WHERE t.transfer_type = 'customer_to_customer' AND (t.from_customer_id = ? OR t.to_customer_id = ?)
                     ORDER BY t.transfer_date, t.id", [$id, $id]);
$outstanding = $totalBooked - $totalPaid;
foreach ($transfers as $t) {
    if ($t['to_customer_id'] == $id) $outstanding += (float)$t['amount'];
    if ($t['from_customer_id'] == $id) $outstanding -= (float)$t['amount'];
}

$ledBookings = [];
foreach ($bookings as $b) {
    if ($ledgerStart !== '' && $b['booking_date'] < $ledgerStart) continue;
    if ($ledgerEnd !== '' && $b['booking_date'] > $ledgerEnd) continue;
    if ($project_id > 0 && (int)$b['project_id'] !== $project_id) continue;
    if ($property_id > 0 && (int)$b['property_id'] !== $property_id) continue;
    $ledBookings[] = $b;
}
$ledPayments = [];
foreach ($paidRows as $pr) {
    if ($ledgerStart !== '' && $pr['receipt_date'] < $ledgerStart) continue;
    if ($ledgerEnd !== '' && $pr['receipt_date'] > $ledgerEnd) continue;
    if ($project_id > 0 && (int)$pr['eff_project_id'] !== $project_id) continue;
    if ($property_id > 0 && (int)$pr['booking_property_id'] !== $property_id) continue;
    $ledPayments[] = $pr;
}
$ledTransfers = [];
foreach ($transfers as $t) {
    if ($ledgerStart !== '' && $t['transfer_date'] < $ledgerStart) continue;
    if ($ledgerEnd !== '' && $t['transfer_date'] > $ledgerEnd) continue;
    if ($project_id > 0 && (int)$t['eff_project_id'] !== $project_id) continue;
    if ($property_id > 0 && (int)$t['booking_property_id'] !== $property_id) continue;
    $ledTransfers[] = $t;
}
$openingBalance = 0.0;
if ($ledgerStart !== '') {
    $ob = (float)db_get("SELECT COALESCE(SUM(b.total_price - b.discount),0) amt FROM bookings b
                         JOIN properties pr ON pr.id = b.property_id
                         WHERE b.customer_id = ? AND b.status <> 'cancelled' AND b.booking_date < ?
                         AND (? = 0 OR pr.project_id = ?) AND (? = 0 OR b.property_id = ?)",
        [$id, $ledgerStart, $project_id, $project_id, $property_id, $property_id])['amt'];
    $op = (float)db_get("SELECT COALESCE(SUM(r.amount),0) amt FROM receipts r
                         LEFT JOIN bookings b ON b.id = r.booking_id
                         LEFT JOIN properties pr ON pr.id = b.property_id
                         WHERE r.customer_id = ? AND r.receipt_date < ?
                         AND (? = 0 OR COALESCE(pr.project_id, r.project_id) = ?)
                         AND (? = 0 OR COALESCE(b.property_id, 0) = ?)",
        [$id, $ledgerStart, $project_id, $project_id, $property_id, $property_id])['amt'];
    $ot = db_get("SELECT COALESCE(SUM(CASE WHEN to_customer_id = ? THEN amount WHEN from_customer_id = ? THEN -amount ELSE 0 END),0) amt
                  FROM transfers t
                  LEFT JOIN bookings b ON b.id = t.booking_id
                  LEFT JOIN properties pr ON pr.id = b.property_id
                  WHERE t.transfer_type = 'customer_to_customer' AND (t.from_customer_id = ? OR t.to_customer_id = ?) AND t.transfer_date < ?
                  AND (? = 0 OR COALESCE(pr.project_id, t.project_id) = ?)
                  AND (? = 0 OR COALESCE(b.property_id, 0) = ?)",
        [$id, $id, $id, $id, $ledgerStart, $project_id, $project_id, $property_id, $property_id]);
    $openingBalance = $ob - $op + (float)$ot['amt'];
}

include '../includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <a href="customers.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <h5 class="mb-0"><?= e($cust['full_name']) ?></h5>
    <?php if ($cust['status']): ?><span class="badge bg-success">Active</span><?php else: ?><span class="badge bg-secondary">Inactive</span><?php endif; ?>
    <?php if ($canEdit): ?>
    <a href="customers.php" class="btn btn-outline-primary btn-sm ms-auto">Edit Profile</a>
    <?php endif; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-journal-check"></i></div><div><div class="stat-label">BOOKINGS</div><div class="stat-value"><?= count($bookings) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-journal-text"></i></div><div><div class="stat-label">BOOKED VALUE</div><div class="stat-value"><?= fmt_money($totalBooked) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-label">PAID</div><div class="stat-value"><?= fmt_money($totalPaid) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card <?= $outstanding > 0 ? 'bg-grad-red' : 'bg-grad-cyan' ?>"><div class="stat-body"><div class="stat-icon"><i class="bi bi-exclamation-circle"></i></div><div><div class="stat-label">OUTSTANDING</div><div class="stat-value"><?= fmt_money($outstanding) ?></div></div></div></div></div>
</div>

<ul class="nav nav-pills mb-3">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#cProfile">Profile</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#cBookings">Bookings (<?= count($bookings) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#cProjects">Projects (<?= count($custProjects) ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#cLedger">Ledger</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#cDocs">Documents (<?= count($docs) ?>)</button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="cProfile">
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3"><div class="text-muted small">Customer No</div><div class="fw-medium"><?= e($cust['customer_no']) ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">CNIC</div><div class="fw-medium"><?= e($cust['cnic'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Passport</div><div class="fw-medium"><?= e($cust['passport_no'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">City</div><div class="fw-medium"><?= e($cust['city_name'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Phone</div><div class="fw-medium"><?= e($cust['phone'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">WhatsApp</div><div class="fw-medium"><?= e($cust['whatsapp'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Email</div><div class="fw-medium"><?= e($cust['email'] ?? '-') ?></div></div>
                    <div class="col-md-3"><div class="text-muted small">Address</div><div class="fw-medium"><?= e($cust['address'] ?? '-') ?></div></div>
                    <div class="col-md-4"><div class="text-muted small">Nominee</div><div class="fw-medium"><?= e($cust['nominee_name'] ?? '-') ?> <?= $cust['nominee_relation'] ? '(' . e($cust['nominee_relation']) . ')' : '' ?></div></div>
                    <div class="col-md-4"><div class="text-muted small">Nominee CNIC</div><div class="fw-medium"><?= e($cust['nominee_cnic'] ?? '-') ?></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="cBookings">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Booking</th><th>Property</th><th>Type</th><th>Date</th><th>Total</th><th>Discount</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td><a href="booking_view.php?id=<?= $b['id'] ?>"><?= e($b['booking_no']) ?></a></td>
                                <td><?= e($b['property_no']) ?></td>
                                <td><?= e($b['type_name'] ?? '-') ?></td>
                                <td><?= fmt_date($b['booking_date']) ?></td>
                                <td><?= fmt_money($b['total_price']) ?></td>
                                <td><?= fmt_money($b['discount']) ?></td>
                                <td><?= status_badge($b['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$bookings): ?><tr><td colspan="7" class="text-center text-muted py-4">No bookings yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="cProjects">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Project</th><th>Bookings</th><th>Booked Value</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($custProjects as $pj): ?>
                            <tr>
                                <td><a href="project_view.php?id=<?= $pj['id'] ?>"><?= e($pj['name']) ?></a></td>
                                <td><?= (int)$pj['bookings_count'] ?></td>
                                <td><?= fmt_money($pj['booked_value']) ?></td>
                                <td class="text-end">
                                    <a href="customer_view.php?id=<?= $id ?>&project_id=<?= $pj['id'] ?>#cLedger" class="btn btn-sm btn-outline-primary"><i class="bi bi-book"></i> Ledger</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$custProjects): ?><tr><td colspan="4" class="text-center text-muted py-4">No projects yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="cLedger">
        <div class="card">
            <div class="card-body">
                <form method="get" action="customer_view.php#cLedger" class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <select name="project_id" class="form-select form-select-sm" style="max-width:220px">
                        <option value="0">All Projects</option>
                        <?php foreach ($custProjects as $pj): ?>
                            <option value="<?= $pj['id'] ?>" <?= $project_id === (int)$pj['id'] ? 'selected' : '' ?>><?= e($pj['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="property_id" class="form-select form-select-sm" style="max-width:220px">
                        <option value="0">All Properties</option>
                        <?php foreach ($custProperties as $pp): ?>
                            <option value="<?= $pp['id'] ?>" <?= $property_id === (int)$pp['id'] ? 'selected' : '' ?>><?= e($pp['project_name'] ?? '') ?><?= ($pp['project_name'] ?? '') !== '' ? ' - ' : '' ?><?= e($pp['property_no']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="input-group input-group-sm" style="max-width:170px">
                        <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                        <input type="date" name="start_date" class="form-control" value="<?= e($ledgerStart) ?>">
                    </div>
                    <div class="input-group input-group-sm" style="max-width:170px">
                        <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                        <input type="date" name="end_date" class="form-control" value="<?= e($ledgerEnd) ?>">
                    </div>
                    <button class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="customer_view.php?id=<?= $id ?>#cLedger" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
                    <a href="customer_ledger_print.php?id=<?= $id ?>&project_id=<?= $project_id ?>&property_id=<?= $property_id ?>&start_date=<?= e($ledgerStart) ?>&end_date=<?= e($ledgerEnd) ?>" class="btn btn-outline-secondary btn-sm ms-auto" target="_blank"><i class="bi bi-printer me-1"></i> Print</a>
                </form>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Description</th><th>Project</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead>
                        <tbody>
                        <?php
                        $balance = $openingBalance;
                        if ($ledgerStart !== '' || $ledgerEnd !== '') {
                            echo '<tr><td>' . fmt_date($ledgerStart) . '</td><td>Opening Balance</td><td>-</td><td>-</td><td>-</td><td>' . fmt_money($balance) . '</td></tr>';
                        }
                        foreach ($ledBookings as $b) {
                            $balance += (float)$b['total_price'] - (float)$b['discount'];
                            echo '<tr><td>' . fmt_date($b['booking_date']) . '</td><td>Booking ' . e($b['booking_no']) . ' - ' . e($b['property_no']) . '</td><td>' . e($b['project_name'] ?? '-') . '</td><td>' . fmt_money((float)$b['total_price'] - (float)$b['discount']) . '</td><td>-</td><td>' . fmt_money($balance) . '</td></tr>';
                        }
                        foreach ($ledTransfers as $t) {
                            if ($t['to_customer_id'] == $id) {
                                $balance += (float)$t['amount'];
                                echo '<tr><td>' . fmt_date($t['transfer_date']) . '</td><td>Transfer from ' . e($t['from_name'] ?? '-') . ' <a class="small" href="transfers.php">(' . e($t['transfer_no']) . ')</a></td><td>' . e($t['project_name'] ?? '-') . '</td><td>' . fmt_money((float)$t['amount']) . '</td><td>-</td><td>' . fmt_money($balance) . '</td></tr>';
                            } else {
                                $balance -= (float)$t['amount'];
                                echo '<tr><td>' . fmt_date($t['transfer_date']) . '</td><td>Transfer to ' . e($t['to_name'] ?? '-') . ' <a class="small" href="transfers.php">(' . e($t['transfer_no']) . ')</a></td><td>' . e($t['project_name'] ?? '-') . '</td><td>-</td><td>' . fmt_money((float)$t['amount']) . '</td><td>' . fmt_money($balance) . '</td></tr>';
                            }
                        }
                        foreach ($ledPayments as $pr) {
                            $balance -= (float)$pr['amount'];
                            echo '<tr><td>' . fmt_date($pr['receipt_date']) . '</td><td>Payment ' . e($pr['receipt_no']) . '</td><td>' . e($pr['project_name'] ?? '-') . '</td><td>-</td><td>' . fmt_money((float)$pr['amount']) . '</td><td>' . fmt_money($balance) . '</td></tr>';
                        }
                        if (!$ledBookings && !$ledTransfers && !$ledPayments && $ledgerStart === '' && $ledgerEnd === '') {
                            echo '<tr><td colspan="6" class="text-center text-muted py-4">No ledger entries yet</td></tr>';
                        }
                        ?>
                        </tbody>
                        <tfoot>
                        <tr class="table-light">
                            <td colspan="5" class="text-end fw-bold"><?= ($ledgerStart !== '' || $ledgerEnd !== '') ? 'Closing Balance' : 'Outstanding Balance' ?></td>
                            <td class="fw-bold"><?= fmt_money($balance) ?></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="cDocs">
        <div class="card">
            <div class="card-body">
                <?php if ($canEdit): ?>
                <form method="post" enctype="multipart/form-data" class="row g-2 align-items-center mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="doc_upload">
                    <div class="col-md-3"><input type="file" name="doc_file" class="form-control" required></div>
                    <div class="col-md-3"><input type="text" name="title" class="form-control" placeholder="Title" required></div>
                    <div class="col-md-3">
                        <select name="document_type_id" class="form-select">
                            <option value="">Doc type</option>
                            <?php foreach ($docTypes as $dt): ?><option value="<?= $dt['id'] ?>"><?= e($dt['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-upload"></i> Upload</button></div>
                </form>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>#</th><th>Title</th><th>Type</th><th>File</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($docs as $i => $d): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= e($d['title']) ?></td>
                                <td><?= e($d['doc_type'] ?? '-') ?></td>
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
                        <?php if (!$docs): ?><tr><td colspan="5" class="text-center text-muted py-4">No documents yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    if (location.hash) {
        $('.nav-pills .nav-link[data-bs-target="' + location.hash.replace(/[^a-zA-Z0-9_#]/g, '') + '"]').tab('show');
    }
});
</script>

<?php include '../includes/footer.php'; ?>

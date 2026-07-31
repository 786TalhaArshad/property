<?php
require_once '../includes/auth.php';
require_login();
require_permission('notifications.view');
$title = 'Notifications';
$active = 'notifications';
$canEdit = has_permission('notifications.view');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'generate') {
        $today = date('Y-m-d');
        $in7 = date('Y-m-d', strtotime('+7 days'));
        $in30 = date('Y-m-d', strtotime('+30 days'));

        $inst = db_all("SELECT i.*, b.booking_no, c.full_name FROM installments i JOIN bookings b ON b.id = i.booking_id JOIN customers c ON c.id = b.customer_id WHERE i.status IN ('pending','partial') AND i.due_date BETWEEN ? AND ? ORDER BY i.due_date", [$today, $in7]);
        foreach ($inst as $x) {
            $exists = db_get("SELECT id FROM notifications WHERE notification_type = 'installment' AND message LIKE ?", ['%' . $x['booking_no'] . '%'])['id'] ?? 0;
            if (!$exists) {
                db_exec("INSERT INTO notifications (notification_type, channel, title, message, recipient_type, recipient_id, scheduled_date, status, created_date, created_time, updated_date, updated_time) VALUES ('installment','system',?,?,?,?,?, 'pending',CURDATE(),CURTIME(),CURDATE(),CURTIME())", ['Installment Due', 'Installment for ' . $x['booking_no'] . ' (' . $x['full_name'] . ') due on ' . $x['due_date'] . ' - Amount: ' . $x['amount'], 'customer', $x['booking_id'], $x['due_date']]);
            }
        }

        $rent = db_all("SELECT rs.*, ra.agreement_no, p.property_no FROM rent_schedule rs JOIN rental_agreements ra ON ra.id = rs.agreement_id JOIN properties p ON p.id = ra.property_id WHERE rs.status IN ('pending','partial') AND rs.due_date BETWEEN ? AND ? ORDER BY rs.due_date", [$today, $in7]);
        foreach ($rent as $x) {
            $exists = db_get("SELECT id FROM notifications WHERE notification_type = 'rent' AND message LIKE ?", ['%' . $x['agreement_no'] . '%'])['id'] ?? 0;
            if (!$exists) {
                db_exec("INSERT INTO notifications (notification_type, channel, title, message, recipient_type, recipient_id, scheduled_date, status, created_date, created_time, updated_date, updated_time) VALUES ('rent','system',?,?,?,?,?, 'pending',CURDATE(),CURTIME(),CURDATE(),CURTIME())", ['Rent Due', 'Rent for ' . $x['property_no'] . ' (' . $x['agreement_no'] . ') due on ' . $x['due_date'] . ' - Amount: ' . $x['rent_amount'], 'agreement', $x['agreement_id'], $x['due_date']]);
            }
        }

        $agr = db_all("SELECT ra.*, c.full_name FROM rental_agreements ra JOIN customers c ON c.id = ra.tenant_id WHERE ra.status IN ('active','renewed') AND ra.end_date BETWEEN ? AND ? ORDER BY ra.end_date", [$today, $in30]);
        foreach ($agr as $x) {
            $exists = db_get("SELECT id FROM notifications WHERE notification_type = 'agreement' AND message LIKE ?", ['%' . $x['agreement_no'] . '%'])['id'] ?? 0;
            if (!$exists) {
                db_exec("INSERT INTO notifications (notification_type, channel, title, message, recipient_type, recipient_id, scheduled_date, status, created_date, created_time, updated_date, updated_time) VALUES ('agreement','system',?,?,?,?,?, 'pending',CURDATE(),CURTIME(),CURDATE(),CURTIME())", ['Agreement Expiring', 'Rental agreement ' . $x['agreement_no'] . ' (' . $x['full_name'] . ') expires on ' . $x['end_date'], 'agreement', $x['id'], $x['end_date']]);
            }
        }

        $leads = db_all("SELECT * FROM leads WHERE status NOT IN ('converted','lost') AND next_follow_up BETWEEN ? AND ? ORDER BY next_follow_up", [$today, $in7]);
        foreach ($leads as $x) {
            $exists = db_get("SELECT id FROM notifications WHERE notification_type = 'lead' AND message LIKE ?", ['%' . $x['lead_no'] . '%'])['id'] ?? 0;
            if (!$exists) {
                db_exec("INSERT INTO notifications (notification_type, channel, title, message, recipient_type, recipient_id, scheduled_date, status, created_date, created_time, updated_date, updated_time) VALUES ('lead','system',?,?,?,?,?, 'pending',CURDATE(),CURTIME(),CURDATE(),CURTIME())", ['Lead Follow Up', 'Lead ' . $x['lead_no'] . ' (' . $x['name'] . ') needs follow up by ' . $x['next_follow_up'], 'lead', $x['id'], $x['next_follow_up']]);
            }
        }
        flash('success', 'Reminders generated for upcoming due dates.');
    } elseif ($action === 'mark_sent') {
        db_exec("UPDATE notifications SET status = 'sent', updated_date=CURDATE(), updated_time=CURTIME() WHERE status = 'pending'");
        flash('success', 'All pending notifications marked as sent.');
    } elseif ($action === 'delete') {
        db_exec("DELETE FROM notifications WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Notification deleted.');
    }
    redirect('notifications.php');
}

$records = db_all("SELECT * FROM notifications ORDER BY scheduled_date DESC, id DESC LIMIT 200");
$typeIcons = ['installment' => 'bi-cash-coin text-success', 'rent' => 'bi-house-heart text-primary', 'agreement' => 'bi-file-earmark-check text-warning', 'lead' => 'bi-person-lines-fill text-info', 'general' => 'bi-bell text-secondary'];
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search notifications...">
    </div>
    <?php if ($canEdit): ?>
    <form method="post" class="ms-auto d-flex gap-2">
        <?= csrf_field() ?>
        <button class="btn btn-primary btn-sm" name="action" value="generate"><i class="bi bi-magic me-1"></i>Generate Reminders</button>
        <button class="btn btn-outline-success btn-sm" name="action" value="mark_sent"><i class="bi bi-check2-all me-1"></i>Mark All Sent</button>
    </form>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Type</th><th>Title</th><th>Message</th><th>Scheduled</th><th>Status</th><?php if ($canEdit): ?><th class="text-end">Actions</th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><i class="bi <?= $typeIcons[$r['notification_type']] ?? $typeIcons['general'] ?>"></i></td>
                        <td class="fw-medium"><?= e($r['title']) ?></td>
                        <td class="small"><?= e($r['message']) ?></td>
                        <td><?= fmt_date($r['scheduled_date']) ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <?php if ($canEdit): ?>
                        <td class="text-end">
                            <form method="post" class="d-inline" data-confirm="Delete this notification?">
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
                    <tr><td colspan="7"><div class="empty-state"><i class="bi bi-bell"></i><p>No notifications. Click "Generate Reminders" to scan for due items.</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Transfers & Withdrawals';
$active = 'transfers';
$canEdit = has_permission('accounting.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $tid = (int)($_POST['id'] ?? 0);
        $rec = db_get("SELECT * FROM transfers WHERE id = ?", [$tid]);
        if ($rec) {
            if ($rec['transfer_type'] === 'customer_withdraw') {
                flash('danger', 'A booking withdraw cannot be deleted.');
            } else {
                if ($rec['voucher_id']) {
                    db_exec("DELETE FROM vouchers WHERE id = ?", [$rec['voucher_id']]);
                }
                db_exec("DELETE FROM transfers WHERE id = ?", [$tid]);
                flash('success', 'Transfer deleted.');
            }
        }
    }
    redirect('transfers.php');
}

$records = db_all("SELECT t.*, fc.full_name AS from_name, tc.full_name AS to_name, fbk.name AS from_bank, tbk.name AS to_bank, bk.booking_no, u.full_name AS created_name
                   FROM transfers t
                   LEFT JOIN customers fc ON fc.id = t.from_customer_id
                   LEFT JOIN customers tc ON tc.id = t.to_customer_id
                   LEFT JOIN banks fbk ON fbk.id = t.from_bank_id
                   LEFT JOIN banks tbk ON tbk.id = t.to_bank_id
                   LEFT JOIN bookings bk ON bk.id = t.booking_id
                   LEFT JOIN users u ON u.id = t.created_by
                   ORDER BY t.transfer_date DESC, t.id DESC");
$typeLabels = [
    'customer_to_customer' => ['Customer to Customer', 'primary'],
    'bank_to_cash' => ['Bank to Cash', 'success'],
    'bank_to_bank' => ['Bank to Bank', 'info'],
    'customer_withdraw' => ['Customer Booking Withdraw', 'danger'],
    'owner_withdraw' => ['Owner / Partner Withdraw', 'warning'],
];
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search transfers...">
    </div>
    <?php if ($canEdit): ?>
    <a class="btn btn-primary ms-auto" href="transfer_form.php"><i class="bi bi-plus-lg me-1"></i>New Transfer / Withdraw</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Transfer</th><th>Date</th><th>Type</th><th>From</th><th>To</th><th>Amount</th><th>Narration</th><th>Created By</th><?php if ($canEdit): ?><th class="text-end">Actions</th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-medium"><?= e($r['transfer_no']) ?><?= $r['voucher_id'] ? ' <a class="small" href="vouchers.php" title="Journal voucher created"><i class="bi bi-journal-arrow-up text-muted"></i></a>' : '' ?></td>
                        <td><?= fmt_date($r['transfer_date']) ?></td>
                        <td><span class="badge bg-<?= $typeLabels[$r['transfer_type']][1] ?? 'secondary' ?>"><?= e($typeLabels[$r['transfer_type']][0] ?? $r['transfer_type']) ?></span></td>
                        <td class="small">
                            <?php
                            $from = '-';
                            if ($r['transfer_type'] === 'customer_to_customer') $from = e($r['from_name'] ?? '-');
                            elseif ($r['transfer_type'] === 'bank_to_cash') $from = e($r['from_bank'] ?? '-');
                            elseif ($r['transfer_type'] === 'bank_to_bank') $from = e($r['from_bank'] ?? '-');
                            elseif ($r['transfer_type'] === 'customer_withdraw') $from = e($r['from_name'] ?? '-');
                            elseif ($r['transfer_type'] === 'owner_withdraw') $from = 'Capital / Equity';
                            echo $from;
                            ?>
                        </td>
                        <td class="small">
                            <?php
                            $to = '-';
                            if ($r['transfer_type'] === 'customer_to_customer') $to = e($r['to_name'] ?? '-');
                            elseif ($r['transfer_type'] === 'bank_to_cash') $to = 'Cash';
                            elseif ($r['transfer_type'] === 'bank_to_bank') $to = e($r['to_bank'] ?? '-');
                            elseif ($r['transfer_type'] === 'customer_withdraw') $to = e($r['booking_no'] ?? '-');
                            elseif ($r['transfer_type'] === 'owner_withdraw') $to = 'Cash / Bank';
                            echo $to;
                            ?>
                        </td>
                        <td class="text-nowrap"><?= fmt_money($r['amount']) ?></td>
                        <td class="small"><?= e($r['narration'] ?? '-') ?></td>
                        <td class="small"><?= e($r['created_name'] ?? '-') ?></td>
                        <?php if ($canEdit): ?>
                        <td class="text-end">
                            <?php if ($r['transfer_type'] !== 'customer_withdraw'): ?>
                            <a class="btn btn-sm btn-outline-primary" href="transfer_form.php?id=<?= $r['id'] ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form method="post" class="d-inline" data-confirm="Delete this transfer?<?= $r['voucher_id'] ? ' The linked journal voucher will also be removed.' : '' ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$records): ?>
                    <tr><td colspan="10"><div class="empty-state"><i class="bi bi-arrow-left-right"></i><p>No transfers yet</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

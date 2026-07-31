<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Chart of Accounts';
$active = 'chart_of_accounts';
$canEdit = has_permission('accounting.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $code = trim($_POST['code'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $account_type = $_POST['account_type'] ?? 'asset';
        $parent_id = (int)($_POST['parent_id'] ?? 0) ?: null;
        $opening_balance = (float)($_POST['opening_balance'] ?? 0);

        if ($code === '' || $name === '') {
            flash('danger', 'Code and name are required.');
        } elseif ($id > 0) {
            db_exec("UPDATE chart_of_accounts SET code=?, name=?, account_type=?, parent_id=?, opening_balance=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=?", [$code, $name, $account_type, $parent_id, $opening_balance, $id]);
            flash('success', 'Account updated successfully.');
        } else {
            db_exec("INSERT INTO chart_of_accounts (code, name, account_type, parent_id, opening_balance, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$code, $name, $account_type, $parent_id, $opening_balance]);
            flash('success', 'Account added successfully.');
        }
    } elseif ($action === 'delete') {
        $aid = (int)($_POST['id'] ?? 0);
        $used = db_get("SELECT COUNT(*) c FROM voucher_items WHERE account_id = ?", [$aid])['c'];
        if ($used > 0) {
            flash('danger', 'Account is used in vouchers and cannot be deleted.');
        } else {
            db_exec("DELETE FROM chart_of_accounts WHERE id=?", [$aid]);
            flash('success', 'Account deleted successfully.');
        }
    }
    redirect('chart_of_accounts.php');
}

$records = db_all("SELECT a.*, p.name AS parent_name,
                   (SELECT COALESCE(SUM(vi.debit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted') AS total_debit,
                   (SELECT COALESCE(SUM(vi.credit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = a.id AND v.status = 'posted') AS total_credit
                   FROM chart_of_accounts a
                   LEFT JOIN chart_of_accounts p ON p.id = a.parent_id
                   ORDER BY a.account_type, a.code");
$parents = db_all("SELECT * FROM chart_of_accounts ORDER BY code");
$typeColors = ['asset' => 'info', 'liability' => 'warning', 'equity' => 'primary', 'income' => 'success', 'expense' => 'danger'];
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search accounts...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#recordModal" data-add="Add Account"><i class="bi bi-plus-lg me-1"></i>Add Account</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Code</th><th>Account</th><th>Type</th><th>Parent</th><th>Opening</th><th>Debit</th><th>Credit</th><th>Balance</th><?php if ($canEdit): ?><th class="text-end">Actions</th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($records as $i => $r): ?>
                    <?php $bal = (float)$r['opening_balance'] + (float)$r['total_debit'] - (float)$r['total_credit']; ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="fw-medium"><?= e($r['code']) ?></td>
                        <td><?= e($r['name']) ?></td>
                        <td><span class="badge bg-<?= $typeColors[$r['account_type']] ?>"><?= ucfirst($r['account_type']) ?></span></td>
                        <td class="small"><?= e($r['parent_name'] ?? '-') ?></td>
                        <td><?= fmt_money($r['opening_balance']) ?></td>
                        <td><?= fmt_money($r['total_debit']) ?></td>
                        <td><?= fmt_money($r['total_credit']) ?></td>
                        <td><span class="<?= $bal < 0 ? 'text-danger fw-medium' : '' ?>"><?= fmt_money($bal) ?></span></td>
                        <?php if ($canEdit): ?>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal" data-edit='<?= h(json_encode(['id' => $r['id'], 'code' => $r['code'], 'name' => $r['name'], 'account_type' => $r['account_type'], 'parent_id' => $r['parent_id'], 'opening_balance' => $r['opening_balance']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this account?">
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
                    <tr><td colspan="10"><div class="empty-state"><i class="bi bi-diagram-3"></i><p>No accounts yet</p></div></td></tr>
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
                <div class="modal-header"><h5 class="modal-title">Add Account</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="recordId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type</label>
                            <select name="account_type" class="form-select">
                                <?php foreach (['asset', 'liability', 'equity', 'income', 'expense'] as $t): ?>
                                    <option value="<?= $t ?>"><?= ucfirst($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Account Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Parent</label>
                            <select name="parent_id" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($parents as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['code']) ?> - <?= e($p['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Opening Balance</label>
                            <input type="number" step="0.01" name="opening_balance" class="form-control" value="0">
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

<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Income Heads';
$active = 'income_heads';
$canEdit = has_permission('accounting.manage');
$accType = 'income';

function next_head_code($accType) {
    $row = db_get("SELECT MAX(CAST(code AS UNSIGNED)) AS m FROM chart_of_accounts WHERE account_type = ? AND parent_id IS NULL AND code REGEXP '^[0-9]+$'", [$accType]);
    $next = (int)($row['m'] ?? 0) + 100;
    return $next >= 1000 ? (string)$next : str_pad((string)$next, 4, '0', STR_PAD_LEFT);
}

function next_sub_code($parent_id, $parent_code) {
    $row = db_get("SELECT COUNT(*) c FROM chart_of_accounts WHERE parent_id = ?", [$parent_id]);
    return $parent_code . '-' . str_pad((int)$row['c'] + 1, 2, '0', STR_PAD_LEFT);
}

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $code = trim($_POST['code'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $parent_id = (int)($_POST['parent_id'] ?? 0) ?: null;
        $opening_balance = (float)($_POST['opening_balance'] ?? 0);

        if ($code === '' || $name === '') {
            flash('danger', 'Code and name are required.');
        } elseif ($parent_id) {
            $parent = db_get("SELECT * FROM chart_of_accounts WHERE id = ? AND account_type = ?", [$parent_id, $accType]);
            if (!$parent) {
                flash('danger', 'Invalid parent head.');
            } elseif ($id > 0) {
                db_exec("UPDATE chart_of_accounts SET code=?, name=?, opening_balance=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=? AND account_type=? AND parent_id IS NOT NULL", [$code, $name, $opening_balance, $id, $accType]);
                flash('success', 'Sub-head updated successfully.');
            } else {
                db_exec("INSERT INTO chart_of_accounts (code, name, account_type, parent_id, opening_balance, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$code, $name, $accType, $parent_id, $opening_balance]);
                flash('success', 'Sub-head added successfully.');
            }
        } else {
            if ($id > 0) {
                db_exec("UPDATE chart_of_accounts SET code=?, name=?, opening_balance=?, updated_date=CURDATE(), updated_time=CURTIME() WHERE id=? AND account_type=? AND parent_id IS NULL", [$code, $name, $opening_balance, $id, $accType]);
                flash('success', 'Income head updated successfully.');
            } else {
                db_exec("INSERT INTO chart_of_accounts (code, name, account_type, parent_id, opening_balance, created_date, created_time, updated_date, updated_time) VALUES (?,?,?,NULL,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$code, $name, $accType, $opening_balance]);
                flash('success', 'Income head added successfully.');
            }
        }
    } elseif ($action === 'delete') {
        $aid = (int)($_POST['id'] ?? 0);
        $acct = db_get("SELECT * FROM chart_of_accounts WHERE id = ? AND account_type = ?", [$aid, $accType]);
        if (!$acct) {
            flash('danger', 'Account not found.');
        } else {
            $children = (int)db_get("SELECT COUNT(*) c FROM chart_of_accounts WHERE parent_id = ?", [$aid])['c'];
            $used = (int)db_get("SELECT COUNT(*) c FROM voucher_items WHERE account_id = ?", [$aid])['c'];
            if ($children > 0) {
                flash('danger', 'This head has sub-heads. Delete its sub-heads first.');
            } elseif ($used > 0) {
                flash('danger', 'This head is used in vouchers and cannot be deleted.');
            } else {
                db_exec("DELETE FROM chart_of_accounts WHERE id = ?", [$aid]);
                flash('success', 'Head deleted successfully.');
            }
        }
    }
    redirect('income_heads.php');
}

$heads = db_all("SELECT a.*,
    (SELECT COALESCE(SUM(vi.debit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id
        WHERE vi.account_id IN (SELECT id FROM chart_of_accounts WHERE id = a.id OR parent_id = a.id) AND v.status = 'posted') AS total_debit,
    (SELECT COALESCE(SUM(vi.credit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id
        WHERE vi.account_id IN (SELECT id FROM chart_of_accounts WHERE id = a.id OR parent_id = a.id) AND v.status = 'posted') AS total_credit
    FROM chart_of_accounts a
    WHERE a.account_type = ? AND a.parent_id IS NULL
    ORDER BY a.code", [$accType]);
$subs = db_all("SELECT s.*,
    (SELECT COALESCE(SUM(vi.debit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = s.id AND v.status = 'posted') AS total_debit,
    (SELECT COALESCE(SUM(vi.credit),0) FROM voucher_items vi JOIN vouchers v ON v.id = vi.voucher_id WHERE vi.account_id = s.id AND v.status = 'posted') AS total_credit
    FROM chart_of_accounts s
    WHERE s.account_type = ? AND s.parent_id IS NOT NULL
    ORDER BY s.code", [$accType]);
$subsByParent = [];
foreach ($subs as $s) {
    $subsByParent[$s['parent_id']][] = $s;
}
$nextHeadCode = next_head_code($accType);
$defaultSubCodes = [];
foreach ($heads as $h) {
    $defaultSubCodes[$h['id']] = next_sub_code($h['id'], $h['code']);
}
include '../includes/header.php';
?>

<div class="toolbar d-flex flex-wrap align-items-center gap-2">
    <div class="input-group input-group-sm" style="max-width:280px">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control table-search" data-table="#dataTable" placeholder="Search heads...">
    </div>
    <?php if ($canEdit): ?>
    <button class="btn btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#headModal" data-code="<?= e($nextHeadCode) ?>"><i class="bi bi-plus-lg me-1"></i>Add Income Head</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-diagram-3 me-2"></i>Income Heads &amp; Sub-Heads</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="dataTable">
                <thead><tr><th style="width:50px">#</th><th>Code</th><th>Head / Sub-Head</th><th>Opening</th><th>Debit</th><th>Credit</th><th>Balance</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php $n = 1; ?>
                <?php foreach ($heads as $h): ?>
                    <?php $bal = (float)$h['opening_balance'] + (float)$h['total_credit'] - (float)$h['total_debit']; ?>
                    <tr class="table-light fw-semibold">
                        <td><?= $n++ ?></td>
                        <td><?= e($h['code']) ?></td>
                        <td><i class="bi bi-folder2 me-1"></i><?= e($h['name']) ?></td>
                        <td><?= fmt_money($h['opening_balance']) ?></td>
                        <td><?= fmt_money($h['total_debit']) ?></td>
                        <td><?= fmt_money($h['total_credit']) ?></td>
                        <td><span class="<?= $bal < 0 ? 'text-danger' : '' ?>"><?= fmt_money($bal) ?></span></td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-info" href="ledger.php?account_id=<?= $h['id'] ?>&include_children=1" title="Ledger"><i class="bi bi-book"></i></a>
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#subModal" data-parent="<?= $h['id'] ?>" data-code="<?= e($defaultSubCodes[$h['id']]) ?>" title="Add Sub-Head"><i class="bi bi-plus-lg"></i></button>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#headModal" data-edit='<?= h(json_encode(['id' => $h['id'], 'code' => $h['code'], 'name' => $h['name'], 'opening_balance' => $h['opening_balance']])) ?>'><i class="bi bi-pencil"></i></button>
                            <form method="post" class="d-inline" data-confirm="Delete this income head?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $h['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php foreach ($subsByParent[$h['id']] ?? [] as $s): ?>
                        <?php $sbal = (float)$s['opening_balance'] + (float)$s['total_credit'] - (float)$s['total_debit']; ?>
                        <tr>
                            <td></td>
                            <td class="text-muted"><?= e($s['code']) ?></td>
                            <td><i class="bi bi-folder me-1 text-muted"></i><?= e($s['name']) ?></td>
                            <td><?= fmt_money($s['opening_balance']) ?></td>
                            <td><?= fmt_money($s['total_debit']) ?></td>
                            <td><?= fmt_money($s['total_credit']) ?></td>
                            <td><span class="<?= $sbal < 0 ? 'text-danger' : '' ?>"><?= fmt_money($sbal) ?></span></td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-info" href="ledger.php?account_id=<?= $s['id'] ?>" title="Ledger"><i class="bi bi-book"></i></a>
                                <?php if ($canEdit): ?>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#subModal" data-parent="<?= $h['id'] ?>" data-edit='<?= h(json_encode(['id' => $s['id'], 'parent_id' => $s['parent_id'], 'code' => $s['code'], 'name' => $s['name'], 'opening_balance' => $s['opening_balance']])) ?>'><i class="bi bi-pencil"></i></button>
                                <form method="post" class="d-inline" data-confirm="Delete this sub-head?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                <?php if (!$heads): ?>
                    <tr><td colspan="8"><div class="empty-state"><i class="bi bi-diagram-3"></i><p>No income heads yet</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="headModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header"><h5 class="modal-title">Add Income Head</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="headId">
                    <input type="hidden" name="parent_id" value="">
                    <div class="mb-3">
                        <label class="form-label">Head Code</label>
                        <input type="text" name="code" id="headCode" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Head Name</label>
                        <input type="text" name="name" id="headName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Opening Balance</label>
                        <input type="number" step="0.01" name="opening_balance" id="headOpening" class="form-control" value="0">
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

<div class="modal fade" id="subModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header"><h5 class="modal-title">Add Sub-Head</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="subId">
                    <input type="hidden" name="parent_id" id="subParent">
                    <div class="mb-3">
                        <label class="form-label">Sub-Head Code</label>
                        <input type="text" name="code" id="subCode" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sub-Head Name</label>
                        <input type="text" name="name" id="subName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Opening Balance</label>
                        <input type="number" step="0.01" name="opening_balance" id="subOpening" class="form-control" value="0">
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

<script>
(function () {
    var headModal = document.getElementById('headModal');
    headModal.addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        var edit = btn.getAttribute('data-edit');
        headModal.querySelector('#headCode').value = edit ? JSON.parse(edit).code : btn.getAttribute('data-code');
        headModal.querySelector('#headName').value = edit ? JSON.parse(edit).name : '';
        headModal.querySelector('#headOpening').value = edit ? JSON.parse(edit).opening_balance : '0';
        headModal.querySelector('#headId').value = edit ? JSON.parse(edit).id : '';
        headModal.querySelector('.modal-title').textContent = edit ? 'Edit Income Head' : 'Add Income Head';
    });
    var subModal = document.getElementById('subModal');
    subModal.addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        var edit = btn.getAttribute('data-edit');
        subModal.querySelector('#subCode').value = edit ? JSON.parse(edit).code : btn.getAttribute('data-code');
        subModal.querySelector('#subName').value = edit ? JSON.parse(edit).name : '';
        subModal.querySelector('#subOpening').value = edit ? JSON.parse(edit).opening_balance : '0';
        subModal.querySelector('#subId').value = edit ? JSON.parse(edit).id : '';
        subModal.querySelector('#subParent').value = edit ? JSON.parse(edit).parent_id : btn.getAttribute('data-parent');
        subModal.querySelector('.modal-title').textContent = edit ? 'Edit Sub-Head' : 'Add Sub-Head';
    });
})();
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>


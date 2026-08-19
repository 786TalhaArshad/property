<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.manage');
$title = 'Opening Balances';
$active = 'opening_balances';

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $cashLocked = (int)setting('cash_ob_locked', '0');
        if (!$cashLocked) {
            $cashOB = (float)($_POST['cash_opening_balance'] ?? 0);
            db_exec("UPDATE chart_of_accounts SET opening_balance = ? WHERE code = '1000'", [$cashOB]);
            $locked = isset($_POST['cash_locked']) ? 1 : 0;
            if ($locked) {
                db_exec("INSERT INTO settings (setting_key, setting_value, created_date, created_time, updated_date, updated_time) VALUES ('cash_ob_locked','1',CURDATE(),CURTIME(),CURDATE(),CURTIME()) ON DUPLICATE KEY UPDATE setting_value='1', updated_date=CURDATE(), updated_time=CURTIME()");
            }
        }

        $banks = db_all("SELECT id FROM banks WHERE status = 1");
        foreach ($banks as $b) {
            $bid = (int)$b['id'];
            $lockKey = 'bank_' . $bid . '_ob_locked';
            $bankLocked = (int)setting($lockKey, '0');
            if (!$bankLocked) {
                $bankOB = (float)($_POST['bank_ob_' . $bid] ?? 0);
                $code = '1001-' . str_pad($bid, 3, '0', STR_PAD_LEFT);
                db_exec("UPDATE chart_of_accounts SET opening_balance = ? WHERE code = ?", [$bankOB, $code]);
                db_exec("UPDATE banks SET opening_balance = ? WHERE id = ?", [$bankOB, $bid]);
                $locked = isset($_POST['bank_locked_' . $bid]) ? 1 : 0;
                if ($locked) {
                    db_exec("INSERT INTO settings (setting_key, setting_value, created_date, created_time, updated_date, updated_time) VALUES (?,?,CURDATE(),CURTIME(),CURDATE(),CURTIME())", [$lockKey, '1']);
                }
            }
        }

        flash('success', 'Opening balances saved successfully.');
        redirect('opening_balances.php');
    }
}

$cashAcc = db_get("SELECT opening_balance FROM chart_of_accounts WHERE code = '1000'");
$cashOB = (float)($cashAcc['opening_balance'] ?? 0);
$cashLocked = (int)setting('cash_ob_locked', '0');

$banks = db_all("SELECT id, name, account_title, opening_balance FROM banks WHERE status = 1 ORDER BY name");
include '../includes/header.php';
?>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-cash-coin me-2"></i>Cash (Account 1000)</div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <div class="mb-3">
                        <label class="form-label">Opening Balance (Rs.)</label>
                        <input type="number" step="0.01" name="cash_opening_balance" class="form-control" value="<?= number_format($cashOB, 2, '.', '') ?>" <?= $cashLocked ? 'disabled' : '' ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" name="cash_locked" class="form-check-input" value="1" <?= $cashLocked ? 'checked disabled' : '' ?>>
                            <span class="form-check-label"><?= $cashLocked ? '<span class="text-danger fw-bold">Locked</span>' : 'Lock (one-time only)' ?></span>
                        </label>
                    </div>
                    <?php if (!$cashLocked): ?>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Cash Balance</button>
                    <?php else: ?>
                    <div class="alert alert-warning mb-0"><i class="bi bi-lock-fill me-1"></i>Cash opening balance is locked and cannot be changed.</div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-bank me-2"></i>Bank Opening Balances</div>
            <div class="card-body">
                <?php if (!$banks): ?>
                    <p class="text-muted">No active banks found. Add banks in Master Data > Banks first.</p>
                <?php else: ?>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="save">
                        <?php foreach ($banks as $b):
                            $bid = (int)$b['id'];
                            $lockKey = 'bank_' . $bid . '_ob_locked';
                            $bankLocked = (int)setting($lockKey, '0');
                        ?>
                        <div class="mb-3 p-2 border rounded">
                            <div class="fw-medium mb-1"><?= e($b['name']) ?> <?= $b['account_title'] ? '<span class="text-muted small">(' . e($b['account_title']) . ')</span>' : '' ?></div>
                            <div class="d-flex align-items-center gap-2">
                                <input type="number" step="0.01" name="bank_ob_<?= $bid ?>" class="form-control form-control-sm" value="<?= number_format((float)$b['opening_balance'], 2, '.', '') ?>" <?= $bankLocked ? 'disabled' : '' ?>>
                                <label class="form-check mb-0">
                                    <input type="checkbox" name="bank_locked_<?= $bid ?>" class="form-check-input" value="1" <?= $bankLocked ? 'checked disabled' : '' ?>>
                                    <span class="form-check-label small"><?= $bankLocked ? '<span class="text-danger">Locked</span>' : 'Lock' ?></span>
                                </label>
                            </div>
                            <?php if ($bankLocked): ?>
                                <div class="small text-muted mt-1"><i class="bi bi-lock-fill"></i> Locked</div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Bank Balances</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Opening balances</strong> are entered once and locked. They represent your starting position and are used throughout the software (Cash Book, Dashboard, Ledgers). After locking, they cannot be changed.
</div>

<?php include '../includes/footer.php'; ?>

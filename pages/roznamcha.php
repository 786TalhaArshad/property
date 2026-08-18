<?php
require_once '../includes/auth.php';
require_login();
require_permission('accounting.view');
$title = 'Roznamcha (Day Book)';
$active = 'roznamcha';
$canEdit = has_permission('accounting.manage');

if (is_post() && $canEdit) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'delete_voucher') {
        db_exec("DELETE FROM vouchers WHERE id=?", [(int)($_POST['id'] ?? 0)]);
        flash('success', 'Voucher deleted.');
        redirect('roznamcha.php');
    } elseif ($action === 'delete_receipt') {
        $rid = (int)($_POST['id'] ?? 0);
        $rec = db_get("SELECT * FROM receipts WHERE id = ?", [$rid]);
        if ($rec && $rec['installment_id']) {
            $inst = db_get("SELECT * FROM installments WHERE id = ?", [$rec['installment_id']]);
            if ($inst) {
                $newPaid = max(0, (float)$inst['paid_amount'] - (float)$rec['amount']);
                $newStatus = $newPaid >= (float)$inst['amount'] ? 'paid' : ($newPaid > 0 ? 'partial' : 'pending');
                db_exec("UPDATE installments SET paid_amount = ?, status = ?, paid_date = NULL, updated_date=CURDATE(), updated_time=CURTIME() WHERE id = ?", [$newPaid, $newStatus, $rec['installment_id']]);
            }
        }
        if ($rec && !empty($rec['voucher_id'])) {
            db_exec("DELETE FROM vouchers WHERE id = ?", [$rec['voucher_id']]);
        }
        db_exec("DELETE FROM receipts WHERE id = ?", [$rid]);
        flash('success', 'Receipt deleted.');
        redirect('roznamcha.php');
    } elseif ($action === 'delete_transfer') {
        $tid = (int)($_POST['id'] ?? 0);
        $rec = db_get("SELECT * FROM transfers WHERE id = ?", [$tid]);
        if ($rec && $rec['transfer_type'] !== 'customer_withdraw') {
            if ($rec['voucher_id']) {
                db_exec("DELETE FROM vouchers WHERE id = ?", [$rec['voucher_id']]);
            }
            db_exec("DELETE FROM transfers WHERE id = ?", [$tid]);
            flash('success', 'Transfer deleted.');
        } else {
            flash('danger', 'This transfer cannot be deleted.');
        }
        redirect('roznamcha.php');
    }
}

$project_id = (int)($_GET['project_id'] ?? active_project_id());
$bank_id = (int)($_GET['bank_id'] ?? 0);
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

$params = [];
$where = '';
if ($project_id > 0) {
    $where .= " AND v.project_id = ?";
    $params[] = $project_id;
}
if ($from) {
    $where .= " AND v.voucher_date >= ?";
    $params[] = $from;
}
if ($to) {
    $where .= " AND v.voucher_date <= ?";
    $params[] = $to;
}

$voucherLines = db_all("SELECT v.id AS voucher_id, v.voucher_date AS d, v.voucher_no AS ref, v.voucher_type AS vtype, v.narration,
                        vi.item_description AS descr, c.code AS acc_code, c.name AS acc_name, vi.debit, vi.credit, p.name AS project_name
                        FROM voucher_items vi
                        JOIN vouchers v ON v.id = vi.voucher_id
                        JOIN chart_of_accounts c ON c.id = vi.account_id
                        LEFT JOIN projects p ON p.id = v.project_id
                        WHERE v.status = 'posted'$where
                        ORDER BY v.voucher_date, v.id", $params);

$rparams = [];
$rwhere = '';
if ($project_id > 0) {
    $rwhere .= " AND r.project_id = ?";
    $rparams[] = $project_id;
}
if ($from) {
    $rwhere .= " AND r.receipt_date >= ?";
    $rparams[] = $from;
}
if ($to) {
    $rwhere .= " AND r.receipt_date <= ?";
    $rparams[] = $to;
}
$receipts = db_all("SELECT r.id AS rid, r.receipt_date AS d, r.receipt_no AS ref, r.amount, r.bank_id, c.full_name, pr.property_no, p.name AS project_name, bk.name AS bank_name
                    FROM receipts r
                    JOIN customers c ON c.id = r.customer_id
                    LEFT JOIN bookings b ON b.id = r.booking_id
                    LEFT JOIN properties pr ON pr.id = b.property_id
                    LEFT JOIN projects p ON p.id = r.project_id
                    LEFT JOIN banks bk ON bk.id = r.bank_id
                    WHERE 1=1$rwhere
                    ORDER BY r.receipt_date, r.id", $rparams);

$tparams = [];
$twhere = '';
if ($project_id > 0) {
    $twhere .= " AND t.project_id = ?";
    $tparams[] = $project_id;
}
if ($from) {
    $twhere .= " AND t.transfer_date >= ?";
    $tparams[] = $from;
}
if ($to) {
    $twhere .= " AND t.transfer_date <= ?";
    $tparams[] = $to;
}
$transfers = db_all("SELECT t.id AS rid, t.transfer_type AS ttype, t.transfer_date AS d, t.transfer_no AS ref, t.amount, fc.full_name AS from_name, tc.full_name AS to_name, p.name AS project_name
                     FROM transfers t
                     LEFT JOIN customers fc ON fc.id = t.from_customer_id
                     LEFT JOIN customers tc ON tc.id = t.to_customer_id
                     LEFT JOIN projects p ON p.id = t.project_id
                     WHERE t.transfer_type = 'customer_to_customer'$twhere
                     ORDER BY t.transfer_date, t.id", $tparams);

$rows = [];
foreach ($voucherLines as $v) {
    $cashBank = '';
    $vBankId = null;
    if ($v['acc_code'] === '1000') {
        $cashBank = 'Cash';
    } elseif (preg_match('/^1001-(\d+)$/', $v['acc_code'], $m)) {
        $cashBank = $v['acc_name'];
        $vBankId = (int)$m[1];
    }
    $rows[] = [
        'd' => $v['d'],
        'ref' => $v['ref'],
        'type' => $v['vtype'],
        'type_label' => 'Voucher',
        'descr' => $v['descr'] ?: $v['narration'],
        'head' => ($v['acc_code'] ? $v['acc_code'] . ' - ' : '') . $v['acc_name'],
        'project_name' => $v['project_name'],
        'cash_bank' => $cashBank,
        'bank_id' => $vBankId,
        'debit' => (float)$v['debit'],
        'credit' => (float)$v['credit'],
        'ref_id' => $v['voucher_id'],
        'ref_kind' => 'voucher',
    ];
}
foreach ($receipts as $r) {
    $rows[] = [
        'd' => $r['d'],
        'ref' => $r['ref'],
        'type' => 'receipt',
        'type_label' => 'Receipt',
        'descr' => 'Receipt from ' . $r['full_name'] . ($r['property_no'] ? ' - ' . $r['property_no'] : ''),
        'head' => 'Customer Receipt',
        'project_name' => $r['project_name'],
        'cash_bank' => $r['bank_id'] ? ($r['bank_name'] ?: 'Bank') : 'Cash',
        'bank_id' => $r['bank_id'] ? (int)$r['bank_id'] : null,
        'debit' => (float)$r['amount'],
        'credit' => 0.0,
        'ref_id' => $r['rid'],
        'ref_kind' => 'receipt',
    ];
}
foreach ($transfers as $t) {
    $rows[] = [
        'd' => $t['d'],
        'ref' => $t['ref'],
        'type' => 'transfer',
        'type_label' => 'Transfer',
        'descr' => 'Balance transfer ' . $t['from_name'] . ' -> ' . $t['to_name'],
        'head' => 'Customer Transfer',
        'project_name' => $t['project_name'],
        'cash_bank' => '-',
        'bank_id' => null,
        'debit' => 0.0,
        'credit' => (float)$t['amount'],
        'ref_id' => (int)$t['rid'],
        'ref_kind' => 'transfer',
    ];
}
if ($bank_id > 0) {
    $rows = array_values(array_filter($rows, function ($r) use ($bank_id) {
        return $r['bank_id'] === $bank_id;
    }));
}
usort($rows, function ($a, $b) {
    return [$a['d'], $a['ref']] <=> [$b['d'], $b['ref']];
});

$typeBadges = ['cash_payment' => 'danger', 'cash_receipt' => 'success', 'bank_payment' => 'warning', 'bank_receipt' => 'info', 'journal' => 'primary', 'receipt' => 'success', 'transfer' => 'secondary'];
$projects = db_all("SELECT * FROM projects WHERE status = 1 ORDER BY name");
$banks = db_all("SELECT * FROM banks ORDER BY name");
$grandDebit = array_sum(array_map(function ($r) { return $r['debit']; }, $rows));
$grandCredit = array_sum(array_map(function ($r) { return $r['credit']; }, $rows));
include '../includes/header.php';
?>

<form method="get" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="project_id" class="form-select form-select-sm">
                    <option value="">All Projects</option>
                    <?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>" <?= $project_id === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="bank_id" class="form-select form-select-sm">
                    <option value="">All Banks / Cash</option>
                    <?php foreach ($banks as $b): ?><option value="<?= $b['id'] ?>" <?= $bank_id === (int)$b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><label class="form-label small text-muted mb-0">Start Date</label><input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>"></div>
            <div class="col-md-2"><label class="form-label small text-muted mb-0">End Date</label><input type="date" name="to" class="form-control form-control-sm" value="<?= e($to) ?>"></div>
            <div class="col-md-2"><button class="btn btn-primary btn-sm w-100 mt-4"><i class="bi bi-funnel me-1"></i>Filter</button></div>
            <div class="col-md-3 text-end small text-muted">Total Debit: <strong><?= fmt_money($grandDebit) ?></strong> &bull; Total Credit: <strong><?= fmt_money($grandCredit) ?></strong></div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-header"><i class="bi bi-journal-text me-2"></i>Roznamcha <?= $project_id ? ' - ' . e((db_get("SELECT name FROM projects WHERE id = ?", [$project_id])['name'] ?? '')) : '' ?> <span class="ms-auto text-muted small"><?= fmt_date($from) ?> to <?= fmt_date($to) ?></span></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                <tr>
                    <th>Date</th><th>Ref No</th><th>Type</th><th>Particulars</th><th>Head / Account</th><th>Project</th><th>Cash / Bank</th>
                    <th class="text-end">Debit</th><th class="text-end">Credit</th>
                    <?php if ($canEdit): ?><th style="width:90px"></th><?php endif; ?>
                </tr>
                </thead>
                <tbody>
                <?php
                $curDate = null;
                $dayDebit = 0.0;
                $dayCredit = 0.0;
                $dayRows = [];
                $flushDay = function () use (&$curDate, &$dayDebit, &$dayCredit, &$dayRows) {
                    if (!$curDate || !$dayRows) return;
                    echo '<tr class="table-light fw-bold"><td class="text-end" colspan="7">Daily Total ' . fmt_date($curDate) . '</td><td class="text-end">' . fmt_money($dayDebit) . '</td><td class="text-end">' . fmt_money($dayCredit) . '</td>';
                    echo $GLOBALS['canEdit'] ? '<td></td>' : '';
                    echo '</tr>';
                };
                foreach ($rows as $r) {
                    if ($curDate !== $r['d']) {
                        $flushDay();
                        $curDate = $r['d'];
                        $dayDebit = 0.0;
                        $dayCredit = 0.0;
                    }
                    $dayDebit += $r['debit'];
                    $dayCredit += $r['credit'];
                    $dayRows[] = $r;
                    echo '<tr>';
                    echo '<td class="text-nowrap">' . fmt_date($r['d']) . '</td>';
                    echo '<td class="fw-medium">' . e($r['ref']) . '</td>';
                    echo '<td><span class="badge bg-' . ($typeBadges[$r['type']] ?? 'secondary') . '">' . e($r['type_label']) . '</span></td>';
                    echo '<td class="small">' . e($r['descr'] ?: '-') . '</td>';
                    echo '<td class="small">' . e($r['head'] ?: '-') . '</td>';
                    echo '<td class="small">' . ($r['project_name'] ? '<span class="badge bg-light text-dark border">' . e($r['project_name']) . '</span>' : '<span class="text-muted">General</span>') . '</td>';
                    echo '<td class="small">' . ($r['cash_bank'] && $r['cash_bank'] !== '-' ? e($r['cash_bank']) : '-') . '</td>';
                    echo '<td class="text-end">' . ($r['debit'] > 0 ? fmt_money($r['debit']) : '-') . '</td>';
                    echo '<td class="text-end">' . ($r['credit'] > 0 ? fmt_money($r['credit']) : '-') . '</td>';
                    if ($canEdit) {
                        echo '<td class="text-end">';
                        if ($r['ref_kind'] === 'voucher') {
                            echo '<a class="btn btn-sm btn-outline-primary py-0" href="voucher_form.php?id=' . $r['ref_id'] . '" title="Edit"><i class="bi bi-pencil"></i></a> ';
                            echo '<form method="post" class="d-inline" data-confirm="Delete voucher ' . e($r['ref']) . '? All its entries will be removed.">
                                      <input type="hidden" name="csrf_token" value="' . csrf_token() . '">
                                      <input type="hidden" name="action" value="delete_voucher">
                                      <input type="hidden" name="id" value="' . $r['ref_id'] . '">
                                      <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
                                  </form>';
                        } elseif ($r['ref_kind'] === 'receipt') {
                            echo '<form method="post" class="d-inline" data-confirm="Delete receipt ' . e($r['ref']) . '?">
                                      <input type="hidden" name="csrf_token" value="' . csrf_token() . '">
                                      <input type="hidden" name="action" value="delete_receipt">
                                      <input type="hidden" name="id" value="' . $r['ref_id'] . '">
                                      <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
                                  </form>';
                        } elseif ($r['ref_kind'] === 'transfer') {
                            echo '<a class="btn btn-sm btn-outline-primary py-0" href="transfer_form.php?id=' . $r['ref_id'] . '" title="Edit"><i class="bi bi-pencil"></i></a> ';
                            echo '<form method="post" class="d-inline" data-confirm="Delete transfer ' . e($r['ref']) . '?">
                                      <input type="hidden" name="csrf_token" value="' . csrf_token() . '">
                                      <input type="hidden" name="action" value="delete_transfer">
                                      <input type="hidden" name="id" value="' . $r['ref_id'] . '">
                                      <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
                                  </form>';
                        }
                        echo '</td>';
                    }
                    echo '</tr>';
                }
                $flushDay();
                if (!$rows) {
                    echo '<tr><td colspan="10"><div class="empty-state"><i class="bi bi-journal-text"></i><p>No entries in the selected period</p></div></td></tr>';
                }
                ?>
                </tbody>
                <tfoot>
                <tr class="table-dark">
                    <td colspan="7" class="text-end fw-bold">Grand Total</td>
                    <td class="text-end fw-bold"><?= fmt_money($grandDebit) ?></td>
                    <td class="text-end fw-bold"><?= fmt_money($grandCredit) ?></td>
                    <?php if ($canEdit): ?><td></td><?php endif; ?>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

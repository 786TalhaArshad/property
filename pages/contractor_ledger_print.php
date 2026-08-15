<?php
require_once '../includes/auth.php';
require_login();
require_permission('contractors.view');

$id = (int)($_GET['contractor_id'] ?? 0);
$contractor = db_get("SELECT * FROM contractors WHERE id = ?", [$id]);
if (!$contractor) {
    flash('danger', 'Contractor not found.');
    redirect('contractors.php');
}

$project_id = (int)($_GET['project_id'] ?? 0);
$filterProject = null;
if ($project_id > 0) {
    $filterProject = db_get("SELECT id, name FROM projects WHERE id = ?", [$project_id]);
}

$conAccId = contractor_payable_account_id($id, $contractor['full_name']);
$entries = db_all("SELECT ce.*, p.name AS project_name
                   FROM contractor_entries ce
                   LEFT JOIN projects p ON p.id = ce.project_id
                   WHERE ce.contractor_id = ? ORDER BY ce.entry_date, ce.id", [$id]);
$voucherLines = db_all("SELECT vi.debit, vi.credit, v.voucher_no, v.voucher_date, v.narration AS voucher_narration, v.project_id, p.name AS project_name
                        FROM voucher_items vi
                        JOIN vouchers v ON v.id = vi.voucher_id
                        LEFT JOIN projects p ON p.id = v.project_id
                        WHERE vi.account_id = ? AND v.status = 'posted'
                          AND v.id NOT IN (SELECT ce2.voucher_id FROM contractor_entries ce2 WHERE ce2.contractor_id = ? AND ce2.voucher_id IS NOT NULL)
                        ORDER BY v.voucher_date, v.id", [$conAccId, $id]);

$ledger = [];
foreach ($entries as $e) {
    $ledger[] = [
        'date' => $e['entry_date'],
        'entry_no' => $e['entry_no'],
        'entry_type' => $e['entry_type'],
        'amount' => (float)$e['amount'],
        'narration' => $e['narration'],
        'project' => $e['project_name'],
        'project_id' => $e['project_id'] ? (int)$e['project_id'] : 0,
    ];
}
foreach ($voucherLines as $vl) {
    $amt = (float)$vl['debit'] > 0 ? (float)$vl['debit'] : (float)$vl['credit'];
    $narr = trim($vl['voucher_narration'] ?? '') !== '' ? $vl['voucher_narration'] : '';
    $ledger[] = [
        'date' => $vl['voucher_date'],
        'entry_no' => $vl['voucher_no'],
        'entry_type' => (float)$vl['credit'] > 0 ? 'payable' : 'paid',
        'amount' => $amt,
        'narration' => $narr,
        'project' => $vl['project_name'],
        'project_id' => $vl['project_id'] ? (int)$vl['project_id'] : 0,
    ];
}
usort($ledger, function ($a, $b) {
    return strcmp($a['date'], $b['date']) ?: strcmp($a['entry_no'], $b['entry_no']);
});
if ($project_id > 0) {
    $ledger = array_values(array_filter($ledger, function ($r) use ($project_id) {
        return $r['project_id'] === $project_id;
    }));
}

$balance = 0.0;
$totalPayable = 0.0;
$totalPaid = 0.0;
$running = [];
foreach ($ledger as $i => $row) {
    if ($row['entry_type'] === 'payable') {
        $totalPayable += $row['amount'];
        $balance += $row['amount'];
    } else {
        $totalPaid += $row['amount'];
        $balance -= $row['amount'];
    }
    $running[$i] = $balance;
}

$companyInfo = [
    'name' => setting('company_name', 'Company'),
    'tagline' => setting('company_tagline', ''),
    'address' => setting('company_address', ''),
    'phone' => setting('company_phone', ''),
    'email' => setting('company_email', ''),
    'logo' => setting('company_logo', ''),
];
include '../includes/header.php';
?>
<style>
    body { background: #fff; }
    .print-head { border-bottom: 3px double #1c2b36; }
    .print-table { font-size: 12px; width: 100%; border-collapse: collapse; }
    .print-table th, .print-table td { border: 1px solid #444; padding: 4px 6px; }
    .print-table th { background: #e9ecef; text-align: left; }
    .total-row td { font-weight: bold; background: #f1f3f5; }
    .no-print { margin: 16px 0; }
    @media print {
        body { background: #fff; }
        .no-print { display: none !important; }
    }
</style>

<div class="no-print text-center">
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    <a class="btn btn-light" href="contractor_view.php?id=<?= $id ?>"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="print-head d-flex justify-content-between align-items-start pb-3 mb-3">
    <div class="d-flex align-items-center gap-3">
        <?php if ($companyInfo['logo']): ?>
            <img src="<?= BASE_URL ?>/assets/<?= e($companyInfo['logo']) ?>" style="max-height:60px" alt="logo">
        <?php endif; ?>
        <div>
            <h3 class="mb-0 fw-bold"><?= e($companyInfo['name']) ?></h3>
            <?php if ($companyInfo['tagline']): ?><div class="small"><?= e($companyInfo['tagline']) ?></div><?php endif; ?>
            <?php if ($companyInfo['address']): ?><div class="small text-muted"><?= e($companyInfo['address']) ?></div><?php endif; ?>
            <div class="small text-muted">
                <?= $companyInfo['phone'] ? 'Phone: ' . e($companyInfo['phone']) : '' ?>
                <?= $companyInfo['phone'] && $companyInfo['email'] ? ' &bull; ' : '' ?>
                <?= $companyInfo['email'] ? 'Email: ' . e($companyInfo['email']) : '' ?>
            </div>
        </div>
    </div>
    <div class="text-end">
        <h5 class="mb-1">Contractor Ledger</h5>
        <div class="fw-medium"><?= e($contractor['full_name']) ?> (<?= e($contractor['contractor_no']) ?>)</div>
        <div class="small text-muted"><?= e($contractor['specialty'] ?: '') ?></div>
        <div class="small">Print Date: <?= fmt_date(date('Y-m-d')) ?></div>
        <div class="small">Project: <?= $filterProject ? e($filterProject['name']) : 'All' ?></div>
    </div>
</div>

<div class="table-responsive">
    <table class="print-table">
        <thead>
        <tr>
            <th>Date</th>
            <th>Entry No</th>
            <th>Type</th>
            <th>Project</th>
            <th>Narration</th>
            <th class="text-end">Payable</th>
            <th class="text-end">Paid</th>
            <th class="text-end">Balance</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($ledger as $i => $row): ?>
            <tr>
                <td><?= fmt_date($row['date']) ?></td>
                <td><?= e($row['entry_no']) ?></td>
                <td><?= ucfirst($row['entry_type']) ?></td>
                <td><?= $row['project'] ? e($row['project']) : '-' ?></td>
                <td><?= $row['narration'] ? e($row['narration']) : '-' ?></td>
                <td class="text-end"><?= $row['entry_type'] === 'payable' ? fmt_money($row['amount']) : '-' ?></td>
                <td class="text-end"><?= $row['entry_type'] === 'paid' ? fmt_money($row['amount']) : '-' ?></td>
                <td class="text-end fw-medium"><?= fmt_money($running[$i]) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$ledger): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No ledger entries yet</td></tr>
        <?php endif; ?>
        </tbody>
        <tfoot>
        <tr class="total-row">
            <td colspan="5" class="text-end fw-bold">Total Payable / Total Paid</td>
            <td class="text-end fw-bold"><?= fmt_money($totalPayable) ?></td>
            <td class="text-end fw-bold"><?= fmt_money($totalPaid) ?></td>
            <td class="text-end fw-bold"><?= fmt_money($balance) ?></td>
        </tr>
        </tfoot>
    </table>
</div>

<?php include '../includes/footer.php'; ?>

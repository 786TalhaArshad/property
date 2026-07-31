<?php
require_once '../includes/auth.php';
require_login();
require_permission('sales.view');

$id = (int)($_GET['id'] ?? 0);
$r = db_get("SELECT r.*, c.full_name AS customer_name, c.cnic AS customer_cnic, c.phone AS customer_phone, c.address AS customer_address,
             b.booking_no, b.total_price, p.property_no, p.address AS property_address,
             i.installment_no, i.installment_type,
             pm.name AS method_name, bk.name AS bank_name, u.full_name AS receiver
             FROM receipts r
             JOIN customers c ON c.id = r.customer_id
             LEFT JOIN bookings b ON b.id = r.booking_id
             LEFT JOIN properties p ON p.id = b.property_id
             LEFT JOIN installments i ON i.id = r.installment_id
             LEFT JOIN payment_methods pm ON pm.id = r.payment_method_id
             LEFT JOIN banks bk ON bk.id = r.bank_id
             LEFT JOIN users u ON u.id = r.received_by
             WHERE r.id = ?", [$id]);
if (!$r) {
    flash('danger', 'Receipt not found.');
    redirect('receipts.php');
}
$company = [
    'name' => setting('company_name', 'Company'),
    'tagline' => setting('company_tagline', ''),
    'address' => setting('company_address', ''),
    'phone' => setting('company_phone', ''),
    'email' => setting('company_email', ''),
];
$amountInWords = function ($n) {
    $words = [
        0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
        11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
        16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty',
        30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
        80 => 'Eighty', 90 => 'Ninety',
    ];
    $numToWords = function ($num) use ($words, &$numToWords) {
        if ($num < 21) return $words[$num];
        if ($num < 100) return $words[10 * floor($num / 10)] . ' ' . ($num % 10 ? $words[$num % 10] : '');
        if ($num < 1000) return $words[floor($num / 100)] . ' Hundred' . ($num % 100 ? ' ' . $numToWords($num % 100) : '');
        if ($num < 100000) return $numToWords(floor($num / 1000)) . ' Thousand' . ($num % 1000 ? ' ' . $numToWords($num % 1000) : '');
        if ($num < 10000000) return $numToWords(floor($num / 100000)) . ' Lakh' . ($num % 100000 ? ' ' . $numToWords($num % 100000) : '');
        return $numToWords(floor($num / 10000000)) . ' Crore' . ($num % 10000000 ? ' ' . $numToWords($num % 10000000) : '');
    };
    $whole = (int)$n;
    $paise = round(($n - $whole) * 100);
    $out = $numToWords($whole);
    if ($paise > 0) $out .= ' and ' . $numToWords($paise) . ' Paise';
    return $out;
};
include '../includes/header.php';
?>
<style>
    body { background: #f2f4f7; }
    .receipt-sheet { max-width: 780px; margin: 24px auto; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; }
    .receipt-sheet .head { border-bottom: 3px double #1c2b36; }
    @media print {
        body { background: #fff; }
        .no-print { display: none !important; }
        .receipt-sheet { margin: 0; border: none; border-radius: 0; box-shadow: none; }
    }
</style>

<div class="receipt-sheet p-4">
    <div class="head d-flex justify-content-between align-items-center pb-3">
        <div>
            <h4 class="mb-0 fw-bold"><?= e($company['name']) ?></h4>
            <div class="small text-muted"><?= e($company['tagline']) ?></div>
            <div class="small text-muted"><?= e($company['address']) ?></div>
            <div class="small text-muted">Phone: <?= e($company['phone']) ?> &bull; Email: <?= e($company['email']) ?></div>
        </div>
        <div class="text-center">
            <div class="badge bg-dark text-uppercase px-3 py-2 fs-6">Payment Receipt</div>
            <div class="mt-2 fw-bold fs-5"><?= e($r['receipt_no']) ?></div>
            <div class="small text-muted"><?= fmt_date($r['receipt_date']) ?></div>
        </div>
    </div>

    <div class="row g-3 py-3">
        <div class="col-md-6">
            <div class="text-muted small">RECEIVED FROM</div>
            <div class="fw-medium"><?= e($r['customer_name']) ?></div>
            <div class="small">CNIC: <?= e($r['customer_cnic'] ?? '-') ?></div>
            <div class="small">Phone: <?= e($r['customer_phone'] ?? '-') ?></div>
            <div class="small"><?= e($r['customer_address'] ?? '') ?></div>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="text-muted small">FOR</div>
            <div class="fw-medium"><?= $r['booking_no'] ? 'Booking ' . e($r['booking_no']) : 'General' ?></div>
            <div class="small">Property: <?= e($r['property_no'] ?? '-') ?></div>
            <?php if ($r['installment_no']): ?>
                <div class="small">Installment #<?= $r['installment_no'] ?> (<?= ucfirst(e($r['installment_type'])) ?>)</div>
            <?php endif; ?>
            <div class="small"><?= e($r['property_address'] ?? '') ?></div>
        </div>
    </div>

    <div class="row g-2 bg-light p-3 rounded">
        <div class="col-md-4"><div class="text-muted small">AMOUNT</div><div class="fs-4 fw-bold"><?= fmt_money($r['amount']) ?></div></div>
        <div class="col-md-4"><div class="text-muted small">PAYMENT METHOD</div><div class="fw-medium"><?= e($r['method_name'] ?? '-') ?></div></div>
        <div class="col-md-4"><div class="text-muted small">BANK / REFERENCE</div><div class="fw-medium"><?= e($r['bank_name'] ?? '-') ?> <?= $r['reference'] ? '(' . e($r['reference']) . ')' : '' ?></div></div>
    </div>

    <div class="mt-3">
        <div class="text-muted small">AMOUNT IN WORDS</div>
        <div class="fw-medium text-uppercase"><?= e($amountInWords($r['amount'])) ?></div>
    </div>

    <?php if ($r['remarks']): ?><div class="mt-2"><span class="text-muted small">Remarks:</span> <?= e($r['remarks']) ?></div><?php endif; ?>

    <div class="row mt-5 pt-3">
        <div class="col-6"><div class="small text-muted">Received by: <?= e($r['receiver'] ?? '-') ?></div></div>
        <div class="col-6 text-end">
            <div class="border-top border-2 d-inline-block px-4 pt-1 small text-muted">Authorized Signature</div>
        </div>
    </div>
</div>

<div class="text-center no-print pb-4">
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    <a class="btn btn-light" href="receipts.php"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<?php include '../includes/footer.php'; ?>

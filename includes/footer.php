    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script>
document.addEventListener('keydown', function(e) {
    if (e.key === 'F1') { e.preventDefault(); window.location.href = '<?= BASE_URL ?>/pages/cash_received.php'; }
    else if (e.key === 'F2') { e.preventDefault(); window.location.href = '<?= BASE_URL ?>/pages/cash_paid.php'; }
    else if (e.key === 'F3') { e.preventDefault(); window.location.href = '<?= BASE_URL ?>/pages/voucher_form.php'; }
});
</script>
</body>
</html>

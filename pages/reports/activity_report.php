<?php
require_once '../../includes/auth.php';
require_login();
require_permission('reports.view');
$title = 'Activity Report';
$active = 'reports';

$from = $_GET['from'] ?? date('Y-01-01');
$to = $_GET['to'] ?? date('Y-m-d');

$calls = db_all("SELECT cl.*, l.name AS lead_name FROM call_logs cl LEFT JOIN leads l ON l.id = cl.lead_id WHERE DATE(cl.call_date) BETWEEN ? AND ? ORDER BY cl.call_date DESC", [$from, $to]);
$meetings = db_all("SELECT m.*, l.name AS lead_name, c.full_name AS customer_name FROM meetings m LEFT JOIN leads l ON l.id = m.lead_id LEFT JOIN customers c ON c.id = m.customer_id WHERE m.meeting_date BETWEEN ? AND ? ORDER BY m.meeting_date DESC", [$from . ' 00:00:00', $to . ' 23:59:59']);
$tasks = db_all("SELECT t.*, u.full_name AS assigned_name FROM tasks t LEFT JOIN users u ON u.id = t.assigned_to WHERE t.due_date BETWEEN ? AND ? ORDER BY t.due_date", [$from, $to]);

$taskStatus = []; $completedTasks = 0;
foreach ($tasks as $t) {
    $s = $t['status']; $taskStatus[$s] = ($taskStatus[$s] ?? 0) + 1;
    if ($s === 'completed') $completedTasks++;
}
$totalCalls = count($calls); $inbound = 0; $outbound = 0;
foreach ($calls as $c) { if ($c['direction'] === 'inbound') $inbound++; else $outbound++; }
$completedMeetings = 0;
foreach ($meetings as $m) { if ($m['status'] === 'completed') $completedMeetings++; }
$taskCompletion = count($tasks) > 0 ? ($completedTasks / count($tasks) * 100) : 0;
include '../../includes/header.php';
?>

<style>
@media print { .no-print,.sidebar,.main-header,.main-footer,.quick-action-btn{display:none!important}.main-content{margin:0!important;padding:10px!important}.card{border:none!important;box-shadow:none!important}body{font-size:11px}.table td,.table th{padding:4px 6px!important;font-size:11px} }
</style>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-activity me-2"></i>Activity Report</h5>
    <div class="ms-auto"><button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print</button></div>
</div>

<form method="get" class="card mb-3 no-print">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-2"><label class="form-label small text-muted mb-0">From</label><input type="date" name="from" class="form-control" value="<?= e($from) ?>"></div>
            <div class="col-md-2"><label class="form-label small text-muted mb-0">To</label><input type="date" name="to" class="form-control" value="<?= e($to) ?>"></div>
            <div class="col-md-2"><label class="form-label mb-0">&nbsp;</label><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>View</button></div>
        </div>
    </div>
</form>

<div class="card print-only" style="display:none"><div class="text-center mb-2"><h5 class="mb-0"><?= e(setting('company_name', APP_NAME)) ?></h5><div class="small text-muted">Activity Report from <?= fmt_date($from) ?> to <?= fmt_date($to) ?></div></div></div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card stat-card bg-grad-blue"><div class="stat-body"><div class="stat-icon"><i class="bi bi-telephone"></i></div><div><div class="stat-label">TOTAL CALLS</div><div class="stat-value"><?= $totalCalls ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-green"><div class="stat-body"><div class="stat-icon"><i class="bi bi-camera-video"></i></div><div><div class="stat-label">MEETINGS</div><div class="stat-value"><?= count($meetings) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-orange"><div class="stat-body"><div class="stat-icon"><i class="bi bi-list-task"></i></div><div><div class="stat-label">TASKS</div><div class="stat-value"><?= count($tasks) ?></div></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card bg-grad-purple"><div class="stat-body"><div class="stat-icon"><i class="bi bi-check-circle"></i></div><div><div class="stat-label">TASK COMPLETION</div><div class="stat-value"><?= number_format($taskCompletion, 0) ?>%</div></div></div></div></div>
</div>

<div class="row g-3 mb-3 no-print">
    <div class="col-md-6"><div class="card"><div class="card-header"><i class="bi bi-bar-chart me-2"></i>Activity Summary</div><div class="card-body"><canvas id="barChart" height="250"></canvas></div></div></div>
    <div class="col-md-6"><div class="card"><div class="card-header"><i class="bi bi-pie-chart me-2"></i>Task Status</div><div class="card-body"><canvas id="taskChart" height="250"></canvas></div></div></div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-telephone me-2"></i>Calls (<?= $totalCalls ?>)</div>
            <div class="card-body p-0" style="max-height:300px;overflow-y:auto">
                <table class="table table-hover mb-0 table-sm">
                    <thead><tr><th>Date</th><th>Lead</th><th>Dir</th><th>Note</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($calls, 0, 20) as $c): ?>
                        <tr><td class="small"><?= date('d M', strtotime($c['call_date'])) ?></td><td><?= e($c['lead_name'] ?? '-') ?></td><td><span class="badge bg-<?= $c['direction'] === 'inbound' ? 'success' : 'primary' ?>"><?= e($c['direction']) ?></span></td><td class="small"><?= e(mb_substr($c['note'] ?? '', 0, 30)) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$calls): ?><tr><td colspan="4" class="text-center text-muted">No calls</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-camera-video me-2"></i>Meetings (<?= count($meetings) ?>)</div>
            <div class="card-body p-0" style="max-height:300px;overflow-y:auto">
                <table class="table table-hover mb-0 table-sm">
                    <thead><tr><th>Date</th><th>Lead</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($meetings, 0, 20) as $m): ?>
                        <tr><td class="small"><?= date('d M', strtotime($m['meeting_date'])) ?></td><td><?= e($m['lead_name'] ?? $m['customer_name'] ?? '-') ?></td><td><?= status_badge($m['status']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$meetings): ?><tr><td colspan="3" class="text-center text-muted">No meetings</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-list-task me-2"></i>Tasks (<?= count($tasks) ?>)</div>
            <div class="card-body p-0" style="max-height:300px;overflow-y:auto">
                <table class="table table-hover mb-0 table-sm">
                    <thead><tr><th>Due</th><th>Title</th><th>Priority</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($tasks, 0, 20) as $t): ?>
                        <tr><td class="small"><?= fmt_date($t['due_date']) ?></td><td><?= e(mb_substr($t['title'], 0, 25)) ?></td><td><?= status_badge($t['priority']) ?></td><td><?= status_badge($t['status']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$tasks): ?><tr><td colspan="4" class="text-center text-muted">No tasks</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('barChart'), { type: 'bar', data: { labels: ['Calls', 'Meetings', 'Tasks'], datasets: [{ data: [<?= $totalCalls ?>, <?= count($meetings) ?>, <?= count($tasks) ?>], backgroundColor: ['#36a2eb','#4bc0c0','#ffce56'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } });
new Chart(document.getElementById('taskChart'), { type: 'doughnut', data: { labels: <?= json_encode(array_keys($taskStatus)) ?>, datasets: [{ data: <?= json_encode(array_values($taskStatus)) ?>, backgroundColor: ['#ffce56','#36a2eb','#4bc0c0'] }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
</script>

<?php include '../../includes/footer.php'; ?>

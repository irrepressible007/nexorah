<?php
session_start();
require_once '../config/database.php';
require_once 'includes/auth_check.php';

$database = new Database();
$db = $database->getConnection();

// ?? Global stats ????????????????????????????????????????????????????????????─
$stats = $db->query("
    SELECT
        COUNT(*) AS total_reg,
        SUM(approval_status='pending')  AS pending,
        SUM(approval_status='approved') AS approved,
        SUM(approval_status='rejected') AS rejected,
        SUM(CASE WHEN payment_status='paid' THEN total_amount ELSE 0 END) AS revenue,
        SUM(registration_type='campus_ambassador') AS ca_apps,
        SUM(registration_type='volunteer')         AS vol_apps
    FROM registrations
")->fetch(PDO::FETCH_ASSOC);

$total_events = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();
$total_msg    = $db->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();

// ?? Per-event breakdown ??????????????????????????????????????????????????????─
$event_stats = $db->query("
    SELECT e.id, e.title, e.event_date, e.status AS event_status,
           COUNT(r.id)                                 AS total_reg,
           SUM(r.approval_status='pending')            AS pending,
           SUM(r.approval_status='approved')           AS approved,
           SUM(r.approval_status='rejected')           AS rejected,
           SUM(CASE WHEN r.payment_status='paid' THEN r.total_amount ELSE 0 END) AS revenue,
           SUM(r.registration_type='campus_ambassador') AS ca_count,
           SUM(r.registration_type='volunteer')         AS vol_count
    FROM events e
    LEFT JOIN registrations r ON r.event_id = e.id
    GROUP BY e.id
    ORDER BY e.event_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ?? Recent 12 registrations for activity feed ????????????????????????????????─
$recent = $db->query("
    SELECT r.id, r.full_name, r.email, r.registration_type, r.approval_status,
           r.total_amount, r.registration_date, e.title AS event_title
    FROM registrations r
    LEFT JOIN events e ON r.event_id = e.id
    ORDER BY r.registration_date DESC LIMIT 12
")->fetchAll(PDO::FETCH_ASSOC);

// ?? Payment method breakdown ??????????????????????????????????????????????????
$methods = $db->query("
    SELECT COALESCE(payment_method,'bkash') AS method, COUNT(*) AS cnt
    FROM registrations GROUP BY method ORDER BY cnt DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard – Nexopay Admin</title>
<link rel="stylesheet" href="../assets/css/admin.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
/* ??─ LAYOUT ?????????????????????????????????????? */
.dash-wrap { padding: 28px 24px; }
.two-col   { display:grid; grid-template-columns:1fr 380px; gap:24px; margin-top:24px; }
@media(max-width:1100px){ .two-col { grid-template-columns:1fr; } }

/* ??─ KPI CARDS ??????????????????????????????????─ */
.kpi-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:16px; margin-bottom:26px; }
.kpi-card { background:#fff; border-radius:14px; padding:20px 16px; box-shadow:0 2px 14px rgba(0,0,0,.06);
            display:flex; align-items:center; gap:14px; transition:transform .2s; position:relative; overflow:hidden; }
.kpi-card::before { content:''; position:absolute; inset:0; border-radius:14px;
                    transition:opacity .2s; opacity:0; }
.kpi-card:hover { transform:translateY(-3px); }
.kpi-card:hover::before { opacity:1; }
.kpi-icon { width:50px; height:50px; border-radius:14px; display:flex; align-items:center;
            justify-content:center; font-size:1.4rem; flex-shrink:0; position:relative; z-index:1; }
.kpi-num  { font-size:1.8rem; font-weight:800; color:#111; line-height:1; position:relative; z-index:1; }
.kpi-label{ font-size:.78rem; color:#6b7280; font-weight:600; margin-top:2px; position:relative; z-index:1; }
.kpi-trend{ font-size:.75rem; font-weight:600; padding:2px 8px; border-radius:50rem; margin-top:5px; display:inline-block; }

.c-blue   { background:#eff6ff; color:#2563eb; }
.c-yellow { background:#fefce8; color:#ca8a04; }
.c-green  { background:#f0fdf4; color:#16a34a; }
.c-red    { background:#fef2f2; color:#dc2626; }
.c-purple { background:#faf5ff; color:#7c3aed; }
.c-orange { background:#fff7ed; color:#ea580c; }
.c-teal   { background:#f0fdfa; color:#0d9488; }

/* ??─ SECTION HEADERS ????????????????????????????─ */
.section-h { font-size:1.1rem; font-weight:700; color:#111; margin:0 0 16px; display:flex;
             align-items:center; gap:8px; }
.section-h i { color:#6366f1; }

/* ??─ EVENT STAT CARDS ???????????????????????????? */
.event-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:16px; }
.ev-card { background:#fff; border-radius:14px; box-shadow:0 2px 12px rgba(0,0,0,.06);
           overflow:hidden; transition:transform .2s; }
.ev-card:hover { transform:translateY(-3px); }
.ev-card-head { padding:16px 20px; background:linear-gradient(135deg,#6366f1,#8b5cf6);
                color:#fff; }
.ev-card-head h3 { font-size:1rem; font-weight:700; margin:0 0 4px; }
.ev-card-head .date { font-size:.8rem; opacity:.85; }
.ev-card-body { padding:16px 20px; }
.ev-stats { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:14px; }
.ev-stat  { text-align:center; }
.ev-stat-num   { font-size:1.4rem; font-weight:800; }
.ev-stat-label { font-size:.72rem; color:#6b7280; font-weight:600; margin-top:2px; }
.ev-prog-bar { height:6px; background:#e5e7eb; border-radius:50rem; overflow:hidden; margin-top:4px; }
.ev-prog-fill { height:100%; border-radius:50rem; transition:width .6s ease; }
.ev-footer { display:flex; justify-content:space-between; align-items:center;
             padding:12px 20px; border-top:1px solid #f1f5f9; }
.ev-revenue { font-size:1rem; font-weight:700; color:#16a34a; }
.ev-link { font-size:.82rem; color:#6366f1; text-decoration:none; font-weight:600; }
.ev-link:hover { text-decoration:underline; }

.status-dot { width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:5px; }
.dot-upcoming { background:#f59e0b; }
.dot-ongoing  { background:#10b981; }
.dot-past     { background:#94a3b8; }

/* ??─ ACTIVITY FEED ??????????????????????????????─ */
.activity-card { background:#fff; border-radius:14px; box-shadow:0 2px 14px rgba(0,0,0,.06);
                 padding:20px; height:fit-content; position:sticky; top:20px; }
.activity-list { list-style:none; padding:0; margin:0; }
.activity-list li { padding:11px 0; border-bottom:1px solid #f1f5f9; display:flex; gap:12px; align-items:flex-start; }
.activity-list li:last-child { border-bottom:none; }
.act-avatar { width:36px; height:36px; border-radius:50%; background:#ede9fe; color:#7c3aed;
              display:flex; align-items:center; justify-content:center; font-weight:700;
              font-size:.85rem; flex-shrink:0; }
.act-name  { font-weight:600; color:#1e293b; font-size:.88rem; }
.act-meta  { font-size:.78rem; color:#94a3b8; margin-top:2px; }
.act-badge { font-size:.72rem; padding:2px 8px; border-radius:50rem; font-weight:700; display:inline-block; margin-top:3px; }
.ab-pending  { background:#fef9c3; color:#92400e; }
.ab-approved { background:#dcfce7; color:#166534; }
.ab-rejected { background:#fee2e2; color:#991b1b; }

/* ??─ CHART CARD ?????????????????????????????????? */
.chart-card { background:#fff; border-radius:14px; box-shadow:0 2px 14px rgba(0,0,0,.06); padding:20px; margin-bottom:24px; }

/* ??─ PAYMENT METHOD BARS ????????????????????????─ */
.method-row { display:flex; align-items:center; gap:12px; margin-bottom:12px; }
.method-label { width:110px; font-size:.85rem; font-weight:600; color:#374151; }
.method-bar-wrap { flex:1; height:10px; background:#f1f5f9; border-radius:50rem; overflow:hidden; }
.method-bar-fill { height:100%; border-radius:50rem; }
.method-count { font-size:.85rem; font-weight:700; color:#6b7280; width:30px; text-align:right; }

@media(max-width:768px){
    .kpi-grid { grid-template-columns:repeat(2,1fr); }
    .ev-stats  { grid-template-columns:1fr 1fr; }
    .dash-wrap { padding:16px; }
}
</style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main class="main-content dash-wrap">

    <!-- ?? Welcome ??????????????????????????????????????????????????????─ -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:24px">
        <div>
            <h1 style="font-size:1.6rem;font-weight:800;color:#111;margin:0">
                <i class="fas fa-hand-sparkles" style="color:#f59e0b;"></i> Welcome back, <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?>!
            </h1>
            <p style="color:#6b7280;font-size:.9rem;margin:4px 0 0">
                <?= date('l, d F Y') ?> &nbsp;&middot;&nbsp; Here's what's happening with Nexopay events.
            </p>
        </div>
        <?php if ($stats['pending'] > 0): ?>
        <a href="registrations.php?status=pending" style="padding:10px 20px;background:#fef9c3;color:#92400e;border-radius:10px;text-decoration:none;font-weight:700;border:1px solid #fde68a;font-size:.9rem;">
            <i class="fas fa-bell"></i> <?= $stats['pending'] ?> Pending Review
        </a>
        <?php endif; ?>
    </div>

    <!-- ?? Dedicated Revenue Overview ???????????????????????????????????????? -->
    <div style="background: linear-gradient(135deg, #10b981, #059669); border-radius: 16px; padding: 30px; margin-bottom: 24px; color: white; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.2);">
        <div>
            <h2 style="margin: 0; font-size: 1.2rem; font-weight: 600; opacity: 0.9;"><i class="fas fa-wallet"></i> Total Lifetime Revenue</h2>
            <div style="font-size: 3.5rem; font-weight: 800; line-height: 1.2; margin-top: 5px;">&#2547;<?= number_format($stats['revenue'], 0) ?></div>
            <p style="margin: 5px 0 0; opacity: 0.8; font-size: 0.95rem;">Accumulated from all approved event registrations globally.</p>
        </div>
        <div style="font-size: 5rem; opacity: 0.15; padding-right: 20px;">
            <i class="fas fa-chart-line"></i>
        </div>
    </div>

    <!-- ?? KPI Grid ?????????????????????????????????????????????????????? -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon c-blue"><i class="fas fa-calendar"></i></div>
            <div><div class="kpi-num"><?= $total_events ?></div><div class="kpi-label">Total Events</div></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon c-teal"><i class="fas fa-users"></i></div>
            <div><div class="kpi-num"><?= number_format($stats['total_reg']) ?></div><div class="kpi-label">Total Registrations</div></div>
        </div>
        <div class="kpi-card" style="border:2px solid <?= $stats['pending'] > 0 ? '#fde68a' : '#e5e7eb' ?>">
            <div class="kpi-icon c-yellow"><i class="fas fa-clock"></i></div>
            <div>
                <div class="kpi-num"><?= number_format($stats['pending']) ?></div>
                <div class="kpi-label">Awaiting Approval</div>
                <?php if ($stats['pending'] > 0): ?>
                <span class="kpi-trend" style="background:#fef9c3;color:#92400e">Action needed!</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon c-green"><i class="fas fa-check-circle"></i></div>
            <div><div class="kpi-num"><?= number_format($stats['approved']) ?></div><div class="kpi-label">Approved</div></div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon c-purple"><i class="fas fa-graduation-cap"></i></div>
            <div><div class="kpi-num"><?= number_format($stats['ca_apps']) ?></div><div class="kpi-label">CA Applications</div></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon c-orange"><i class="fas fa-hands-helping"></i></div>
            <div><div class="kpi-num"><?= number_format($stats['vol_apps']) ?></div><div class="kpi-label">Volunteer Applications</div></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon c-red" style="background:#fee2e2"><i class="fas fa-envelope"></i></div>
            <div><div class="kpi-num"><?= $total_msg ?></div><div class="kpi-label">Messages</div>
            <?php if ($total_msg > 0): ?>
            <a href="messages.php" style="font-size:.75rem;color:#6366f1">View &rarr;</a>
            <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ?? Per-Event Cards ??????????????????????????????????????????????─ -->
    <h2 class="section-h"><i class="fas fa-chart-bar"></i> Event-by-Event Breakdown</h2>
    <div class="event-cards" style="margin-bottom:30px">
    <?php foreach ($event_stats as $ev):
        $total = max(1, $ev['total_reg']);
        $pct_approved = round(($ev['approved'] / $total) * 100);
        $pct_pending  = round(($ev['pending']  / $total) * 100);
        $dot_cls = ['upcoming'=>'dot-upcoming','ongoing'=>'dot-ongoing','past'=>'dot-past'][$ev['event_status']] ?? 'dot-past';
    ?>
    <div class="ev-card">
        <div class="ev-card-head">
            <h3>
                <span class="status-dot <?= $dot_cls ?>"></span>
                <?= htmlspecialchars($ev['title']) ?>
            </h3>
            <div class="date"><i class="fas fa-calendar-alt"></i> <?= $ev['event_date'] ? date('d M Y', strtotime($ev['event_date'])) : '&mdash;' ?>
                &nbsp;&middot;&nbsp; <?= ucfirst($ev['event_status'] ?? '&mdash;') ?>
            </div>
        </div>
        <div class="ev-card-body">
            <div class="ev-stats">
                <div class="ev-stat">
                    <div class="ev-stat-num" style="color:#6366f1"><?= $ev['total_reg'] ?></div>
                    <div class="ev-stat-label">Total</div>
                </div>
                <div class="ev-stat">
                    <div class="ev-stat-num" style="color:#ca8a04"><?= $ev['pending'] ?></div>
                    <div class="ev-stat-label">Pending</div>
                </div>
                <div class="ev-stat">
                    <div class="ev-stat-num" style="color:#16a34a"><?= $ev['approved'] ?></div>
                    <div class="ev-stat-label">Approved</div>
                </div>
            </div>

            <!-- Approval progress bar -->
            <div style="font-size:.77rem;color:#94a3b8;margin-bottom:4px">Approval Rate: <strong style="color:#16a34a"><?= $pct_approved ?>%</strong></div>
            <div class="ev-prog-bar">
                <div class="ev-prog-fill" style="width:<?= $pct_approved ?>%;background:#22c55e"></div>
            </div>

            <!-- CA + Volunteer counts -->
            <?php if ($ev['ca_count'] > 0 || $ev['vol_count'] > 0): ?>
            <div style="display:flex;gap:10px;margin-top:12px">
                <?php if ($ev['ca_count'] > 0): ?>
                <span style="background:#ede9fe;color:#6d28d9;padding:3px 9px;border-radius:50rem;font-size:.77rem;font-weight:700">
                    <i class="fas fa-graduation-cap"></i> <?= $ev['ca_count'] ?> CA
                </span>
                <?php endif; ?>
                <?php if ($ev['vol_count'] > 0): ?>
                <span style="background:#ecfdf5;color:#047857;padding:3px 9px;border-radius:50rem;font-size:.77rem;font-weight:700">
                    🤝 <?= $ev['vol_count'] ?> Volunteers
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="ev-footer">
            <div class="ev-revenue">&#2547;<?= number_format($ev['revenue'], 0) ?> collected</div>
            <a href="registrations.php?event_id=<?= $ev['id'] ?>" class="ev-link">View Registrations &rarr;</a>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <!-- ?? Two-column section: chart + feed ????????????????????????????─ -->
    <div class="two-col">
        <!-- Left: Charts -->
        <div>
            <!-- Revenue bar chart per event -->
            <div class="chart-card">
                <h2 class="section-h" style="margin-bottom:18px"><i class="fas fa-chart-bar"></i> Revenue by Event</h2>
                <canvas id="revenueChart" height="120"></canvas>
            </div>

            <!-- Payment method breakdown -->
            <div class="chart-card">
                <h2 class="section-h"><i class="fas fa-credit-card"></i> Payment Methods</h2>
                <?php
                $max_m = max(array_column($methods, 'cnt') ?: [1]);
                $method_names  = ['bkash'=>'bKash','bank_transfer'=>'Bank Transfer','nagad'=>'Nagad','cash'=>'Cash'];
                $method_colors = ['bkash'=>'#d946ef','bank_transfer'=>'#3b82f6','nagad'=>'#f59e0b','cash'=>'#10b981'];
                foreach ($methods as $m):
                    $pct = round(($m['cnt'] / $max_m) * 100);
                    $mlabel = $method_names[$m['method']] ?? ucfirst($m['method']);
                    $mcolor = $method_colors[$m['method']] ?? '#6366f1';
                ?>
                <div class="method-row">
                    <div class="method-label"><?= $mlabel ?></div>
                    <div class="method-bar-wrap">
                        <div class="method-bar-fill" style="width:<?= $pct ?>%;background:<?= $mcolor ?>"></div>
                    </div>
                    <div class="method-count"><?= $m['cnt'] ?></div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($methods)): ?>
                <p style="color:#94a3b8;font-size:.9rem">No data yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Live Activity Feed -->
        <div>
            <div class="activity-card">
                <h2 class="section-h">
                    <i class="fas fa-stream"></i> Live Activity Feed
                    <span style="margin-left:auto;font-size:.78rem;color:#94a3b8;font-weight:400">Last 12</span>
                </h2>
                <ul class="activity-list" id="actFeed">
                <?php foreach ($recent as $ra):
                    $initials = strtoupper(substr($ra['full_name'], 0, 1));
                    $since = human_time_diff(strtotime($ra['registration_date']));
                    function human_time_diff($ts) {
                        $diff = time() - $ts;
                        if ($diff < 60) return 'Just now';
                        if ($diff < 3600) return floor($diff/60) . 'm ago';
                        if ($diff < 86400) return floor($diff/3600) . 'h ago';
                        return floor($diff/86400) . 'd ago';
                    }
                ?>
                <li>
                    <div class="act-avatar"><?= $initials ?></div>
                    <div>
                        <div class="act-name"><?= htmlspecialchars($ra['full_name']) ?></div>
                        <div class="act-meta"><?= htmlspecialchars($ra['event_title'] ?? '&mdash;') ?> &middot; <?= $since ?></div>
                        <span class="act-badge ab-<?= $ra['approval_status'] ?>">
                            <?= ['pending'=>'⏳ Pending','approved'=>'<i class="fas fa-check-circle"></i> Approved','rejected'=>'❌ Rejected'][$ra['approval_status']] ?? $ra['approval_status'] ?>
                        </span>
                    </div>
                    <span style="margin-left:auto;font-size:.85rem;font-weight:700;color:#16a34a;white-space:nowrap">
                        &#2547;<?= number_format($ra['total_amount'] ?? 0, 0) ?>
                    </span>
                </li>
                <?php endforeach; ?>
                <?php if (empty($recent)): ?>
                <li style="text-align:center;color:#94a3b8;padding:30px 0">No registrations yet.</li>
                <?php endif; ?>
                </ul>
                <a href="registrations.php" style="display:block;text-align:center;margin-top:14px;font-size:.85rem;color:#6366f1;font-weight:600;text-decoration:none;padding:10px;background:#f5f3ff;border-radius:8px">
                    View All Registrations &rarr;
                </a>
            </div>
        </div>
    </div>

</main>

<script>
// ?? Revenue Chart ????????????????????????????????????????????????????????????
const ctx = document.getElementById('revenueChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map(fn($e) => substr($e['title'], 0, 25) . (strlen($e['title']) > 25 ? '…' : ''), $event_stats)) ?>,
            datasets: [{
                label: 'Revenue (BDT)',
                data: <?= json_encode(array_column($event_stats, 'revenue')) ?>,
                backgroundColor: '#818cf8',
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false },
                tooltip: { callbacks: { label: ctx => '&#2547;' + Number(ctx.raw).toLocaleString() } } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' },
                     ticks: { callback: v => '&#2547;' + v.toLocaleString() } },
                x: { grid: { display: false } }
            }
        }
    });
}
</script>
</body>
</html>

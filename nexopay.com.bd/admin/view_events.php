<?php
session_start();
require_once '../config/database.php';
require_once 'includes/auth_check.php';

$database = new Database();
$db = $database->getConnection();

// Fetch active events and their detailed stats
$event_stats = $db->query("
    SELECT e.id, e.title, e.event_date, e.status AS event_status, e.fee,
           COUNT(r.id)                                 AS total_reg,
           SUM(r.approval_status='pending')            AS pending,
           SUM(r.approval_status='approved')           AS approved,
           SUM(r.approval_status='rejected')           AS rejected,
           SUM(CASE WHEN r.payment_status='paid' THEN r.total_amount ELSE 0 END) AS revenue,
           SUM(r.registration_type='general')          AS gen_count,
           SUM(r.registration_type='campus_ambassador') AS ca_count,
           SUM(r.registration_type='volunteer')         AS vol_count
    FROM events e
    LEFT JOIN registrations r ON r.event_id = e.id
    WHERE e.status IN ('upcoming', 'ongoing')
    GROUP BY e.id
    ORDER BY e.event_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Monitor Active Events – Nexopay Admin</title>
<link rel="stylesheet" href="../assets/css/admin.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
.dash-wrap { padding: 28px 24px; }

/* ─── EVENT STAT CARDS ────────────────────────────── */
.event-cards { display:flex; flex-direction:column; gap:20px; }
.ev-card { background:#fff; border-radius:14px; box-shadow:0 2px 14px rgba(0,0,0,.08);
           overflow:hidden; transition:transform .2s; }
.ev-card-head { padding:18px 24px; background:linear-gradient(135deg,#111,#1e293b);
                color:#fff; display:flex; justify-content:space-between; align-items:center;}
.ev-card-head h2 { font-size:1.4rem; font-weight:800; margin:0 0 6px; }
.ev-card-head .meta { font-size:.9rem; color:#94a3b8; }
.ev-card-body { padding:24px; display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:20px; }

.ev-stat-box { display:flex; align-items:center; gap:16px; padding:16px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0; }
.ev-stat-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
.ev-stat-num { font-size:1.6rem; font-weight:800; color:#1e293b; line-height:1; }
.ev-stat-label { font-size:.8rem; color:#64748b; font-weight:600; margin-top:4px; text-transform:uppercase; letter-spacing:.5px; }

.icon-blue { background:#dbeafe; color:#2563eb; }
.icon-teal { background:#ccfbf1; color:#0d9488; }
.icon-purple { background:#f3e8ff; color:#9333ea; }
.icon-green { background:#dcfce7; color:#16a34a; }
.icon-yellow { background:#fef3c7; color:#d97706; }
.icon-red { background:#fee2e2; color:#dc2626; }

.ev-footer { padding:16px 24px; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:#f1f5f9; }
.ev-revenue { font-size:1.1rem; font-weight:800; color:#059669; }
.ev-btn { padding:8px 18px; background:#6366f1; color:#fff; border-radius:8px; text-decoration:none; font-weight:600; font-size:.9rem; transition:background .2s; }
.ev-btn:hover { background:#4f46e5; }

.status-dot { width:10px; height:10px; border-radius:50%; display:inline-block; margin-right:6px; animation:pulse 2s infinite; }
.dot-upcoming { background:#f59e0b; }
.dot-ongoing { background:#22c55e; }
@keyframes pulse { 0% { box-shadow:0 0 0 0 rgba(0,0,0,0.2); } 70% { box-shadow:0 0 0 6px rgba(0,0,0,0); } 100% { box-shadow:0 0 0 0 rgba(0,0,0,0); } }

@media(max-width:768px){
    .ev-card-head { flex-direction:column; align-items:flex-start; gap:10px; }
    .ev-card-body { grid-template-columns:1fr 1fr; }
}
</style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<main class="main-content dash-wrap">

    <div style="margin-bottom:28px;">
        <h1 style="font-size:1.8rem;font-weight:800;color:#111;margin:0">
            <i class="fas fa-chart-pie" style="color:#6366f1;margin-right:8px"></i> Event Monitoring
        </h1>
        <p style="color:#6b7280;font-size:1rem;margin:6px 0 0">
            Real-time statistics for all active events (Upcoming & Ongoing).
        </p>
    </div>

    <div class="event-cards">
        <?php if (empty($event_stats)): ?>
            <div style="background:#fff;padding:40px;text-align:center;border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.05);">
                <i class="fas fa-folder-open" style="font-size:3rem;color:#cbd5e1;margin-bottom:14px;"></i>
                <h3 style="margin:0;color:#64748b;">No active events found.</h3>
                <p style="color:#94a3b8;">Create a new upcoming event to monitor statistics here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($event_stats as $ev): 
                $status_color = $ev['event_status'] == 'ongoing' ? 'dot-ongoing' : 'dot-upcoming';
            ?>
            <div class="ev-card">
                <div class="ev-card-head">
                    <div>
                        <h2><span class="status-dot <?= $status_color ?>"></span> <?= htmlspecialchars($ev['title']) ?></h2>
                        <div class="meta">
                            <i class="fas fa-calendar-alt"></i> <?= date('d M Y', strtotime($ev['event_date'])) ?> 
                            &nbsp;•&nbsp; Registration Fee: ৳<?= number_format($ev['fee'] ?? 0, 2) ?>
                        </div>
                    </div>
                    <span style="background:<?php echo $ev['event_status'] == 'ongoing' ? '#dcfce7;color:#166534' : '#fef3c7;color:#92400e'; ?>;padding:6px 14px;border-radius:50rem;font-size:.85rem;font-weight:700;text-transform:uppercase;">
                        <?= htmlspecialchars($ev['event_status']) ?>
                    </span>
                </div>
                
                <div class="ev-card-body">
                    <div class="ev-stat-box">
                        <div class="ev-stat-icon icon-blue"><i class="fas fa-users"></i></div>
                        <div><div class="ev-stat-num"><?= $ev['total_reg'] ?></div><div class="ev-stat-label">Total Registrations</div></div>
                    </div>
                    
                    <div class="ev-stat-box">
                        <div class="ev-stat-icon icon-teal"><i class="fas fa-ticket-alt"></i></div>
                        <div><div class="ev-stat-num"><?= $ev['gen_count'] ?></div><div class="ev-stat-label">General</div></div>
                    </div>
                    
                    <div class="ev-stat-box">
                        <div class="ev-stat-icon icon-purple"><i class="fas fa-graduation-cap"></i></div>
                        <div><div class="ev-stat-num"><?= $ev['ca_count'] ?></div><div class="ev-stat-label">CA Reg.</div></div>
                    </div>
                    
                    <div class="ev-stat-box">
                        <div class="ev-stat-icon icon-green"><i class="fas fa-hands-helping"></i></div>
                        <div><div class="ev-stat-num"><?= $ev['vol_count'] ?></div><div class="ev-stat-label">Volunteers</div></div>
                    </div>

                    <div class="ev-stat-box" style="border-left:4px solid #10b981;">
                        <div class="ev-stat-icon icon-green" style="background:transparent;"><i class="fas fa-check-circle"></i></div>
                        <div><div class="ev-stat-num"><?= $ev['approved'] ?></div><div class="ev-stat-label">Approved</div></div>
                    </div>

                    <div class="ev-stat-box" style="border-left:4px solid #f59e0b;">
                        <div class="ev-stat-icon icon-yellow" style="background:transparent;"><i class="fas fa-clock"></i></div>
                        <div><div class="ev-stat-num"><?= $ev['pending'] ?></div><div class="ev-stat-label">Pending</div></div>
                    </div>
                </div>

                <div class="ev-footer">
                    <div class="ev-revenue">
                        <i class="fas fa-money-bill-wave" style="margin-right:6px"></i> Revenue: ৳<?= number_format($ev['revenue'], 0) ?>
                    </div>
                    <a href="registrations.php?event_id=<?= $ev['id'] ?>" class="ev-btn">
                        Manage Registrations <i class="fas fa-arrow-right" style="margin-left:4px"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</main>
</body>
</html>

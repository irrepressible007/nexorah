?<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// ── Single event detail view (triggered by ?id=N) ─────────────────────────
$singleEvent = false;
$event = null;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $eventId = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM events WHERE id = :id");
    $stmt->bindParam(':id', $eventId);
    $stmt->execute();
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($event) $singleEvent = true;
}

// ── Card grid: fetch all events ───────────────────────────────────────────
$events = [];
if (!$singleEvent) {
    $query = "SELECT * FROM events ORDER BY
              CASE WHEN status='upcoming' THEN 1
                   WHEN status='ongoing'  THEN 2
                   WHEN status='past'     THEN 3
              END, event_date ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $singleEvent ? htmlspecialchars($event['title']).' — ' : ''; ?>Events | Nexopay</title>
    <link rel="icon"         type="image/png" href="assets/images/public/Nex Pay new logo.png">
    <link rel="shortcut icon"                  href="assets/images/public/Nex Pay new logo.png">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script defer src="assets/js/main.js"></script>
    <style>
        /* ── Override: make cards clickable with no visual weirdness ─── */
        .event-card-link {
            display: block;
            text-decoration: none;
            color: inherit;
            height: 100%;
            border-radius: 12px;
        }
        .event-card-link:hover .event-card {
            box-shadow: 0 12px 30px rgba(0,0,0,.45);
            transform: translateY(-5px);
            border-color: rgba(52,152,219,.5);
        }
        /* Stop hover style from applying doubly */
        .event-card:hover { transform: none; box-shadow: none; border-color: rgba(255,255,255,.1); }

        /* ── Card footer: price left, buttons right ──────────────────── */
        .card-footer-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: auto;
            padding-top: 14px;
            border-top: 1px solid rgba(255,255,255,.08);
        }
        .card-price {
            font-size: .9rem;
            font-weight: 600;
            color: #94a3b8;
            white-space: nowrap;
        }
        .card-btn-group { display: flex; gap: 6px; flex-wrap: wrap; }
        .btn-card {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: .82rem;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
            transition: filter .2s, transform .2s;
        }
        .btn-card:hover { filter: brightness(1.12); transform: translateY(-1px); }
        .btn-card-blue  { background: linear-gradient(135deg,#3498db,#2980b9); color:#fff; }
        .btn-card-green { background: linear-gradient(135deg,#27ae60,#2ecc71); color:#fff; }

        /* ── Single Event Detail ─────────────────────────────────────── */
        .detail-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin: 36px 0 60px;
            align-items: flex-start;
        }
        .detail-main  { flex: 1 1 55%; min-width: 0; }
        .detail-side  { flex: 0 0 300px; }

        .detail-main .hero-img {
            width: 100%;
            height: auto;
            max-height: 420px;
            object-fit: cover;
            border-radius: 14px;
            margin-bottom: 1.5rem;
            box-shadow: 0 6px 24px rgba(0,0,0,.25);
            display: block;
        }
        .detail-main h2 {
            font-size: clamp(1.5rem,3vw,2.1rem);
            font-weight: 800;
            color: #fff;
            margin-bottom: 1rem;
            line-height: 1.3;
        }
        .detail-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 1.2rem;
        }
        .detail-meta span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: .88rem;
            color: #94a3b8;
            background: rgba(255,255,255,.06);
            padding: 5px 12px;
            border-radius: 20px;
        }
        .status-pill {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: .78rem;
            font-weight: 700;
            margin-bottom: 1rem;
            letter-spacing: .4px;
        }
        .pill-upcoming { background: rgba(39,174,96,.2);  color: #2ecc71; border: 1px solid rgba(39,174,96,.3); }
        .pill-ongoing  { background: rgba(241,196,15,.2); color: #f1c40f; border: 1px solid rgba(241,196,15,.3); }
        .pill-past     { background: rgba(149,165,166,.2);color: #95a5a6; border: 1px solid rgba(149,165,166,.3); }

        .detail-body {
            color: #cbd5e1;
            line-height: 1.85;
            font-size: 1rem;
        }
        /* Let stored HTML render properly */
        .detail-body h2, .detail-body h3, .detail-body h4, .detail-body h5 {
            color: #e2e8f0; margin: 1.2rem 0 .5rem; font-weight: 700;
        }
        .detail-body ul, .detail-body ol {
            padding-left: 1.5rem; margin-bottom: 1rem;
        }
        .detail-body li { margin-bottom: .35rem; }
        .detail-body strong { color: #e2e8f0; }
        .detail-body a { color: #3498db; }

        /* ── Sidebar widgets ─────────────────────────────────────────── */
        .side-widget {
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 14px;
            padding: 22px;
            margin-bottom: 20px;
        }
        .side-widget h3 {
            font-size: 1rem;
            font-weight: 700;
            color: #e2e8f0;
            border-bottom: 1px solid rgba(255,255,255,.1);
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .info-list {
            list-style: none;
            padding: 0; margin: 0;
        }
        .info-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 14px;
            font-size: .9rem;
            color: #94a3b8;
        }
        .info-list li i { color: #3498db; margin-top: 3px; min-width: 16px; }
        .info-list li strong { color: #e2e8f0; display: block; margin-bottom: 2px; }

        .btn-action {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 13px;
            border-radius: 10px;
            font-weight: 700;
            font-size: .95rem;
            text-decoration: none;
            margin-bottom: 10px;
            transition: filter .2s, transform .2s;
        }
        .btn-action:hover { filter: brightness(1.1); transform: translateY(-2px); }
        .btn-action:last-child { margin-bottom: 0; }
        .btn-carnival  { background: linear-gradient(135deg,#3498db,#2980b9); color:#fff; }
        .btn-ca        { background: linear-gradient(135deg,#8e44ad,#9b59b6); color:#fff; }
        .btn-volunteer { background: linear-gradient(135deg,#e67e22,#f39c12); color:#fff; }

        .status-notice {
            padding: 12px 14px;
            border-radius: 10px;
            font-size: .88rem;
            line-height: 1.5;
        }
        .notice-ongoing { background: rgba(241,196,15,.15); color: #f1c40f; border: 1px solid rgba(241,196,15,.3); }
        .notice-past    { background: rgba(149,165,166,.15); color: #95a5a6; border: 1px solid rgba(149,165,166,.3); }

        /* ── Back link ───────────────────────────────────────────────── */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #3498db;
            font-weight: 600;
            font-size: .9rem;
            text-decoration: none;
            margin: 28px 0 8px;
        }
        .back-link:hover { text-decoration: underline; }

        /* ── Register pill override ── */
        .nav-register-pill {
            background-color: #fff; color: #002147 !important; font-weight: bold;
            text-transform: uppercase; padding: 8px 24px !important; border-radius: 50rem;
            text-decoration: none; display: inline-block; box-shadow: 0 2px 4px rgba(0,0,0,.075);
        }

        /* ── Mobile ──────────────────────────────────────────────────── */
        @media(max-width: 768px) {
            .detail-wrap  { flex-direction: column; }
            .detail-side  { flex: 1 1 auto; width: 100%; }
            .card-footer-row { flex-direction: column; align-items: flex-start; }
            .card-btn-group  { width: 100%; }
            .btn-card { flex: 1; text-align: center; }
        }
    </style>
</head>
<body>
<!-- Navigation -->
<nav class="navbar">
    <div class="container">
        <div class="nav-brand">
            <a href="index.php" style="display:flex;align-items:center;text-decoration:none;">
                <img src="assets/images/public/Nex Pay new logo.png" alt="Nexopay Logo" style="height:45px;width:auto;">
            </a>
        </div>
        <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
            <i class="fas fa-bars"></i>
        </button>
        <ul class="nav-menu">
            <li><a href="index.php">Home</a></li>
            <li><a href="events.php" class="active">Events</a></li>
            <li><a href="about.php">About Us</a></li>
            <li><a href="https://nexorah.com.bd/" target="_blank" rel="noopener">Nexorah</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><a href="user-panel.php">My Registrations</a></li>
            <li><a href="register.php" class="nav-register-pill">Register</a></li>
        </ul>
    </div>
</nav>

<div class="container">

<?php if ($singleEvent): ?>

    <!-- ── SINGLE EVENT DETAIL ─────────────────────────────────────────── -->
    <a href="events.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to All Events</a>

    <?php
    $img = (!empty($event['image']) && file_exists($event['image']))
         ? htmlspecialchars($event['image'])
         : 'assets/images/placeholder.jpg';
    $status = $event['status'] ?? 'past';
    $pillClass = 'pill-' . $status;
    $isCarnival = (stripos($event['title'], 'national steam carnival') !== false);
    ?>

    <div class="detail-wrap">
        <!-- Left main -->
        <div class="detail-main">
            <img src="<?php echo $img; ?>"
                 alt="<?php echo htmlspecialchars($event['title']); ?>"
                 class="hero-img">

            <div class="detail-meta">
                <span><i class="far fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($event['event_date'])); ?></span>
                <?php if (!empty($event['location'])): ?>
                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?></span>
                <?php endif; ?>
                <span><i class="fas fa-ticket-alt"></i> ৳<?php echo number_format($event['fee'], 2); ?> BDT</span>
            </div>

            <span class="status-pill <?php echo $pillClass; ?>"><?php echo strtoupper($status); ?></span>
            <h2><?php echo htmlspecialchars($event['title']); ?></h2>

            <!-- Render stored HTML description as-is -->
            <div class="detail-body">
                <?php echo $event['description']; ?>
            </div>
        </div>

        <!-- Right sidebar -->
        <div class="detail-side">
            <div class="side-widget">
                <h3>Event Details</h3>
                <ul class="info-list">
                    <li>
                        <i class="far fa-calendar-alt"></i>
                        <div><strong>Date</strong><?php echo date('F d, Y', strtotime($event['event_date'])); ?></div>
                    </li>
                    <li>
                        <i class="fas fa-ticket-alt"></i>
                        <div><strong>Base Ticket Fee</strong>৳<?php echo number_format($event['fee'], 2); ?> BDT</div>
                    </li>
                    <?php if (!empty($event['location'])): ?>
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <div><strong>Location</strong><?php echo htmlspecialchars($event['location']); ?></div>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="side-widget">
                <h3>Action Hub</h3>
                <?php if ($status === 'upcoming'): ?>
                    <?php if ($isCarnival): ?>
                        <p style="font-size:.85rem;color:#94a3b8;margin-bottom:14px;">
                            This 3-day signature event has its own modular registration portal.
                        </p>
                        <a href="register.php?event_id=<?php echo (int)$event['id']; ?>" class="btn-action btn-carnival">
                            <i class="fas fa-ticket-alt"></i> Carnival Registration
                        </a>
                        <a href="register-ca.php?event_id=<?php echo (int)$event['id']; ?>" class="btn-action btn-ca">
                            <i class="fas fa-user-tie"></i> Register as CA
                        </a>
                        <a href="register-volunteer.php?event_id=<?php echo (int)$event['id']; ?>" class="btn-action btn-volunteer">
                            <i class="fas fa-hands-helping"></i> Register as Volunteer
                        </a>
                    <?php else: ?>
                        <p style="font-size:.85rem;color:#94a3b8;margin-bottom:14px;">Registration is open! Secure your spot now.</p>
                        <a href="event-details.php?id=<?php echo (int)$event['id']; ?>" class="btn-action btn-carnival">
                            <i class="fas fa-clipboard-list"></i> Register Now
                        </a>
                    <?php endif; ?>
                <?php elseif ($status === 'ongoing'): ?>
                    <div class="status-notice notice-ongoing">
                        <i class="fas fa-play-circle"></i>
                        This event is currently ongoing. New registrations are not available.
                    </div>
                <?php else: ?>
                    <div class="status-notice notice-past">
                        <i class="fas fa-history"></i>
                        This event has ended. Registration is no longer available.
                    </div>
                <?php endif; ?>
            </div>
        </div><!-- /detail-side -->
    </div>

<?php else: ?>

    <!-- ── EVENT CARD GRID ─────────────────────────────────────────────── -->
    <div class="page-header reveal">
        <h1>All Events</h1>
        <p>Discover our upcoming, ongoing, and past events</p>
    </div>

    <div class="events-grid reveal">
        <?php if (!empty($events)): ?>
            <?php foreach ($events as $ev):
                $img = (!empty($ev['image']) && file_exists($ev['image']))
                     ? htmlspecialchars($ev['image'])
                     : 'assets/images/placeholder.jpg';
                $isUpcoming   = ($ev['status'] === 'upcoming');
                $isCarnival   = (stripos($ev['title'], 'national steam carnival') !== false);
                $registerHref = $isCarnival
                    ? 'register.php?event_id=' . $ev['id']
                    : 'event-details.php?id='  . $ev['id'];
            ?>
            <div class="event-card-link">
                <div class="event-card <?php echo $ev['status']; ?>" onclick="window.location.href='events.php?id=<?php echo $ev['id']; ?>'" style="cursor: pointer;">
                    <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($ev['title']); ?>">
                    <div class="event-info">
                        <div class="event-meta">
                            <span><i class="far fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($ev['event_date'])); ?></span>
                            <?php if ($ev['status'] === 'past'): ?>
                                <span class="status status-past">Past</span>
                            <?php elseif ($ev['status'] === 'ongoing'): ?>
                                <span class="status status-ongoing">Ongoing</span>
                            <?php endif; ?>
                        </div>
                        <h3><?php echo htmlspecialchars($ev['title']); ?></h3>
                        <p><?php echo htmlspecialchars(substr(strip_tags($ev['description']), 0, 110)); ?>…</p>

                        <div class="card-footer-row">
                            <span class="card-price"><i class="fas fa-ticket-alt"></i> ৳<?php echo number_format($ev['fee'], 2); ?></span>
                            <div class="card-btn-group">
                                <!-- Stop click bubbling to the card link so button goes to its own url -->
                                <a href="events.php?id=<?php echo $ev['id']; ?>"
                                   class="btn-card btn-card-blue"
                                   onclick="event.stopPropagation();">Details</a>
                                <?php if ($isUpcoming): ?>
                                <a href="<?php echo $registerHref; ?>"
                                   class="btn-card btn-card-green"
                                   onclick="event.stopPropagation();">Register</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-events" style="grid-column:1/-1;text-align:center;padding:60px 20px;color:#94a3b8;">
                <i class="fas fa-calendar-times" style="font-size:3rem;opacity:.4;margin-bottom:1rem;display:block;"></i>
                <p>No events available at the moment.</p>
            </div>
        <?php endif; ?>
    </div>

<?php endif; ?>

</div><!-- /container -->

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Nexopay</h3>
                <p>Smart. Secure. Seamless. Premium payments, perfected.</p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Contact Info</h4>
                <p><i class="fas fa-envelope"></i> contact@nexopay.com</p>
                <p><i class="fas fa-phone"></i> +880123456789</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Nexopay. All rights reserved.</p>
        </div>
    </div>
</footer>
<button id="scrollToTop" class="scroll-to-top"><i class="fas fa-arrow-up"></i></button>
</body>
</html>

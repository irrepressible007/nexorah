<?php
// Load database connections (excluded from Git for security)
require_once __DIR__ . '/config/nexorah_db.php';
require_once __DIR__ . '/config/database.php';

// ── Routing: determine which view to show ──────────────────────────────────
$event_id  = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$show_form = false;
$event     = null;
$error_message = '';

// Map of event IDs → which form template to use
// Add new event forms here as they are created
$supported_forms = [4 => 'steam_carnival', 6 => 'steam_olympiad'];

if ($event_id && array_key_exists($event_id, $supported_forms)) {
    // Fetch the event from the main nexorah DB
    try {
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND is_registration_open = 1");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($event) $show_form = $supported_forms[$event_id];
    } catch (Exception $e) {
        // fall through to hub view
    }
}

// ── POST Handling ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $event) {
    try {
        $db_obj = new Database();
        $db = $db_obj->getConnection();

        // Resolve nexopay event_id by matching the nexorah event title.
        // This ensures registrations.event_id correctly joins with nexopay's events table.
        $nexopay_event_id = null;
        try {
            $evStmt = $db->prepare("SELECT id FROM events WHERE title LIKE :t LIMIT 1");
            $evStmt->execute([':t' => '%' . $event['title'] . '%']);
            $evRow = $evStmt->fetch(PDO::FETCH_ASSOC);
            if ($evRow) $nexopay_event_id = (int)$evRow['id'];
        } catch (Exception $ev_e) {
            // If lookup fails, fall back to nexorah event id (FK already dropped)
            $nexopay_event_id = $event['id'];
        }
        // If no match found in nexopay events, store the nexorah event_id anyway (FK dropped)
        if (!$nexopay_event_id) $nexopay_event_id = $event['id'];

        $reg_type = $_POST['reg_type'] ?? '';
        $full_name = '';
        $email = ''; // STEAM form doesn't explicitly ask for email
        $phone = '';
        $guardian_number = '';
        $ticket_quantity = 1;
        
        $extra_data = [
            'reg_type'        => $reg_type,
            'segments'        => $_POST['segments'] ?? [],
            'nexorah_event_id'=> $event['id'],  // store original nexorah ID for traceability
        ];

        if ($reg_type === 'solo') {
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['contact'] ?? '');
            $guardian_number = trim($_POST['emergency_contact'] ?? '');
            
            $extra_data['category'] = $_POST['category'] ?? '';
            $extra_data['institution'] = trim($_POST['institution'] ?? '');
            $extra_data['class_grade'] = trim($_POST['class_grade'] ?? '');
            $extra_data['guardian_name'] = trim($_POST['guardian_name'] ?? '');
            $extra_data['ca_reference'] = trim($_POST['ca_reference'] ?? '');
            $ticket_quantity = 1;

            if (empty($full_name) || empty($phone)) {
                throw new Exception("Full Name and Contact Number are required.");
            }
        } elseif ($reg_type === 'group') {
            $full_name = trim($_POST['grp_p1_name'] ?? 'Group Leader');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['grp_p1_contact'] ?? '');
            $guardian_number = trim($_POST['grp_emergency_contact'] ?? '');
            
            $extra_data['group_category'] = $_POST['group_category'] ?? '';
            $extra_data['p1'] = [
                'name'        => trim($_POST['grp_p1_name'] ?? ''),
                'institution' => trim($_POST['grp_p1_institution'] ?? ''),
                'class'       => trim($_POST['grp_p1_class'] ?? ''),
                'contact'     => trim($_POST['grp_p1_contact'] ?? '')
            ];
            $extra_data['p2'] = [
                'name'        => trim($_POST['grp_p2_name'] ?? ''),
                'institution' => trim($_POST['grp_p2_institution'] ?? ''),
                'class'       => trim($_POST['grp_p2_class'] ?? ''),
                'contact'     => trim($_POST['grp_p2_contact'] ?? '')
            ];
            $ticket_quantity = 2;
            
            if (!empty($_POST['grp_p3_name'])) {
                $extra_data['p3'] = [
                    'name'        => trim($_POST['grp_p3_name'] ?? ''),
                    'institution' => trim($_POST['grp_p3_institution'] ?? ''),
                    'class'       => trim($_POST['grp_p3_class'] ?? ''),
                    'contact'     => trim($_POST['grp_p3_contact'] ?? '')
                ];
                $ticket_quantity = 3;
            }
            $extra_data['guardian_name'] = trim($_POST['grp_guardian_name'] ?? '');
            $extra_data['ca_reference'] = trim($_POST['grp_ca_reference'] ?? '');

            if (empty($_POST['grp_p1_name']) || empty($_POST['grp_p2_name'])) {
                throw new Exception("Group requires at least 2 participant names.");
            }
        } else {
            throw new Exception("Invalid registration type selected.");
        }

        $total_amount = floatval($_POST['total_amount'] ?? 0);
        $payment_method = $_POST['payment_method'] ?? '';
        $transaction_id = trim($_POST['transaction_id'] ?? '');
        $extra_data['sender_number'] = trim($_POST['sender_number'] ?? '');

        // Validation for payment (if amount > 0, txn ID is required)
        if ($total_amount > 0 && empty($transaction_id)) {
            throw new Exception("Transaction ID is required for paid registrations.");
        }

        $stmt = $db->prepare("
            INSERT INTO registrations (
                event_id, full_name, email, phone, guardian_number, 
                ticket_quantity, total_amount, payment_method, transaction_id, 
                registration_type, extra_data
            ) VALUES (
                :event_id, :full_name, :email, :phone, :guardian_number, 
                :ticket_quantity, :total_amount, :payment_method, :transaction_id, 
                :registration_type, :extra_data
            )
        ");

        $stmt->execute([
            ':event_id'          => $nexopay_event_id,
            ':full_name'         => $full_name,
            ':email'             => $email,
            ':phone'             => $phone,
            ':guardian_number'   => $guardian_number,
            ':ticket_quantity'   => $ticket_quantity,
            ':total_amount'      => $total_amount,
            ':payment_method'    => strtolower($payment_method),
            ':transaction_id'    => $transaction_id,
            ':registration_type' => 'general',
            ':extra_data'        => json_encode($extra_data)
        ]);

        header("Location: success.php?status=success&type=general&txn=" . urlencode($transaction_id));
        exit;

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// ── Hub view: fetch all events with open registration ──────────────────────
$open_events = [];
if (!$show_form) {
    try {
        $stmt = $pdo->query("SELECT * FROM events WHERE is_registration_open = 1 ORDER BY event_date ASC");
        $open_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $open_events = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $show_form ? htmlspecialchars($event['title']) . ' — Register' : 'Register for an Event'; ?> | Nexopay</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/public/Nex Pay new logo.png">
    <link rel="shortcut icon" href="assets/images/public/Nex Pay new logo.png">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script defer src="assets/js/main.js"></script>
    <style>
        /* ── Hero ──────────────────────────────────────────────────── */
        .register-hero {
            padding: 120px 0 60px;
            min-height: 45vh;
            background: linear-gradient(rgba(0,0,0,0.65), rgba(0,0,0,0.75)),
                        url('assets/images/public/Steam Carnival Wallpaper.jpeg') center/cover no-repeat;
            display: flex; align-items: center;
        }
        .register-hero .hero-content { text-align: center; color: #fff; }
        .register-hero h1 { font-size: clamp(1.8rem, 4vw, 2.8rem); font-weight: 800; margin-bottom: .6rem; text-shadow: 0 2px 8px rgba(0,0,0,.4); }
        .register-hero p  { font-size: 1.05rem; opacity: .88; max-width: 560px; margin: 0 auto; }

        /* ── Hub: event grid ───────────────────────────────────────── */
        .hub-section { padding: 60px 0 80px; background: #f4f7fb; }
        .hub-section .section-label { font-size: .85rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: #3498db; margin-bottom: .4rem; }
        .hub-section h2 { font-size: 1.9rem; font-weight: 800; color: #1a2a4a; margin-bottom: 2.5rem; }

        .event-hub-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.8rem; }
        .event-hub-card {
            background: #fff; border-radius: 16px; overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
            transition: transform .25s, box-shadow .25s;
            display: flex; flex-direction: column;
        }
        .event-hub-card:hover { transform: translateY(-6px); box-shadow: 0 12px 35px rgba(0,0,0,.14); }
        .event-hub-card-img { position: relative; height: 200px; overflow: hidden; }
        .event-hub-card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s; }
        .event-hub-card:hover .event-hub-card-img img { transform: scale(1.05); }
        .hub-reg-badge {
            position: absolute; top: 14px; right: 14px;
            background: #27ae60; color: #fff; font-size: .75rem;
            font-weight: 700; padding: 4px 12px; border-radius: 20px;
            letter-spacing: .5px;
        }
        .event-hub-card-body { padding: 1.4rem 1.5rem; flex: 1; display: flex; flex-direction: column; }
        .event-hub-card-body h3 { font-size: 1.15rem; font-weight: 700; color: #1a2a4a; margin-bottom: .5rem; }
        .event-hub-card-meta { font-size: .85rem; color: #6b7280; margin-bottom: .5rem; display: flex; gap: 1rem; flex-wrap: wrap; }
        .event-hub-card-meta span { display: flex; align-items: center; gap: 5px; }
        .event-hub-card-body p  { font-size: .9rem; color: #555; line-height: 1.6; margin-bottom: 1.2rem; flex: 1; }
        .btn-register-hub {
            display: inline-block; padding: 12px 28px; background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff; font-weight: 700; border-radius: 10px; text-decoration: none;
            text-align: center; transition: filter .2s, transform .2s;
        }
        .btn-register-hub:hover { filter: brightness(1.1); transform: translateY(-2px); }

        .no-events-box { text-align: center; padding: 60px 20px; color: #6b7280; }
        .no-events-box i { font-size: 3rem; margin-bottom: 1rem; opacity:.4; }
        .no-events-box p { font-size: 1rem; }

        /* ── Form view ──────────────────────────────────────────────── */
        .form-section { padding: 60px 0 80px; background: #f4f7fb; }
        .back-link { display: inline-flex; align-items: center; gap: 7px; color: #3498db; font-weight: 600; text-decoration: none; margin-bottom: 1.5rem; font-size: .95rem; }
        .back-link:hover { text-decoration: underline; }
        .registration-form {
            padding: 2.5rem; background: #fff;
            border-top: 3px solid #3498db; border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,.07);
        }
        .registration-form .form-desc { color: #666; margin-bottom: 2rem; }

        .registration-form .form-control {
            width: 100%; padding: 15px;
            border: 2px solid #e9ecef; border-radius: 10px;
            font-size: 1rem; background: white; font-family: inherit;
            transition: border-color .25s, box-shadow .25s;
            -webkit-appearance: none; appearance: none;
        }
        .registration-form .form-control:focus {
            outline: none; border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52,152,219,.15);
            transform: translateY(-1px);
        }

        /* Reg-type toggle */
        .reg-type-toggle { display: flex; gap: 1rem; margin-bottom: 2rem; }
        .reg-type-btn {
            flex: 1; padding: 16px 12px; border: 2px solid #e9ecef;
            border-radius: 12px; background: #f9fafb; cursor: pointer;
            text-align: center; transition: all .2s; font-family: inherit;
            font-size: 1rem; font-weight: 600; color: #374151;
        }
        .reg-type-btn .btn-icon { font-size: 1.6rem; display: block; margin-bottom: 4px; }
        .reg-type-btn .btn-sub  { font-size: 0.78rem; font-weight: 400; color: #6b7280; margin-top: 2px; display: block; }
        .reg-type-btn.active    { border-color: #3498db; background: #eff6ff; color: #1d4ed8; }
        .reg-type-btn.active .btn-sub { color: #3b82f6; }
        @media(max-width:480px){ .reg-type-toggle { flex-direction: column; } }

        /* Participant cards */
        .participant-card { border: 2px solid #e9ecef; border-radius: 12px; margin-bottom: 1.25rem; overflow: hidden; }
        .participant-card-header {
            background: linear-gradient(135deg,#f0f9ff,#e0f2fe);
            padding: 10px 16px; font-weight: 700; font-size: 0.95rem;
            color: #0369a1; display: flex; align-items: center; gap: 8px;
        }
        .participant-card-header.optional { background: linear-gradient(135deg,#f9fafb,#f3f4f6); color: #6b7280; }
        .participant-card-body { padding: 16px 16px 8px; }
        .participant-badge { font-size: 0.75rem; font-weight: 600; padding: 2px 8px; border-radius: 20px; background: #0369a1; color: #fff; margin-left: auto; }
        .participant-badge.optional { background: #9ca3af; }

        /* Group notice */
        .group-notice {
            background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px;
            padding: 12px 16px; margin-bottom: 1.5rem; font-size: 0.9rem; color: #92400e;
            display: flex; align-items: flex-start; gap: 10px;
        }
        .group-notice i { flex-shrink: 0; margin-top: 2px; }

        /* option labels */
        .option-label {
            display: flex; align-items: flex-start; gap: 10px;
            font-weight: normal; font-size: 1rem; color: #333;
            cursor: pointer; line-height: 1.5; padding: 5px 0;
        }
        .option-label input { flex-shrink: 0; width: auto; margin: 3px 0 0; cursor: pointer; touch-action: manipulation; }

        /* Segment headings & cards */
        .segments-main-label { font-weight: bold; font-size: 1.1rem; color: #2c3e50; margin-bottom: 1rem; display: block; }
        .segments-main-label span { font-weight: normal; font-size: .9em; color: #666; }
        .segment-groups { display: flex; flex-direction: column; gap: 1rem; margin-bottom: .5rem; }
        .segment-card { border: 2px solid #e9ecef; border-radius: 12px; overflow: hidden; transition: border-color .2s; }
        .segment-card:focus-within { border-color: #3498db; }
        .segment-card-header { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; font-weight: 700; font-size: .95rem; }
        .seg-rate-badge { font-size: .82rem; font-weight: 600; padding: 3px 10px; border-radius: 20px; white-space: nowrap; }
        .seg-fee-note { font-size: .82rem; color: #6b7280; font-style: italic; margin: 0 0 8px; }

        .seg-olympiad .segment-card-header { background: linear-gradient(135deg,#eef4ff,#dbeafe); color: #1e40af; }
        .seg-olympiad .seg-rate-badge      { background: #1e40af; color: #fff; }
        .seg-olympiad .segment-subtotal    { color: #1e40af; }
        .seg-art .segment-card-header      { background: linear-gradient(135deg,#fff0f9,#fce7f3); color: #9d174d; }
        .seg-art .seg-rate-badge           { background: #9d174d; color: #fff; }
        .seg-art .segment-subtotal         { color: #9d174d; }
        .seg-photo .segment-card-header    { background: linear-gradient(135deg,#fffbeb,#fef3c7); color: #92400e; }
        .seg-photo .seg-rate-badge         { background: #92400e; color: #fff; }
        .seg-photo .segment-subtotal       { color: #92400e; }
        .seg-group .segment-card-header    { background: linear-gradient(135deg,#f0fdf4,#dcfce7); color: #166534; }
        .seg-group .seg-rate-badge         { background: #166534; color: #fff; }
        .seg-group .segment-subtotal       { color: #166534; }

        .segment-card-body   { padding: 10px 16px 4px; background: #fafafa; }
        .segment-card-footer { padding: 8px 16px; background: #f3f4f6; font-size: .9rem; font-weight: 600; text-align: right; border-top: 1px solid #e9ecef; }

        /* Grand total */
        #totalPriceDisplay {
            background: linear-gradient(45deg,#27ae60,#2ecc71); color: white;
            padding: 16px 20px; border-radius: 15px; font-weight: bold; font-size: 1.3rem;
            text-align: center; box-shadow: 0 8px 25px rgba(39,174,96,.3); margin: 1.5rem 0;
        }
        .grand-total-breakdown { display: flex; flex-direction: column; gap: 4px; font-size: .85rem; font-weight: 500; opacity: .92; margin-top: 6px; }

        /* Payment */
        .payment-section-divider { margin-top: 20px; padding-top: 20px; border-top: 2px solid #eee; }
        .payment-title { color: #2c3e50; font-size: 1.5rem; margin-bottom: 1rem; }
        .payment-instructions {
            color: #475569; margin-bottom: 1.5rem; line-height: 1.7;
            background: #f8f9fa; padding: 15px; border-radius: 8px;
            border-left: 4px solid #3498db; font-size: .95rem; word-break: break-word;
        }
        .payment-instructions strong { color: #e74c3c; }

        /* Submit */
        .btn-submit {
            background: linear-gradient(45deg,#3498db,#2980b9); color: white;
            width: 100%; padding: 15px; font-weight: bold; font-size: 1.1em;
            border-radius: 8px; border: none; cursor: pointer; transition: all .3s ease;
            touch-action: manipulation; font-family: inherit;
        }
        .btn-submit:hover  { filter: brightness(1.08); transform: translateY(-2px); box-shadow: 0 8px 25px rgba(52,152,219,.4); }
        .btn-submit:active { transform: translateY(0); }

        .nav-register-pill {
            background-color: #fff; color: #002147 !important; font-weight: bold;
            text-transform: uppercase; padding: 8px 24px !important; border-radius: 50rem;
            text-decoration: none; display: inline-block; box-shadow: 0 2px 4px rgba(0,0,0,.075);
        }

        .action-buttons-container { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem; }
        .action-buttons-right { display: flex; gap: 10px; flex-wrap: wrap; }

        @media(max-width:600px){
            .registration-form { padding: 1.5rem; }
            .segment-card-header { flex-wrap: wrap; gap: 6px; }
            .segment-card-footer { text-align: left; }
            #totalPriceDisplay   { font-size: 1.1rem; }
            .action-buttons-container { flex-direction: column; align-items: stretch; gap: 1.5rem; }
            .action-buttons-right { flex-direction: column; align-items: stretch; }
            .action-buttons-right a { text-align: center; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="index.php" style="display: flex; align-items: center; text-decoration: none;">
                    <img src="assets/images/public/Nex Pay new logo.png" alt="Nexopay Logo" style="height: 45px; width: auto;">
                </a>
            </div>
            <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
            <ul class="nav-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="https://nexorah.com.bd/" target="_blank" rel="noopener">Nexorah</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="user-panel.php">My Registrations</a></li>
                <li><a href="register.php" class="active nav-register-pill">Register</a></li>
            </ul>
        </div>
    </nav>

    <!-- ── HERO ────────────────────────────────────────────────────────── -->
    <section class="register-hero">
        <div class="container">
            <div class="hero-content">
                <?php if ($show_form && $event): ?>
                    <h1>Register for <?php echo htmlspecialchars($event['title']); ?></h1>
                    <p><?php echo htmlspecialchars($event['location']); ?></p>
                <?php else: ?>
                    <h1>Register for an Event</h1>
                    <p>Browse all events currently open for registration and secure your spot.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if ($show_form === 'steam_carnival' && $event): ?>
    <!-- ── STEAM CARNIVAL FORM ────────────────────────────────────────── -->
    <section class="form-section">
        <div class="container">
            <div class="action-buttons-container">
                <a href="register.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to all events</a>
                <div class="action-buttons-right">
                    <a href="register-ca.php?event_id=<?php echo $event['id']; ?>" class="btn-register-hub" style="background: linear-gradient(135deg, #8e44ad, #9b59b6); padding: 10px 20px; font-size: 0.9rem;"><i class="fas fa-user-tie"></i> Register as CA</a>
                    <a href="register-volunteer.php?event_id=<?php echo $event['id']; ?>" class="btn-register-hub" style="background: linear-gradient(135deg, #e67e22, #f39c12); padding: 10px 20px; font-size: 0.9rem;"><i class="fas fa-hands-helping"></i> Register as Volunteer</a>
                </div>
            </div>
            <form id="steamRegistrationForm" action="?event_id=<?php echo $event['id']; ?>" method="POST" class="registration-form">
                <h3>National STEAM Carnival Registration</h3>
                <?php if ($error_message): ?>
                <div style="background:#fee2e2;border:1.5px solid #fca5a5;border-radius:10px;padding:15px;margin-bottom:20px;color:#991b1b;font-weight:600">
                    <i class="fas fa-exclamation-circle" style="margin-right:8px;"></i> <?= htmlspecialchars($error_message) ?>
                </div>
                <?php endif; ?>
                <p class="form-desc">The event will span 3 days of exciting science, technology, and art competitions, with the Final Round being held at BUET.</p>

                <!-- ── STEP 1: Registration Type ──────────────────────── -->
                <div class="form-group mb-3">
                    <label class="segments-main-label">Registration Type *</label>
                    <div class="reg-type-toggle">
                        <button type="button" class="reg-type-btn" id="btnSolo" onclick="setRegType('solo')">
                            <span class="btn-icon">🧑</span>
                            Solo
                            <span class="btn-sub">Individual participant</span>
                        </button>
                        <button type="button" class="reg-type-btn" id="btnGroup" onclick="setRegType('group')">
                            <span class="btn-icon">👥</span>
                            Group
                            <span class="btn-sub">2–3 participants</span>
                        </button>
                    </div>
                    <input type="hidden" name="reg_type" id="regTypeInput" value="">
                </div>

                <!-- ── SOLO FORM ───────────────────────────────────────── -->
                <div id="soloForm" style="display:none;">

                    <!-- Segments (solo) -->
                    <div class="form-group mb-3">
                        <label class="segments-main-label">Segments * <span>(Can choose multiple across sections)</span></label>
                        <div class="segment-groups">

                            <div class="segment-card seg-olympiad">
                                <div class="segment-card-header">
                                    <span>🔬 Olympiad &amp; STEM</span>
                                    <span class="seg-rate-badge">200 BDT per segment</span>
                                </div>
                                <div class="segment-card-body">
                                    <p class="seg-fee-note">Select any segment(s) — each costs 200 BDT.</p>
                                    <label class="option-label"><input type="checkbox" name="segments[]" value="Olympiads"           data-section="olympiad" onchange="calculateTotal()"> Olympiads (Physics, Chemistry, Bio, Math, etc.)</label>
                                    <label class="option-label"><input type="checkbox" name="segments[]" value="Instant Story Writing" data-section="olympiad" onchange="calculateTotal()"> Instant Story Writing</label>
                                    <label class="option-label"><input type="checkbox" name="segments[]" value="Craftwork"           data-section="olympiad" onchange="calculateTotal()"> Craftwork</label>
                                    <label class="option-label"><input type="checkbox" name="segments[]" value="Web Design"          data-section="olympiad" onchange="calculateTotal()"> Web Design &amp; Development</label>
                                </div>
                                <div class="segment-card-footer">Section Fee: <span class="segment-subtotal" id="subtotal-olympiad">—</span></div>
                            </div>

                            <div class="segment-card seg-art">
                                <div class="segment-card-header">
                                    <span>🎨 Art Exhibition &amp; Competition</span>
                                    <span class="seg-rate-badge">200 BDT per segment</span>
                                </div>
                                <div class="segment-card-body">
                                    <p class="seg-fee-note">Select any segment(s) — each costs 200 BDT.</p>
                                    <label class="option-label"><input type="checkbox" name="segments[]" value="Art Competition" data-section="art" onchange="calculateTotal()"> Art Competition</label>
                                    <label class="option-label"><input type="checkbox" name="segments[]" value="Art Exhibition"  data-section="art" onchange="calculateTotal()"> Art Exhibition</label>
                                </div>
                                <div class="segment-card-footer">Section Fee: <span class="segment-subtotal" id="subtotal-art">—</span></div>
                            </div>

                            <div class="segment-card seg-photo">
                                <div class="segment-card-header">
                                    <span>📸 Photography</span>
                                    <span class="seg-rate-badge">300 BDT per segment</span>
                                </div>
                                <div class="segment-card-body">
                                    <p class="seg-fee-note">Select any photography type — each costs 300 BDT.</p>
                                    <label class="option-label"><input type="checkbox" name="segments[]" value="Mobile Photography" data-section="photo" onchange="calculateTotal()"> Mobile Photography</label>
                                    <label class="option-label"><input type="checkbox" name="segments[]" value="DSLR Photography"   data-section="photo" onchange="calculateTotal()"> DSLR Photography</label>
                                    <hr style="border:none;border-top:1px dashed #e5e7eb;margin:8px 0;">
                                    <label class="option-label"><input type="checkbox" name="segments[]" value="Workshop" data-section="workshop" onchange="calculateTotal()"> Workshop — <strong style="color:#16a34a;">Free</strong></label>
                                </div>
                                <div class="segment-card-footer">Photography Fee: <span class="segment-subtotal" id="subtotal-photo">—</span></div>
                            </div>

                        </div>
                    </div>

                    <!-- Category — all 5 -->
                    <div class="form-group mb-3 radio-group" style="display:flex;flex-direction:column;gap:6px;">
                        <label>Choose Category *</label>
                        <label class="option-label"><input type="radio" name="category" value="Pre-Primary" required> Pre-Primary (Play – Class 1)</label>
                        <label class="option-label"><input type="radio" name="category" value="Primary"> Primary (Class 2 – Class 5)</label>
                        <label class="option-label"><input type="radio" name="category" value="Junior"> Junior (Class 6 – Class 8)</label>
                        <label class="option-label"><input type="radio" name="category" value="Secondary"> Secondary (Class 9 – Class 10)</label>
                        <label class="option-label"><input type="radio" name="category" value="Senior"> Senior (Class 11 – Class 12 + Undergraduate)</label>
                    </div>

                    <div class="participant-card">
                        <div class="participant-card-header">
                            <i class="fas fa-user"></i> Your Details
                            <span class="participant-badge">Required</span>
                        </div>
                        <div class="participant-card-body">
                            <div class="form-group mb-3">
                                <label>Full Name *</label>
                                <input type="text" name="full_name" class="form-control" required autocomplete="name">
                            </div>
                            <div class="form-group mb-3">
                                <label>Email Address *</label>
                                <input type="email" name="email" class="form-control" required autocomplete="email">
                            </div>
                            <div class="form-group mb-3">
                                <label>Institution Name *</label>
                                <input type="text" name="institution" class="form-control" required autocomplete="organization">
                            </div>
                            <div class="form-group mb-3">
                                <label>Class / Grade *</label>
                                <input type="text" name="class_grade" class="form-control" required>
                            </div>
                            <div class="form-group mb-3">
                                <label>Contact Number *</label>
                                <input type="tel" name="contact" class="form-control" required autocomplete="tel" inputmode="tel">
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label>Emergency Contact Number *</label>
                        <input type="tel" name="emergency_contact" class="form-control" required inputmode="tel">
                    </div>
                    <div class="form-group mb-3">
                        <label>Guardian Name *</label>
                        <input type="text" name="guardian_name" class="form-control" required autocomplete="name">
                    </div>

                    <div class="form-group mb-3">
                        <label>CA Reference <span style="font-weight:normal;">(If any)</span></label>
                        <input type="text" name="ca_reference" class="form-control">
                    </div>
                </div><!-- /#soloForm -->

                <!-- ── GROUP FORM ──────────────────────────────────────── -->
                <div id="groupForm" style="display:none;">

                    <!-- 1. Segments -->
                    <div class="form-group mb-3">
                        <label class="segments-main-label">Segments * <span>(Can choose multiple)</span></label>
                        <div class="group-notice">
                            <i class="fas fa-info-circle"></i>
                            <span>Select the collaborative display events your group will participate in — the fee is charged per segment, covering the entire group.</span>
                        </div>
                        <div class="segment-groups">
                            <div class="segment-card seg-group">
                                <div class="segment-card-header">
                                    <span>👥 Group Display Events</span>
                                    <span class="seg-rate-badge">200 BDT per segment</span>
                                </div>
                                <div class="segment-card-body">
                                    <p class="seg-fee-note">Select any segment(s) — each costs 200 BDT for the whole group.</p>
                                    <label class="option-label"><input type="checkbox" name="segments[]" value="Project Display"   data-section="group" onchange="calculateTotal()"> Project Display</label>
                                    <label class="option-label"><input type="checkbox" name="segments[]" value="Wall Magazine"     data-section="group" onchange="calculateTotal()"> Wall Magazine</label>
                                    <label class="option-label"><input type="checkbox" name="segments[]" value="Scrapbook Display" data-section="group" onchange="calculateTotal()"> Scrapbook Display</label>
                                </div>
                                <div class="segment-card-footer">Section Fee: <span class="segment-subtotal" id="subtotal-group">—</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Category — Junior / Secondary / Senior only -->
                    <div class="form-group mb-3 radio-group" style="display:flex;flex-direction:column;gap:6px;">
                        <label>Choose Category *</label>
                        <label class="option-label"><input type="radio" name="group_category" value="Junior">    Junior (Class 6 – Class 8)</label>
                        <label class="option-label"><input type="radio" name="group_category" value="Secondary"> Secondary (Class 9 – Class 10)</label>
                        <label class="option-label"><input type="radio" name="group_category" value="Senior">    Senior (Class 11 – Class 12 + Undergraduate)</label>
                    </div>

                    <!-- 3. Participants -->
                    <div class="participant-card">
                        <div class="participant-card-header">
                            <i class="fas fa-user"></i> Participant 1
                            <span class="participant-badge">Required</span>
                        </div>
                        <div class="participant-card-body">
                            <div class="form-group mb-3"><label>Full Name *</label><input type="text" name="grp_p1_name" id="grpP1Name" class="form-control" autocomplete="name"></div>
                            <div class="form-group mb-3"><label>Email Address *</label><input type="email" name="email" class="form-control" autocomplete="email"></div>
                            <div class="form-group mb-3"><label>Institution Name *</label><input type="text" name="grp_p1_institution" class="form-control" autocomplete="organization"></div>
                            <div class="form-group mb-3"><label>Class / Grade *</label><input type="text" name="grp_p1_class" class="form-control"></div>
                            <div class="form-group mb-3"><label>Contact Number *</label><input type="tel" name="grp_p1_contact" class="form-control" inputmode="tel"></div>
                        </div>
                    </div>

                    <div class="participant-card">
                        <div class="participant-card-header">
                            <i class="fas fa-user"></i> Participant 2
                            <span class="participant-badge">Required</span>
                        </div>
                        <div class="participant-card-body">
                            <div class="form-group mb-3"><label>Full Name *</label><input type="text" name="grp_p2_name" id="grpP2Name" class="form-control" autocomplete="name"></div>
                            <div class="form-group mb-3"><label>Institution Name *</label><input type="text" name="grp_p2_institution" class="form-control" autocomplete="organization"></div>
                            <div class="form-group mb-3"><label>Class / Grade *</label><input type="text" name="grp_p2_class" class="form-control"></div>
                            <div class="form-group mb-3"><label>Contact Number *</label><input type="tel" name="grp_p2_contact" class="form-control" inputmode="tel"></div>
                        </div>
                    </div>

                    <div class="participant-card">
                        <div class="participant-card-header optional">
                            <i class="fas fa-user-plus"></i> Participant 3
                            <span class="participant-badge optional">Optional</span>
                        </div>
                        <div class="participant-card-body">
                            <div class="form-group mb-3"><label>Full Name</label><input type="text" name="grp_p3_name" class="form-control" autocomplete="name" placeholder="Leave blank if only 2 participants"></div>
                            <div class="form-group mb-3"><label>Institution Name</label><input type="text" name="grp_p3_institution" class="form-control" autocomplete="organization"></div>
                            <div class="form-group mb-3"><label>Class / Grade</label><input type="text" name="grp_p3_class" class="form-control"></div>
                            <div class="form-group mb-3"><label>Contact Number</label><input type="tel" name="grp_p3_contact" class="form-control" inputmode="tel"></div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label>Emergency Contact Number *</label>
                        <input type="tel" name="grp_emergency_contact" class="form-control" inputmode="tel">
                    </div>
                    <div class="form-group mb-3">
                        <label>Guardian Name *</label>
                        <input type="text" name="grp_guardian_name" class="form-control" autocomplete="name">
                    </div>

                    <!-- 4. CA Reference -->
                    <div class="form-group mb-3">
                        <label>CA Reference <span style="font-weight:normal;">(If any)</span></label>
                        <input type="text" name="grp_ca_reference" class="form-control">
                    </div>

                </div><!-- /#groupForm -->

                <!-- ── SHARED: Grand Total + Payment ─────────────────── -->
                <div id="sharedPayment" style="display:none;">

                    <div id="totalPriceDisplay">
                        Grand Total: <span id="grandTotalAmt">0</span> BDT
                        <div class="grand-total-breakdown" id="grandTotalBreakdown" style="display:none;">
                            <span id="breakOlympiad" style="display:none;">🔬 Olympiad &amp; STEM: <span id="brkOlympiadAmt">0</span> BDT</span>
                            <span id="breakArt"      style="display:none;">🎨 Art: <span id="brkArtAmt">0</span> BDT</span>
                            <span id="breakPhoto"    style="display:none;">📸 Photography: <span id="brkPhotoAmt">0</span> BDT</span>
                            <span id="breakGroup"    style="display:none;">👥 Group Display: <span id="brkGroupAmt">0</span> BDT</span>
                        </div>
                    </div>
                    <input type="hidden" name="total_amount" id="totalAmountInput" value="0">

                    <div class="form-group mb-3">
                        <label>Choose your Payment Method *</label>
                        <select name="payment_method" id="paymentMethod" class="form-control" required onchange="showPaymentSection()">
                            <option value="">-- Select Payment Method --</option>
                            <option value="Bkash">bKash</option>
                            <option value="Bank">Bank Deposit</option>
                        </select>
                    </div>

                    <div id="paymentSection" style="display:none;" class="payment-section-divider">
                        <h3 id="paymentTitle" class="payment-title">Payment Details</h3>
                        <div id="mobilePayInstructions">
                            <p class="payment-instructions">
                                1. Go to <strong>Payment</strong> option in your app or simply scan the QR<br>
                                2. Type the receiver number: <strong>01805548970 (Merchant)</strong><br>
                                3. Enter reference as your <strong>Name</strong> or <strong>CA Ref</strong><br>
                                4. Enter your exact total amount. (Example: <strong id="instructionAmount">200 BDT</strong>)<br>
                                5. Make the payment.<br>
                                6. Copy the <strong>Transaction ID</strong> given by your operator<br>
                                7. Paste the Transaction ID below<br><br>
                                <img src="assets/images/public/bKash qr.png" alt="bKash QR" style="max-width:260px; display:block; margin: 10px auto; border-radius: 8px;">
                            </p>
                        </div>
                        <div id="bankPayInstructions" style="display:none; margin-bottom:15px;">
                            <p class="payment-instructions">
                                1. Transfer your total amount: <strong id="bankInstructionAmount">0 BDT</strong> to:<br>
                            </p>
                            <div style="background:#f1f5f9; padding:15px; border-radius:8px; font-size:0.9rem;">
                                <strong>Bank:</strong> BRAC Bank PLC<br>
                                <strong>Branch Name:</strong> MAZAR ROAD SUB BRANCH<br>
                                <strong>Account Name:</strong> NEXOPAY<br>
                                <strong>Account Number:</strong> 2076517310001<br>
                                <strong>Routing Number:</strong> 060261997<br>
                                <strong>SWIFT Code:</strong> BRAKBDDH
                            </div>
                            <p class="payment-instructions" style="margin-top:10px;">
                                2. Save your Bank Receipt or Transaction Reference ID.
                            </p>
                        </div>
                        <div class="form-group mb-3">
                            <label><span id="methodNameLabel"></span> Number *</label>
                            <input type="tel" name="sender_number" id="senderNumber" class="form-control" inputmode="tel">
                        </div>
                        <div class="form-group mb-3">
                            <label><span id="methodTxnLabel"></span> Transaction ID *</label>
                            <input type="text" name="transaction_id" id="transactionId" class="form-control" autocomplete="off">
                        </div>
                        <div class="form-group mt-4">
                            <button type="submit" class="btn-submit">Complete Registration</button>
                        </div>
                    </div>

                </div><!-- /#sharedPayment -->

            </form>
        </div>
    </section>

    <?php elseif ($show_form === 'steam_olympiad' && $event): ?>
    <!-- ── STEAM OLYMPIAD FORM ────────────────────────────────────────── -->
    <section class="form-section">
        <div class="container">
            <div class="action-buttons-container">
                <a href="register.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to all events</a>
                <div class="action-buttons-right">
                    <a href="register-ca.php?event_id=<?php echo $event['id']; ?>" class="btn-register-hub" style="background: linear-gradient(135deg, #8e44ad, #9b59b6); padding: 10px 20px; font-size: 0.9rem;"><i class="fas fa-user-tie"></i> Register as CA</a>
                    <a href="register-volunteer.php?event_id=<?php echo $event['id']; ?>" class="btn-register-hub" style="background: linear-gradient(135deg, #e67e22, #f39c12); padding: 10px 20px; font-size: 0.9rem;"><i class="fas fa-hands-helping"></i> Register as Volunteer</a>
                </div>
            </div>
            <form id="steamOlympiadForm" action="?event_id=<?php echo $event['id']; ?>" method="POST" class="registration-form">
                <h3>National STEAM Olympiad Registration</h3>
                <?php if ($error_message): ?>
                <div style="background:#fee2e2;border:1.5px solid #fca5a5;border-radius:10px;padding:15px;margin-bottom:20px;color:#991b1b;font-weight:600">
                    <i class="fas fa-exclamation-circle" style="margin-right:8px;"></i> <?= htmlspecialchars($error_message) ?>
                </div>
                <?php endif; ?>
                <p class="form-desc">Join the National STEAM Olympiad! All registrations are Individual (Solo).</p>

                <!-- ── REQUIRED FIELDS ── -->
                <input type="hidden" name="reg_type" value="solo">
                
                <h4 class="form-heading"><i class="fas fa-user"></i> Participant Information</h4>
                <div class="form-grid">
                    <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" class="form-control" required></div>
                    <div class="form-group"><label>Email Address *</label><input type="email" name="email" class="form-control" required></div>
                    <div class="form-group"><label>Institution *</label><input type="text" name="institution" class="form-control" required></div>
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="class_grade" id="olympiadCategory" class="form-control" required onchange="handleCategoryChange()">
                            <option value="">Select Category...</option>
                            <option value="Elementary">Elementary (Class 1 - Class 3)</option>
                            <option value="Primary">Primary (Class 4 - Class 5)</option>
                            <option value="Junior">Junior (Class 6 - Class 8)</option>
                            <option value="Secondary">Secondary (Class 9 - Class 10)</option>
                            <option value="Senior">Senior (Class 11 - Class 12+)</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Contact Number *</label><input type="tel" name="contact" class="form-control" required></div>
                </div>

                <div class="form-group mb-4" style="margin-top:20px;">
                    <label class="segments-main-label">Segments * <span>(Can choose multiple)</span></label>
                    <div class="segment-groups">

                        <div class="segment-card seg-olympiad">
                            <div class="segment-card-header">
                                <span><i class="fas fa-brain"></i> Olympiad</span>
                                <span class="seg-rate-badge">200 BDT</span>
                            </div>
                            <div class="segment-card-body">
                                <label class="option-label"><input type="checkbox" name="segments[]" value="Olympiad" data-section="olympiad" onchange="calcOlympiadTotal()"> National STEAM Olympiad</label>
                            </div>
                        </div>

                        <div class="segment-card seg-art">
                            <div class="segment-card-header">
                                <span><i class="fas fa-palette"></i> Art</span>
                                <span class="seg-rate-badge">200 BDT</span>
                            </div>
                            <div class="segment-card-body">
                                <label class="option-label"><input type="checkbox" name="segments[]" value="Art Exhibition" data-section="art" onchange="calcOlympiadTotal()"> Art Exhibition</label>
                                <label class="option-label" id="artCompetitionWrapper"><input type="checkbox" name="segments[]" value="Art Competition" id="artCompCheckbox" data-section="art" onchange="calcOlympiadTotal()"> Art Competition</label>
                            </div>
                        </div>

                        <div class="segment-card seg-photo">
                            <div class="segment-card-header">
                                <span><i class="fas fa-camera"></i> Photography</span>
                                <span class="seg-rate-badge">300 BDT</span>
                            </div>
                            <div class="segment-card-body">
                                <label class="option-label"><input type="checkbox" name="segments[]" value="Mobile Photography" data-section="photo" onchange="calcOlympiadTotal()"> Mobile Photography</label>
                                <label class="option-label"><input type="checkbox" name="segments[]" value="DSLR Photography" data-section="photo" onchange="calcOlympiadTotal()"> DSLR Photography</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="total-bar">
                    <span class="total-label">Grand Total:</span>
                    <span class="total-amount"><span id="olympiadGrandTotalAmt">0</span> BDT</span>
                    <input type="hidden" name="total_amount" id="olympiadTotalInput" value="0">
                </div>
                
                <div id="olympiadSharedPayment" style="display:none; margin-top:20px;">
                    <div class="form-group mb-3">
                        <label>Choose your Payment Method *</label>
                        <select name="payment_method" id="olympiadPaymentMethod" class="form-control" onchange="showOlympiadPaymentSection()">
                            <option value="">-- Select Payment Method --</option>
                            <option value="bKash">bKash</option>
                            <option value="Bank">Bank Deposit</option>
                        </select>
                    </div>

                    <div id="olympiadPaymentSection" style="display:none;" class="payment-section-divider">
                        <h3 id="olympiadPaymentTitle" class="payment-title">Payment Details</h3>
                        
                        <div id="olympiadMobileInst">
                            <p class="payment-instructions" style="background:#f8f9fa; padding:15px; border-radius:8px; border-left:4px solid #3498db;">
                                1. Go to <strong>Payment</strong> option in your app or simply scan the QR<br>
                                2. Type the receiver number: <strong>01805548970 (Merchant)</strong><br>
                                3. Enter reference as your <strong>Name</strong> or <strong>CA Ref</strong><br>
                                4. Enter your exact total amount. (Example: <strong id="olympiadInstructionAmount">200 BDT</strong>)<br>
                                5. Make the payment.<br>
                                6. Copy the <strong>Transaction ID</strong> given by your operator<br>
                                7. Paste the Transaction ID below<br><br>
                                <img src="assets/images/public/bKash qr.png" alt="bKash QR" style="max-width:260px; display:block; margin: 10px auto; border-radius: 8px;">
                            </p>
                        </div>
                        <div id="olympiadBankInst" style="display:none; margin-bottom:15px;">
                            <p class="payment-instructions">Bank Transfer of <strong id="olympiadBankAmount">0 BDT</strong> to:</p>
                            <div style="background:#f1f5f9; padding:15px; border-radius:8px; font-size:0.9rem;">
                                <strong>Bank:</strong> BRAC Bank PLC<br>
                                <strong>Branch Name:</strong> MAZAR ROAD SUB BRANCH<br>
                                <strong>Account Name:</strong> NEXOPAY<br>
                                <strong>Account Number:</strong> 2076517310001<br>
                                <strong>Routing Number:</strong> 060261997<br>
                                <strong>SWIFT Code:</strong> BRAKBDDH
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label><span id="olympiadMethodNameLabel"></span> Number *</label>
                            <input type="tel" name="sender_number" id="olympiadSenderNumber" class="form-control" inputmode="tel" placeholder="e.g. 01XXXXXXXXX">
                        </div>
                        <div class="form-group mb-3">
                            <label><span id="olympiadMethodTxnLabel"></span> Transaction ID *</label>
                            <input type="text" name="transaction_id" id="olympiadTransactionId" class="form-control" autocomplete="off" placeholder="e.g. TRNX12345678">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Complete Registration</button>
            </form>
        </div>
    </section>

    <?php else: ?>
    <!-- ── EVENT HUB ───────────────────────────────────────────────────── -->
    <section class="hub-section">
        <div class="container">
            <div class="section-label">Open for Registration</div>
            <h2>Choose an Event to Register</h2>

            <?php if (empty($open_events)): ?>
                <div class="no-events-box">
                    <i class="fas fa-calendar-times"></i>
                    <p>No events are currently open for registration.<br>Check back soon!</p>
                </div>
            <?php else: ?>
                <div class="event-hub-grid">
                    <?php foreach ($open_events as $ev):
                        $imgSrc = 'assets/images/public/Steam Carnival Wallpaper.jpeg';
                        if (!empty($ev['image_path'])) {
                            $imgSrc = (strpos($ev['image_path'], 'assets/') === 0) ? '../' . $ev['image_path'] : '../9173182/' . $ev['image_path'];
                        }
                        $formSupported = array_key_exists((int)$ev['id'], $supported_forms);
                    ?>
                    <div class="event-hub-card">
                        <div class="event-hub-card-img">
                            <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($ev['title']); ?>">
                            <span class="hub-reg-badge">✓ Registration Open</span>
                        </div>
                        <div class="event-hub-card-body">
                            <h3><?php echo htmlspecialchars($ev['title']); ?></h3>
                            <div class="event-hub-card-meta">
                                <?php if ($ev['event_date']): ?>
                                <span><i class="far fa-calendar-alt"></i> <?php echo date('M j, Y', strtotime($ev['event_date'])); ?></span>
                                <?php endif; ?>
                                <?php if ($ev['location']): ?>
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($ev['location']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php 
                                $cleanDesc = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $ev['description']);
                                $cleanDesc = strip_tags(str_replace('<', ' <', $cleanDesc));
                                $cleanDesc = trim(preg_replace('/\s+/', ' ', $cleanDesc));
                            ?>
                            <p><?php echo htmlspecialchars(mb_substr($cleanDesc, 0, 120)) . '…'; ?></p>
                            <?php if ($formSupported): ?>
                                <a href="register.php?event_id=<?php echo (int)$ev['id']; ?>" class="btn-register-hub">Register Now &rarr;</a>
                            <?php else: ?>
                                <span style="color:#9ca3af;font-size:.9rem;"><i class="fas fa-clock"></i> Registration form coming soon</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom" style="text-align:center;padding-top:20px;border-top:1px solid #eaeaea;">
                <p>&copy; <?php echo date('Y'); ?> Nexopay. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <?php if ($show_form === 'steam_carnival'): ?>
    <script>
        const SECTION_FEES = { olympiad: 200, art: 200, photo: 300, group: 200 };

        /* ── Registration Type Toggle ─────────────────────────────── */
        function setRegType(type) {
            document.getElementById('btnSolo').classList.toggle('active',  type === 'solo');
            document.getElementById('btnGroup').classList.toggle('active', type === 'group');
            document.getElementById('regTypeInput').value = type;

            const isSolo  = (type === 'solo');
            const isGroup = (type === 'group');

            document.getElementById('soloForm').style.display    = isSolo  ? 'block' : 'none';
            document.getElementById('groupForm').style.display   = isGroup ? 'block' : 'none';
            document.getElementById('sharedPayment').style.display = 'block';

            const soloRequired  = ['[name="full_name"]','[name="email"]','[name="institution"]','[name="class_grade"]','[name="contact"]','[name="emergency_contact"]','[name="guardian_name"]'];
            const groupRequired = ['[name="grp_p1_name"]','[name="email"]','[name="grp_p1_institution"]','[name="grp_p1_class"]','[name="grp_p1_contact"]',
                                   '[name="grp_p2_name"]','[name="grp_p2_institution"]','[name="grp_p2_class"]','[name="grp_p2_contact"]',
                                   '[name="grp_emergency_contact"]','[name="grp_guardian_name"]'];

            soloRequired.forEach(sel => {
                const el = document.querySelector('#soloForm ' + sel);
                if (el) isSolo ? el.setAttribute('required','required') : el.removeAttribute('required');
            });
            groupRequired.forEach(sel => {
                const el = document.querySelector('#groupForm ' + sel);
                if (el) isGroup ? el.setAttribute('required','required') : el.removeAttribute('required');
            });

            if (isSolo)  document.querySelectorAll('input[data-section="group"]').forEach(cb => cb.checked = false);
            if (isGroup) document.querySelectorAll('input[data-section="olympiad"],input[data-section="art"],input[data-section="photo"]').forEach(cb => cb.checked = false);

            calculateTotal();
        }

        /* ── Calculate Total ──────────────────────────────────────── */
        function calculateTotal() {
            const regType = document.getElementById('regTypeInput').value;
            const counts  = { olympiad: 0, art: 0, photo: 0, group: 0 };

            document.querySelectorAll('input[name="segments[]"]:checked').forEach(cb => {
                const sec = cb.getAttribute('data-section');
                if (sec && counts.hasOwnProperty(sec)) counts[sec]++;
            });

            const totals = {};
            Object.keys(counts).forEach(sec => {
                const relevant = regType === 'group' ? sec === 'group' : sec !== 'group';
                totals[sec] = relevant ? (counts[sec] * SECTION_FEES[sec]) : 0;
            });

            ['olympiad','art','photo','group'].forEach(sec => {
                const el = document.getElementById('subtotal-' + sec);
                if (el) el.innerText = totals[sec] > 0 ? totals[sec] + ' BDT' : '—';
            });

            const grand = Object.values(totals).reduce((a,b) => a + b, 0);
            document.getElementById('grandTotalAmt').innerText     = grand;
            document.getElementById('totalAmountInput').value      = grand;
            document.getElementById('instructionAmount').innerText = grand + ' BDT';

            const bd = document.getElementById('grandTotalBreakdown');
            bd.style.display = grand > 0 ? 'flex' : 'none';

            const sh = (id, amtId, val, count) => {
                document.getElementById(id).style.display = count > 0 ? 'inline' : 'none';
                document.getElementById(amtId).innerText  = val;
            };
            sh('breakOlympiad','brkOlympiadAmt', totals.olympiad, regType === 'solo'  ? counts.olympiad : 0);
            sh('breakArt',     'brkArtAmt',      totals.art,      regType === 'solo'  ? counts.art      : 0);
            sh('breakPhoto',   'brkPhotoAmt',    totals.photo,    regType === 'solo'  ? counts.photo    : 0);
            sh('breakGroup',   'brkGroupAmt',    totals.group,    regType === 'group' ? counts.group    : 0);
        }

        /* ── Payment Section Toggle ───────────────────────────────── */
        function showPaymentSection() {
            const method  = document.getElementById('paymentMethod').value;
            const section = document.getElementById('paymentSection');
            const sender  = document.getElementById('senderNumber');
            const txn     = document.getElementById('transactionId');
            
            const mobilePay = document.getElementById('mobilePayInstructions');
            const bankPay   = document.getElementById('bankPayInstructions');

            if (!method) {
                section.style.display = 'none';
                sender.removeAttribute('required'); txn.removeAttribute('required');
            } else {
                section.style.display = 'block';
                document.getElementById('paymentTitle').innerText = method + ' Payment Details';
                
                if (method === 'Bank') {
                    if(mobilePay) mobilePay.style.display = 'none';
                    if(bankPay) { 
                        bankPay.style.display = 'block';
                        document.getElementById('bankInstructionAmount').innerText = document.getElementById('instructionAmount').innerText;
                    }
                    document.getElementById('methodNameLabel').innerText = "Bank Account Name / Number";
                    document.getElementById('methodTxnLabel').innerText  = "Bank Reference / Receipt";
                } else {
                    if(mobilePay) mobilePay.style.display = 'block';
                    if(bankPay) bankPay.style.display = 'none';
                    document.getElementById('methodNameLabel').innerText = method;
                    document.getElementById('methodTxnLabel').innerText  = method;
                }
                
                sender.setAttribute('required','required');
                txn.setAttribute('required','required');
            }
        }

        /* ── Form Validation ──────────────────────────────────────── */
        document.getElementById('steamRegistrationForm').addEventListener('submit', function(e) {
            const regType = document.getElementById('regTypeInput').value;

            if (!regType) {
                e.preventDefault();
                alert('Please select a registration type (Solo or Group) before continuing.');
                return;
            }

            if (regType === 'group') {
                const p2 = document.querySelector('[name="grp_p2_name"]');
                if (p2 && !p2.value.trim()) {
                    e.preventDefault();
                    alert('Group registration requires at least 2 participants. Please fill in Participant 2 details.');
                    p2.focus();
                }
            }
        });
    </script>
    <?php elseif ($show_form === 'steam_olympiad'): ?>
    <script>
        const OLYMPIAD_FEES = { olympiad: 200, art: 200, photo: 300 };

        function handleCategoryChange() {
            // Category change no longer hides Art Competition
            calcOlympiadTotal();
        }

        function calcOlympiadTotal() {
            let total = 0;
            document.querySelectorAll('#steamOlympiadForm input[name="segments[]"]:checked').forEach(cb => {
                const sec = cb.getAttribute('data-section');
                if (sec && OLYMPIAD_FEES[sec]) total += OLYMPIAD_FEES[sec];
            });

            document.getElementById('olympiadGrandTotalAmt').innerText = total;
            document.getElementById('olympiadTotalInput').value = total;
            document.getElementById('olympiadInstructionAmount').innerText = total + ' BDT';

            const paymentBlock = document.getElementById('olympiadSharedPayment');
            const pm = document.getElementById('olympiadPaymentMethod');
            if (total > 0) {
                paymentBlock.style.display = 'block';
                pm.setAttribute('required', 'required');
            } else {
                paymentBlock.style.display = 'none';
                pm.removeAttribute('required');
                pm.value = '';
                showOlympiadPaymentSection();
            }
        }

        function showOlympiadPaymentSection() {
            const method = document.getElementById('olympiadPaymentMethod').value;
            const section = document.getElementById('olympiadPaymentSection');
            const sender  = document.getElementById('olympiadSenderNumber');
            const txn     = document.getElementById('olympiadTransactionId');
            
            const mobInst = document.getElementById('olympiadMobileInst');
            const bankInst = document.getElementById('olympiadBankInst');

            if (!method || method === '') {
                section.style.display = 'none';
                sender.removeAttribute('required'); txn.removeAttribute('required');
            } else {
                section.style.display = 'block';
                document.getElementById('olympiadPaymentTitle').innerText = method + ' Payment Details';
                
                if (method === 'Bank') {
                    if(mobInst) mobInst.style.display = 'none';
                    if(bankInst) {
                        bankInst.style.display = 'block';
                        document.getElementById('olympiadBankAmount').innerText = document.getElementById('olympiadInstructionAmount').innerText;
                    }
                    document.getElementById('olympiadMethodNameLabel').innerText = "Bank Account Name / Number";
                    document.getElementById('olympiadMethodTxnLabel').innerText  = "Bank Reference / Receipt";
                } else {
                    if(mobInst) mobInst.style.display = 'block';
                    if(bankInst) bankInst.style.display = 'none';
                    document.getElementById('olympiadMethodNameLabel').innerText = method;
                    document.getElementById('olympiadMethodTxnLabel').innerText  = method;
                }
                
                sender.setAttribute('required','required');
                txn.setAttribute('required','required');
            }
        }
        
        document.getElementById('steamOlympiadForm').addEventListener('submit', function(e) {
            const checked = document.querySelectorAll('#steamOlympiadForm input[name="segments[]"]:checked').length;
            if (checked === 0) {
                e.preventDefault();
                alert('Please select at least one segment to register for.');
            }
            
            const total = parseInt(document.getElementById('olympiadTotalInput').value) || 0;
            if (total > 0 && document.getElementById('olympiadPaymentMethod').value === '') {
                e.preventDefault();
                alert('Please select a payment method and provide transaction details.');
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>

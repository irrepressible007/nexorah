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
        if ($event) {
            $show_form = $supported_forms[$event_id];
            if ($event['id'] == 6) {
                $event['location'] = "SOS HERMANN GMEINER COLLEGE DHAKA, Plot No. 3-B, Block-A, Section-13, Mirpur, Dhaka-1216, Bangladesh";
            }
            if ($event['id'] == 4) {
                $event['location'] = "Bangladesh University of Engineering and Technology (BUET)";
                $event['event_date'] = '2026-05-07';
                $event['event_time'] = '08:00:00';
            }
        }
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
        $email = '';
        $phone = '';
        $guardian_number = '';
        $ticket_quantity = 0;
        
        // ── 1. Determine Registration Type Dynamically for STEAM Carnival ──
        $solo_qty = $_POST['solo_qty'] ?? [];
        $grp_toggle = !empty($_POST['grp_toggle']);
        $is_carnival = (!empty($solo_qty) || $grp_toggle || isset($_POST['grp_segments']));

        $derived_segments = $_POST['segments'] ?? [];

        if ($is_carnival) {
            $total_solo_tickets = 0;
            $max_solo_qty = 0;
            foreach ($solo_qty as $key => $val) {
                $int_val = max(0, (int)$val);
                if ($int_val > 0) {
                    $total_solo_tickets += $int_val;
                    if ($int_val > $max_solo_qty) $max_solo_qty = $int_val;
                    $derived_segments[] = $key . " (x" . $int_val . ")";
                }
            }

            if (!empty($_POST['grp_segments'])) {
                foreach ($_POST['grp_segments'] as $gseg) {
                    $derived_segments[] = $gseg . " [Group]";
                }
            }

            if ($max_solo_qty > 0 && $grp_toggle) {
                $reg_type = 'both';
            } elseif ($max_solo_qty > 0) {
                $reg_type = 'solo';
            } elseif ($grp_toggle) {
                $reg_type = 'group';
            } else {
                throw new Exception("Please select at least one segment.");
            }
        }

        $extra_data = [
            'reg_type'        => $reg_type,
            'segments'        => $derived_segments,
            'nexorah_event_id'=> $event['id'],
        ];

        if ($is_carnival) {
            $extra_data['solo_qty_raw'] = $solo_qty;
        }

        // ── 2. Process Participant Details based on Type ──
        if ($reg_type === 'solo' || $reg_type === 'both' || ($reg_type === 'solo' && !$is_carnival)) {
            // "Your Details" becomes primary contact
            $full_name       = trim($_POST['full_name'] ?? '');
            $email           = trim($_POST['email'] ?? '');
            $phone           = trim($_POST['contact'] ?? '');
            $guardian_number = trim($_POST['emergency_contact'] ?? '');

            $extra_data['solo'] = [
                'category'       => $_POST['category'] ?? $_POST['class_grade'] ?? '',
                'institution'    => trim($_POST['institution'] ?? ''),
                'class_grade'    => trim($_POST['class_grade'] ?? ''),
                'guardian_name'  => trim($_POST['guardian_name'] ?? ''),
                'ca_reference'   => trim($_POST['ca_reference'] ?? ''),
            ];

            if ($is_carnival) {
                $ticket_quantity += $max_solo_qty;
                // Add dynamically captured extra participants if any
                if ($max_solo_qty > 1 && !empty($_POST['add_p2_name'])) {
                    $extra_data['solo']['add_p2'] = [
                        'name' => trim($_POST['add_p2_name']),
                        'institution' => trim($_POST['add_p2_institution'] ?? ''),
                        'class' => trim($_POST['add_p2_class_grade'] ?? ''),
                        'contact' => trim($_POST['add_p2_contact'] ?? '')
                    ];
                }
                if ($max_solo_qty > 2 && !empty($_POST['add_p3_name'])) {
                    $extra_data['solo']['add_p3'] = [
                        'name' => trim($_POST['add_p3_name']),
                        'institution' => trim($_POST['add_p3_institution'] ?? ''),
                        'class' => trim($_POST['add_p3_class_grade'] ?? ''),
                        'contact' => trim($_POST['add_p3_contact'] ?? '')
                    ];
                }
            } else {
                $ticket_quantity = 1; // Olympiad
            }

            if (empty($full_name) || empty($phone)) {
                throw new Exception("Full Name and Contact Number are required.");
            }
        }

        if ($reg_type === 'group' || $reg_type === 'both') {
            if ($reg_type === 'group' && !$is_carnival) {
                // Not possible in current design, but handle fallback
                $full_name       = trim($_POST['grp_p1_name'] ?? 'Group Leader');
                $email           = trim($_POST['grp_email'] ?? '');
                $phone           = trim($_POST['grp_p1_contact'] ?? '');
                $guardian_number = trim($_POST['grp_emergency_contact'] ?? '');
            } else if ($reg_type === 'group' && $is_carnival) {
                // Group only in carnival - "Your Details" is still filled and meant for primary contact
                $full_name       = trim($_POST['full_name'] ?? trim($_POST['grp_p1_name'] ?? 'Group Leader'));
                $email           = trim($_POST['email'] ?? trim($_POST['grp_email'] ?? ''));
                $phone           = trim($_POST['contact'] ?? trim($_POST['grp_p1_contact'] ?? ''));
                $guardian_number = trim($_POST['emergency_contact'] ?? trim($_POST['grp_emergency_contact'] ?? ''));
            }

            $extra_data['group'] = [
                'group_category' => $_POST['group_category'] ?? '',
                'guardian_name'  => trim($_POST['grp_guardian_name'] ?? trim($_POST['guardian_name']??'')),
                'ca_reference'   => trim($_POST['grp_ca_reference'] ?? trim($_POST['ca_reference']??'')),
                'p1' => [
                    'name'        => trim($_POST['grp_p1_name'] ?? ''),
                    'institution' => trim($_POST['grp_p1_institution'] ?? ''),
                    'class'       => trim($_POST['grp_p1_class'] ?? ''),
                    'contact'     => trim($_POST['grp_p1_contact'] ?? ''),
                    'email'       => trim($_POST['grp_email'] ?? ''),
                ],
                'p2' => [
                    'name'        => trim($_POST['grp_p2_name'] ?? ''),
                    'institution' => trim($_POST['grp_p2_institution'] ?? ''),
                    'class'       => trim($_POST['grp_p2_class'] ?? ''),
                    'contact'     => trim($_POST['grp_p2_contact'] ?? ''),
                ],
            ];

            $grp_size = 2;
            if (!empty($_POST['grp_p3_name'])) {
                $extra_data['group']['p3'] = [
                    'name'        => trim($_POST['grp_p3_name'] ?? ''),
                    'institution' => trim($_POST['grp_p3_institution'] ?? ''),
                    'class'       => trim($_POST['grp_p3_class'] ?? ''),
                    'contact'     => trim($_POST['grp_p3_contact'] ?? ''),
                ];
                $grp_size = 3;
            }

            if (empty($_POST['grp_p1_name']) || empty($_POST['grp_p2_name'])) {
                throw new Exception("Group registration requires at least 2 participant names.");
            }

            $ticket_quantity += $grp_size;
        }

        if ($reg_type !== 'solo' && $reg_type !== 'group' && $reg_type !== 'both') {
            throw new Exception("Invalid registration type selected or no segments chosen.");
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
        foreach ($open_events as &$ev) {
            if ($ev['id'] == 6) {
                $ev['location'] = "SOS HERMANN GMEINER COLLEGE DHAKA, Plot No. 3-B, Block-A, Section-13, Mirpur, Dhaka-1216, Bangladesh";
            }
            if ($ev['id'] == 4) {
                $ev['location'] = "Bangladesh University of Engineering and Technology (BUET)";
            }
        }
        unset($ev);
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


        /* ── Qty Stepper ─────────────────────────────────────────── */
        .qty-stepper {
            display: inline-flex; align-items: center; gap: 0;
            border: 2px solid #e9ecef; border-radius: 10px; overflow: hidden;
            transition: border-color .2s;
        }
        .qty-stepper.active { border-color: #3498db; }
        .qty-btn {
            width: 36px; height: 36px; border: none; background: #f3f4f6;
            font-size: 1.2rem; font-weight: 700; color: #374151; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background .15s; font-family: inherit; flex-shrink: 0;
            touch-action: manipulation;
        }
        .qty-btn:hover { background: #e5e7eb; }
        .qty-btn:active { background: #d1d5db; }
        .qty-val {
            width: 38px; text-align: center; font-weight: 700; font-size: 1rem;
            color: #1a2a4a; background: #fff; border-left: 1px solid #e9ecef;
            border-right: 1px solid #e9ecef; padding: 0 4px; line-height: 36px;
        }

        /* ── Segment rows (solo) ─────────────────────────────────── */
        .seg-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 12px; border-radius: 8px; margin-bottom: 6px;
            background: #f9fafb; border: 1.5px solid transparent;
            transition: all .2s; gap: 10px;
        }
        .seg-row:last-child { margin-bottom: 0; }
        .seg-row-active { background: #eff6ff; border-color: #93c5fd; }
        .seg-row-info { flex: 1; min-width: 0; }
        .seg-row-name { font-weight: 600; font-size: .95rem; color: #1a2a4a; }
        .seg-row-sub  { font-size: .8rem; color: #6b7280; margin-top: 1px; }
        .seg-row-price { font-size: .82rem; font-weight: 600; color: #6b7280; white-space: nowrap; margin-right: 10px; }
        .seg-row-price.free { color: #16a34a; }
        .seg-row-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
        .seg-row-fee   { font-size: .82rem; font-weight: 700; color: #3498db; min-width: 60px; text-align: right; }

        /* ── Group panel toggle ──────────────────────────────────── */
        .group-panel-wrapper {
            border: 2px solid #e9ecef; border-radius: 14px; overflow: hidden;
            margin-top: 1.5rem; transition: border-color .2s;
        }
        .group-panel-wrapper.is-active { border-color: #166534; }
        .group-toggle-bar {
            display: flex; align-items: center; gap: 14px;
            padding: 16px 18px; background: #f0fdf4; cursor: pointer;
            border-bottom: 2px solid transparent; transition: background .2s, border-color .2s;
        }
        .group-panel-wrapper.is-active .group-toggle-bar {
            background: linear-gradient(135deg,#f0fdf4,#dcfce7);
            border-bottom-color: #bbf7d0;
        }
        .group-toggle-bar input[type="checkbox"] {
            width: 20px; height: 20px; flex-shrink: 0; cursor: pointer;
            accent-color: #166534;
        }
        .group-toggle-label {
            flex: 1; font-weight: 700; font-size: 1rem; color: #166534;
            cursor: pointer; user-select: none;
        }
        .group-toggle-hint {
            font-size: .82rem; color: #6b7280; font-style: italic; white-space: nowrap;
        }
        .group-panel-body {
            padding: 20px 20px 10px; background: #fff;
            transition: opacity .25s;
        }
        .group-panel-body.disabled {
            opacity: .4; pointer-events: none; user-select: none;
        }

        /* ── Additional participants (solo overflow) ─────────────── */
        .add-participants-section {
            border: 2px dashed #93c5fd; border-radius: 12px; padding: 16px;
            margin-top: 1rem; background: #f0f9ff;
        }
        .add-participants-section-label {
            font-size: .82rem; font-weight: 700; color: #0369a1;
            letter-spacing: 1px; text-transform: uppercase; margin-bottom: 12px;
            display: flex; align-items: center; gap: 8px;
        }


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

            /* New STEAM Carnival components mobile optimization */
            .seg-row { flex-wrap: wrap; gap: 8px; align-items: flex-start; flex-direction: column; }
            .seg-row-info { width: 100%; }
            .seg-row-right { width: 100%; justify-content: space-between; }
            .seg-row-fee { min-width: auto; flex: 1; text-align: left; }
            .qty-stepper { transform: scale(0.95); transform-origin: right center; }

            .group-toggle-bar { flex-wrap: wrap; gap: 6px; padding: 14px; }
            .group-toggle-label { width: 100%; font-size: 0.95rem; display: flex; align-items: flex-start; gap: 8px; }
            .group-toggle-hint { width: 100%; margin-left: 28px; font-size: 0.8rem; }
            .group-panel-body { padding: 16px 12px 10px; }
            
            .add-participants-section-label { flex-wrap: wrap; }
            .add-participants-section { padding: 12px; }
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
                <p class="form-desc">The event will span 3 days of exciting science, technology, and art competitions, with the Final Round being held at BUET. Select the segments you'd like to participate in — you can register for individual events, group display events, or both simultaneously.</p>

                <!-- ── SECTION 1: Your Details ─────────────────────────── -->
                <div class="participant-card" style="margin-bottom:1.5rem;">
                    <div class="participant-card-header">
                        <i class="fas fa-user"></i> Your Details
                        <span class="participant-badge">Required</span>
                    </div>
                    <div class="participant-card-body">
                        <div class="form-group mb-3">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" id="primaryName" class="form-control" required autocomplete="name">
                        </div>
                        <div class="form-group mb-3">
                            <label>Email Address *</label>
                            <input type="email" name="email" class="form-control" required autocomplete="email">
                        </div>
                        <div class="form-group mb-3">
                            <label>Institution Name *</label>
                            <input type="text" name="institution" id="primaryInstitution" class="form-control" required autocomplete="organization">
                        </div>
                        <div class="form-group mb-3">
                            <label>Class / Grade *</label>
                            <input type="text" name="class_grade" id="primaryClass" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Contact Number *</label>
                            <input type="tel" name="contact" id="primaryContact" class="form-control" required autocomplete="tel" inputmode="tel">
                        </div>
                        <div class="form-group mb-3">
                            <label>Category *</label>
                            <select name="category" class="form-control" required>
                                <option value="">-- Select your category --</option>
                                <option value="Pre-Primary">Pre-Primary (Play – Class 1)</option>
                                <option value="Primary">Primary (Class 2 – Class 5)</option>
                                <option value="Junior">Junior (Class 6 – Class 8)</option>
                                <option value="Secondary">Secondary (Class 9 – Class 10)</option>
                                <option value="Senior">Senior (Class 11 – Class 12 + Undergraduate)</option>
                            </select>
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

                <!-- ── SECTION 2: Individual Segments ─────────────────── -->
                <div class="form-group mb-3" style="margin-top:1.8rem;">
                    <label class="segments-main-label">Individual Segments <span>(Select any — use + to add tickets for teammates)</span></label>

                    <div class="segment-groups">

                        <!-- Olympiad & STEM -->
                        <div class="segment-card seg-olympiad">
                            <div class="segment-card-header">
                                <span>🔬 Olympiad &amp; STEM</span>
                                <span class="seg-rate-badge">200 BDT / ticket</span>
                            </div>
                            <div class="segment-card-body">
                                <p class="seg-fee-note">Each ticket = 200 BDT. Use + to buy tickets for teammates (max 3).</p>
                                <?php
                                $solo_segs_olympiad = [
                                    ['key'=>'Olympiads',           'label'=>'Olympiads', 'sub'=>'Physics, Chemistry, Bio, Math, etc.'],
                                    ['key'=>'Instant Story Writing','label'=>'Instant Story Writing', 'sub'=>''],
                                    ['key'=>'Craftwork',           'label'=>'Craftwork', 'sub'=>''],
                                    ['key'=>'Web Design',          'label'=>'Web Design &amp; Development', 'sub'=>''],
                                ];
                                foreach ($solo_segs_olympiad as $ss): ?>
                                <div class="seg-row" id="row-<?= htmlspecialchars($ss['key']) ?>" data-section="olympiad" data-fee="200" data-key="<?= htmlspecialchars($ss['key']) ?>">
                                    <div class="seg-row-info">
                                        <div class="seg-row-name"><?= $ss['label'] ?></div>
                                        <?php if ($ss['sub']): ?><div class="seg-row-sub"><?= $ss['sub'] ?></div><?php endif; ?>
                                    </div>
                                    <div class="seg-row-right">
                                        <span class="seg-row-fee" id="fee-<?= htmlspecialchars($ss['key']) ?>">—</span>
                                        <div class="qty-stepper" id="stepper-<?= htmlspecialchars($ss['key']) ?>">
                                            <button type="button" class="qty-btn qty-minus" onclick="stepQty('<?= $ss['key'] ?>', -1)" aria-label="Decrease">−</button>
                                            <span class="qty-val" id="qty-<?= htmlspecialchars($ss['key']) ?>">0</span>
                                            <button type="button" class="qty-btn qty-plus" onclick="stepQty('<?= $ss['key'] ?>', 1)" aria-label="Increase">+</button>
                                            <input type="hidden" name="solo_qty[<?= $ss['key'] ?>]" id="qtyInput-<?= htmlspecialchars($ss['key']) ?>" value="0">
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="segment-card-footer">Section Fee: <span class="segment-subtotal" id="subtotal-olympiad">—</span></div>
                        </div>

                        <!-- Art -->
                        <div class="segment-card seg-art">
                            <div class="segment-card-header">
                                <span>🎨 Art Exhibition &amp; Competition</span>
                                <span class="seg-rate-badge">200 BDT / ticket</span>
                            </div>
                            <div class="segment-card-body">
                                <p class="seg-fee-note">Each ticket = 200 BDT. Use + to buy tickets for teammates (max 3).</p>
                                <?php
                                $solo_segs_art = [
                                    ['key'=>'Art Competition','label'=>'Art Competition','sub'=>''],
                                    ['key'=>'Art Exhibition', 'label'=>'Art Exhibition', 'sub'=>''],
                                ];
                                foreach ($solo_segs_art as $ss): ?>
                                <div class="seg-row" id="row-<?= htmlspecialchars($ss['key']) ?>" data-section="art" data-fee="200" data-key="<?= htmlspecialchars($ss['key']) ?>">
                                    <div class="seg-row-info">
                                        <div class="seg-row-name"><?= $ss['label'] ?></div>
                                    </div>
                                    <div class="seg-row-right">
                                        <span class="seg-row-fee" id="fee-<?= htmlspecialchars($ss['key']) ?>">—</span>
                                        <div class="qty-stepper" id="stepper-<?= htmlspecialchars($ss['key']) ?>">
                                            <button type="button" class="qty-btn qty-minus" onclick="stepQty('<?= $ss['key'] ?>', -1)" aria-label="Decrease">−</button>
                                            <span class="qty-val" id="qty-<?= htmlspecialchars($ss['key']) ?>">0</span>
                                            <button type="button" class="qty-btn qty-plus" onclick="stepQty('<?= $ss['key'] ?>', 1)" aria-label="Increase">+</button>
                                            <input type="hidden" name="solo_qty[<?= $ss['key'] ?>]" id="qtyInput-<?= htmlspecialchars($ss['key']) ?>" value="0">
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="segment-card-footer">Section Fee: <span class="segment-subtotal" id="subtotal-art">—</span></div>
                        </div>

                        <!-- Photography -->
                        <div class="segment-card seg-photo">
                            <div class="segment-card-header">
                                <span>📸 Photography</span>
                                <span class="seg-rate-badge">300 BDT / ticket</span>
                            </div>
                            <div class="segment-card-body">
                                <p class="seg-fee-note">Each ticket = 300 BDT. Use + to buy tickets for teammates (max 3).</p>
                                <?php
                                $solo_segs_photo = [
                                    ['key'=>'Mobile Photography','label'=>'Mobile Photography','sub'=>''],
                                    ['key'=>'DSLR Photography',  'label'=>'DSLR Photography',  'sub'=>''],
                                ];
                                foreach ($solo_segs_photo as $ss): ?>
                                <div class="seg-row" id="row-<?= htmlspecialchars($ss['key']) ?>" data-section="photo" data-fee="300" data-key="<?= htmlspecialchars($ss['key']) ?>">
                                    <div class="seg-row-info">
                                        <div class="seg-row-name"><?= $ss['label'] ?></div>
                                    </div>
                                    <div class="seg-row-right">
                                        <span class="seg-row-fee" id="fee-<?= htmlspecialchars($ss['key']) ?>">—</span>
                                        <div class="qty-stepper" id="stepper-<?= htmlspecialchars($ss['key']) ?>">
                                            <button type="button" class="qty-btn qty-minus" onclick="stepQty('<?= $ss['key'] ?>', -1)" aria-label="Decrease">−</button>
                                            <span class="qty-val" id="qty-<?= htmlspecialchars($ss['key']) ?>">0</span>
                                            <button type="button" class="qty-btn qty-plus" onclick="stepQty('<?= $ss['key'] ?>', 1)" aria-label="Increase">+</button>
                                            <input type="hidden" name="solo_qty[<?= $ss['key'] ?>]" id="qtyInput-<?= htmlspecialchars($ss['key']) ?>" value="0">
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <hr style="border:none;border-top:1px dashed #e5e7eb;margin:8px 0;">
                                <!-- Workshop — free, treated as a special qty row -->
                                <div class="seg-row" id="row-Workshop" data-section="workshop" data-fee="0" data-key="Workshop">
                                    <div class="seg-row-info">
                                        <div class="seg-row-name">Workshop <strong style="color:#16a34a;">— Free</strong></div>
                                    </div>
                                    <div class="seg-row-right">
                                        <div class="qty-stepper" id="stepper-Workshop">
                                            <button type="button" class="qty-btn qty-minus" onclick="stepQty('Workshop', -1)" aria-label="Decrease">−</button>
                                            <span class="qty-val" id="qty-Workshop">0</span>
                                            <button type="button" class="qty-btn qty-plus" onclick="stepQty('Workshop', 1)" aria-label="Increase">+</button>
                                            <input type="hidden" name="solo_qty[Workshop]" id="qtyInput-Workshop" value="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="segment-card-footer">Photography Fee: <span class="segment-subtotal" id="subtotal-photo">—</span></div>
                        </div>

                    </div><!-- /.segment-groups -->
                </div>

                <!-- ── SECTION 3: Additional Participants (solo qty > 1) ─ -->
                <div id="additionalParticipants" style="display:none;" class="add-participants-section">
                    <div class="add-participants-section-label">
                        <i class="fas fa-users"></i> Additional Participants
                        <span style="font-size:.8rem;font-weight:400;color:#0369a1;margin-left:4px;">(for extra tickets purchased above)</span>
                    </div>

                    <!-- Participant 2 -->
                    <div id="addP2Section" style="display:none;">
                        <div class="participant-card" style="margin-bottom:1rem;">
                            <div class="participant-card-header">
                                <i class="fas fa-user"></i> Participant 2
                                <span class="participant-badge">Required</span>
                            </div>
                            <div class="participant-card-body">
                                <div class="form-group mb-3"><label>Full Name *</label><input type="text" name="add_p2_name" class="form-control" autocomplete="name"></div>
                                <div class="form-group mb-3"><label>Institution Name *</label><input type="text" name="add_p2_institution" class="form-control" autocomplete="organization"></div>
                                <div class="form-group mb-3"><label>Class / Grade *</label><input type="text" name="add_p2_class_grade" class="form-control"></div>
                                <div class="form-group mb-3"><label>Contact Number *</label><input type="tel" name="add_p2_contact" class="form-control" inputmode="tel"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Participant 3 -->
                    <div id="addP3Section" style="display:none;">
                        <div class="participant-card">
                            <div class="participant-card-header optional">
                                <i class="fas fa-user-plus"></i> Participant 3
                                <span class="participant-badge optional">Required</span>
                            </div>
                            <div class="participant-card-body">
                                <div class="form-group mb-3"><label>Full Name *</label><input type="text" name="add_p3_name" class="form-control" autocomplete="name"></div>
                                <div class="form-group mb-3"><label>Institution Name *</label><input type="text" name="add_p3_institution" class="form-control" autocomplete="organization"></div>
                                <div class="form-group mb-3"><label>Class / Grade *</label><input type="text" name="add_p3_class_grade" class="form-control"></div>
                                <div class="form-group mb-3"><label>Contact Number *</label><input type="tel" name="add_p3_contact" class="form-control" inputmode="tel"></div>
                            </div>
                        </div>
                    </div>
                </div><!-- /#additionalParticipants -->

                <!-- ── SECTION 4: Group Events Toggle ─────────────────── -->
                <div class="group-panel-wrapper" id="groupPanelWrapper">
                    <label class="group-toggle-bar" for="groupToggle">
                        <input type="checkbox" id="groupToggle" name="grp_toggle" value="1" onchange="toggleGroupPanel()">
                        <span class="group-toggle-label">👥 Also register for Group Display Events</span>
                        <span class="group-toggle-hint">300 BDT / segment (covers whole group)</span>
                    </label>
                    <div class="group-panel-body disabled" id="groupPanel">

                        <!-- Group segments -->
                        <div class="form-group mb-3">
                            <div class="group-notice">
                                <i class="fas fa-info-circle"></i>
                                <span>Select the collaborative display events your group will participate in. Each segment fee covers all group members.</span>
                            </div>
                            <div class="segment-groups">
                                <div class="segment-card seg-group">
                                    <div class="segment-card-header">
                                        <span>👥 Group Display Events</span>
                                        <span class="seg-rate-badge">300 BDT per segment</span>
                                    </div>
                                    <div class="segment-card-body">
                                        <p class="seg-fee-note">Select any segment(s) — each costs 300 BDT for the whole group.</p>
                                        <label class="option-label"><input type="checkbox" name="grp_segments[]" value="Project Display"   data-section="group" onchange="calculateTotal()"> Project Display</label>
                                        <label class="option-label"><input type="checkbox" name="grp_segments[]" value="Wall Magazine"     data-section="group" onchange="calculateTotal()"> Wall Magazine</label>
                                        <label class="option-label"><input type="checkbox" name="grp_segments[]" value="Scrapbook Display" data-section="group" onchange="calculateTotal()"> Scrapbook Display</label>
                                    </div>
                                    <div class="segment-card-footer">Section Fee: <span class="segment-subtotal" id="subtotal-group">—</span></div>
                                </div>
                            </div>
                        </div>

                        <!-- Group Category -->
                        <div class="form-group mb-3 radio-group" style="display:flex;flex-direction:column;gap:6px;">
                            <label>Group Category *</label>
                            <label class="option-label"><input type="radio" name="group_category" value="Junior">    Junior (Class 6 – Class 8)</label>
                            <label class="option-label"><input type="radio" name="group_category" value="Secondary"> Secondary (Class 9 – Class 10)</label>
                            <label class="option-label"><input type="radio" name="group_category" value="Senior">    Senior (Class 11 – Class 12 + Undergraduate)</label>
                        </div>

                        <!-- Group Participant 1 (auto-filled) -->
                        <div class="participant-card">
                            <div class="participant-card-header">
                                <i class="fas fa-user"></i> Group Leader (Participant 1)
                                <span class="participant-badge">Auto-filled from Your Details</span>
                            </div>
                            <div class="participant-card-body">
                                <div class="form-group mb-3"><label>Full Name *</label><input type="text" name="grp_p1_name" id="grpP1Name" class="form-control" autocomplete="name"></div>
                                <div class="form-group mb-3"><label>Email Address</label><input type="email" name="grp_email" class="form-control" autocomplete="email"></div>
                                <div class="form-group mb-3"><label>Institution Name *</label><input type="text" name="grp_p1_institution" class="form-control" autocomplete="organization"></div>
                                <div class="form-group mb-3"><label>Class / Grade *</label><input type="text" name="grp_p1_class" class="form-control"></div>
                                <div class="form-group mb-3"><label>Contact Number *</label><input type="tel" name="grp_p1_contact" class="form-control" inputmode="tel"></div>
                            </div>
                        </div>

                        <!-- Group Participant 2 -->
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

                        <!-- Group Participant 3 (optional) -->
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
                        <div class="form-group mb-3">
                            <label>CA Reference <span style="font-weight:normal;">(If any)</span></label>
                            <input type="text" name="grp_ca_reference" class="form-control">
                        </div>

                    </div><!-- /#groupPanel -->
                </div><!-- /.group-panel-wrapper -->

                <!-- ── SECTION 5: Grand Total + Payment ───────────────── -->
                <div id="totalPriceDisplay" style="margin-top:2rem; margin-bottom: 2rem;">
                    Grand Total: <span id="grandTotalAmt">0</span> BDT
                    <div class="grand-total-breakdown" id="grandTotalBreakdown" style="display:none;">
                        <span id="breakOlympiad" style="display:none;">🔬 Olympiad &amp; STEM: <span id="brkOlympiadAmt">0</span> BDT</span>
                        <span id="breakArt"      style="display:none;">🎨 Art: <span id="brkArtAmt">0</span> BDT</span>
                        <span id="breakPhoto"    style="display:none;">📸 Photography: <span id="brkPhotoAmt">0</span> BDT</span>
                        <span id="breakGroup"    style="display:none;">👥 Group Display: <span id="brkGroupAmt">0</span> BDT</span>
                    </div>
                </div>
                <input type="hidden" name="total_amount" id="totalAmountInput" value="0">

                <div id="grandTotalSection" style="display:none;">

                    <div class="form-group mb-3">
                        <label>Choose your Payment Method *</label>
                        <select name="payment_method" id="paymentMethod" class="form-control" onchange="showPaymentSection()">
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
                            <div style="background:#f1f5f9; padding:15px; border-radius:8px; font-size:0.9rem; color:#1e293b;">
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
                    </div>
                </div><!-- /#grandTotalSection -->

                <div class="form-group mt-4" style="text-align: center;">
                    <button type="submit" class="btn-submit" style="width: 100%; max-width: 400px; margin: 0 auto; display: block;">Complete Registration</button>
                </div>

            </form>
        </div>
    </section>


    <script>
        const SECTION_FEES = { olympiad: 200, art: 200, photo: 300, workshop: 0, group: 300 };

        /* ── Autofill Group Leader ───────────────────────────────── */
        const p1Inputs = ['Name', 'Institution', 'Class', 'Contact'];
        p1Inputs.forEach(f => {
            const src = document.getElementById('primary' + f);
            const dst = document.getElementById('grpP1' + (f === 'Contact' ? 'contact' : f.toLowerCase()));
            if (src && dst) {
                src.addEventListener('input', () => {
                    if(document.getElementById('groupToggle').checked) dst.value = src.value;
                });
            }
        });

        /* ── Qty Stepper Logic ───────────────────────────────────── */
        function stepQty(segKey, delta) {
            const qtySpan  = document.getElementById('qty-' + segKey);
            const qtyInput = document.getElementById('qtyInput-' + segKey);
            let current = parseInt(qtyInput.value) || 0;
            current += delta;
            if (current < 0) current = 0;
            if (current > 3) current = 3;

            qtySpan.innerText = current;
            qtyInput.value    = current;

            // UI updates
            const row = document.getElementById('row-' + segKey);
            const feeSpan = document.getElementById('fee-' + segKey);
            const stepper = document.getElementById('stepper-' + segKey);
            const feeRate = parseInt(row.getAttribute('data-fee'));

            if (current > 0) {
                row.classList.add('seg-row-active');
                stepper.classList.add('active');
                feeSpan.innerText = (current * feeRate) + ' BDT';
                if (feeRate === 0) feeSpan.innerText = 'Free';
            } else {
                row.classList.remove('seg-row-active');
                stepper.classList.remove('active');
                feeSpan.innerText = '—';
            }

            calculateTotal();
        }

        /* ── Group Toggle Logic ──────────────────────────────────── */
        function toggleGroupPanel() {
            const isChecked = document.getElementById('groupToggle').checked;
            const panel = document.getElementById('groupPanel');
            const wrapper = document.getElementById('groupPanelWrapper');

            if (isChecked) {
                panel.classList.remove('disabled');
                wrapper.classList.add('is-active');

                // Autofill Group P1
                document.getElementById('grpP1Name').value = document.getElementById('primaryName').value;
                document.getElementById('grpP1institution').value = document.getElementById('primaryInstitution').value;
                document.getElementById('grpP1class').value = document.getElementById('primaryClass').value;
                document.getElementById('grpP1contact').value = document.getElementById('primaryContact').value;

                // Sync required attributes
                document.querySelector('[name="group_category"]').setAttribute('required', 'required');
                document.getElementById('grpP2Name').setAttribute('required', 'required');
            } else {
                panel.classList.add('disabled');
                wrapper.classList.remove('is-active');

                document.querySelector('[name="group_category"]').removeAttribute('required');
                document.getElementById('grpP2Name').removeAttribute('required');

                // Clear group checkmarks
                document.querySelectorAll('input[name="grp_segments[]"]').forEach(cb => cb.checked = false);
            }
            calculateTotal();
        }

        /* ── Calculate Total & Dynamic Participants ──────────────── */
        function calculateTotal() {
            let grandTotal = 0;
            let maxSoloQty = 0;
            const bd = { olympiad: 0, art: 0, photo: 0, group: 0 };

            // 1. Calc Solo
            document.querySelectorAll('input[name^="solo_qty"]').forEach(input => {
                const qty = parseInt(input.value) || 0;
                if (qty > 0) {
                    if (qty > maxSoloQty) maxSoloQty = qty;
                    const key = input.id.replace('qtyInput-', '');
                    const row = document.getElementById('row-' + key);
                    const sec = row.getAttribute('data-section');
                    const fee = parseInt(row.getAttribute('data-fee')) || 0;
                    bd[sec] += (qty * fee);
                }
            });

            // Toggle dynamic participant fields based on maxSoloQty
            const addParts = document.getElementById('additionalParticipants');
            const p2Sec = document.getElementById('addP2Section');
            const p3Sec = document.getElementById('addP3Section');
            
            if (maxSoloQty > 1) {
                addParts.style.display = 'block';
                p2Sec.style.display = 'block';
                document.querySelector('[name="add_p2_name"]').setAttribute('required','required');
                
                if (maxSoloQty > 2) {
                    p3Sec.style.display = 'block';
                    document.querySelector('[name="add_p3_name"]').setAttribute('required','required');
                } else {
                    p3Sec.style.display = 'none';
                    document.querySelector('[name="add_p3_name"]').removeAttribute('required');
                }
            } else {
                addParts.style.display = 'none';
                p2Sec.style.display = 'none';
                p3Sec.style.display = 'none';
                document.querySelector('[name="add_p2_name"]').removeAttribute('required');
                document.querySelector('[name="add_p3_name"]').removeAttribute('required');
            }

            // 2. Calc Group (if active)
            if (document.getElementById('groupToggle').checked) {
                const grpChecked = document.querySelectorAll('input[name="grp_segments[]"]:checked').length;
                bd.group = grpChecked * SECTION_FEES.group;
            }

            // Subtotals
            document.getElementById('subtotal-olympiad').innerText = bd.olympiad > 0 ? bd.olympiad + ' BDT' : '—';
            document.getElementById('subtotal-art').innerText      = bd.art > 0 ? bd.art + ' BDT' : '—';
            document.getElementById('subtotal-photo').innerText    = bd.photo > 0 ? bd.photo + ' BDT' : '—';
            document.getElementById('subtotal-group').innerText    = bd.group > 0 ? bd.group + ' BDT' : '—';

            grandTotal = bd.olympiad + bd.art + bd.photo + bd.group;

            // Grand Total Display
            document.getElementById('grandTotalAmt').innerText = grandTotal;
            document.getElementById('totalAmountInput').value  = grandTotal;

            if (document.getElementById('instructionAmount')) {
                document.getElementById('instructionAmount').innerText = grandTotal + ' BDT';
                document.getElementById('bankInstructionAmount').innerText = grandTotal + ' BDT';
            }

            // Breakdown
            const breakdown = document.getElementById('grandTotalBreakdown');
            breakdown.style.display = grandTotal > 0 ? 'flex' : 'none';
            
            const sh = (id, amtId, val) => {
                document.getElementById(id).style.display = val > 0 ? 'inline' : 'none';
                document.getElementById(amtId).innerText  = val;
            };
            sh('breakOlympiad', 'brkOlympiadAmt', bd.olympiad);
            sh('breakArt',      'brkArtAmt',      bd.art);
            sh('breakPhoto',    'brkPhotoAmt',    bd.photo);
            sh('breakGroup',    'brkGroupAmt',    bd.group);

            // Payment section toggle
            const pmentSec = document.getElementById('grandTotalSection');
            const pMethod = document.getElementById('paymentMethod');
            if (grandTotal > 0 || maxSoloQty > 0 || (document.getElementById('groupToggle').checked && bd.group > 0) || document.getElementById('qtyInput-Workshop').value > 0 ) {
                pmentSec.style.display = 'block';
                if (grandTotal > 0) {
                    pMethod.setAttribute('required', 'required');
                } else {
                    pMethod.removeAttribute('required');
                }
            } else {
                pmentSec.style.display = 'none';
                pMethod.removeAttribute('required');
            }
        }

        /* ── Payment Section Dropdown ────────────────────────────── */
        function showPaymentSection() {
            const method    = document.getElementById('paymentMethod').value;
            const section   = document.getElementById('paymentSection');
            const sender    = document.getElementById('senderNumber');
            const txn       = document.getElementById('transactionId');
            const mobilePay = document.getElementById('mobilePayInstructions');
            const bankPay   = document.getElementById('bankPayInstructions');

            if (!method) {
                section.style.display = 'none';
                sender.removeAttribute('required'); txn.removeAttribute('required');
            } else {
                section.style.display = 'block';
                document.getElementById('paymentTitle').innerText = method + ' Payment Details';

                if (method === 'Bank') {
                    if (mobilePay) mobilePay.style.display = 'none';
                    if (bankPay) bankPay.style.display = 'block';
                    document.getElementById('methodNameLabel').innerText = "Bank Account Name /";
                    document.getElementById('methodTxnLabel').innerText  = "Bank Reference / Receipt";
                } else {
                    if (mobilePay) mobilePay.style.display = 'block';
                    if (bankPay)   bankPay.style.display   = 'none';
                    document.getElementById('methodNameLabel').innerText = method;
                    document.getElementById('methodTxnLabel').innerText  = method;
                }

                sender.setAttribute('required','required');
                txn.setAttribute('required','required');
            }
        }

        /* ── Form Submit Validation ──────────────────────────────── */
        document.getElementById('steamRegistrationForm').addEventListener('submit', function(e) {
            let total = parseInt(document.getElementById('totalAmountInput').value) || 0;
            let maxSoloQty = 0;
            document.querySelectorAll('input[name^="solo_qty"]').forEach(input => {
                const qty = parseInt(input.value) || 0;
                if (qty > maxSoloQty) maxSoloQty = qty;
            });

            const groupToggle = document.getElementById('groupToggle').checked;
            const grpChecked = document.querySelectorAll('input[name="grp_segments[]"]:checked').length;

            if (maxSoloQty === 0 && (!groupToggle || grpChecked === 0)) {
                e.preventDefault();
                alert('Please select at least one individual segment or group display event.');
                return;
            }

            if (total > 0 && !document.getElementById('paymentMethod').value) {
                e.preventDefault();
                alert('Please select a payment method and provide transaction details.');
                return;
            }
        });
    </script>
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
                
                <div class="participant-card" style="margin-bottom: 2rem;">
                    <div class="participant-card-header">
                        <i class="fas fa-user"></i> Participant Information
                        <span class="participant-badge">Required</span>
                    </div>
                    <div class="participant-card-body">
                        <div class="form-group mb-3"><label>Full Name *</label><input type="text" name="full_name" class="form-control" required></div>
                        <div class="form-group mb-3"><label>Email Address *</label><input type="email" name="email" class="form-control" required></div>
                        <div class="form-group mb-3"><label>Institution *</label><input type="text" name="institution" class="form-control" required></div>
                        <div class="form-group mb-3"><label>Class *</label><input type="text" name="class" class="form-control" placeholder="e.g. Class 8" required></div>
                        <div class="form-group mb-3">
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
                        <div class="form-group mb-3"><label>Contact Number *</label><input type="tel" name="contact" class="form-control" required></div>
                    </div>
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

                <div id="totalPriceDisplay" style="margin-bottom: 2rem;">
                    Grand Total: <span id="olympiadGrandTotalAmt">0</span> BDT
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
                            <div style="background:#f1f5f9; padding:15px; border-radius:8px; font-size:0.9rem; color:#1e293b;">
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
                    <?php if ($formSupported): ?>
                    <div class="event-hub-card" style="cursor: pointer;" onclick="window.location.href='register.php?event_id=<?php echo (int)$ev['id']; ?>'">
                    <?php else: ?>
                    <div class="event-hub-card">
                    <?php endif; ?>
                        <div class="event-hub-card-img">
                            <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($ev['title']); ?>">
                            <span class="hub-reg-badge">✓ Registration Open</span>
                        </div>
                        <div class="event-hub-card-body">
                            <h3><?php echo htmlspecialchars($ev['title']); ?></h3>
                            <div class="event-hub-card-meta">
                                <?php
                                    $evDateDisp = $ev['event_date'] ? date('M j, Y', strtotime($ev['event_date'])) : '';
                                    $evLocDisp  = htmlspecialchars($ev['location']);
                                    if ((int)$ev['id'] === 6) {
                                        $evDateDisp = 'May 2, 2026';
                                    }
                                    if ((int)$ev['id'] === 4) {
                                        $evDateDisp = 'May 7–9, 2026';
                                    }
                                ?>
                                <?php if ($evDateDisp): ?>
                                <span><i class="far fa-calendar-alt"></i> <?php echo $evDateDisp; ?></span>
                                <?php endif; ?>
                                <?php if ($evLocDisp): ?>
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo $evLocDisp; ?></span>
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
        const SECTION_FEES = { olympiad: 200, art: 200, photo: 300, group: 300 };

        /* ── Registration Type Toggle ─────────────────────────────── */
        function setRegType(type) {
            // Reset all button states
            ['btnSolo','btnBoth','btnGroup'].forEach(id => {
                const btn = document.getElementById(id);
                if (btn) btn.classList.remove('active', 'active-both');
            });

            // Apply appropriate active class
            if (type === 'both') {
                document.getElementById('btnBoth').classList.add('active-both');
            } else if (type === 'solo') {
                document.getElementById('btnSolo').classList.add('active');
            } else {
                document.getElementById('btnGroup').classList.add('active');
            }

            document.getElementById('regTypeInput').value = type;

            const isSolo  = (type === 'solo'  || type === 'both');
            const isGroup = (type === 'group' || type === 'both');
            const isBoth  = (type === 'both');

            document.getElementById('soloForm').style.display      = isSolo  ? 'block' : 'none';
            document.getElementById('groupForm').style.display      = isGroup ? 'block' : 'none';
            document.getElementById('sharedPayment').style.display  = 'block';

            // Show / hide "Both" section divider inside the group form
            const divider = document.getElementById('bothDivider');
            const sLabel  = document.getElementById('bothSectionLabel');
            if (divider) divider.style.display = isBoth ? 'block' : 'none';
            if (sLabel)  sLabel.style.display  = isBoth ? 'flex'  : 'none';

            // Manage required attributes
            const soloRequired  = ['[name="full_name"]','[name="email"]','[name="institution"]','[name="class_grade"]','[name="contact"]','[name="emergency_contact"]','[name="guardian_name"]'];
            const groupRequired = ['[name="grp_p1_name"]','[name="grp_email"]','[name="grp_p1_institution"]','[name="grp_p1_class"]','[name="grp_p1_contact"]',
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

            // Only clear segments not applicable to current type
            if (!isGroup) document.querySelectorAll('input[data-section="group"]').forEach(cb => cb.checked = false);
            if (!isSolo)  document.querySelectorAll('input[data-section="olympiad"],input[data-section="art"],input[data-section="photo"]').forEach(cb => cb.checked = false);

            calculateTotal();
        }

        /* ── Calculate Total ──────────────────────────────────────── */
        function calculateTotal() {
            const regType = document.getElementById('regTypeInput').value;
            const isSolo  = (regType === 'solo'  || regType === 'both');
            const isGroup = (regType === 'group' || regType === 'both');
            const counts  = { olympiad: 0, art: 0, photo: 0, group: 0 };

            document.querySelectorAll('input[name="segments[]"]:checked').forEach(cb => {
                const sec = cb.getAttribute('data-section');
                if (sec && counts.hasOwnProperty(sec)) counts[sec]++;
            });

            const totals = {};
            Object.keys(counts).forEach(sec => {
                if (sec === 'group') {
                    totals[sec] = isGroup ? counts[sec] * SECTION_FEES[sec] : 0;
                } else {
                    totals[sec] = isSolo  ? counts[sec] * SECTION_FEES[sec] : 0;
                }
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
            sh('breakOlympiad','brkOlympiadAmt', totals.olympiad, isSolo  ? counts.olympiad : 0);
            sh('breakArt',     'brkArtAmt',      totals.art,      isSolo  ? counts.art      : 0);
            sh('breakPhoto',   'brkPhotoAmt',    totals.photo,    isSolo  ? counts.photo    : 0);
            sh('breakGroup',   'brkGroupAmt',    totals.group,    isGroup ? counts.group    : 0);
        }

        /* ── Payment Section Toggle ───────────────────────────────── */
        function showPaymentSection() {
            const method    = document.getElementById('paymentMethod').value;
            const section   = document.getElementById('paymentSection');
            const sender    = document.getElementById('senderNumber');
            const txn       = document.getElementById('transactionId');
            const mobilePay = document.getElementById('mobilePayInstructions');
            const bankPay   = document.getElementById('bankPayInstructions');

            if (!method) {
                section.style.display = 'none';
                sender.removeAttribute('required'); txn.removeAttribute('required');
            } else {
                section.style.display = 'block';
                document.getElementById('paymentTitle').innerText = method + ' Payment Details';

                if (method === 'Bank') {
                    if (mobilePay) mobilePay.style.display = 'none';
                    if (bankPay) {
                        bankPay.style.display = 'block';
                        document.getElementById('bankInstructionAmount').innerText = document.getElementById('instructionAmount').innerText;
                    }
                    document.getElementById('methodNameLabel').innerText = "Bank Account Name /";
                    document.getElementById('methodTxnLabel').innerText  = "Bank Reference / Receipt";
                } else {
                    if (mobilePay) mobilePay.style.display = 'block';
                    if (bankPay)   bankPay.style.display   = 'none';
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
                alert('Please select a registration type (Solo, Group, or Both) before continuing.');
                return;
            }

            if (regType === 'group' || regType === 'both') {
                const p2 = document.querySelector('[name="grp_p2_name"]');
                if (p2 && !p2.value.trim()) {
                    e.preventDefault();
                    alert('Group registration requires at least 2 participants. Please fill in Participant 2 details.');
                    p2.focus();
                    return;
                }
            }

            if (regType === 'both') {
                const soloSegs  = document.querySelectorAll('#soloForm input[data-section]:checked').length;
                const groupSegs = document.querySelectorAll('#groupForm input[data-section="group"]:checked').length;
                if (soloSegs === 0 && groupSegs === 0) {
                    e.preventDefault();
                    alert('Please select at least one segment for the Solo or Group registration.');
                    return;
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
                    document.getElementById('olympiadMethodNameLabel').innerText = "Bank Account Name /";
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

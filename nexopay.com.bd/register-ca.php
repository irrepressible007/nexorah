?<?php
// Load database connection from external config (excluded from Git for security)
require_once __DIR__ . '/config/nexorah_db.php';
require_once __DIR__ . '/config/database.php'; // nexopay DB for admin tables

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$event = null;
$success_message = '';
$error_message   = '';

if ($event_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $event = null;
    }
}

// ── Handle form POST ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db_obj = new Database();
        $db     = $db_obj->getConnection();

        // Resolve nexopay event_id by title match (nexorah and nexopay use different IDs)
        $nexopay_event_id = $event_id ?: null;
        if ($event_id) {
            try {
                $evStmt = $db->prepare("SELECT id FROM events WHERE title LIKE :t LIMIT 1");
                $evStmt->execute([':t' => '%' . ($event['title'] ?? '') . '%']);
                $evRow = $evStmt->fetch(PDO::FETCH_ASSOC);
                if ($evRow) $nexopay_event_id = (int)$evRow['id'];
            } catch (Exception $ev_e) { /* keep original */ }
        }
        // Handle photo upload
        $picture_path = null;
        if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/uploads/ca/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $ext     = pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION);
            $fname   = 'ca_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['picture']['tmp_name'], $upload_dir . $fname)) {
                $picture_path = 'uploads/ca/' . $fname;
            }
        }

        $stmt = $db->prepare("
            INSERT INTO ca_applications
                (event_id, full_name, email, institution, contact_number, whatsapp_number,
                 dob, nid_bc, guardian_name, guardian_contact, experience, picture_path)
            VALUES
                (:event_id, :full_name, :email, :institution, :contact_number, :whatsapp_number,
                 :dob, :nid_bc, :guardian_name, :guardian_contact, :experience, :picture_path)
        ");
        $stmt->execute([
            ':event_id'        => $nexopay_event_id,
            ':full_name'       => trim($_POST['full_name'] ?? ''),
            ':email'           => trim($_POST['email'] ?? ''),
            ':institution'     => trim($_POST['institution'] ?? ''),
            ':contact_number'  => trim($_POST['contact_number'] ?? ''),
            ':whatsapp_number' => trim($_POST['whatsapp_number'] ?? ''),
            ':dob'             => $_POST['dob'] ?? null,
            ':nid_bc'          => trim($_POST['nid_bc'] ?? ''),
            ':guardian_name'   => trim($_POST['guardian_name'] ?? ''),
            ':guardian_contact'=> trim($_POST['guardian_contact'] ?? ''),
            ':experience'      => trim($_POST['experience'] ?? ''),
            ':picture_path'    => $picture_path,
        ]);

        // Insert summary tracker into main registrations table
        $stmtTracker = $db->prepare("
            INSERT INTO registrations (
                event_id, full_name, email, phone, guardian_number,
                ticket_quantity, total_amount, payment_method,
                registration_type
            ) VALUES (
                :event_id, :full_name, :email, :phone, :guardian_number,
                :ticket_quantity, :total_amount, :payment_method,
                :registration_type
            )
        ");
        $stmtTracker->execute([
            ':event_id'          => $nexopay_event_id,
            ':full_name'         => trim($_POST['full_name'] ?? ''),
            ':email'             => trim($_POST['email'] ?? ''),
            ':phone'             => trim($_POST['contact_number'] ?? ''),
            ':guardian_number'   => trim($_POST['guardian_contact'] ?? ''),
            ':ticket_quantity'   => 1,
            ':total_amount'      => 0,
            ':payment_method'    => 'n/a',
            ':registration_type' => 'campus_ambassador'
        ]);

        header("Location: success.php?status=success&type=ca");
        exit;
    } catch (Exception $e) {
        $error_message = '⚠️ Something went wrong. Please try again. (' . $e->getMessage() . ')';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Ambassador Registration | Nexopay</title>
    <link rel="icon" type="image/png" href="assets/images/public/Nex Pay new logo.png">
    <link rel="shortcut icon" href="assets/images/public/Nex Pay new logo.png">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script defer src="assets/js/main.js"></script>
    <style>
        .register-hero {
            padding: 120px 0 60px;
            min-height: 40vh;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.85)),
                        url('assets/images/public/Steam Carnival Wallpaper.jpeg') center/cover no-repeat;
            display: flex; align-items: center; text-align: center; color: #fff;
        }
        .form-section { padding: 60px 0; background: #f4f7fb; }
        .registration-form {
            padding: 2.5rem; background: #fff;
            border-top: 4px solid #8e44ad; border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,.08); max-width: 800px; margin: 0 auto;
        }
        .registration-form h3 { color: #8e44ad; font-size: 1.8rem; margin-bottom: .5rem; font-weight: 800; }
        .form-desc { color: #666; margin-bottom: 2rem; line-height: 1.6; }
        
        .form-control {
            width: 100%; padding: 14px;
            border: 2px solid #e9ecef; border-radius: 10px;
            font-size: 1rem; background: #fdfdfd; font-family: inherit;
            transition: all 0.3s ease;
        }
        .form-control:focus { outline: none; border-color: #8e44ad; box-shadow: 0 0 0 4px rgba(142,68,173,.15); transform: translateY(-1px); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; color: #374151; margin-bottom: 8px; }
        
        .form-row { display: flex; flex-wrap: wrap; margin: 0 -10px; }
        .form-col { flex: 1; min-width: 300px; padding: 0 10px; }

        @media(max-width: 768px) {
            .registration-form { padding: 1.5rem; }
            .form-row { flex-direction: column; margin: 0; }
            .form-col { padding: 0; min-width: 100%; }
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #8e44ad, #9b59b6); color: white;
            width: 100%; padding: 16px; font-weight: 700; font-size: 1.1rem;
            border-radius: 10px; border: none; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;
            touch-action: manipulation; font-family: inherit;
        }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(142,68,173,.3); }
        .btn-submit:active { transform: translateY(0); }
        
        .nav-register-pill {
            background-color: #fff; color: #002147 !important; font-weight: bold;
            text-transform: uppercase; padding: 8px 24px !important; border-radius: 50rem;
            text-decoration: none; display: inline-block; box-shadow: 0 2px 4px rgba(0,0,0,.075);
        }
        .back-link { display: inline-flex; align-items: center; gap: 7px; color: #8e44ad; font-weight: 600; text-decoration: none; margin-bottom: 20px; font-size: .95rem; }
        .back-link:hover { text-decoration: underline; }
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
            <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false"><i class="fas fa-bars"></i></button>
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

    <section class="register-hero">
        <div class="container">
            <h1 style="font-size: clamp(2rem, 5vw, 3rem); font-weight: 800; margin-bottom: 15px;">Become a Campus Ambassador</h1>
            <?php if ($event): ?>
                <p style="font-size: 1.1rem; opacity: 0.9;">Join the amazing team for <strong><?php echo htmlspecialchars($event['title']); ?></strong></p>
            <?php else: ?>
                <p style="font-size: 1.1rem; opacity: 0.9;">Lead, Inspire, and Discover with us.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="form-section">
        <div class="container">
            <a href="register.php<?php echo $event_id ? '?event_id='.$event_id : ''; ?>" class="back-link"><i class="fas fa-arrow-left"></i> Back to Event Registration</a>

            <a href="register.php<?php echo $event_id ? '?event_id='.$event_id : ''; ?>" class="back-link"><i class="fas fa-arrow-left"></i> Back to Event Registration</a>

            <?php if ($error_message): ?>
            <div style="background:#fee2e2;border:1.5px solid #fca5a5;border-radius:14px;padding:18px 22px;margin-bottom:20px;color:#991b1b;font-weight:600">
                <?= htmlspecialchars($error_message) ?>
            </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data" class="registration-form">
                <h3>Campus Ambassador Application</h3>
                <p class="form-desc">Please fill out the form below accurately. All fields marked with an asterisk (*) are required.</p>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" class="form-control" required autocomplete="email">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" class="form-control" required autocomplete="name">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Institution Name *</label>
                    <input type="text" name="institution" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label>Contact Number *</label>
                            <input type="tel" name="contact_number" class="form-control" required inputmode="tel" autocomplete="tel">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label>WhatsApp Number *</label>
                            <input type="tel" name="whatsapp_number" class="form-control" required inputmode="tel">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label>Date of Birth *</label>
                            <input type="date" name="dob" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label>Birth Certificate / NID Number *</label>
                            <input type="text" name="nid_bc" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label>Guardian Full Name *</label>
                            <input type="text" name="guardian_name" class="form-control" required autocomplete="name">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label>Guardian Contact Number *</label>
                            <input type="tel" name="guardian_contact" class="form-control" required inputmode="tel">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Did you work as a Campus Ambassador before? And where was it? *</label>
                    <textarea name="experience" class="form-control" rows="3" required placeholder="Describe your experience or write 'No' if not applicable."></textarea>
                </div>

                <div class="form-group mb-4">
                    <label>Upload Picture * <span style="font-weight:normal; color:#666; font-size:0.9rem;">(Provide a clear picture of your face)</span></label>
                    <input type="file" name="picture" class="form-control" accept="image/*" required style="padding:10px;">
                </div>

                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                
                <button type="submit" class="btn-submit">Submit CA Application</button>
            </form>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom" style="text-align:center;padding-top:20px;border-top:1px solid #eaeaea;">
                <p>&copy; <?php echo date('Y'); ?> Nexopay. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>

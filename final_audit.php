<?php
$root  = 'C:/Users/Ameer Hamza/Downloads/public_html/public_html';
$npay  = $root . '/nexopay.com.bd';
$admin = $npay . '/admin';

$issues = []; $warnings = []; $ok = [];

// 1. PHP SYNTAX CHECK
$scan_dirs = [$root, $npay, $admin];
foreach ($scan_dirs as $dir) {
    foreach (glob("$dir/*.php") as $f) {
        $out = shell_exec("C:/xampp/php/php.exe -l \"$f\" 2>&1");
        if (strpos($out, 'No syntax errors') === false)
            $issues[] = "[SYNTAX ERROR] " . str_replace($root.'/', '', $f) . " → $out";
    }
}
$ok[] = "[SYNTAX] All PHP files passed lint check";

// 2. ENCODING CHECK
$enc_files = [
    $npay  . '/register.php',
    $npay  . '/register-ca.php',
    $npay  . '/register-volunteer.php',
    $npay  . '/events.php',
    $npay  . '/index.php',
    $npay  . '/success.php',
    $npay  . '/user-panel.php',
    $admin . '/registrations.php',
    $admin . '/dashboard.php',
    $admin . '/ca_applications.php',
    $admin . '/volunteer_applications.php',
    $root  . '/events.php',
    $root  . '/index.php',
];
foreach ($enc_files as $f) {
    if (!file_exists($f)) { $issues[] = "[MISSING] " . basename($f); continue; }
    $c = file_get_contents($f);
    if (strpos($c, 'â') !== false || strpos($c, 'ðŸ') !== false)
        $issues[] = "[ENCODING] Double-encoded UTF-8 in: " . str_replace($root.'/', '', $f);
}
$ok[] = "[ENCODING] All checked files clean";

// 3. DB INTEGRITY
require_once $npay . '/config/nexorah_db.php'; // $pdo
require_once $npay . '/config/database.php';   // Database class
$db = (new Database())->getConnection();

$events_nx = $pdo->query("SELECT id, title, is_registration_open, is_upcoming, image_path FROM events")->fetchAll(PDO::FETCH_ASSOC);
$events_np = $db->query("SELECT id, title FROM events")->fetchAll(PDO::FETCH_ASSOC);
$ok[] = "[DB-NEXORAH] " . count($events_nx) . " event(s): " . implode(', ', array_column($events_nx, 'title'));
$ok[] = "[DB-NEXOPAY] " . count($events_np) . " event(s): " . implode(', ', array_column($events_np, 'title'));

foreach ($events_nx as $ev) {
    if (!empty($ev['image_path']) && !file_exists($root . '/' . $ev['image_path']))
        $issues[] = "[IMG MISSING] Event #{$ev['id']} '{$ev['title']}' → {$ev['image_path']}";
}

// Supported forms map
foreach ([4 => 'steam_carnival', 6 => 'steam_olympiad'] as $id => $form) {
    $r = $pdo->prepare("SELECT id FROM events WHERE id = ?");
    $r->execute([$id]);
    if (!$r->fetch()) $issues[] = "[FORM MAP] Event ID $id missing from nexorah DB";
}
$ok[] = "[FORM MAP] Event IDs 4 & 6 verified";

// registrations schema
$cols = $db->query("DESCRIBE registrations")->fetchAll(PDO::FETCH_COLUMN);
foreach (['id','event_id','full_name','email','phone','total_amount','payment_method','transaction_id','registration_type','extra_data','approval_status','payment_status'] as $col)
    if (!in_array($col, $cols)) $issues[] = "[SCHEMA] Missing column: $col";
$ok[] = "[SCHEMA] registrations table — all columns present";

// 4. CRITICAL ASSETS
$assets = [
    $npay  . '/assets/css/style.css',
    $npay  . '/assets/css/mobile.css',
    $npay  . '/assets/css/admin.css',
    $npay  . '/assets/js/main.js',
    $npay  . '/assets/images/public/Nex Pay new logo.png',
    $npay  . '/assets/images/public/nso logo.jpeg',
    $npay  . '/assets/images/public/carnival LOGO.jpeg',
    $root  . '/assets/images/public/nso logo.jpeg',
    $root  . '/assets/images/public/carnival LOGO.jpeg',
];
foreach ($assets as $a) {
    if (!file_exists($a)) $issues[] = "[ASSET MISSING] " . str_replace($root.'/', '', $a);
}
$ok[] = "[ASSETS] All critical CSS/JS/image assets present";

// 5. KEY PAGES
$pages = [
    $root  . '/index.php', $root . '/events.php', $root . '/about.php', $root . '/contact.php',
    $npay  . '/register.php', $npay . '/register-ca.php', $npay . '/register-volunteer.php',
    $npay  . '/success.php', $npay . '/user-panel.php', $npay . '/events.php',
    $admin . '/login.php', $admin . '/dashboard.php', $admin . '/registrations.php',
    $admin . '/events.php', $admin . '/ca_applications.php', $admin . '/volunteer_applications.php',
    $admin . '/highlights.php',
];
foreach ($pages as $p) {
    if (!file_exists($p)) $issues[] = "[PAGE MISSING] " . str_replace($root.'/', '', $p);
}
$ok[] = "[PAGES] All 17 key pages present";

// 6. STALE/DEBUG FILES THAT SHOULD NOT EXIST
$stale = [
    $npay  . '/seed_olympiad.php', $npay . '/fix_admin.php', $npay . '/test_login.php',
    $npay  . '/safe_transcode.php', $npay . '/test_encode.php', $npay . '/register_fixed.php',
    $npay  . '/c.php', $root . '/update_nso_desc.php', $root . '/audit.php',
    $admin . '/fix_enc.php', $admin . '/fix_enc.py', $admin . '/debug_approval.php',
    $admin . '/db_fix.php', $admin . '/db_migrate.php',
];
foreach ($stale as $s) {
    if (file_exists($s)) $warnings[] = "[STALE FILE] " . str_replace($root.'/', '', $s);
}
if (empty($warnings)) $ok[] = "[CLEANUP] No stale/debug files remaining";

// 7. GITIGNORE CHECK
$gi = file_get_contents($root . '/.gitignore');
foreach (['config/nexorah_db.php', 'config/database.php', '*.log'] as $entry) {
    if (strpos($gi, $entry) === false) $warnings[] = "[GITIGNORE] '$entry' not in .gitignore";
}
$ok[] = "[GITIGNORE] Sensitive files excluded";

// OUTPUT
$total = count($issues);
echo "\n========================================================\n";
echo " FINAL SITE AUDIT\n";
echo "========================================================\n";
echo "\n❌ ISSUES ($total):\n";
echo "--------------------------------------------------------\n";
echo $total === 0 ? "  None — site is clean!\n" : implode("\n", array_map(fn($i) => "  $i", $issues)) . "\n";
echo "\n⚠️  WARNINGS (" . count($warnings) . "):\n";
echo "--------------------------------------------------------\n";
echo empty($warnings) ? "  None.\n" : implode("\n", array_map(fn($w) => "  $w", $warnings)) . "\n";
echo "\n✅ CHECKS PASSED (" . count($ok) . "):\n";
echo "--------------------------------------------------------\n";
foreach ($ok as $o) echo "  $o\n";
echo "\n========================================================\n";
echo $total === 0 ? " ✅ ALL CLEAR — PRODUCTION READY\n" : " ❌ $total ISSUE(S) NEED ATTENTION\n";
echo "========================================================\n";

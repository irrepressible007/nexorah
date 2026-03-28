<?php
/**
 * DB Migration: Add payment_method, registration_type, extra_data columns
 * Run this ONCE at: /admin/db_migrate.php
 * Then delete or protect this file.
 */
require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

$sql = [];

// ── Add columns to registrations if not present ──────────────────────────────
$sql[] = "ALTER TABLE registrations 
          ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT 'bkash' AFTER transaction_id";

$sql[] = "ALTER TABLE registrations 
          ADD COLUMN IF NOT EXISTS registration_type ENUM('general','campus_ambassador','volunteer') DEFAULT 'general' AFTER payment_method";

$sql[] = "ALTER TABLE registrations 
          ADD COLUMN IF NOT EXISTS extra_data TEXT NULL COMMENT 'JSON – extra fields for CA/volunteer forms' AFTER registration_type";

// ── Create ca_applications table ─────────────────────────────────────────────
$sql[] = "CREATE TABLE IF NOT EXISTS ca_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT DEFAULT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    institution VARCHAR(255) NOT NULL,
    contact_number VARCHAR(30) NOT NULL,
    whatsapp_number VARCHAR(30) NOT NULL,
    dob DATE NULL,
    nid_bc VARCHAR(100) NULL,
    guardian_name VARCHAR(255) NULL,
    guardian_contact VARCHAR(30) NULL,
    experience TEXT NULL,
    picture_path VARCHAR(500) NULL,
    approval_status ENUM('pending','approved','rejected') DEFAULT 'pending',
    rejection_reason TEXT NULL,
    approved_by INT NULL,
    approved_date DATETIME NULL,
    submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event (event_id),
    INDEX idx_status (approval_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// ── Create volunteer_applications table ─────────────────────────────────────
$sql[] = "CREATE TABLE IF NOT EXISTS volunteer_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT DEFAULT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    institution VARCHAR(255) NULL,
    class VARCHAR(50) NULL,
    `group` VARCHAR(50) NULL,
    whatsapp_number VARCHAR(30) NULL,
    phone_number VARCHAR(30) NOT NULL,
    nid_bc VARCHAR(100) NULL,
    dob DATE NULL,
    age INT NULL,
    division VARCHAR(100) NULL,
    district VARCHAR(100) NULL,
    guardian_name VARCHAR(255) NULL,
    guardian_number VARCHAR(30) NULL,
    experience TEXT NULL,
    facebook_link VARCHAR(500) NULL,
    picture_path VARCHAR(500) NULL,
    terms_agreed TINYINT(1) DEFAULT 0,
    approval_status ENUM('pending','approved','rejected') DEFAULT 'pending',
    rejection_reason TEXT NULL,
    approved_by INT NULL,
    approved_date DATETIME NULL,
    submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event (event_id),
    INDEX idx_status (approval_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$results = [];
foreach ($sql as $query) {
    try {
        $db->exec($query);
        $results[] = ['status' => 'OK', 'query' => substr($query, 0, 80) . '...'];
    } catch (PDOException $e) {
        $results[] = ['status' => 'ERROR: ' . $e->getMessage(), 'query' => substr($query, 0, 80) . '...'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DB Migration</title>
    <style>
        body { font-family: monospace; padding: 30px; background: #1a1a2e; color: #eee; }
        h1 { color: #00d4aa; }
        .ok { color: #2ecc71; }
        .error { color: #e74c3c; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        td, th { border: 1px solid #333; padding: 10px; text-align: left; font-size: .85rem; }
        th { background: #16213e; color: #00d4aa; }
        .done { margin-top: 30px; padding: 15px; background: #0f3460; border-radius: 8px; }
    </style>
</head>
<body>
    <h1>🔧 DB Migration Results</h1>
    <table>
        <thead><tr><th>Status</th><th>Query (truncated)</th></tr></thead>
        <tbody>
            <?php foreach ($results as $r): ?>
                <tr>
                    <td class="<?= strpos($r['status'], 'ERROR') !== false ? 'error' : 'ok' ?>">
                        <?= htmlspecialchars($r['status']) ?>
                    </td>
                    <td><?= htmlspecialchars($r['query']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="done">
        ✅ Migration complete. <strong>Delete or rename this file after running.</strong>
        <br><a href="dashboard.php" style="color:#00d4aa;">→ Go to Admin Dashboard</a>
    </div>
</body>
</html>

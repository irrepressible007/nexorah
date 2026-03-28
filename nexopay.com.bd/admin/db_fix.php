<?php
/**
 * DB Fix Script - Run once to:
 * 1. Drop the FK constraint on registrations.event_id (cross-DB references can't be enforced)
 * 2. Make email, phone, guardian_number nullable (CA/volunteer rows may not have these)
 * 3. Ensure extra_data column exists
 */
require __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

$fixes = [
    // Drop FK constraint on event_id (nexopay events != nexorah events IDs)
    "ALTER TABLE registrations DROP FOREIGN KEY registrations_ibfk_1",
    // Make email nullable (CA/volunteer may not submit email to nexopay side)
    "ALTER TABLE registrations MODIFY email VARCHAR(100) NULL DEFAULT ''",
    // Make phone nullable
    "ALTER TABLE registrations MODIFY phone VARCHAR(20) NULL DEFAULT ''",
    // Make guardian_number nullable
    "ALTER TABLE registrations MODIFY guardian_number VARCHAR(20) NULL DEFAULT ''",
    // Ensure extra_data exists
    "ALTER TABLE registrations ADD COLUMN IF NOT EXISTS extra_data LONGTEXT NULL COMMENT 'JSON extra fields'",
];

foreach ($fixes as $sql) {
    try {
        $db->exec($sql);
        echo "OK: " . substr($sql, 0, 70) . "...\n";
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        // Ignore "already exists" or "doesn't exist" errors
        if (strpos($msg, 'Duplicate column') !== false || 
            strpos($msg, "Can't DROP") !== false ||
            strpos($msg, 'check that') !== false) {
            echo "SKIP (already applied): " . substr($sql, 0, 60) . "...\n";
        } else {
            echo "ERROR: $msg\n";
        }
    }
}

echo "\nDone! Verify by running test_insert.php\n";
?>

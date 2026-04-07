<?php
require_once __DIR__ . '/config/database.php';
$db_nexopay = (new Database())->getConnection();

// Quick dirty connection to nexorah by replacing db name in the string
// Or just hardcode
$dsn = "mysql:host=localhost;dbname=nexorah;charset=utf8mb4";
$pdo_nexorah = new PDO($dsn, "root", "");

$stmt = $pdo_nexorah->query("SELECT description FROM events WHERE id = 6");
$nexorah_desc = $stmt->fetchColumn();

if ($nexorah_desc) {
    $update = $db_nexopay->prepare("UPDATE events SET description = ? WHERE id = 3");
    $update->execute([$nexorah_desc]);
    echo "Updated event 3 description successfully.";
} else {
    echo "Failed to fetch event 6.";
}
unlink(__FILE__); // self-delete
?>

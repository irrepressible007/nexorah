<?php
$pdo_nexorah = new PDO("mysql:host=127.0.0.1;dbname=nexorah;charset=utf8mb4", "root", "");
$pdo_nexopay = new PDO("mysql:host=127.0.0.1;dbname=nexopay;charset=utf8mb4", "root", "");
$stmt = $pdo_nexorah->query("SELECT description FROM events WHERE id = 6");
$nexorah_desc = $stmt->fetchColumn();
if ($nexorah_desc) {
    $update = $pdo_nexopay->prepare("UPDATE events SET description = ? WHERE id = 3");
    $update->execute([$nexorah_desc]);
    echo "Updated description for Nexopay event ID 3 from Nexorah ID 6.\nContent length: " . strlen($nexorah_desc) . "\n";
} else {
    echo "Could not find event 6 in Nexorah.\n";
}
?>

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'includes/db.php';

echo "<h2>Cleaning up duplicated dummy data...</h2><ul>";

try {
    // 1. Delete dummy events entirely
    $dummy_events = ['Annual Tech Conference', 'Science Fair 2023', 'Sports Tournament Finals'];
    $placeholders = implode(',', array_fill(0, count($dummy_events), '?'));
    $stmt = $pdo->prepare("DELETE FROM events WHERE title IN ($placeholders)");
    $stmt->execute($dummy_events);
    echo "<li>Deleted all dummy template events (Science Fair, Tech Conference, Sports Tournament).</li>";

    // 2. Remove duplicates from highlights, partners, blog_posts, keeping only the lowest ID for each title
    $tablesToDeduplicate = [
        'highlights' => 'title',
        'partners' => 'name',
        'blog_posts' => 'title'
    ];

    foreach ($tablesToDeduplicate as $table => $col) {
        // Find duplicate titles/names
        $stmt = $pdo->query("SELECT min(id) as min_id, `$col` FROM `$table` GROUP BY `$col` HAVING COUNT(*) > 1");
        $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $deletedCount = 0;
        foreach ($duplicates as $row) {
            $min_id = $row['min_id'];
            $value = $row[$col];
            
            // Delete all rows with the same title except the one with the minimum ID
            $delStmt = $pdo->prepare("DELETE FROM `$table` WHERE `$col` = ? AND id > ?");
            $delStmt->execute([$value, $min_id]);
            $deletedCount += $delStmt->rowCount();
        }
        echo "<li>Cleaned up $deletedCount duplicated entries from `$table`.</li>";
    }

    echo "</ul><h2 style='color:green'>Cleanup Complete! Go refresh nexorah.com.bd homepage to see the cleaned site!</h2>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>Error during cleanup: " . $e->getMessage() . "</h2>";
}

<?php
// ── Nexorah main database connection ──────────────────────────────────────
// Copy this file to nexorah_db.php and fill in your live credentials.
// nexorah_db.php is excluded from Git — never commit live passwords!
// ──────────────────────────────────────────────────────────────────────────

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=your_nexorah_database;charset=utf8mb4",
        'your_db_username',
        'your_db_password'
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection error: " . $e->getMessage());
}

<?php
// ── DATABASE CONFIGURATION ─────────────────────────────────────────────────
// Copy this file to database.php and fill in your actual credentials.
// database.php is excluded from Git via .gitignore — never commit live passwords!
// ──────────────────────────────────────────────────────────────────────────

class Database {
    private $host     = 'localhost';
    private $db_name  = 'your_nexopay_database';  // e.g. nexorahc_nexopay on live server
    private $username = 'your_db_username';        // e.g. nexorahc_nexopay on live server
    private $password = 'your_db_password';        // e.g. Nexorah.com.bd on live server
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $exception) {
            die("Connection error: " . $exception->getMessage());
        }
        return $this->conn;
    }
}

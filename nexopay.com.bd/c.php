<?php
require 'config/nexorah_db.php';
$stmt = $pdo->query('SELECT id, title, image_path FROM events');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

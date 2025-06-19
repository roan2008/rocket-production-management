<?php
require_once __DIR__ . '/src/Database.php';
$pdo = Database::connect();
$result = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo 'Tables: ' . implode(', ', $result) . PHP_EOL;
?>

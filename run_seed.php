<?php
require_once __DIR__ . '/src/Database.php';

try {
    $pdo = Database::connect();
    echo "Connected to database successfully.\n";
    
    $seedSQL = file_get_contents(__DIR__ . '/sql/seed_default_templates.sql');
    $statements = explode(';', $seedSQL);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !str_starts_with($statement, '--')) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            $pdo->exec($statement);
        }
    }
    
    echo "Seed data inserted successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

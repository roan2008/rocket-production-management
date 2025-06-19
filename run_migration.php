<?php
require_once __DIR__ . '/src/Database.php';

try {
    $pdo = Database::connect();
    echo "Connected to database successfully.\n";
    
    // Read migration file
    $migrationSQL = file_get_contents(__DIR__ . '/sql/migration_process_templates.sql');
    
    // Split by semicolon and execute each statement
    $statements = explode(';', $migrationSQL);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !str_starts_with($statement, '--')) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            $pdo->exec($statement);
        }
    }
    
    echo "Migration completed successfully!\n";
    
    // Verify tables were created
    $tables = $pdo->query("SHOW TABLES LIKE '%Template%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Created tables: " . implode(', ', $tables) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

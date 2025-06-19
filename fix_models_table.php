<?php
require_once __DIR__ . '/src/Database.php';

try {
    $pdo = Database::connect();
    echo "Connected to database successfully.\n";
    
    // Check if DefaultTemplateID column exists
    $stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE table_name = 'Models' 
        AND column_name = 'DefaultTemplateID' 
        AND table_schema = DATABASE()
    ");
    
    $columnExists = $stmt->fetchColumn();
    
    if ($columnExists == 0) {
        echo "Adding DefaultTemplateID column to Models table...\n";
        $pdo->exec("ALTER TABLE Models ADD COLUMN DefaultTemplateID INT NULL");
        echo "Column added successfully.\n";
        
        // Add foreign key constraint
        echo "Adding foreign key constraint...\n";
        $pdo->exec("
            ALTER TABLE Models 
            ADD CONSTRAINT FK_Models_DefaultTemplate 
            FOREIGN KEY (DefaultTemplateID) REFERENCES processtemplates(TemplateID) 
            ON DELETE SET NULL
        ");
        echo "Foreign key constraint added successfully.\n";
    } else {
        echo "DefaultTemplateID column already exists.\n";
    }
    
    // Verify the column structure
    $stmt = $pdo->query("DESCRIBE Models");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nModels table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

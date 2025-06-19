<?php
require_once __DIR__ . '/src/Database.php';

try {
    $pdo = Database::connect();
    echo "Connected to database successfully.\n";
    
    // Fix 1: Check if DefaultTemplateID column exists in Models table
    $stmt = $pdo->query("DESCRIBE Models");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('DefaultTemplateID', $columns)) {
        echo "Adding DefaultTemplateID column to Models table...\n";
        $pdo->exec("ALTER TABLE Models ADD COLUMN DefaultTemplateID INT NULL");
        echo "Column added successfully!\n";
        
        // Add foreign key constraint
        echo "Adding foreign key constraint...\n";
        $pdo->exec("ALTER TABLE Models ADD CONSTRAINT FK_Models_DefaultTemplate FOREIGN KEY (DefaultTemplateID) REFERENCES processtemplates(TemplateID) ON DELETE SET NULL");
        echo "Foreign key constraint added successfully!\n";
    } else {
        echo "DefaultTemplateID column already exists.\n";
    }
    
    // Fix 2: Check if Description column exists in ProcessTemplates table
    $stmt = $pdo->query("DESCRIBE processtemplates");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('Description', $columns)) {
        echo "Adding Description column to processtemplates table...\n";
        $pdo->exec("ALTER TABLE processtemplates ADD COLUMN Description TEXT AFTER TemplateName");
        echo "Description column added successfully!\n";
    } else {
        echo "Description column already exists.\n";
    }
    
    // Fix 3: Check if StepName column exists in processtemplatesteps table
    $stmt = $pdo->query("DESCRIBE processtemplatesteps");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('StepName', $columns)) {
        echo "Adding StepName column to processtemplatesteps table...\n";
        $pdo->exec("ALTER TABLE processtemplatesteps ADD COLUMN StepName VARCHAR(100) AFTER ProcessName");
        echo "StepName column added successfully!\n";
    } else {
        echo "StepName column already exists.\n";
    }
    
    // Fix 4: Check if DefaultValue column exists in processtemplatesteps table
    if (!in_array('DefaultValue', $columns)) {
        echo "Adding DefaultValue column to processtemplatesteps table...\n";
        $pdo->exec("ALTER TABLE processtemplatesteps ADD COLUMN DefaultValue VARCHAR(255) AFTER StepName");
        echo "DefaultValue column added successfully!\n";
    } else {
        echo "DefaultValue column already exists.\n";
    }
      // Verify all table structures
    echo "\n=== MODELS TABLE STRUCTURE ===\n";
    $stmt = $pdo->query("DESCRIBE Models");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) " . ($column['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
    
    echo "\n=== PROCESSTEMPLATES TABLE STRUCTURE ===\n";
    $stmt = $pdo->query("DESCRIBE processtemplates");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) " . ($column['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
    
    echo "\n=== PROCESSTEMPLATESTEPS TABLE STRUCTURE ===\n";
    $stmt = $pdo->query("DESCRIBE processtemplatesteps");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) " . ($column['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

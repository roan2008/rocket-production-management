<?php
// Test script to verify process template functionality
require_once __DIR__ . '/src/Database.php';

echo "Testing Process Template System...\n\n";

try {
    $pdo = Database::connect();
    echo "✅ Database connection successful\n";
    
    // Test 1: Check if all required tables exist
    $tables = ['processtemplates', 'processtemplatesteps', 'models', 'projects'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists\n";
        } else {
            echo "❌ Table '$table' missing\n";
        }
    }
    
    // Test 2: Check if required columns exist
    echo "\n--- Checking column structure ---\n";
    
    // Check processtemplates table
    $stmt = $pdo->query("DESCRIBE processtemplates");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $requiredColumns = ['TemplateID', 'TemplateName', 'Description', 'ModelID', 'CreatedBy'];
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columns)) {
            echo "✅ processtemplates.$col exists\n";
        } else {
            echo "❌ processtemplates.$col missing\n";
        }
    }
    
    // Check processtemplatesteps table
    $stmt = $pdo->query("DESCRIBE processtemplatesteps");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $requiredColumns = ['TemplateStepID', 'TemplateID', 'StepName', 'DefaultValue', 'IsRequired', 'StepOrder'];
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columns)) {
            echo "✅ processtemplatesteps.$col exists\n";
        } else {
            echo "❌ processtemplatesteps.$col missing\n";
        }
    }
    
    // Test 3: Check if there's sample data
    echo "\n--- Checking sample data ---\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM projects");
    $count = $stmt->fetchColumn();
    echo "📊 Projects: $count\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM models");
    $count = $stmt->fetchColumn();
    echo "📊 Models: $count\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM processtemplates");
    $count = $stmt->fetchColumn();
    echo "📊 Process Templates: $count\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM processtemplatesteps");
    $count = $stmt->fetchColumn();
    echo "📊 Template Steps: $count\n";
    
    echo "\n🎉 Process Template System Test Complete!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>

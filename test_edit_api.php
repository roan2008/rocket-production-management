<?php
// ทดสอบ API edit_order.php
require_once __DIR__ . '/src/Database.php';

echo "Testing edit_order.php API...\n\n";

try {
    $pdo = Database::connect();
    echo "✅ Database connection successful\n";
    
    // ทดสอบการรับค่า Production Number จาก POST และ GET
    echo "\n--- Testing Parameter Reception ---\n";
    
    // จำลองการรับค่าแบบที่ API ทำ
    $_POST['ProductionNumber'] = 'TEST-FROM-POST';
    $_GET['pn'] = 'TEST-FROM-GET';
    
    $productionNumber1 = $_POST['ProductionNumber'] ?? $_GET['pn'] ?? '';
    echo "Test 1 - POST priority: " . $productionNumber1 . "\n";
    
    unset($_POST['ProductionNumber']);
    $productionNumber2 = $_POST['ProductionNumber'] ?? $_GET['pn'] ?? '';
    echo "Test 2 - GET fallback: " . $productionNumber2 . "\n";
    
    // ตรวจสอบ Production Number ที่มีอยู่จริง
    echo "\n--- Checking Existing Production Numbers ---\n";
    $stmt = $pdo->query("SELECT ProductionNumber FROM ProductionOrders LIMIT 3");
    $numbers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($numbers as $num) {
        echo "✅ " . $num . "\n";
    }
    
    echo "\n🎉 Test completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>

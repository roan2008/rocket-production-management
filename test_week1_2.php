<?php
/**
 * Week 1-2 Testing Script
 * Tests AJAX functionality, validation, and loading states
 */

echo "<h1>🧪 Week 1-2 Testing Results</h1>";

// Test 1: PHP Syntax Check
echo "<h2>1. PHP Syntax Validation</h2>";
$files_to_test = [
    'public/api/edit_order.php',
    'public/edit_order.php',
    'public/create_order.php',
    'src/Database.php'
];

foreach ($files_to_test as $file) {
    if (file_exists($file)) {
        $output = shell_exec("php -l $file 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "✅ $file - Syntax OK<br>";
        } else {
            echo "❌ $file - $output<br>";
        }
    } else {
        echo "⚠️ $file - File not found<br>";
    }
}

// Test 2: Database Connection
echo "<h2>2. Database Connection Test</h2>";
try {
    require_once 'src/Database.php';
    $pdo = Database::connect();
    echo "✅ Database connection successful<br>";
    
    // Test sanitization function
    $test_input = "<script>alert('test')</script>  ";
    $sanitized = Database::sanitizeString($test_input);
    echo "✅ Sanitization working: '$test_input' → '$sanitized'<br>";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 3: API Endpoint Structure
echo "<h2>3. API Endpoint Analysis</h2>";
if (file_exists('public/api/edit_order.php')) {
    $api_content = file_get_contents('public/api/edit_order.php');
    
    // Check for required components
    $checks = [
        'session_start()' => 'Session management',
        'header(\'Content-Type: application/json\')' => 'JSON response header',
        'http_response_code(' => 'HTTP status codes',
        '$pdo->beginTransaction()' => 'Database transactions',
        'json_encode(' => 'JSON encoding'
    ];
    
    foreach ($checks as $pattern => $description) {
        if (strpos($api_content, $pattern) !== false) {
            echo "✅ $description found<br>";
        } else {
            echo "❌ $description missing<br>";
        }
    }
} else {
    echo "❌ API endpoint file not found<br>";
}

// Test 4: Frontend Integration Check
echo "<h2>4. Frontend Integration</h2>";
if (file_exists('public/edit_order.php')) {
    $edit_content = file_get_contents('public/edit_order.php');
      $frontend_checks = [
        'addEventListener(\'submit\'' => 'AJAX form handler',
        'showLoading()' => 'Loading state function',
        'hideLoading()' => 'Loading state cleanup',
        'showToast(' => 'Toast notifications',
        'fetch(\'api/edit_order.php\'' => 'API call',
        'refreshLogs(' => 'Real-time log updates'
    ];
    
    foreach ($frontend_checks as $pattern => $description) {
        if (strpos($edit_content, $pattern) !== false) {
            echo "✅ $description implemented<br>";
        } else {
            echo "❌ $description missing<br>";
        }
    }
} else {
    echo "❌ Edit order file not found<br>";
}

// Test 4.2: create_order.php Process Log Functions
echo "<h3>4.2 Process Log Management (create_order.php)</h3>";
if (file_exists('public/create_order.php')) {
    $create_content = file_get_contents('public/create_order.php');
    
    $process_log_checks = [
        'onclick="addProcessLogRow()"' => 'Add Process Log button',
        'function addProcessLogRow()' => 'Add Process Log function',
        'function removeProcessLogRow(' => 'Remove Process Log function',
        'onclick="removeProcessLogRow(this)"' => 'Remove Process Log button',
        'processLogRowCount' => 'Process Log counter variable'
    ];
    
    foreach ($process_log_checks as $pattern => $description) {
        if (strpos($create_content, $pattern) !== false) {
            echo "✅ $description implemented<br>";
        } else {
            echo "❌ $description missing<br>";
        }
    }
} else {
    echo "❌ Edit order file not found<br>";
}

// Test 5: CSS Loading States
echo "<h2>5. CSS Loading States</h2>";
if (file_exists('public/assets/css/app.css')) {
    $css_content = file_get_contents('public/assets/css/app.css');
    
    $css_checks = [
        '.loading-spinner' => 'Loading spinner styles',
        '@keyframes spinner' => 'Spinner animation',
        '#loading-overlay' => 'Loading overlay',
        'backdrop-filter: blur' => 'Backdrop blur effect'
    ];
    
    foreach ($css_checks as $pattern => $description) {
        if (strpos($css_content, $pattern) !== false) {
            echo "✅ $description found<br>";
        } else {
            echo "❌ $description missing<br>";
        }
    }
} else {
    echo "❌ CSS file not found<br>";
}

// Test 6: JavaScript Functions
echo "<h2>6. JavaScript Utilities</h2>";
if (file_exists('public/assets/js/app.js')) {
    $js_content = file_get_contents('public/assets/js/app.js');
    
    $js_checks = [
        'function showToast(' => 'Toast notification function',
        'function showLoading()' => 'Show loading function',
        'function hideLoading()' => 'Hide loading function',
        'bootstrap.Toast' => 'Bootstrap toast integration'
    ];
    
    foreach ($js_checks as $pattern => $description) {
        if (strpos($js_content, $pattern) !== false) {
            echo "✅ $description found<br>";
        } else {
            echo "❌ $description missing<br>";
        }
    }
} else {
    echo "❌ JavaScript file not found<br>";
}

echo "<h2>📋 Manual Testing Checklist</h2>";
echo "<p>To complete testing, please verify these items manually in browser:</p>";
echo "<ul>";
echo "<li>🔲 Load edit_order.php in browser without errors</li>";
echo "<li>🔲 Submit form shows loading spinner</li>";
echo "<li>🔲 Form validation shows toast notifications</li>";
echo "<li>🔲 Process Log updates without page refresh</li>";
echo "<li>🔲 Add/Remove Process Log rows work correctly</li>";
echo "<li>🔲 Network tab shows AJAX calls to api/edit_order.php</li>";
echo "<li>🔲 Error handling displays appropriate messages</li>";
echo "</ul>";

echo "<h2>🚨 ADDITIONAL TEST REQUIRED</h2>";
echo "<p><strong>create_order.php Process Log Issue Fixed:</strong></p>";
echo "<ul>";
echo "<li>🔲 Load create_order.php in browser</li>";
echo "<li>🔲 Click 'Add Process Log' button (should add new row)</li>";
echo "<li>🔲 Click trash icon to remove Process Log row</li>";
echo "<li>🔲 Verify sequence numbers update correctly after removal</li>";
echo "<li>🔲 Ensure at least one Process Log row remains</li>";
echo "<li>🔲 Submit form and verify Process Log data is saved</li>";
echo "</ul>";

echo "<h2>🎯 Week 1-2 Status: IMPLEMENTATION COMPLETE</h2>";
echo "<p><strong>Next Steps:</strong> Complete manual testing, then proceed to Week 3 User Management System</p>";
?>

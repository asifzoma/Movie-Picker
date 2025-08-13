<?php
// Simple test file to verify deployment
echo "<h1>🎬 Movie Picker - Deployment Test</h1>";

// Test 1: Check if config loads
echo "<h2>Test 1: Configuration Loading</h2>";
try {
    require_once 'config.php';
    echo "✅ Config loaded successfully<br>";
    echo "TMDB Base URL: " . TMDB_BASE_URL . "<br>";
    echo "Environment: " . getenv('APP_ENV') . "<br>";
} catch (Exception $e) {
    echo "❌ Config failed: " . $e->getMessage() . "<br>";
}

// Test 2: Check if required files exist
echo "<h2>Test 2: Required Files</h2>";
$requiredFiles = [
    'index.php',
    'api.php',
    'SessionManager.php',
    'RecommendationEngine.php',
    '.env'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

// Test 3: Check data directory permissions
echo "<h2>Test 3: Data Directory</h2>";
$dataDir = 'data';
if (is_dir($dataDir)) {
    echo "✅ Data directory exists<br>";
    if (is_writable($dataDir)) {
        echo "✅ Data directory is writable<br>";
    } else {
        echo "❌ Data directory is not writable<br>";
    }
} else {
    echo "❌ Data directory missing<br>";
}

// Test 4: Check PHP extensions
echo "<h2>Test 4: PHP Extensions</h2>";
$requiredExtensions = ['json', 'session'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext extension loaded<br>";
    } else {
        echo "❌ $ext extension missing<br>";
    }
}

// Test 5: Check PHP version
echo "<h2>Test 5: PHP Version</h2>";
$phpVersion = phpversion();
echo "Current PHP version: $phpVersion<br>";
if (version_compare($phpVersion, '7.4.0', '>=')) {
    echo "✅ PHP version is compatible<br>";
} else {
    echo "❌ PHP version too old (need 7.4+)<br>";
}

// Test 6: Test TMDB API connection
echo "<h2>Test 6: TMDB API Connection</h2>";
if (defined('TMDB_API_READ_ACCESS_TOKEN') && !empty(TMDB_API_READ_ACCESS_TOKEN)) {
    echo "✅ API token configured<br>";
    
    // Test a simple API call
    $url = TMDB_BASE_URL . '/configuration';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'Authorization: Bearer ' . TMDB_API_READ_ACCESS_TOKEN,
                'Accept: application/json'
            ],
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response !== false) {
        echo "✅ API connection successful<br>";
    } else {
        echo "❌ API connection failed<br>";
    }
} else {
    echo "❌ API token not configured<br>";
}

echo "<hr>";
echo "<p><strong>If all tests pass, your deployment is ready for cPanel!</strong></p>";
echo "<p>Upload the files to your hosting provider and test the main application.</p>";
?>

<?php
// Load environment variables from .env file
function loadEnv($path = '.env') {
    if (!file_exists($path)) {
        throw new Exception('.env file is missing. Please copy .env.example to .env and configure your settings.');
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments and empty lines
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }

        // Check if the line contains an equals sign
        if (strpos($line, '=') === false) {
            continue;
        }

        // Split the line into name and value
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $name = trim($parts[0]);
        $value = trim($parts[1]);
        
        // Skip if name is empty
        if (empty($name)) {
            continue;
        }

        // Only set if not already set in environment
        if (!array_key_exists($name, $_ENV)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Load environment variables
loadEnv();

// TMDB API Configuration
define('TMDB_API_KEY', getenv('TMDB_API_KEY'));
define('TMDB_API_READ_ACCESS_TOKEN', getenv('TMDB_API_READ_ACCESS_TOKEN'));
define('TMDB_BASE_URL', getenv('TMDB_BASE_URL'));
define('TMDB_IMAGE_BASE_URL', getenv('TMDB_IMAGE_BASE_URL'));

// Error reporting based on environment
$appEnv = getenv('APP_ENV');
$appDebug = getenv('APP_DEBUG');

if ($appEnv === 'development' && $appDebug === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session configuration
session_start();

// Ensure data directory exists and is writable
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// Check if data files exist, create if they don't
$sessionFile = $dataDir . '/session_data.json';
if (!file_exists($sessionFile)) {
    file_put_contents($sessionFile, json_encode([
        'sessions' => []
    ], JSON_PRETTY_PRINT));
}

$recHistoryFile = $dataDir . '/rec_history.json';
if (!file_exists($recHistoryFile)) {
    file_put_contents($recHistoryFile, json_encode([
        'recommendations' => []
    ], JSON_PRETTY_PRINT));
} 
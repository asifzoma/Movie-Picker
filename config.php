<?php
// Load environment variables from .env file
function loadEnv($path = '.env') {
    if (!file_exists($path)) {
        throw new Exception('.env file is missing. Please copy .env.example to .env and configure your settings.');
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
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
if (getenv('APP_ENV') === 'development' && getenv('APP_DEBUG') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session configuration
session_start(); 
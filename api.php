<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Include configuration
require_once 'config.php';

// Log the request
error_log("Search query: " . $_GET['query']);

// Basic validation
if (!isset($_GET['action']) || $_GET['action'] !== 'search' || !isset($_GET['query'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Initialize cURL
$ch = curl_init();

// Build the URL
$url = TMDB_BASE_URL . '/search/movie';
$params = [
    'api_key' => TMDB_API_KEY,
    'query' => $_GET['query']
];
$url .= '?' . http_build_query($params);

// Set cURL options
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . TMDB_API_READ_ACCESS_TOKEN,
        'Accept: application/json'
    ]
]);

// Execute the request
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    echo json_encode(['error' => 'CURL Error: ' . curl_error($ch)]);
    exit;
}

// Close cURL
curl_close($ch);

// Output the response
echo $response; 
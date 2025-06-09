<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration
require_once 'config.php';

// Log the request
error_log("Search query: " . $_GET['query']);

// Basic validation
if (!isset($_GET['action']) || $_GET['action'] !== 'search' || !isset($_GET['query'])) {
    error_log("Invalid request: missing action or query");
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Build the URL
$url = TMDB_BASE_URL . '/search/movie';
$params = [
    'query' => $_GET['query']
];
$url .= '?' . http_build_query($params);

error_log("Making request to TMDB API: " . $url);

// Set up the stream context
$opts = [
    'http' => [
        'method' => 'GET',
        'header' => [
            'Authorization: Bearer ' . TMDB_API_READ_ACCESS_TOKEN,
            'Accept: application/json'
        ]
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
];

$context = stream_context_create($opts);

try {
    // Make the request
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        error_log("Failed to get response from TMDB API");
        echo json_encode(['error' => 'Failed to connect to TMDB API']);
        exit;
    }
    
    // Check if the response is valid JSON
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Invalid JSON response from TMDB API: " . json_last_error_msg());
        echo json_encode(['error' => 'Invalid response from TMDB API']);
        exit;
    }
    
    // Log successful response
    error_log("Successfully received TMDB API response");
    
    // Output the response
    echo $response;
    
} catch (Exception $e) {
    error_log("Exception while making TMDB API request: " . $e->getMessage());
    echo json_encode(['error' => 'Internal server error']);
    exit;
} 
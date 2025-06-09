<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration
require_once 'config.php';

// Log the request
if (isset($_GET['query'])) {
    error_log("Search query: " . $_GET['query']);
}

// Basic validation
if (!isset($_GET['action'])) {
    error_log("Invalid request: missing action");
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Set up common stream context
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

// Handle different actions
switch ($_GET['action']) {
    case 'search':
        if (!isset($_GET['query'])) {
            error_log("Invalid search request: missing query");
            echo json_encode(['error' => 'Invalid request']);
            exit;
        }
        
        // Build the search URL
        $url = TMDB_BASE_URL . '/search/movie';
        $params = [
            'query' => $_GET['query']
        ];
        $url .= '?' . http_build_query($params);
        break;
        
    case 'recommend':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("Invalid recommend request: must be POST");
            echo json_encode(['error' => 'Invalid request method']);
            exit;
        }
        
        $movies = json_decode(urldecode($_POST['movies']), true);
        if (!$movies || !isset($movies['childhood']) || !isset($movies['recommend']) || !isset($movies['watched'])) {
            error_log("Invalid recommend request: missing movie data");
            echo json_encode(['error' => 'Invalid movie data']);
            exit;
        }
        
        error_log("Processing recommendation for movies: " . print_r($movies, true));
        
        // Get recommendations based on the most watched movie
        $url = TMDB_BASE_URL . '/movie/' . $movies['watched']['id'] . '/recommendations';
        $url .= '?' . http_build_query([]);
        break;
        
    default:
        error_log("Invalid action: " . $_GET['action']);
        echo json_encode(['error' => 'Invalid action']);
        exit;
}

error_log("Making request to TMDB API: " . $url);

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
    
    // Handle recommendation response
    if ($_GET['action'] === 'recommend' && isset($data['results']) && !empty($data['results'])) {
        // Sort by vote average and count to get the best recommendation
        usort($data['results'], function($a, $b) {
            $scoreA = ($a['vote_average'] * 0.7) + (min($a['vote_count'] / 1000, 1) * 0.3);
            $scoreB = ($b['vote_average'] * 0.7) + (min($b['vote_count'] / 1000, 1) * 0.3);
            return $scoreB <=> $scoreA;
        });
        
        // Get the best recommendation
        $recommendation = $data['results'][0];
        
        // Create a journey narrative
        $journey = [
            [
                'type' => 'start',
                'movieTitle' => $movies['watched']['title'],
                'year' => $movies['watched']['year']
            ],
            [
                'type' => 'final_recommendation',
                'movieTitle' => $recommendation['title'],
                'year' => substr($recommendation['release_date'], 0, 4),
                'personName' => 'The Algorithm'
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'recommendation' => $recommendation,
            'journey' => $journey
        ]);
    } else {
        // For search, just return the raw response
        echo $response;
    }
    
    error_log("Successfully processed API response");
    
} catch (Exception $e) {
    error_log("Exception while making TMDB API request: " . $e->getMessage());
    echo json_encode(['error' => 'Internal server error']);
    exit;
} 
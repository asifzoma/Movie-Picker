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
        
        try {
            // Build preference profile from user's movies
            $preferenceProfile = [
                'genres' => [],
                'vote_averages' => []
            ];
            
            // Get detailed info for each movie to build profile
            foreach ($movies as $key => $movie) {
                if (!isset($movie['id'])) continue;
                
                $detailUrl = TMDB_BASE_URL . '/movie/' . $movie['id'] . '?append_to_response=credits';
                $detailResponse = file_get_contents($detailUrl, false, $context);
                
                if ($detailResponse !== false) {
                    $details = json_decode($detailResponse, true);
                    
                    // Collect genres
                    if (isset($details['genres'])) {
                        foreach ($details['genres'] as $genre) {
                            $preferenceProfile['genres'][$genre['id']] = ($preferenceProfile['genres'][$genre['id']] ?? 0) + 1;
                        }
                    }
                    
                    $preferenceProfile['vote_averages'][] = $details['vote_average'];
                }
            }
            
            $recommendations = [];
            
            // Use only genre-based recommendation (Tier 2)
            if (!empty($preferenceProfile['genres'])) {
                arsort($preferenceProfile['genres']);
                $topGenres = array_slice(array_keys($preferenceProfile['genres']), 0, 2);
                
                $discoverUrl = TMDB_BASE_URL . '/discover/movie?' . http_build_query([
                    'with_genres' => implode('|', $topGenres),
                    'vote_average.gte' => 7.0,
                    'vote_count.gte' => 1000,
                    'sort_by' => 'vote_average.desc',
                    'page' => 1
                ]);
                
                $discoverResponse = file_get_contents($discoverUrl, false, $context);
                if ($discoverResponse !== false) {
                    $discoverData = json_decode($discoverResponse, true);
                    
                    foreach ($discoverData['results'] ?? [] as $movie) {
                        if (!in_array($movie['id'], array_column($movies, 'id'))) {
                            $recommendations[] = $movie;
                            if (count($recommendations) >= 3) break; // Get 3 movies
                        }
                    }
                }
            }
            
            // If we don't have enough genre-based recommendations, get more from page 2
            if (count($recommendations) < 3 && !empty($preferenceProfile['genres'])) {
                $discoverUrl = TMDB_BASE_URL . '/discover/movie?' . http_build_query([
                    'with_genres' => implode('|', $topGenres),
                    'vote_average.gte' => 7.0,
                    'vote_count.gte' => 1000,
                    'sort_by' => 'vote_average.desc',
                    'page' => 2
                ]);
                
                $discoverResponse = file_get_contents($discoverUrl, false, $context);
                if ($discoverResponse !== false) {
                    $discoverData = json_decode($discoverResponse, true);
                    
                    foreach ($discoverData['results'] ?? [] as $movie) {
                        if (!in_array($movie['id'], array_column($movies, 'id')) && 
                            !in_array($movie['id'], array_column($recommendations, 'id'))) {
                            $recommendations[] = $movie;
                            if (count($recommendations) >= 3) break;
                        }
                    }
                }
            }
            
            if (!empty($recommendations)) {
                // Separate main recommendation and alternatives
                $mainRecommendation = array_shift($recommendations);
                $alternatives = array_slice($recommendations, 0, 2);
                
                echo json_encode([
                    'success' => true,
                    'recommendation' => $mainRecommendation,
                    'alternatives' => $alternatives
                ]);
                exit;
            } else {
                echo json_encode(['error' => 'Could not find suitable recommendations']);
                exit;
            }
            
        } catch (Exception $e) {
            error_log("Error in recommendation logic: " . $e->getMessage());
            echo json_encode(['error' => 'Failed to generate recommendation']);
            exit;
        }
        
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
    
    // For search, just return the raw response
    echo $response;
    
    error_log("Successfully processed API response");
    
} catch (Exception $e) {
    error_log("Exception while making TMDB API request: " . $e->getMessage());
    echo json_encode(['error' => 'Internal server error']);
    exit;
} 
<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration and classes
require_once 'config.php';
require_once 'SessionManager.php';
require_once 'RecommendationEngine.php';

// Initialize session manager
$sessionManager = new SessionManager();

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
        
    case 'more_movies':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("Invalid more_movies request: must be POST");
            echo json_encode(['error' => 'Invalid request method']);
            exit;
        }
        
        $requestData = json_decode(urldecode($_POST['data']), true);
        if (!$requestData || !isset($requestData['movies']) || !isset($requestData['page']) || !isset($requestData['exclude_ids'])) {
            error_log("Invalid more_movies request: missing data");
            echo json_encode(['error' => 'Invalid request data']);
            exit;
        }
        
        error_log("Processing more movies request: " . print_r($requestData, true));
        
        try {
            // Build preference profile from user's movies
            $preferenceProfile = [
                'genres' => []
            ];
            
            // Get detailed info for each movie to build profile
            foreach ($requestData['movies'] as $movie) {
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
                }
            }
            
            $recommendations = [];
            
            // Use genre-based recommendation
            if (!empty($preferenceProfile['genres'])) {
                arsort($preferenceProfile['genres']);
                $topGenres = array_slice(array_keys($preferenceProfile['genres']), 0, 2);
                
                $discoverUrl = TMDB_BASE_URL . '/discover/movie?' . http_build_query([
                    'with_genres' => implode('|', $topGenres),
                    'vote_average.gte' => 7.0,
                    'vote_count.gte' => 1000,
                    'sort_by' => 'vote_average.desc',
                    'page' => $requestData['page']
                ]);
                
                $discoverResponse = file_get_contents($discoverUrl, false, $context);
                if ($discoverResponse !== false) {
                    $discoverData = json_decode($discoverResponse, true);
                    
                    foreach ($discoverData['results'] ?? [] as $movie) {
                        if (!in_array($movie['id'], $requestData['exclude_ids'])) {
                            $recommendations[] = $movie;
                            if (count($recommendations) >= 3) break;
                        }
                    }
                }
            }
            
            if (!empty($recommendations)) {
                echo json_encode([
                    'success' => true,
                    'new_movies' => $recommendations,
                    'has_more' => count($discoverData['results'] ?? []) > count($recommendations)
                ]);
                exit;
            } else {
                echo json_encode(['error' => 'No more recommendations available']);
                exit;
            }
            
        } catch (Exception $e) {
            error_log("Error in more_movies logic: " . $e->getMessage());
            echo json_encode(['error' => 'Failed to load more movies']);
            exit;
        }
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
            // Convert movies object to array
            $userMovies = [
                $movies['childhood'],
                $movies['recommend'],
                $movies['watched']
            ];
            
            // Initialize recommendation engine
            $recommendationEngine = new RecommendationEngine($sessionManager);
            
            // Generate recommendations using the new engine
            $allRecommendations = $recommendationEngine->generateRecommendations($userMovies, 15);
            
            if (!empty($allRecommendations)) {
                // Separate main recommendation and alternatives
                $mainRecommendation = array_shift($allRecommendations);
                $alternatives = array_slice($allRecommendations, 0, 10); // More alternatives
                
                // Store recommendations in session
                $sessionManager->setCurrentRecommendations($allRecommendations);
                $sessionManager->addToRecommendationQueue($alternatives);
                
                // Add to recommendation history
                $sessionManager->addToRecommendationHistory($mainRecommendation);
                
                echo json_encode([
                    'success' => true,
                    'recommendation' => $mainRecommendation,
                    'alternatives' => $alternatives,
                    'total_available' => count($allRecommendations)
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
        
    case 'like_movie':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Invalid request method']);
            exit;
        }
        
        $movieData = json_decode(urldecode($_POST['movie']), true);
        if (!$movieData || !isset($movieData['id'])) {
            echo json_encode(['error' => 'Invalid movie data']);
            exit;
        }
        
        $sessionManager->addLikedMovie($movieData);
        $sessionManager->removeFromQueue($movieData['id']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Movie added to favorites',
            'stats' => $sessionManager->getSessionStats()
        ]);
        exit;
        
    case 'dislike_movie':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Invalid request method']);
            exit;
        }
        
        $movieData = json_decode(urldecode($_POST['movie']), true);
        if (!$movieData || !isset($movieData['id'])) {
            echo json_encode(['error' => 'Invalid movie data']);
            exit;
        }
        
        $sessionManager->addDislikedMovie($movieData);
        $sessionManager->removeFromQueue($movieData['id']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Movie marked as disliked',
            'stats' => $sessionManager->getSessionStats()
        ]);
        exit;
        
    case 'get_liked_movies':
        $likedMovies = $sessionManager->getLikedMovies();
        echo json_encode([
            'success' => true,
            'movies' => $likedMovies,
            'count' => count($likedMovies)
        ]);
        exit;
        
    case 'clear_liked_movies':
        $sessionManager->clearLikedMovies();
        echo json_encode([
            'success' => true,
            'message' => 'Liked movies cleared'
        ]);
        exit;
        
    case 'remove_liked_movie':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Invalid request method']);
            exit;
        }
        
        $movieId = $_POST['movie_id'] ?? null;
        if ($movieId) {
            $sessionManager->removeLikedMovie($movieId);
            echo json_encode([
                'success' => true,
                'message' => 'Movie removed from liked movies'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Movie ID required']);
        }
        exit;
        
    case 'get_more_movies':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Invalid request method']);
            exit;
        }
        
        $currentCount = intval($_POST['current_count'] ?? 0);
        
        try {
            $recommendationEngine = new RecommendationEngine($sessionManager);
            $newMovies = $recommendationEngine->getMoreRecommendations($currentCount, 5);
            
            if (!empty($newMovies)) {
                $sessionManager->addToRecommendationQueue($newMovies);
                
                echo json_encode([
                    'success' => true,
                    'new_movies' => $newMovies,
                    'total_available' => count($sessionManager->getRecommendationQueue())
                ]);
            } else {
                echo json_encode(['error' => 'No more recommendations available']);
            }
        } catch (Exception $e) {
            error_log("Error getting more movies: " . $e->getMessage());
            echo json_encode(['error' => 'Failed to get more movies']);
        }
        exit;
        
    case 'get_session_stats':
        echo json_encode([
            'success' => true,
            'stats' => $sessionManager->getSessionStats()
        ]);
        exit;
        
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
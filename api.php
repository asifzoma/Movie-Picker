<?php
require_once 'config.php';
require_once 'tmdb_api.php';

header('Content-Type: application/json');

if (!isset($_GET['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No action specified']);
    exit;
}

switch ($_GET['action']) {
    case 'search':
        if (!isset($_GET['query'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No search query provided']);
            exit;
        }
        $results = TMDBApi::searchMovies($_GET['query']);
        echo json_encode($results);
        break;
        
    case 'recommend':
        if (!isset($_POST['movies'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No movies provided']);
            exit;
        }
        
        $movies = json_decode($_POST['movies'], true);
        $journey = [];
        $recommendation = null;
        
        try {
            // Get movie details for all selected movies
            $moviesWithDetails = [];
            foreach ($movies as $type => $movie) {
                $details = TMDBApi::getMovieDetails($movie['id']);
                $moviesWithDetails[] = array_merge($movie, ['details' => $details]);
            }
            
            // Find seed movie (highest rated)
            $seedMovie = $moviesWithDetails[0];
            foreach ($moviesWithDetails as $movie) {
                if ($movie['vote_average'] > $seedMovie['vote_average']) {
                    $seedMovie = $movie;
                }
            }
            
            // Start journey
            $journey[] = [
                'type' => 'start',
                'movieId' => $seedMovie['id'],
                'movieTitle' => $seedMovie['title'],
                'year' => $seedMovie['year']
            ];
            
            // Get credits for seed movie
            $credits = TMDBApi::getMovieCredits($seedMovie['id']);
            $writers = array_filter($credits['crew'], function($person) {
                return $person['job'] === 'Writer';
            });
            
            if (!empty($writers)) {
                // Sort writers by popularity
                usort($writers, function($a, $b) {
                    return $b['popularity'] - $a['popularity'];
                });
                
                $writer = $writers[0];
                $journey[] = [
                    'type' => 'writer',
                    'personId' => $writer['id'],
                    'personName' => $writer['name'],
                    'movieTitle' => $seedMovie['title']
                ];
                
                // Get writer's best work
                $writerCredits = TMDBApi::getPersonCredits($writer['id']);
                $writerMovies = array_merge($writerCredits['cast'], $writerCredits['crew']);
                $writerMovies = array_filter($writerMovies, function($item) use ($seedMovie) {
                    return $item['media_type'] === 'movie' && 
                           isset($item['vote_average']) && 
                           $item['id'] !== $seedMovie['id'];
                });
                
                if (!empty($writerMovies)) {
                    usort($writerMovies, function($a, $b) {
                        return $b['vote_average'] - $a['vote_average'];
                    });
                    
                    $bestWriterMovie = $writerMovies[0];
                    $journey[] = [
                        'type' => 'best_writer_movie',
                        'movieId' => $bestWriterMovie['id'],
                        'movieTitle' => $bestWriterMovie['title'],
                        'year' => isset($bestWriterMovie['release_date']) ? 
                                substr($bestWriterMovie['release_date'], 0, 4) : 'Unknown',
                        'personName' => $writer['name']
                    ];
                    
                    // Get director of best writer movie
                    $bestMovieCredits = TMDBApi::getMovieCredits($bestWriterMovie['id']);
                    $directors = array_filter($bestMovieCredits['crew'], function($person) {
                        return $person['job'] === 'Director';
                    });
                    
                    if (!empty($directors)) {
                        $director = reset($directors);
                        $journey[] = [
                            'type' => 'director',
                            'personId' => $director['id'],
                            'personName' => $director['name'],
                            'movieTitle' => $bestWriterMovie['title']
                        ];
                        
                        // Get director's best work
                        $directorCredits = TMDBApi::getPersonCredits($director['id']);
                        $directorMovies = array_merge($directorCredits['cast'], $directorCredits['crew']);
                        $directorMovies = array_filter($directorMovies, function($item) use ($bestWriterMovie) {
                            return $item['media_type'] === 'movie' && 
                                   isset($item['vote_average']) && 
                                   $item['id'] !== $bestWriterMovie['id'];
                        });
                        
                        if (!empty($directorMovies)) {
                            usort($directorMovies, function($a, $b) {
                                return $b['vote_average'] - $a['vote_average'];
                            });
                            
                            $recommendation = $directorMovies[0];
                            $journey[] = [
                                'type' => 'final_recommendation',
                                'movieId' => $recommendation['id'],
                                'movieTitle' => $recommendation['title'],
                                'year' => isset($recommendation['release_date']) ? 
                                        substr($recommendation['release_date'], 0, 4) : 'Unknown',
                                'personName' => $director['name']
                            ];
                        }
                    }
                }
            }
            
            // Fallback to similar movies if no recommendation yet
            if (!$recommendation) {
                $similarMovies = TMDBApi::getSimilarMovies($seedMovie['id']);
                if (!empty($similarMovies['results'])) {
                    $recommendation = $similarMovies['results'][0];
                    $journey[] = [
                        'type' => 'fallback',
                        'movieId' => $recommendation['id'],
                        'movieTitle' => $recommendation['title'],
                        'year' => isset($recommendation['release_date']) ? 
                                substr($recommendation['release_date'], 0, 4) : 'Unknown',
                        'seedMovieTitle' => $seedMovie['title']
                    ];
                } else {
                    // Final fallback to popular movies
                    $popularMovies = TMDBApi::getPopularMovies();
                    if (!empty($popularMovies['results'])) {
                        $recommendation = $popularMovies['results'][0];
                        $journey[] = [
                            'type' => 'final_fallback',
                            'movieId' => $recommendation['id'],
                            'movieTitle' => $recommendation['title'],
                            'year' => isset($recommendation['release_date']) ? 
                                    substr($recommendation['release_date'], 0, 4) : 'Unknown'
                        ];
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'recommendation' => $recommendation,
                'journey' => $journey
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Failed to generate recommendation',
                'message' => $e->getMessage()
            ]);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
} 
<?php

class RecommendationEngine {
    private $sessionManager;
    private $context;
    private $baseUrl;
    
    public function __construct($sessionManager) {
        $this->sessionManager = $sessionManager;
        $this->baseUrl = TMDB_BASE_URL;
        
        // Set up HTTP context
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
        $this->context = stream_context_create($opts);
    }
    
    public function generateRecommendations($userMovies, $count = 10) {
        // Build comprehensive user profile
        $userProfile = $this->buildUserProfile($userMovies);
        
        // Get user preferences from session
        $sessionPrefs = $this->sessionManager->getUserPreferences();
        
        // Merge profiles
        $mergedProfile = $this->mergeProfiles($userProfile, $sessionPrefs);
        
        // Generate recommendations using multiple strategies
        $recommendations = [];
        
        // Strategy 1: Exact genre + director matches
        $exactMatches = $this->getExactMatches($mergedProfile, $count / 3);
        $recommendations = array_merge($recommendations, $exactMatches);
        
        // Strategy 2: Genre matches with similar directors
        $genreMatches = $this->getGenreMatches($mergedProfile, $count / 3);
        $recommendations = array_merge($recommendations, $genreMatches);
        
        // Strategy 3: Director matches with similar genres
        $directorMatches = $this->getDirectorMatches($mergedProfile, $count / 3);
        $recommendations = array_merge($recommendations, $directorMatches);
        
        // Remove duplicates and filter out already seen movies
        $recommendations = $this->filterRecommendations($recommendations);
        
        // Sort by relevance score
        $recommendations = $this->sortByRelevance($recommendations, $mergedProfile);
        
        return array_slice($recommendations, 0, $count);
    }
    
    private function buildUserProfile($userMovies) {
        $profile = [
            'genres' => [],
            'directors' => [],
            'actors' => [],
            'vote_averages' => [],
            'years' => [],
            'keywords' => []
        ];
        
        foreach ($userMovies as $movie) {
            if (!isset($movie['id'])) continue;
            
            $details = $this->getMovieDetails($movie['id']);
            if (!$details) continue;
            
            // Collect genres
            if (isset($details['genres'])) {
                foreach ($details['genres'] as $genre) {
                    $profile['genres'][$genre['id']] = ($profile['genres'][$genre['id']] ?? 0) + 1;
                }
            }
            
            // Collect directors
            if (isset($details['credits']['crew'])) {
                foreach ($details['credits']['crew'] as $crew) {
                    if ($crew['job'] === 'Director') {
                        $profile['directors'][$crew['name']] = ($profile['directors'][$crew['name']] ?? 0) + 1;
                    }
                }
            }
            
            // Collect actors
            if (isset($details['credits']['cast'])) {
                foreach (array_slice($details['credits']['cast'], 0, 5) as $actor) {
                    $profile['actors'][$actor['name']] = ($profile['actors'][$actor['name']] ?? 0) + 1;
                }
            }
            
            // Collect vote averages and years
            $profile['vote_averages'][] = $details['vote_average'] ?? 0;
            if (isset($details['release_date'])) {
                $profile['years'][] = intval(substr($details['release_date'], 0, 4));
            }
        }
        
        return $profile;
    }
    
    private function mergeProfiles($userProfile, $sessionPrefs) {
        $merged = $userProfile;
        
        // Merge genres
        foreach ($sessionPrefs['genres'] as $genreId => $weight) {
            $merged['genres'][$genreId] = ($merged['genres'][$genreId] ?? 0) + $weight;
        }
        
        // Merge directors
        foreach ($sessionPrefs['directors'] as $director => $weight) {
            $merged['directors'][$director] = ($merged['directors'][$director] ?? 0) + $weight;
        }
        
        return $merged;
    }
    
    private function getExactMatches($profile, $count) {
        $recommendations = [];
        
        // Get top genres and directors
        arsort($profile['genres']);
        arsort($profile['directors']);
        
        $topGenres = array_slice(array_keys($profile['genres']), 0, 3);
        $topDirectors = array_slice(array_keys($profile['directors']), 0, 3);
        
        foreach ($topDirectors as $director) {
            $directorMovies = $this->getMoviesByDirector($director);
            foreach ($directorMovies as $movie) {
                if ($this->hasMatchingGenres($movie, $topGenres)) {
                    $movie['relevance_score'] = 10; // High score for exact matches
                    $movie['match_type'] = 'exact_genre_director';
                    $recommendations[] = $movie;
                    if (count($recommendations) >= $count) break 2;
                }
            }
        }
        
        return $recommendations;
    }
    
    private function getGenreMatches($profile, $count) {
        $recommendations = [];
        
        arsort($profile['genres']);
        $topGenres = array_slice(array_keys($profile['genres']), 0, 3);
        
        $discoverUrl = $this->baseUrl . '/discover/movie?' . http_build_query([
            'with_genres' => implode('|', $topGenres),
            'vote_average.gte' => 7.0,
            'vote_count.gte' => 1000,
            'sort_by' => 'vote_average.desc',
            'page' => 1
        ]);
        
        $response = file_get_contents($discoverUrl, false, $this->context);
        if ($response !== false) {
            $data = json_decode($response, true);
            
            foreach ($data['results'] ?? [] as $movie) {
                $movie['relevance_score'] = $this->calculateGenreScore($movie, $profile);
                $movie['match_type'] = 'genre_match';
                $recommendations[] = $movie;
                if (count($recommendations) >= $count) break;
            }
        }
        
        return $recommendations;
    }
    
    private function getDirectorMatches($profile, $count) {
        $recommendations = [];
        
        arsort($profile['directors']);
        $topDirectors = array_slice(array_keys($profile['directors']), 0, 3);
        
        foreach ($topDirectors as $director) {
            $directorMovies = $this->getMoviesByDirector($director);
            foreach ($directorMovies as $movie) {
                $movie['relevance_score'] = $this->calculateDirectorScore($movie, $profile);
                $movie['match_type'] = 'director_match';
                $recommendations[] = $movie;
                if (count($recommendations) >= $count) break 2;
            }
        }
        
        return $recommendations;
    }
    
    private function getMovieDetails($movieId) {
        $url = $this->baseUrl . '/movie/' . $movieId . '?append_to_response=credits,keywords';
        $response = file_get_contents($url, false, $this->context);
        
        if ($response !== false) {
            $details = json_decode($response, true);
            
            // Extract director information
            if (isset($details['credits']['crew'])) {
                foreach ($details['credits']['crew'] as $crew) {
                    if ($crew['job'] === 'Director') {
                        $details['director'] = $crew['name'];
                        break;
                    }
                }
            }
            
            return $details;
        }
        
        return null;
    }
    
    private function getMoviesByDirector($directorName) {
        // Search for movies by director
        $searchUrl = $this->baseUrl . '/search/person?' . http_build_query([
            'query' => $directorName
        ]);
        
        $response = file_get_contents($searchUrl, false, $this->context);
        if ($response === false) return [];
        
        $data = json_decode($response, true);
        $directorId = null;
        
        // Find the director
        foreach ($data['results'] ?? [] as $person) {
            if (stripos($person['name'], $directorName) !== false) {
                $directorId = $person['id'];
                break;
            }
        }
        
        if (!$directorId) return [];
        
        // Get director's movies
        $moviesUrl = $this->baseUrl . '/person/' . $directorId . '/movie_credits';
        $moviesResponse = file_get_contents($moviesUrl, false, $this->context);
        
        if ($moviesResponse === false) return [];
        
        $moviesData = json_decode($moviesResponse, true);
        $movies = [];
        
        foreach ($moviesData['crew'] ?? [] as $movie) {
            if ($movie['job'] === 'Director' && $movie['vote_average'] >= 7.0) {
                $movies[] = $movie;
            }
        }
        
        return $movies;
    }
    
    private function hasMatchingGenres($movie, $targetGenres) {
        if (!isset($movie['genre_ids'])) return false;
        
        foreach ($targetGenres as $genreId) {
            if (in_array($genreId, $movie['genre_ids'])) {
                return true;
            }
        }
        
        return false;
    }
    
    private function calculateGenreScore($movie, $profile) {
        $score = 0;
        
        if (isset($movie['genre_ids'])) {
            foreach ($movie['genre_ids'] as $genreId) {
                $score += $profile['genres'][$genreId] ?? 0;
            }
        }
        
        // Bonus for high rating
        if (isset($movie['vote_average']) && $movie['vote_average'] >= 8.0) {
            $score += 2;
        }
        
        return $score;
    }
    
    private function calculateDirectorScore($movie, $profile) {
        $score = 0;
        
        // Base score for director match
        if (isset($movie['director'])) {
            $score += $profile['directors'][$movie['director']] ?? 0;
        }
        
        // Bonus for genre overlap
        if (isset($movie['genre_ids'])) {
            foreach ($movie['genre_ids'] as $genreId) {
                $score += ($profile['genres'][$genreId] ?? 0) * 0.5;
            }
        }
        
        return $score;
    }
    
    private function filterRecommendations($recommendations) {
        $filtered = [];
        $seenIds = [];
        
        // Get already seen movies
        $history = $this->sessionManager->getRecommendationHistory();
        $likedMovies = $this->sessionManager->getLikedMovies();
        $dislikedMovies = $this->sessionManager->getDislikedMovies();
        
        $excludeIds = array_merge(
            array_column($history, 'id'),
            array_column($likedMovies, 'id'),
            array_column($dislikedMovies, 'id')
        );
        
        foreach ($recommendations as $movie) {
            if (!in_array($movie['id'], $excludeIds) && !in_array($movie['id'], $seenIds)) {
                $filtered[] = $movie;
                $seenIds[] = $movie['id'];
            }
        }
        
        return $filtered;
    }
    
    private function sortByRelevance($recommendations, $profile) {
        usort($recommendations, function($a, $b) {
            $scoreA = $a['relevance_score'] ?? 0;
            $scoreB = $b['relevance_score'] ?? 0;
            return $scoreB <=> $scoreA;
        });
        
        return $recommendations;
    }
    
    public function getMoreRecommendations($currentCount = 0, $additionalCount = 5) {
        $userPrefs = $this->sessionManager->getUserPreferences();
        
        // Use more relaxed criteria for additional recommendations
        $recommendations = [];
        
        // Get top genres with relaxed criteria
        arsort($userPrefs['genres']);
        $topGenres = array_slice(array_keys($userPrefs['genres']), 0, 2);
        
        if (!empty($topGenres)) {
            $discoverUrl = $this->baseUrl . '/discover/movie?' . http_build_query([
                'with_genres' => implode('|', $topGenres),
                'vote_average.gte' => 6.5, // Relaxed rating requirement
                'vote_count.gte' => 500,   // Relaxed vote count
                'sort_by' => 'popularity.desc', // Use popularity instead of rating
                'page' => rand(1, 5) // Random page for variety
            ]);
            
            $response = file_get_contents($discoverUrl, false, $this->context);
            if ($response !== false) {
                $data = json_decode($response, true);
                
                foreach ($data['results'] ?? [] as $movie) {
                    $movie['relevance_score'] = $this->calculateGenreScore($movie, $userPrefs);
                    $movie['match_type'] = 'additional_recommendation';
                    $recommendations[] = $movie;
                    if (count($recommendations) >= $additionalCount) break;
                }
            }
        }
        
        return $this->filterRecommendations($recommendations);
    }
}

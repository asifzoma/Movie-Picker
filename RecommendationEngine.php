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
        // First, handle any duplicates in user input
        $duplicateAnalysis = $this->detectAndHandleDuplicates($userMovies);
        if ($duplicateAnalysis['duplicate_count'] > 0) {
            error_log("Found " . $duplicateAnalysis['duplicate_count'] . " duplicate movies in user input");
            $userMovies = $duplicateAnalysis['unique_movies'];
        }
        
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
        
        // Remove duplicates and filter out already seen movies and franchises
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
        
        // Enhanced to find more obscure/niche films
        $discoverUrl = $this->baseUrl . '/discover/movie?' . http_build_query([
            'with_genres' => implode('|', $topGenres),
            'vote_average.gte' => 6.5,  // Lower threshold for more obscure films
            'vote_count.gte' => 50,     // Much lower vote count for niche films
            'vote_count.lte' => 5000,   // Upper limit to avoid mainstream hits
            'sort_by' => 'vote_average.desc',
            'page' => 1,
            'include_adult' => false,
            'with_original_language' => 'en'  // Focus on English films for better accessibility
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
            // Enhanced criteria for more obscure films
            if ($movie['job'] === 'Director' && 
                $movie['vote_average'] >= 6.5 && 
                $movie['vote_count'] >= 50 && 
                $movie['vote_count'] <= 5000) {
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
        
        // Get franchise information for input movies to avoid franchise repetition
        $inputFranchises = $this->getInputMovieFranchises();
        
        foreach ($recommendations as $movie) {
            if (!in_array($movie['id'], $excludeIds) && !in_array($movie['id'], $seenIds)) {
                // Check if movie is from the same franchise as input movies
                if (!$this->isSameFranchise($movie, $inputFranchises)) {
                    $filtered[] = $movie;
                    $seenIds[] = $movie['id'];
                }
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
    
    /**
     * Get franchise information for input movies to avoid franchise repetition
     */
    private function getInputMovieFranchises() {
        $franchises = [];
        $userMovies = $this->sessionManager->getLikedMovies();
        
        foreach ($userMovies as $movie) {
            if (isset($movie['id'])) {
                $details = $this->getMovieDetails($movie['id']);
                if ($details && isset($details['belongs_to_collection'])) {
                    $collection = $details['belongs_to_collection'];
                    $franchises[$collection['id']] = [
                        'name' => $collection['name'],
                        'id' => $collection['id']
                    ];
                }
            }
        }
        
        return $franchises;
    }
    
    /**
     * Check if a movie belongs to the same franchise as input movies
     */
    private function isSameFranchise($movie, $inputFranchises) {
        if (empty($inputFranchises)) {
            return false;
        }
        
        // Get movie details to check franchise
        $details = $this->getMovieDetails($movie['id']);
        if (!$details || !isset($details['belongs_to_collection'])) {
            return false;
        }
        
        $movieCollection = $details['belongs_to_collection'];
        
        // Check if this movie belongs to any of the input franchises
        foreach ($inputFranchises as $franchise) {
            if ($franchise['id'] === $movieCollection['id']) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Detect and handle duplicate movie inputs intelligently
     */
    public function detectAndHandleDuplicates($userMovies) {
        $duplicates = [];
        $uniqueMovies = [];
        $seenTitles = [];
        $seenIds = [];
        
        foreach ($userMovies as $movie) {
            $title = strtolower(trim($movie['title'] ?? ''));
            $id = $movie['id'] ?? null;
            
            // Check for exact ID duplicates
            if ($id && in_array($id, $seenIds)) {
                $duplicates[] = [
                    'type' => 'exact_duplicate',
                    'movie' => $movie,
                    'reason' => 'Same movie ID already provided'
                ];
                continue;
            }
            
            // Check for title duplicates (with fuzzy matching)
            $isDuplicate = false;
            foreach ($seenTitles as $seenTitle) {
                if ($this->isSimilarTitle($title, $seenTitle)) {
                    $duplicates[] = [
                        'type' => 'title_duplicate',
                        'movie' => $movie,
                        'reason' => 'Similar title already provided: ' . $seenTitle
                    ];
                    $isDuplicate = true;
                    break;
                }
            }
            
            if (!$isDuplicate) {
                $uniqueMovies[] = $movie;
                $seenTitles[] = $title;
                if ($id) $seenIds[] = $id;
            }
        }
        
        return [
            'unique_movies' => $uniqueMovies,
            'duplicates' => $duplicates,
            'duplicate_count' => count($duplicates)
        ];
    }
    
    /**
     * Fuzzy title matching to detect similar movies
     */
    private function isSimilarTitle($title1, $title2) {
        // Remove common suffixes and prefixes
        $clean1 = $this->cleanTitle($title1);
        $clean2 = $this->cleanTitle($title2);
        
        // Exact match after cleaning
        if ($clean1 === $clean2) {
            return true;
        }
        
        // Check for franchise patterns (e.g., "Star Wars: Episode IV" vs "Star Wars: Episode V")
        if ($this->isFranchisePattern($clean1, $clean2)) {
            return true;
        }
        
        // Calculate similarity using Levenshtein distance
        $similarity = 1 - (levenshtein($clean1, $clean2) / max(strlen($clean1), strlen($clean2)));
        
        return $similarity > 0.8; // 80% similarity threshold
    }
    
    /**
     * Clean movie title for comparison
     */
    private function cleanTitle($title) {
        // Remove common suffixes
        $suffixes = ['(film)', '(movie)', '(2019)', '(2020)', '(2021)', '(2022)', '(2023)', '(2024)'];
        $title = str_replace($suffixes, '', $title);
        
        // Remove year patterns
        $title = preg_replace('/\s*\(\d{4}\)\s*/', '', $title);
        
        // Remove special characters and extra spaces
        $title = preg_replace('/[^\w\s]/', '', $title);
        $title = preg_replace('/\s+/', ' ', $title);
        
        return trim(strtolower($title));
    }
    
    /**
     * Check if titles follow franchise naming patterns
     */
    private function isFranchisePattern($title1, $title2) {
        // Common franchise patterns
        $patterns = [
            '/^(.*?)\s*:\s*episode\s*[ivxlcdm]+$/i',
            '/^(.*?)\s*part\s*[ivxlcdm]+$/i',
            '/^(.*?)\s*#\s*\d+$/i',
            '/^(.*?)\s*vol\.\s*\d+$/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $title1) && preg_match($pattern, $title2)) {
                $base1 = preg_replace($pattern, '$1', $title1);
                $base2 = preg_replace($pattern, '$1', $title2);
                
                if ($base1 === $base2) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Enhanced recommendation generation with duplicate handling
     */
    public function generateRecommendationsWithDuplicateHandling($userMovies, $count = 10) {
        // First, detect and handle duplicates
        $duplicateAnalysis = $this->detectAndHandleDuplicates($userMovies);
        
        if ($duplicateAnalysis['duplicate_count'] > 0) {
            // Log duplicates for user awareness
            error_log("Found " . $duplicateAnalysis['duplicate_count'] . " duplicate movies in user input");
            
            // Use only unique movies for recommendations
            $userMovies = $duplicateAnalysis['unique_movies'];
        }
        
        // Generate recommendations using unique movies
        return $this->generateRecommendations($userMovies, $count);
    }
    
    /**
     * Get detailed analysis of user input for debugging and user feedback
     */
    public function analyzeUserInput($userMovies) {
        $analysis = [
            'total_inputs' => count($userMovies),
            'duplicates' => $this->detectAndHandleDuplicates($userMovies),
            'franchises' => $this->getInputMovieFranchises(),
            'genres' => [],
            'directors' => [],
            'years' => []
        ];
        
        // Analyze genres and directors
        foreach ($userMovies as $movie) {
            if (isset($movie['id'])) {
                $details = $this->getMovieDetails($movie['id']);
                if ($details) {
                    // Collect genres
                    if (isset($details['genres'])) {
                        foreach ($details['genres'] as $genre) {
                            $analysis['genres'][$genre['name']] = ($analysis['genres'][$genre['name']] ?? 0) + 1;
                        }
                    }
                    
                    // Collect directors
                    if (isset($details['credits']['crew'])) {
                        foreach ($details['credits']['crew'] as $crew) {
                            if ($crew['job'] === 'Director') {
                                $analysis['directors'][$crew['name']] = ($analysis['directors'][$crew['name']] ?? 0) + 1;
                            }
                        }
                    }
                    
                    // Collect years
                    if (isset($details['release_date'])) {
                        $year = intval(substr($details['release_date'], 0, 4));
                        $analysis['years'][$year] = ($analysis['years'][$year] ?? 0) + 1;
                    }
                }
            }
        }
        
        return $analysis;
    }
}

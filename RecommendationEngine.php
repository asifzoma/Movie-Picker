<?php

require_once __DIR__ . '/TMDBCache.php';

/**
 * Generates movie recommendations for a user's input list.
 *
 * The primary signal is TMDB's own /movie/{id}/recommendations and
 * /movie/{id}/similar endpoints, which already run collaborative filtering
 * server-side. Candidates from those endpoints are then re-ranked with a
 * genre-overlap score built from the user's profile (computed from cached
 * movie details, no extra API calls per candidate).
 *
 * Everything that hits TMDB goes through TMDBCache, so repeat requests for
 * the same movies don't re-hit the API (and don't risk rate limits).
 */
class RecommendationEngine {
    private $sessionManager;
    private $context;
    private $baseUrl;
    private $cache;

    // Weight given to TMDB's own "people who liked this also liked" signal
    // vs. TMDB's content-based "similar" signal.
    private const RECOMMENDATIONS_WEIGHT = 3.0;
    private const SIMILAR_WEIGHT = 1.5;

    // How much the local genre-overlap score contributes on top of the
    // TMDB signal above.
    private const GENRE_OVERLAP_WEIGHT = 0.6;

    // Lower-ranked results within a single recommendations/similar page
    // count for less than the top ones.
    private const RANK_DECAY = 0.15;

    private const CACHE_TTL_DETAILS = 604800;      // 7 days - stable metadata
    private const CACHE_TTL_RELATED_LISTS = 43200; // 12 hours - TMDB re-ranks periodically
    private const CACHE_TTL_DISCOVER = 3600;       // 1 hour - used for "load more"

    public function __construct($sessionManager) {
        $this->sessionManager = $sessionManager;
        $this->baseUrl = TMDB_BASE_URL;
        $this->cache = new TMDBCache(__DIR__ . '/data/cache');

        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'Authorization: Bearer ' . TMDB_API_READ_ACCESS_TOKEN,
                    'Accept: application/json'
                ],
                'timeout' => 15
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

        // Build comprehensive user profile (genres etc.), used only for
        // re-ranking - not for looking up movies.
        $userProfile = $this->buildUserProfile($userMovies);

        // Get user preferences from session
        $sessionPrefs = $this->sessionManager->getUserPreferences();

        // Merge profiles
        $mergedProfile = $this->mergeProfiles($userProfile, $sessionPrefs);

        // Pull candidates from TMDB's recommendations/similar endpoints for
        // every seed movie, blended with the genre-overlap score.
        $candidates = $this->collectCandidates($userMovies, $mergedProfile);

        // Remove duplicates and filter out already seen movies and franchises
        $recommendations = $this->filterRecommendations(array_values($candidates));

        // Sort by relevance score
        $recommendations = $this->sortByRelevance($recommendations, $mergedProfile);

        return array_slice($recommendations, 0, $count);
    }

    /**
     * Build a pool of candidate movies from TMDB's recommendations/similar
     * endpoints for each seed movie, scored by TMDB rank plus genre overlap.
     */
    private function collectCandidates($userMovies, $profile) {
        $candidates = [];
        $seedIds = array_unique(array_filter(array_column($userMovies, 'id')));

        foreach ($seedIds as $seedId) {
            $recommended = $this->getRelatedMovies($seedId, 'recommendations');
            $this->accumulateCandidates($candidates, $recommended, self::RECOMMENDATIONS_WEIGHT, 'tmdb_recommendation', $profile);

            $similar = $this->getRelatedMovies($seedId, 'similar');
            $this->accumulateCandidates($candidates, $similar, self::SIMILAR_WEIGHT, 'tmdb_similar', $profile);
        }

        return $candidates;
    }

    private function accumulateCandidates(&$candidates, $movies, $baseWeight, $matchType, $profile) {
        foreach ($movies as $index => $movie) {
            if (!isset($movie['id'])) continue;

            $id = $movie['id'];
            $rankWeight = $baseWeight / (1 + $index * self::RANK_DECAY);
            $genreScore = $this->calculateGenreScore($movie, $profile);
            $contribution = $rankWeight + ($genreScore * self::GENRE_OVERLAP_WEIGHT);

            if (isset($candidates[$id])) {
                // Seen from another seed movie or the other endpoint -
                // agreement across seeds is itself a useful signal.
                $candidates[$id]['relevance_score'] += $contribution;
            } else {
                $movie['relevance_score'] = $contribution;
                $movie['match_type'] = $matchType;
                $candidates[$id] = $movie;
            }
        }
    }

    /**
     * Cached wrapper around /movie/{id}/recommendations and /movie/{id}/similar.
     */
    private function getRelatedMovies($movieId, $type) {
        $result = $this->cache->remember(
            "movie_{$movieId}_{$type}",
            self::CACHE_TTL_RELATED_LISTS,
            function () use ($movieId, $type) {
                $data = $this->fetch($this->baseUrl . "/movie/{$movieId}/{$type}");
                return $data === null ? null : ($data['results'] ?? []);
            }
        );

        return $result ?? [];
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

    /**
     * Cached wrapper around GET /movie/{id} (with credits+keywords appended).
     */
    private function getMovieDetails($movieId) {
        return $this->cache->remember(
            "movie_{$movieId}_details",
            self::CACHE_TTL_DETAILS,
            function () use ($movieId) {
                $details = $this->fetch($this->baseUrl . '/movie/' . $movieId . '?append_to_response=credits,keywords');
                if ($details === null) return null;

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
        );
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

    private function fetch($url) {
        $response = @file_get_contents($url, false, $this->context);
        if ($response === false) return null;

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) return null;

        return $data;
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
            $page = rand(1, 5); // Random page for variety
            $genreKey = implode(',', $topGenres);

            $data = $this->cache->remember(
                "discover_genres_{$genreKey}_page_{$page}",
                self::CACHE_TTL_DISCOVER,
                function () use ($topGenres, $page) {
                    $discoverUrl = $this->baseUrl . '/discover/movie?' . http_build_query([
                        'with_genres' => implode('|', $topGenres),
                        'vote_average.gte' => 6.5, // Relaxed rating requirement
                        'vote_count.gte' => 500,   // Relaxed vote count
                        'sort_by' => 'popularity.desc', // Use popularity instead of rating
                        'page' => $page
                    ]);

                    return $this->fetch($discoverUrl);
                }
            );

            foreach ($data['results'] ?? [] as $movie) {
                $movie['relevance_score'] = $this->calculateGenreScore($movie, $userPrefs);
                $movie['match_type'] = 'additional_recommendation';
                $recommendations[] = $movie;
                if (count($recommendations) >= $additionalCount) break;
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

<?php

class SessionManager {
    private $sessionKey = 'momo_movies_session';
    private $dataFile = 'data/session_data.json';
    
    public function __construct() {
        if (!session_id()) {
            session_start();
        }
        $this->initializeSession();
    }
    
    private function initializeSession() {
        if (!isset($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = [
                'session_id' => uniqid('momo_', true),
                'created_at' => time(),
                'liked_movies' => [],
                'disliked_movies' => [],
                'recommendation_history' => [],
                'user_preferences' => [
                    'genres' => [],
                    'directors' => [],
                    'actors' => [],
                    'vote_average_range' => [7.0, 10.0],
                    'year_range' => [1950, date('Y')]
                ],
                'current_recommendations' => [],
                'recommendation_queue' => [],
                'user_input_movies' => []
            ];
        }
    }
    
    public function getSessionData() {
        return $_SESSION[$this->sessionKey];
    }
    
    public function addLikedMovie($movie) {
        $movieData = [
            'id' => $movie['id'],
            'title' => $movie['title'],
            'poster_path' => $movie['poster_path'] ?? null,
            'release_date' => $movie['release_date'] ?? null,
            'vote_average' => $movie['vote_average'] ?? null,
            'genres' => $movie['genres'] ?? [],
            'director' => $movie['director'] ?? null,
            'liked_at' => time()
        ];
        
        $_SESSION[$this->sessionKey]['liked_movies'][] = $movieData;
        $this->updateUserPreferences($movie, 'like');
        $this->saveSessionToFile();
    }
    
    public function addDislikedMovie($movie) {
        $movieData = [
            'id' => $movie['id'],
            'title' => $movie['title'],
            'disliked_at' => time()
        ];
        
        $_SESSION[$this->sessionKey]['disliked_movies'][] = $movieData;
        $this->updateUserPreferences($movie, 'dislike');
        $this->saveSessionToFile();
    }
    
    public function getLikedMovies() {
        return $_SESSION[$this->sessionKey]['liked_movies'];
    }
    
    public function getDislikedMovies() {
        return $_SESSION[$this->sessionKey]['disliked_movies'];
    }
    
    public function clearLikedMovies() {
        $_SESSION[$this->sessionKey]['liked_movies'] = [];
        $this->saveSessionToFile();
    }
    
    public function removeLikedMovie($movieId) {
        $likedMovies = &$_SESSION[$this->sessionKey]['liked_movies'];
        $likedMovies = array_filter($likedMovies, function($movie) use ($movieId) {
            return $movie['id'] != $movieId;
        });
        $this->saveSessionToFile();
    }
    
    public function clearDislikedMovies() {
        $_SESSION[$this->sessionKey]['disliked_movies'] = [];
        $this->saveSessionToFile();
    }
    
    public function addToRecommendationHistory($movie) {
        $_SESSION[$this->sessionKey]['recommendation_history'][] = [
            'id' => $movie['id'],
            'title' => $movie['title'],
            'shown_at' => time()
        ];
    }
    
    public function isMovieInHistory($movieId) {
        $history = $_SESSION[$this->sessionKey]['recommendation_history'];
        return in_array($movieId, array_column($history, 'id'));
    }
    
    public function getRecommendationHistory() {
        return $_SESSION[$this->sessionKey]['recommendation_history'];
    }
    
    public function updateUserPreferences($movie, $action) {
        $prefs = &$_SESSION[$this->sessionKey]['user_preferences'];
        
        // Update genres
        if (isset($movie['genres']) && is_array($movie['genres'])) {
            foreach ($movie['genres'] as $genre) {
                $genreId = is_array($genre) ? $genre['id'] : $genre;
                if (!isset($prefs['genres'][$genreId])) {
                    $prefs['genres'][$genreId] = 0;
                }
                $prefs['genres'][$genreId] += ($action === 'like' ? 1 : -0.5);
            }
        }
        
        // Update directors
        if (isset($movie['director'])) {
            $director = $movie['director'];
            if (!isset($prefs['directors'][$director])) {
                $prefs['directors'][$director] = 0;
            }
            $prefs['directors'][$director] += ($action === 'like' ? 1 : -0.5);
        }
        
        // Update vote average range
        if (isset($movie['vote_average'])) {
            $currentAvg = $movie['vote_average'];
            if ($action === 'like') {
                $prefs['vote_average_range'][0] = min($prefs['vote_average_range'][0], $currentAvg);
                $prefs['vote_average_range'][1] = max($prefs['vote_average_range'][1], $currentAvg);
            }
        }
    }
    
    public function getUserPreferences() {
        return $_SESSION[$this->sessionKey]['user_preferences'];
    }
    
    public function setCurrentRecommendations($recommendations) {
        $_SESSION[$this->sessionKey]['current_recommendations'] = $recommendations;
    }
    
    public function getCurrentRecommendations() {
        return $_SESSION[$this->sessionKey]['current_recommendations'];
    }
    
    public function addToRecommendationQueue($movies) {
        $_SESSION[$this->sessionKey]['recommendation_queue'] = array_merge(
            $_SESSION[$this->sessionKey]['recommendation_queue'],
            $movies
        );
    }
    
    public function getRecommendationQueue() {
        return $_SESSION[$this->sessionKey]['recommendation_queue'];
    }
    
    public function removeFromQueue($movieId) {
        $queue = &$_SESSION[$this->sessionKey]['recommendation_queue'];
        $queue = array_filter($queue, function($movie) use ($movieId) {
            return $movie['id'] !== $movieId;
        });
    }
    
    private function saveSessionToFile() {
        $sessionData = [
            'session_id' => $_SESSION[$this->sessionKey]['session_id'],
            'created_at' => $_SESSION[$this->sessionKey]['created_at'],
            'liked_movies' => $_SESSION[$this->sessionKey]['liked_movies'],
            'user_preferences' => $_SESSION[$this->sessionKey]['user_preferences']
        ];
        
        file_put_contents($this->dataFile, json_encode($sessionData, JSON_PRETTY_PRINT));
    }
    
    public function setUserInputMovies($movies) {
        $_SESSION[$this->sessionKey]['user_input_movies'] = $movies;
        $this->saveSessionToFile();
    }
    
    public function getUserInputMovies() {
        return $_SESSION[$this->sessionKey]['user_input_movies'];
    }
    
    public function getSessionStats() {
        $data = $this->getSessionData();
        return [
            'total_liked' => count($data['liked_movies']),
            'total_disliked' => count($data['disliked_movies']),
            'total_recommendations' => count($data['recommendation_history']),
            'queue_size' => count($data['recommendation_queue']),
            'session_duration' => time() - $data['created_at']
        ];
    }
}

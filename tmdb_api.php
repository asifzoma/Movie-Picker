<?php
require_once 'config.php';

class TMDBApi {
    private static function makeRequest($endpoint, $params = []) {
        try {
            $params['api_key'] = TMDB_API_KEY;
            $url = TMDB_BASE_URL . $endpoint . '?' . http_build_query($params);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . TMDB_API_READ_ACCESS_TOKEN,
                'Accept: application/json'
            ]);
            
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                throw new Exception('Curl error: ' . curl_error($ch));
            }
            
            curl_close($ch);
            return json_decode($response, true);
        } catch (Exception $e) {
            throw new Exception('API request failed: ' . $e->getMessage());
        }
    }
    
    public static function searchMovies($query) {
        return self::makeRequest('/search/movie', ['query' => $query]);
    }
    
    public static function getMovieDetails($movieId) {
        return self::makeRequest("/movie/$movieId");
    }
    
    public static function getMovieCredits($movieId) {
        return self::makeRequest("/movie/$movieId/credits");
    }
    
    public static function getPersonCredits($personId) {
        return self::makeRequest("/person/$personId/combined_credits");
    }
    
    public static function getSimilarMovies($movieId) {
        return self::makeRequest("/movie/$movieId/similar");
    }
    
    public static function getPopularMovies() {
        return self::makeRequest("/movie/popular");
    }
} 
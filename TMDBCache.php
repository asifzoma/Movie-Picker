<?php

/**
 * Simple file-based cache for TMDB API responses, keyed by a caller-supplied
 * string and invalidated by TTL. This is what lets RecommendationEngine stop
 * hitting TMDB live on every request for data that barely changes (movie
 * details, recommendations, similar-movie lists).
 */
class TMDBCache {
    private $cacheDir;

    public function __construct($cacheDir) {
        $this->cacheDir = rtrim($cacheDir, '/');
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }
    }

    public function remember($key, $ttlSeconds, callable $fetch) {
        $path = $this->pathFor($key);

        $cached = $this->read($path);
        if ($cached !== null) {
            return $cached;
        }

        $value = $fetch();
        if ($value !== null) {
            $this->write($path, $value, $ttlSeconds);
        }

        return $value;
    }

    private function read($path) {
        if (!is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        $entry = json_decode($raw, true);
        if (!is_array($entry) || !isset($entry['expires_at'], $entry['data'])) {
            return null;
        }

        if ($entry['expires_at'] < time()) {
            return null;
        }

        return $entry['data'];
    }

    private function write($path, $value, $ttlSeconds) {
        $entry = [
            'expires_at' => time() + $ttlSeconds,
            'data' => $value
        ];
        @file_put_contents($path, json_encode($entry), LOCK_EX);
    }

    private function pathFor($key) {
        return $this->cacheDir . '/' . sha1($key) . '.json';
    }
}

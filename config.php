<?php
// TMDB API Configuration
define('TMDB_API_KEY', 'c371b0a5fb75f2c85c3f40c7b9069f72');
define('TMDB_BASE_URL', 'https://api.themoviedb.org/3');
define('TMDB_IMAGE_BASE_URL', 'https://image.tmdb.org/t/p/w500');

// Error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session configuration
session_start(); 
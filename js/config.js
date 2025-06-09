// API Configuration
const IMAGE_BASE_URL = document.querySelector('meta[name="tmdb-image-base-url"]').content;

// DOM Elements
const elements = {
    form: document.getElementById('movie-form'),
    formSection: document.getElementById('form-section'),
    resultSection: document.getElementById('result-section'),
    loadingSection: document.getElementById('loading-section'),
    errorSection: document.getElementById('error-section'),
    tryAgainBtn: document.getElementById('try-again'),
    errorRetryBtn: document.getElementById('error-retry'),
    
    // Search inputs and dropdowns
    childhoodInput: document.getElementById('childhood'),
    childhoodDropdown: document.getElementById('childhood-dropdown'),
    recommendInput: document.getElementById('recommend'),
    recommendDropdown: document.getElementById('recommend-dropdown'),
    watchedInput: document.getElementById('watched'),
    watchedDropdown: document.getElementById('watched-dropdown'),
    
    // Recommendation display elements
    moviePoster: document.getElementById('movie-poster'),
    movieTitle: document.getElementById('movie-title'),
    movieYear: document.getElementById('movie-year'),
    ratingValue: document.getElementById('rating-value'),
    voteCount: document.getElementById('vote-count'),
    explanation: document.getElementById('explanation'),
    journeyElement: document.getElementById('journey')
};

export { IMAGE_BASE_URL, elements }; 
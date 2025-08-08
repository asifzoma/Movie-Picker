// API Configuration
let IMAGE_BASE_URL = 'https://image.tmdb.org/t/p/w500'; // Default fallback URL

// Function to initialize configuration
function initConfig() {
    const metaTag = document.querySelector('meta[name="tmdb-image-base-url"]');
    if (metaTag) {
        IMAGE_BASE_URL = metaTag.content;
    }
    console.log('Initialized IMAGE_BASE_URL:', IMAGE_BASE_URL);
}

// DOM Elements
const elements = {
    form: document.getElementById('movie-form'),
    formSection: document.getElementById('form-section'),
    resultSection: document.getElementById('result-section'),
    loadingSection: document.getElementById('loading-section'),
    errorSection: document.getElementById('error-section'),
    tryAgainBtn: document.getElementById('try-again'),
    errorRetryBtn: document.querySelector('#error-section button'),
    
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

// Initialize configuration when DOM is loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initConfig);
} else {
    initConfig();
}

export { IMAGE_BASE_URL, elements }; 
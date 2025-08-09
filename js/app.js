import { elements, IMAGE_BASE_URL } from './config.js';
import { selectedMovies, setupSearch } from './movieSearch.js';
import { displayRecommendation, showError, generateMovieCardContent } from './recommendation.js';
import { MovieSwiper, showNextMovie } from './swipe.js';

// Initialize search functionality
console.log('🚀 Initializing search functionality...');
console.log('Elements:', elements);
setupSearch(elements.childhoodInput, 'childhood');
setupSearch(elements.recommendInput, 'recommend');
setupSearch(elements.watchedInput, 'watched');
console.log('✅ Search functionality initialized');

// Form submission handler
elements.form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Validate all movies are selected
    if (!selectedMovies.childhood || !selectedMovies.recommend || !selectedMovies.watched) {
        showError('Please select movies for all three fields');
        return;
    }
    
    // Show loading state
    elements.formSection.classList.add('hidden');
    elements.resultSection.classList.add('hidden');
    elements.errorSection.classList.add('hidden');
    elements.loadingSection.classList.remove('hidden');
    
    try {
        const response = await fetch('api.php?action=recommend', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `movies=${encodeURIComponent(JSON.stringify(selectedMovies))}`
        });
        
        const data = await response.json();
        
        if (data.success && data.recommendation) {
            // Pass the original movies array as the third parameter
            const userMovies = [
                selectedMovies.childhood,
                selectedMovies.recommend,
                selectedMovies.watched
            ];
            displayRecommendation(data.recommendation, data.alternatives || [], userMovies);
            
            // Initialize the swiper after displaying recommendations
            new MovieSwiper();
        } else {
            throw new Error(data.error || 'Failed to generate recommendation');
        }
    } catch (error) {
        console.error('Error generating recommendation:', error);
        showError('Failed to generate a recommendation. Please try again.');
    }
});

// Try again button handlers
elements.tryAgainBtn.addEventListener('click', () => {
    elements.resultSection.classList.add('hidden');
    elements.formSection.classList.remove('hidden');
    // Clear form
    elements.form.reset();
    Object.keys(selectedMovies).forEach(key => selectedMovies[key] = null);
});

elements.errorRetryBtn.addEventListener('click', () => {
    elements.errorSection.classList.add('hidden');
    elements.formSection.classList.remove('hidden');
});

// Event listeners for like/dislike buttons are now handled by swipe.js MovieSwiper class

// Load liked movies on page load
document.addEventListener('DOMContentLoaded', () => {
    loadLikedMovies();
});

// Touch swipe events are now handled by swipe.js MovieSwiper class

// handleLike and handleDislike functions are now in swipe.js

async function loadLikedMovies() {
    console.log('🔄 Loading liked movies...');
    try {
        const response = await fetch('api.php?action=get_liked_movies');
        console.log('📡 Response status:', response.status);
        const data = await response.json();
        console.log('📦 Response data:', data);
        
        if (data.success) {
            console.log('✅ Successfully loaded liked movies');
            displayLikedMovies(data.movies);
        } else {
            console.error('❌ Failed to load liked movies:', data.error);
        }
    } catch (error) {
        console.error('💥 Error loading liked movies:', error);
    }
}

function displayLikedMovies(movies) {
    console.log('🎬 Displaying liked movies:', movies);
    const container = document.getElementById('saved-movies-container');
    console.log('📦 Container found:', container);
    container.innerHTML = '';
    
    if (movies.length === 0) {
        console.log('📭 No movies to display, showing empty message');
        container.innerHTML = '<p class="text-gray-500 text-center py-4">No movies saved yet</p>';
        return;
    }
    
    movies.forEach(movie => {
        const movieCard = document.createElement('div');
        movieCard.className = 'saved-movie-card';
        
        const posterUrl = movie.poster_path ? 
            IMAGE_BASE_URL + movie.poster_path : 
            'https://via.placeholder.com/150x200/1a1a1a/ffffff?text=No+Poster';
        
        movieCard.innerHTML = `
            <button class="saved-movie-delete" onclick="removeLikedMovie(${movie.id}, this)" title="Remove from liked movies">
                <i class="fas fa-times"></i>
            </button>
            <img src="${posterUrl}" alt="${movie.title}" class="saved-movie-poster">
            <h4 class="saved-movie-title">${movie.title}</h4>
            <p class="saved-movie-year">${movie.release_date ? movie.release_date.substring(0, 4) : 'N/A'}</p>
        `;
        
        container.appendChild(movieCard);
    });
    
    // Initialize slider controls
    initializeSliderControls();
}

async function removeLikedMovie(movieId, buttonElement) {
    try {
        const response = await fetch('api.php?action=remove_liked_movie', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `movie_id=${movieId}`
        });
        const data = await response.json();
        
        if (data.success) {
            // Remove the card from the DOM
            const movieCard = buttonElement.closest('.saved-movie-card');
            if (movieCard) {
                movieCard.remove();
                
                // If no movies left, show empty message
                const container = document.getElementById('saved-movies-container');
                if (container.children.length === 0) {
                    container.innerHTML = '<p class="text-gray-500 text-center py-4">No movies saved yet</p>';
                }
            }
        }
    } catch (error) {
        console.error('Error removing liked movie:', error);
    }
}

function initializeSliderControls() {
    const slider = document.getElementById('saved-movies-container');
    const scrollLeftBtn = document.getElementById('scroll-left');
    const scrollRightBtn = document.getElementById('scroll-right');
    
    if (!slider || !scrollLeftBtn || !scrollRightBtn) return;
    
    // Show/hide scroll buttons based on scroll position
    function updateScrollButtons() {
        scrollLeftBtn.style.display = slider.scrollLeft > 0 ? 'flex' : 'none';
        scrollRightBtn.style.display = 
            slider.scrollLeft < (slider.scrollWidth - slider.clientWidth) ? 'flex' : 'none';
    }
    
    // Scroll left
    scrollLeftBtn.addEventListener('click', () => {
        slider.scrollBy({ left: -200, behavior: 'smooth' });
    });
    
    // Scroll right
    scrollRightBtn.addEventListener('click', () => {
        slider.scrollBy({ left: 200, behavior: 'smooth' });
    });
    
    // Update buttons on scroll
    slider.addEventListener('scroll', updateScrollButtons);
    
    // Initial button state
    updateScrollButtons();
}

async function updateSessionStats(stats) {
    // Update stats display if you have one
    console.log('Session stats updated:', stats);
}

// loadMoreMovies function is handled by recommendation.js
// Import and use that function instead

async function clearAllLikedMovies() {
    if (confirm('Are you sure you want to clear all liked movies?')) {
        try {
            const response = await fetch('api.php?action=clear_liked_movies');
            const data = await response.json();
            
            if (data.success) {
                loadLikedMovies(); // Reload the list
            }
        } catch (error) {
            console.error('Error clearing liked movies:', error);
        }
    }
}

// getCurrentMovie function is now in swipe.js

// Helper function to save movie to UI (legacy function for compatibility)
function saveMovie(movie) {
    // This is now handled by the server-side session management
    console.log('Movie saved:', movie.title);
}

// Make functions available globally for onclick handlers
window.removeLikedMovie = removeLikedMovie;
window.clearAllLikedMovies = clearAllLikedMovies;
window.initializeSliderControls = initializeSliderControls; 
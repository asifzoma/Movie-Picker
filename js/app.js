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
    console.log('🔄 Try Again clicked - refreshing page for fresh start');
    
    // Clear session on server first (optional - for cleanup)
    try {
        fetch('api.php?action=clear_session', { method: 'POST' })
            .then(() => console.log('✅ Server session cleared'))
            .catch(err => console.log('⚠️ Could not clear server session:', err));
    } catch (error) {
        console.log('⚠️ Server session clear failed:', error);
    }
    
    // Refresh the entire page for a completely fresh start
    window.location.reload();
});

elements.errorRetryBtn.addEventListener('click', () => {
    console.log('🔄 Error retry clicked - refreshing page for fresh start');
    
    // Refresh the entire page for a completely fresh start
    window.location.reload();
});

// Event listeners for like/dislike buttons are now handled by swipe.js MovieSwiper class

// Load liked movies on page load
document.addEventListener('DOMContentLoaded', () => {
    loadLikedMovies();
    loadHomeLikedMovies(); // Load liked movies for home page
    
    // Update selected movie displays on page load
    updateAllSelectedMovieDisplays();
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
            
            // Also update home section if it exists
            const homeSection = document.getElementById('home-liked-movies');
            if (homeSection) {
                if (data.movies.length > 0) {
                    displayHomeLikedMovies(data.movies);
                    homeSection.classList.remove('hidden');
                    console.log('✅ Home section updated and visible');
                } else {
                    homeSection.classList.add('hidden');
                    console.log('📭 Home section hidden - no movies');
                }
            }
        } else {
            console.error('❌ Failed to load liked movies:', data.error);
        }
    } catch (error) {
        console.error('💥 Error loading liked movies:', error);
    }
}

function displayLikedMovies(movies) {
    console.log('🎬 Displaying liked movies:', movies);
    displayLikedMoviesInContainer(movies, 'home-saved-movies-container', initializeHomeSliderControls);
}

function displayLikedMoviesInContainer(movies, containerId, initCallback = null) {
    const container = document.getElementById(containerId);
    if (!container) {
        console.error(`Container ${containerId} not found`);
        return;
    }
    
    console.log(`📦 Displaying ${movies.length} movies in ${containerId}`);
    container.innerHTML = '';
    
    if (movies.length === 0) {
        console.log('📭 No movies to display');
        container.innerHTML = '';
        return;
    }
    
    movies.forEach(movie => {
        const movieCard = document.createElement('div');
        movieCard.className = 'saved-movie-card';
        movieCard.style.cursor = 'pointer';
        
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
        
        // Add cursor pointer for visual feedback
        movieCard.style.cursor = 'pointer';
        
        container.appendChild(movieCard);
    });
    
    // Initialize callback if provided
    if (initCallback) {
        initCallback();
    }
}

async function removeLikedMovie(movieId, buttonElement) {
    console.log('🗑️ Removing movie with ID:', movieId);
    
    try {
        // First remove the visual card immediately for better UX
        if (buttonElement) {
            const movieCard = buttonElement.closest('.saved-movie-card');
            if (movieCard) {
                movieCard.style.opacity = '0.5';
                movieCard.style.pointerEvents = 'none';
                console.log('👻 Made card semi-transparent while processing...');
            }
        }
        
        const response = await fetch('api.php?action=remove_liked_movie', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `movie_id=${movieId}`
        });
        const data = await response.json();
        
        if (data.success) {
            console.log('✅ Movie removed successfully from server');
            
            // Remove the specific card from DOM
            if (buttonElement) {
                const movieCard = buttonElement.closest('.saved-movie-card');
                if (movieCard) {
                    movieCard.remove();
                    console.log('🗑️ Movie card removed from DOM');
                }
            }
            
            // Only refresh the data to get the correct count, but don't completely re-render if cards are still there
            const remainingCards = document.querySelectorAll('.saved-movie-card');
            console.log(`📊 Remaining cards after removal: ${remainingCards.length}`);
            
            // Check if we need to hide the sections because no movies are left
            const refreshResponse = await fetch('api.php?action=get_liked_movies');
            const refreshData = await refreshResponse.json();
            
            if (refreshData.success && refreshData.movies.length === 0) {
                // Hide both sections if no movies left
                const homeSection = document.getElementById('home-liked-movies');
                if (homeSection) {
                    homeSection.classList.add('hidden');
                }
                const resultsSection = document.querySelector('.saved-movies');
                if (resultsSection) {
                    resultsSection.classList.add('hidden');
                }
                console.log('📭 All sections hidden - no movies remaining');
            }
        } else {
            console.error('❌ Failed to remove movie:', data.error);
            // Restore the card if removal failed
            if (buttonElement) {
                const movieCard = buttonElement.closest('.saved-movie-card');
                if (movieCard) {
                    movieCard.style.opacity = '1';
                    movieCard.style.pointerEvents = 'auto';
                }
            }
        }
    } catch (error) {
        console.error('💥 Error removing liked movie:', error);
        // Restore the card if there was an error
        if (buttonElement) {
            const movieCard = buttonElement.closest('.saved-movie-card');
            if (movieCard) {
                movieCard.style.opacity = '1';
                movieCard.style.pointerEvents = 'auto';
            }
        }
    }
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
                console.log('✅ All movies cleared, refreshing section');
                
                // Refresh the liked movies section
                await loadLikedMovies();
            }
        } catch (error) {
            console.error('Error clearing liked movies:', error);
        }
    }
}

// getCurrentMovie function is now in swipe.js

// ===== SHARING FUNCTIONALITY =====

// Show share modal
function shareLikedMovies() {
    const modal = document.getElementById('share-modal');
    modal.classList.remove('hidden');
    
    // Check if native sharing is supported
    const nativeShareBtn = document.getElementById('native-share-btn');
    if (navigator.share) {
        nativeShareBtn.classList.remove('hidden');
    } else {
        nativeShareBtn.classList.add('hidden');
    }
    
    // Add click outside to close
    setTimeout(() => {
        modal.addEventListener('click', handleModalClick);
    }, 100);
    
    // Add ESC key to close
    document.addEventListener('keydown', handleEscKey);
}

// Handle ESC key
function handleEscKey(event) {
    if (event.key === 'Escape') {
        closeShareModal();
    }
}

// Handle modal click outside
function handleModalClick(event) {
    const modal = document.getElementById('share-modal');
    const modalContent = modal.querySelector('.bg-gray-900');
    
    if (event.target === modal) {
        closeShareModal();
    }
}

// Close share modal
function closeShareModal() {
    const modal = document.getElementById('share-modal');
    modal.classList.add('hidden');
    
    // Remove click outside listener
    modal.removeEventListener('click', handleModalClick);
    
    // Remove ESC key listener
    document.removeEventListener('keydown', handleEscKey);
}

// Get liked movies data for sharing
function getLikedMoviesData() {
    const likedMovies = document.querySelectorAll('.saved-movie-card');
    const movies = [];
    
    likedMovies.forEach(card => {
        const title = card.querySelector('h4')?.textContent || 'Unknown Movie';
        const year = card.querySelector('.year')?.textContent || '';
        movies.push(`${title}${year ? ` (${year})` : ''}`);
    });
    
    return movies;
}

// Copy to clipboard
async function copyToClipboard() {
    try {
        const movies = getLikedMoviesData();
        if (movies.length === 0) {
            showNotification('No movies to share!', 'error');
            return;
        }
        
        const text = `🎬 My Movie Collection:\n\n${movies.join('\n')}\n\nShared from Momo Movies`;
        
        if (navigator.clipboard) {
            await navigator.clipboard.writeText(text);
            showNotification('Copied to clipboard!', 'success');
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showNotification('Copied to clipboard!', 'success');
        }
    } catch (error) {
        console.error('Error copying to clipboard:', error);
        showNotification('Failed to copy to clipboard', 'error');
    }
}

// Share via Email
function shareViaEmail() {
    const movies = getLikedMoviesData();
    if (movies.length === 0) {
        showNotification('No movies to share!', 'error');
        return;
    }
    
    const subject = 'My Movie Collection from Momo Movies';
    const body = `🎬 My Movie Collection:\n\n${movies.join('\n')}\n\nCheck out Momo Movies for personalized recommendations!`;
    
    const mailtoLink = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
    window.open(mailtoLink);
}

// Share on Twitter
function shareViaTwitter() {
    const movies = getLikedMoviesData();
    if (movies.length === 0) {
        showNotification('No movies to share!', 'error');
        return;
    }
    
    const text = `🎬 My Movie Collection:\n${movies.slice(0, 3).join(', ')}${movies.length > 3 ? ' and more!' : ''}\n\nCheck out Momo Movies!`;
    const url = window.location.href;
    
    const twitterUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`;
    window.open(twitterUrl, '_blank');
}

// Share on Facebook
function shareViaFacebook() {
    const url = window.location.href;
    const facebookUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
    window.open(facebookUrl, '_blank');
}

// Share on WhatsApp
function shareViaWhatsApp() {
    const movies = getLikedMoviesData();
    if (movies.length === 0) {
        showNotification('No movies to share!', 'error');
        return;
    }
    
    const text = `🎬 My Movie Collection:\n${movies.slice(0, 3).join(', ')}${movies.length > 3 ? ' and more!' : ''}\n\nCheck out Momo Movies!`;
    const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(text)}`;
    window.open(whatsappUrl, '_blank');
}

// Native sharing (mobile devices)
async function shareNative() {
    try {
        const movies = getLikedMoviesData();
        if (movies.length === 0) {
            showNotification('No movies to share!', 'error');
            return;
        }
        
        const shareData = {
            title: 'My Movie Collection',
            text: `🎬 My Movie Collection:\n${movies.slice(0, 3).join(', ')}${movies.length > 3 ? ' and more!' : ''}`,
            url: window.location.href
        };
        
        if (navigator.share) {
            await navigator.share(shareData);
        }
    } catch (error) {
        console.error('Error sharing:', error);
        showNotification('Failed to share', 'error');
    }
}

// Show notification
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg text-white font-medium shadow-lg transition-all duration-300 transform translate-x-full`;
    
    // Set color based on type
    switch (type) {
        case 'success':
            notification.classList.add('bg-green-600');
            break;
        case 'error':
            notification.classList.add('bg-red-600');
            break;
        default:
            notification.classList.add('bg-blue-600');
    }
    
    notification.textContent = message;
    
    // Add to DOM
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Helper function to save movie to UI (legacy function for compatibility)
function saveMovie(movie) {
    // This is now handled by the server-side session management
    console.log('Movie saved:', movie.title);
}

// ===== SELECTED MOVIE MANAGEMENT =====

/**
 * Display a selected movie in the form
 */
function displaySelectedMovie(type, movie) {
    const input = document.getElementById(type);
    const removeBtn = document.getElementById(`${type}-remove`);
    
    if (input && removeBtn) {
        // Update the input field with the movie title
        input.value = movie.title;
        
        // Show the remove button (X)
        removeBtn.classList.remove('hidden');
        
        console.log(`✅ Displayed selected movie for ${type}:`, movie.title);
    }
}

/**
 * Remove a selected movie from the form
 */
function removeSelectedMovie(type) {
    console.log(`🗑️ removeSelectedMovie called for: ${type}`);
    
    const input = document.getElementById(type);
    const removeBtn = document.getElementById(`${type}-remove`);
    
    console.log(`Input element:`, input);
    console.log(`Remove button:`, removeBtn);
    
    if (input && removeBtn) {
        // Hide the remove button (X)
        removeBtn.classList.add('hidden');
        console.log(`✅ Hidden remove button for ${type}`);
        
        // Clear the selectedMovies object
        if (selectedMovies[type]) {
            selectedMovies[type] = null;
            console.log(`🗑️ Cleared selectedMovies[${type}]`);
        }
        
        // Clear the input field
        if (input) {
            input.value = '';
            input.focus();
            console.log(`✅ Cleared input field for ${type}`);
        }
    } else {
        console.error(`❌ Missing elements for ${type}:`, { input, removeBtn });
    }
}

/**
 * Update all selected movie displays based on current state
 */
function updateAllSelectedMovieDisplays() {
    Object.keys(selectedMovies).forEach(type => {
        const removeBtn = document.getElementById(`${type}-remove`);
        if (selectedMovies[type] && removeBtn) {
            // Show the remove button (X) if a movie is selected
            removeBtn.classList.remove('hidden');
        } else if (removeBtn) {
            // Hide the remove button (X) if no movie is selected
            removeBtn.classList.add('hidden');
        }
    });
}

// Make functions available globally for onclick handlers
window.removeLikedMovie = removeLikedMovie;
window.clearAllLikedMovies = clearAllLikedMovies;
window.initializeHomeSliderControls = initializeHomeSliderControls;
window.shareLikedMovies = shareLikedMovies;
window.closeShareModal = closeShareModal;
window.copyToClipboard = copyToClipboard;
window.shareViaEmail = shareViaEmail;
window.shareViaTwitter = shareViaTwitter;
window.shareViaFacebook = shareViaFacebook;
window.shareViaWhatsApp = shareViaWhatsApp;
window.shareNative = shareNative;
window.handleEscKey = handleEscKey;
window.removeSelectedMovie = removeSelectedMovie;
window.displaySelectedMovie = displaySelectedMovie;
window.loadLikedMovies = loadLikedMovies; // Make available globally
window.openMovieDetailsModal = openMovieDetailsModal;
window.closeMovieDetailsModal = closeMovieDetailsModal;


// ===== HOME LIKED MOVIES FUNCTIONALITY =====

async function loadHomeLikedMovies() {
    console.log('🏠 Loading home page liked movies...');
    try {
        const response = await fetch('api.php?action=get_liked_movies');
        const data = await response.json();
        
        if (data.success && data.movies.length > 0) {
            console.log('✅ Successfully loaded home liked movies:', data.movies.length);
            displayHomeLikedMovies(data.movies);
            const homeSection = document.getElementById('home-liked-movies');
            if (homeSection) {
                homeSection.classList.remove('hidden');
                console.log('✅ Home liked movies section is now visible');
            }
        } else {
            console.log('📭 No liked movies to show on home page');
            const homeSection = document.getElementById('home-liked-movies');
            if (homeSection) {
                homeSection.classList.add('hidden');
            }
        }
    } catch (error) {
        console.error('💥 Error loading home liked movies:', error);
        const homeSection = document.getElementById('home-liked-movies');
        if (homeSection) {
            homeSection.classList.add('hidden');
        }
    }
}

function displayHomeLikedMovies(movies) {
    console.log('🎬 Displaying home liked movies:', movies);
    displayLikedMoviesInContainer(movies, 'home-saved-movies-container', initializeHomeSliderControls);
}

function initializeHomeSliderControls() {
    const slider = document.getElementById('home-saved-movies-container');
    const scrollLeftBtn = document.getElementById('home-scroll-left');
    const scrollRightBtn = document.getElementById('home-scroll-right');
    
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

 

// ===== REFRESH LIKED MOVIES DATA =====

/**
 * Refresh liked movies data without clearing the containers
 * This is used when movies are added/removed to keep both sections in sync
 */
async function refreshLikedMoviesData() {
    try {
        const response = await fetch('api.php?action=get_liked_movies');
        const data = await response.json();
        
        if (data.success) {
            // Update the home section if it exists and has movies
            const homeSection = document.getElementById('home-liked-movies');
            if (homeSection && data.movies.length > 0) {
                // Only update if the section is visible
                if (!homeSection.classList.contains('hidden')) {
                    displayHomeLikedMovies(data.movies);
                }
            } else if (homeSection && data.movies.length === 0) {
                // Hide the home section if no movies
                homeSection.classList.add('hidden');
            }
            
            // Update the recommendation section if it exists and has movies
            const recSection = document.getElementById('saved-movies-container');
            if (recSection && data.movies.length > 0) {
                // Only update if the section is visible
                if (recSection.closest('.saved-movies') && !recSection.closest('.saved-movies').classList.contains('hidden')) {
                    displayLikedMovies(data.movies);
                }
            }
        }
    } catch (error) {
        console.error('Error refreshing liked movies data:', error);
    }
} 
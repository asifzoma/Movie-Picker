// Swipe functionality for movie recommendations
export class MovieSwiper {
    constructor() {
        this.container = document.querySelector('.swipe-container');
        this.currentCard = document.querySelector('.swipe-card');
        this.movieStack = document.getElementById('movie-stack');
        this.savedMoviesContainer = document.getElementById('saved-movies-container');
        this.savedMovies = new Set();
        
        // Desktop action buttons (these exist in the HTML)
        this.desktopDislikeButton = document.getElementById('dislike-btn');
        this.desktopLikeButton = document.getElementById('like-btn');
        
        // Touch handling variables
        this.isDragging = false;
        this.startX = 0;
        this.currentX = 0;
        
        // Check if we're on mobile
        this.isMobile = window.matchMedia('(max-width: 768px)').matches;
        
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        // Desktop action buttons
        this.desktopDislikeButton.addEventListener('click', () => this.swipeLeft());
        this.desktopLikeButton.addEventListener('click', () => this.swipeRight());
        
        // Only setup touch/mouse events on mobile
        if (this.isMobile) {
            // Touch events
            this.currentCard.addEventListener('touchstart', (e) => this.handleTouchStart(e));
            this.currentCard.addEventListener('touchmove', (e) => this.handleTouchMove(e));
            this.currentCard.addEventListener('touchend', () => this.handleTouchEnd());
            
            // Mouse events for mobile
            this.currentCard.addEventListener('mousedown', (e) => this.handleMouseDown(e));
            document.addEventListener('mousemove', (e) => this.handleMouseMove(e));
            document.addEventListener('mouseup', () => this.handleMouseUp());
            
            // Mobile swipe functionality is handled by touch events
        }
        
        // Listen for window resize to update mobile state
        window.addEventListener('resize', () => {
            const wasMobile = this.isMobile;
            this.isMobile = window.matchMedia('(max-width: 768px)').matches;
            
            // If mobile state changed, reset any ongoing interactions
            if (wasMobile !== this.isMobile) {
                this.resetCard();
                this.isDragging = false;
                this.currentCard.classList.remove('swiping');
            }
        });
    }
    
    handleTouchStart(e) {
        if (!this.isMobile) return;
        this.isDragging = true;
        this.startX = e.touches[0].clientX;
        this.currentCard.classList.add('swiping');
    }
    
    handleTouchMove(e) {
        if (!this.isDragging || !this.isMobile) return;
        
        this.currentX = e.touches[0].clientX;
        const deltaX = this.currentX - this.startX;
        const rotation = deltaX * 0.1; // Rotate card while dragging
        
        this.currentCard.style.transform = `translate(${deltaX}px, 0) rotate(${rotation}deg)`;
    }
    
    handleTouchEnd() {
        if (!this.isDragging || !this.isMobile) return;
        
        const deltaX = this.currentX - this.startX;
        const threshold = window.innerWidth * 0.3; // 30% of screen width
        
        if (Math.abs(deltaX) > threshold) {
            // Swipe was strong enough
            if (deltaX > 0) {
                this.swipeRight();
            } else {
                this.swipeLeft();
            }
        } else {
            // Reset card position
            this.resetCard();
        }
        
        this.isDragging = false;
        this.currentCard.classList.remove('swiping');
    }
    
    handleMouseDown(e) {
        if (!this.isMobile) return;
        this.isDragging = true;
        this.startX = e.clientX;
        this.currentCard.classList.add('swiping');
    }
    
    handleMouseMove(e) {
        if (!this.isDragging || !this.isMobile) return;
        
        this.currentX = e.clientX;
        const deltaX = this.currentX - this.startX;
        const rotation = deltaX * 0.1;
        
        this.currentCard.style.transform = `translate(${deltaX}px, 0) rotate(${rotation}deg)`;
    }
    
    handleMouseUp() {
        if (!this.isDragging || !this.isMobile) return;
        
        const deltaX = this.currentX - this.startX;
        const threshold = window.innerWidth * 0.3;
        
        if (Math.abs(deltaX) > threshold) {
            if (deltaX > 0) {
                this.swipeRight();
            } else {
                this.swipeLeft();
            }
        } else {
            this.resetCard();
        }
        
        this.isDragging = false;
        this.currentCard.classList.remove('swiping');
    }
    
    swipeLeft() {
        // Handle dislike - need to call the proper dislike function
        this.handleDislike();
        this.currentCard.classList.add('swipe-left');
        setTimeout(() => this.showNextMovie(), 300);
    }
    
    swipeRight() {
        // Handle like - need to call the proper like function
        this.handleLike();
        this.currentCard.classList.add('swipe-right');
        setTimeout(() => this.showNextMovie(), 300);
    }
    
    // Get current movie data for API calls
    getCurrentMovie() {
        if (!this.currentCard) return null;
        
        const movieId = this.currentCard.dataset.movieId;
        const title = this.currentCard.querySelector('#movie-title')?.textContent;
        const posterPath = this.currentCard.querySelector('#movie-poster')?.src;
        const releaseDate = this.currentCard.querySelector('#movie-year')?.textContent;
        const voteAverage = parseFloat(this.currentCard.querySelector('#rating-value')?.textContent);
        
        return {
            id: parseInt(movieId),
            title: title,
            poster_path: posterPath ? posterPath.replace('https://image.tmdb.org/t/p/w500', '') : null,
            release_date: releaseDate ? `${releaseDate}-01-01` : null,
            vote_average: voteAverage || null
        };
    }
    
    // Handle like functionality
    async handleLike() {
        const currentMovie = this.getCurrentMovie();
        if (currentMovie) {
            try {
                // Send like to server
                const response = await fetch('api.php?action=like_movie', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `movie=${encodeURIComponent(JSON.stringify(currentMovie))}`
                });
                
                const data = await response.json();
                if (data.success) {
                    // Update UI - save movie locally for display
                    this.saveCurrentMovie();
                    console.log('Movie liked successfully:', currentMovie.title);
                }
            } catch (error) {
                console.error('Error liking movie:', error);
            }
        }
    }
    
    // Handle dislike functionality
    async handleDislike() {
        const currentMovie = this.getCurrentMovie();
        if (currentMovie) {
            try {
                // Send dislike to server
                const response = await fetch('api.php?action=dislike_movie', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `movie=${encodeURIComponent(JSON.stringify(currentMovie))}`
                });
                
                const data = await response.json();
                if (data.success) {
                    console.log('Movie disliked successfully:', currentMovie.title);
                }
            } catch (error) {
                console.error('Error disliking movie:', error);
            }
        }
    }
    
    resetCard() {
        this.currentCard.style.transform = '';
    }
    
    saveCurrentMovie() {
        const movieId = this.currentCard.dataset.movieId;
        if (this.savedMovies.has(movieId)) return;
        
        this.savedMovies.add(movieId);
        
        // Refresh both liked movie sections to keep them synchronized
        if (window.loadLikedMovies) {
            window.loadLikedMovies();
        } else {
            console.warn('loadLikedMovies function not available');
        }
    }
    
    showNextMovie() {
        // Get next movie from the stack
        const nextMovie = this.movieStack.firstElementChild;
        if (!nextMovie) {
            // No more movies, trigger more movies load
            if (window.loadMoreMovies) {
                window.loadMoreMovies();
            }
            return;
        }
        
        // Clone the next movie's content into the current card
        this.currentCard.innerHTML = nextMovie.innerHTML;
        this.currentCard.dataset.movieId = nextMovie.dataset.movieId;
        
        // Remove the used movie from the stack
        nextMovie.remove();
        
        // Reset the current card's state
        this.currentCard.classList.remove('swipe-left', 'swipe-right');
        this.currentCard.style.transform = '';
        
        // Reattach event listeners to the new buttons
        this.reattachButtonListeners();
    }
    
    reattachButtonListeners() {
        // Remove old listeners and reattach to new buttons
        this.desktopDislikeButton = document.getElementById('dislike-btn');
        this.desktopLikeButton = document.getElementById('like-btn');
        
        if (this.desktopDislikeButton) {
            this.desktopDislikeButton.addEventListener('click', () => this.swipeLeft());
        }
        
        if (this.desktopLikeButton) {
            this.desktopLikeButton.addEventListener('click', () => this.swipeRight());
        }
    }
}

// Global swiper instance
let globalSwiper = null;

// Global function to show next movie (for compatibility with app.js)
function showNextMovie() {
    if (globalSwiper) {
        globalSwiper.showNextMovie();
    }
}

// Initialize the swiper when the page loads
document.addEventListener('DOMContentLoaded', () => {
    globalSwiper = new MovieSwiper();
});

// Export the global function
export { showNextMovie }; 
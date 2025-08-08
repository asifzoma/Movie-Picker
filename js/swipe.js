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
        this.currentCard.classList.add('swipe-left');
        this.showNextMovie();
    }
    
    swipeRight() {
        this.currentCard.classList.add('swipe-right');
        this.saveCurrentMovie();
        this.showNextMovie();
    }
    
    resetCard() {
        this.currentCard.style.transform = '';
    }
    
    saveCurrentMovie() {
        const movieId = this.currentCard.dataset.movieId;
        if (this.savedMovies.has(movieId)) return;
        
        this.savedMovies.add(movieId);
        
        const movieCard = document.createElement('div');
        movieCard.className = 'saved-movie-card';
        movieCard.innerHTML = `
            <img src="${this.currentCard.querySelector('#movie-poster').src}" 
                 alt="${this.currentCard.querySelector('#movie-title').textContent}">
            <div class="movie-info">
                <h4>${this.currentCard.querySelector('#movie-title').textContent}</h4>
                <span class="year">${this.currentCard.querySelector('#movie-year').textContent}</span>
            </div>
        `;
        
        this.savedMoviesContainer.appendChild(movieCard);
    }
    
    showNextMovie() {
        // Get next movie from the stack
        const nextMovie = this.movieStack.firstElementChild;
        if (!nextMovie) {
            // No more movies, trigger more movies load
            document.getElementById('more-movies-btn')?.click();
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
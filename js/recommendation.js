import { IMAGE_BASE_URL, elements } from './config.js';

// Global variables to track current state
let currentPage = 1;
let excludeIds = [];
let originalMovies = null;
let recentlyShownMovies = [];
const MAX_RECENT_MOVIES = 30;

// Generate a fun explanation for the recommendation
function generateExplanation(movie) {
    const explanations = [
        `Your movie taste is as unique as your fingerprint. We analyzed the patterns and discovered "${movie.title}" as your perfect match.`,
        `Like a cinematic fingerprint, your unique taste led us to "${movie.title}" - a perfect reflection of your preferences.`,
        `Through analyzing your movie DNA, we discovered "${movie.title}" matches your cinematic fingerprint perfectly.`,
        `Your movie preferences are one of a kind, and "${movie.title}" aligns perfectly with your unique taste pattern.`,
        `Just as no two fingerprints are alike, your movie taste is unique. That's why we chose "${movie.title}" for you.`
    ];
    
    // Pick a random explanation
    elements.explanation.textContent = explanations[Math.floor(Math.random() * explanations.length)];
}

// Display the recommendation with alternatives
function displayRecommendation(movie, alternatives, userMovies = null) {
    // Add current movie to recently shown list
    if (!recentlyShownMovies.includes(movie.id)) {
        recentlyShownMovies.unshift(movie.id);
        if (recentlyShownMovies.length > MAX_RECENT_MOVIES) {
            recentlyShownMovies.pop();
        }
    }

    elements.loadingSection.classList.add('hidden');
    elements.resultSection.classList.remove('hidden');
    elements.resultSection.classList.add('fade-in');
    
    // Store original movies for more movies functionality
    if (userMovies) {
        console.log('Setting originalMovies:', userMovies);
        originalMovies = userMovies;
        currentPage = 1;
        excludeIds = [movie.id, ...alternatives.map(m => m.id)];
    }
    
    // Display main recommendation
    displayMainMovie(movie);
    
    // Add alternatives to the hidden stack
    const movieStack = document.getElementById('movie-stack');
    movieStack.innerHTML = ''; // Clear existing stack
    
    // Filter out recently shown movies from alternatives
    const filteredAlternatives = alternatives.filter(alt => !recentlyShownMovies.includes(alt.id));
    
    filteredAlternatives.forEach(altMovie => {
        const altElement = document.createElement('div');
        altElement.dataset.movieId = altMovie.id;
        altElement.innerHTML = generateMovieCardContent(altMovie);
        movieStack.appendChild(altElement);
    });
    
    // Generate fun explanation
    generateExplanation(movie);
}

// Helper function to generate movie card content
function generateMovieCardContent(movie) {
    const posterUrl = movie.poster_path ? 
        IMAGE_BASE_URL + movie.poster_path : 
        'https://via.placeholder.com/300x450/1a1a1a/ffffff?text=No+Poster';
    
    return `
        <div class="flex flex-col gap-3">
            <div id="poster-container" class="w-full">
                <img id="movie-poster" src="${posterUrl}" 
                     alt="${movie.title}" class="w-full rounded-lg shadow-lg">
                <p id="movie-rating" class="mt-3 flex items-center">
                    <i class="fas fa-star text-yellow-400 mr-2"></i>
                    <span id="rating-value" class="font-bold">${movie.vote_average ? movie.vote_average.toFixed(1) : 'N/A'}</span>/10
                    <span id="vote-count" class="text-gray-400 ml-2 text-sm">(${movie.vote_count ? movie.vote_count.toLocaleString() : 0} votes)</span>
                </p>
            </div>
            
            <div>
                <h3 id="movie-title" class="bebas text-xl mb-1">${movie.title}</h3>
                <p id="movie-year" class="text-sm text-gray-300 mb-2">${movie.release_date ? movie.release_date.split('-')[0] : 'Unknown year'}</p>
                
                <div class="mb-3">
                    <h4 class="text-base font-bold mb-1 text-pink-400">Synopsis</h4>
                    <p id="movie-overview" class="text-xs text-gray-300 leading-relaxed line-clamp-3">${movie.overview || 'No synopsis available.'}</p>
                </div>

                <div class="mb-3">
                    <p id="explanation" class="text-xs text-purple-400 italic leading-relaxed">
                        Your unique cinematic journey led us to this recommendation.
                    </p>
                </div>
            </div>
        </div>
    `;
}

// Display main movie
function displayMainMovie(movie) {
    const mainCard = document.getElementById('recommendation');
    mainCard.dataset.movieId = movie.id;
    mainCard.innerHTML = generateMovieCardContent(movie);
}

// Load more movies function
async function loadMoreMovies() {
    console.log('Loading more movies...');
    console.log('Current page:', currentPage);
    console.log('Original movies:', originalMovies);
    console.log('Exclude IDs:', excludeIds);
    console.log('Recently shown movies:', recentlyShownMovies);

    const loadingIndicator = document.getElementById('more-movies-loading');
    if (loadingIndicator) {
        loadingIndicator.classList.remove('hidden');
    }
    
    try {
        currentPage++;
        const requestData = {
            movies: originalMovies,
            page: currentPage,
            exclude_ids: [...new Set([...excludeIds, ...recentlyShownMovies])]
        };
        console.log('Sending request with data:', requestData);

        const response = await fetch('api.php?action=more_movies', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `data=${encodeURIComponent(JSON.stringify(requestData))}`
        });
        
        console.log('Response status:', response.status);
        const data = await response.json();
        console.log('Response data:', data);
        
        if (data.success && data.new_movies) {
            // Filter out any recently shown movies that might have slipped through
            const newMovies = data.new_movies.filter(movie => !recentlyShownMovies.includes(movie.id));
            
            // Add new movies to the stack
            const movieStack = document.getElementById('movie-stack');
            
            newMovies.forEach(movie => {
                excludeIds.push(movie.id);
                
                const altElement = document.createElement('div');
                altElement.dataset.movieId = movie.id;
                altElement.innerHTML = generateMovieCardContent(movie);
                movieStack.appendChild(altElement);
            });
            
            // Update loading state
            if (loadingIndicator) {
                loadingIndicator.classList.add('hidden');
            }
        } else {
            throw new Error(data.error || 'Failed to load more movies');
        }
    } catch (error) {
        console.error('Error loading more movies:', error);
        if (loadingIndicator) {
            loadingIndicator.classList.add('hidden');
        }
    }
}

// Show error message
function showError(message) {
    elements.loadingSection.classList.add('hidden');
    document.getElementById('error-message').textContent = message;
    elements.errorSection.classList.remove('hidden');
}

export { displayRecommendation, showError }; 
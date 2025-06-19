import { IMAGE_BASE_URL, elements } from './config.js';

// Global variables to track current state
let currentPage = 1;
let excludeIds = [];
let originalMovies = null;

// Generate a fun explanation for the recommendation
function generateExplanation(movie) {
    const explanations = [
        `Based on your unique cinematic DNA, we carefully selected "${movie.title}" as your next must-watch. It represents the perfect intersection of your taste and creative connections.`,
        `The cinematic universe connected your choices to this gem! "${movie.title}" is the result of following the creative thread from your favorite films.`,
        `Through the magic of movie connections, we've found the ideal film that evolves from your preferences. "${movie.title}" is cinema at its finest.`,
        `Your movie taste is as unique as your fingerprint. We analyzed the patterns and discovered "${movie.title}" as your perfect match.`,
        `Like following a trail of breadcrumbs (but much tastier), we traced your movie preferences to this stunning recommendation. "${movie.title}" awaits!`
    ];
    
    // Pick a random explanation
    elements.explanation.textContent = explanations[Math.floor(Math.random() * explanations.length)];
}

// Display the recommendation with alternatives
function displayRecommendation(movie, alternatives, userMovies = null) {
    elements.loadingSection.classList.add('hidden');
    elements.resultSection.classList.remove('hidden');
    elements.resultSection.classList.add('fade-in');
    
    // Store original movies for more movies functionality
    if (userMovies) {
        originalMovies = userMovies;
        currentPage = 1;
        excludeIds = [movie.id, ...alternatives.map(m => m.id)];
    }
    
    // Store movie ID in the title element for reference
    elements.movieTitle.textContent = movie.title;
    elements.movieTitle.dataset.movieId = movie.id;
    
    // Set movie details
    elements.movieYear.textContent = movie.release_date ? movie.release_date.split('-')[0] : 'Unknown year';
    elements.ratingValue.textContent = movie.vote_average ? movie.vote_average.toFixed(1) : 'N/A';
    elements.voteCount.textContent = `(${movie.vote_count ? movie.vote_count.toLocaleString() : 0} votes)`;
    
    // Set poster image
    if (movie.poster_path) {
        elements.moviePoster.src = IMAGE_BASE_URL + movie.poster_path;
        elements.moviePoster.alt = movie.title + ' poster';
    } else {
        elements.moviePoster.src = 'https://via.placeholder.com/300x450/1a1a1a/ffffff?text=No+Poster';
    }
    
    // Build the alternatives display
    elements.journeyElement.innerHTML = '<h4 class="text-xl font-bold mb-4 text-purple-400">You Might Also Like</h4>';
    
    alternatives.forEach((altMovie, index) => {
        const altElement = document.createElement('div');
        altElement.className = 'flex items-center text-gray-300 mb-4 p-3 bg-gray-800 rounded-lg hover:bg-gray-700 transition-colors cursor-pointer';
        
        const posterUrl = altMovie.poster_path ? 
            IMAGE_BASE_URL + altMovie.poster_path : 
            'https://via.placeholder.com/60x90/1a1a1a/ffffff?text=No+Poster';
        
        altElement.innerHTML = `
            <img src="${posterUrl}" alt="${altMovie.title}" class="w-15 h-20 object-cover rounded mr-4" onerror="this.src='https://via.placeholder.com/60x90/1a1a1a/ffffff?text=No+Poster';">
            <div class="flex-grow">
                <div class="font-medium">${altMovie.title}</div>
                <div class="text-sm text-gray-400">
                    ${altMovie.release_date ? altMovie.release_date.split('-')[0] : 'Unknown year'}
                    <span class="ml-2">
                        <i class="fas fa-star text-yellow-400 mr-1"></i>
                        ${altMovie.vote_average ? altMovie.vote_average.toFixed(1) : 'N/A'}/10
                    </span>
                </div>
            </div>
        `;
        
        // Add click handler to select this alternative as main recommendation
        altElement.addEventListener('click', () => {
            displayRecommendation(altMovie, alternatives.filter((_, i) => i !== index).concat([movie]));
        });
        
        elements.journeyElement.appendChild(altElement);
    });
    
    // Add "More Movies" button if we have original movies
    if (originalMovies) {
        const moreButton = document.createElement('button');
        moreButton.id = 'more-movies-btn';
        moreButton.className = 'w-full mt-6 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-bold py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-800 focus:ring-offset-gray-900';
        moreButton.innerHTML = '<i class="fas fa-plus mr-2"></i>Show More Movies';
        moreButton.addEventListener('click', loadMoreMovies);
        
        elements.journeyElement.appendChild(moreButton);
    }
    
    // Generate fun explanation
    generateExplanation(movie);
}

// Load more movies function
async function loadMoreMovies() {
    const moreButton = document.getElementById('more-movies-btn');
    if (moreButton) {
        moreButton.disabled = true;
        moreButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
    }
    
    try {
        currentPage++;
        const response = await fetch('api.php?action=more_movies', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `data=${encodeURIComponent(JSON.stringify({
                movies: originalMovies,
                page: currentPage,
                exclude_ids: excludeIds
            }))}`
        });
        
        const data = await response.json();
        
        if (data.success && data.new_movies) {
            // Add new movies to alternatives
            data.new_movies.forEach(movie => {
                excludeIds.push(movie.id);
                
                const altElement = document.createElement('div');
                altElement.className = 'flex items-center text-gray-300 mb-4 p-3 bg-gray-800 rounded-lg hover:bg-gray-700 transition-colors cursor-pointer animate-fadeIn';
                
                const posterUrl = movie.poster_path ? 
                    IMAGE_BASE_URL + movie.poster_path : 
                    'https://via.placeholder.com/60x90/1a1a1a/ffffff?text=No+Poster';
                
                altElement.innerHTML = `
                    <img src="${posterUrl}" alt="${movie.title}" class="w-15 h-20 object-cover rounded mr-4" onerror="this.src='https://via.placeholder.com/60x90/1a1a1a/ffffff?text=No+Poster';">
                    <div class="flex-grow">
                        <div class="font-medium">${movie.title}</div>
                        <div class="text-sm text-gray-400">
                            ${movie.release_date ? movie.release_date.split('-')[0] : 'Unknown year'}
                            <span class="ml-2">
                                <i class="fas fa-star text-yellow-400 mr-1"></i>
                                ${movie.vote_average ? movie.vote_average.toFixed(1) : 'N/A'}/10
                            </span>
                        </div>
                    </div>
                `;
                
                // Add click handler to select this alternative as main recommendation
                altElement.addEventListener('click', () => {
                    const currentMainMovie = {
                        id: elements.movieTitle.dataset.movieId,
                        title: elements.movieTitle.textContent,
                        release_date: elements.movieYear.textContent,
                        vote_average: parseFloat(elements.ratingValue.textContent),
                        vote_count: parseInt(elements.voteCount.textContent.match(/\d+/)[0]),
                        poster_path: elements.moviePoster.src.replace(IMAGE_BASE_URL, '')
                    };
                    
                    displayRecommendation(movie, [currentMainMovie]);
                });
                
                // Insert before the more button
                const moreBtn = document.getElementById('more-movies-btn');
                if (moreBtn) {
                    moreBtn.parentNode.insertBefore(altElement, moreBtn);
                } else {
                    elements.journeyElement.appendChild(altElement);
                }
            });
            
            // Update or remove the more button
            if (moreButton) {
                if (!data.has_more) {
                    moreButton.remove();
                } else {
                    moreButton.disabled = false;
                    moreButton.innerHTML = '<i class="fas fa-plus mr-2"></i>Show More Movies';
                }
            }
        } else {
            throw new Error(data.error || 'Failed to load more movies');
        }
    } catch (error) {
        console.error('Error loading more movies:', error);
        if (moreButton) {
            moreButton.disabled = false;
            moreButton.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i>Error - Try Again';
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
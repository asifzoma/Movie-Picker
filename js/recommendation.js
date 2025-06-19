import { IMAGE_BASE_URL, elements } from './config.js';

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
function displayRecommendation(movie, alternatives) {
    elements.loadingSection.classList.add('hidden');
    elements.resultSection.classList.remove('hidden');
    elements.resultSection.classList.add('fade-in');
    
    // Scroll to the recommendation section
    elements.resultSection.scrollIntoView({ behavior: 'smooth' });
    
    // Set movie details
    elements.movieTitle.textContent = movie.title;
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
    
    // Generate fun explanation
    generateExplanation(movie);
}

// Show error message
function showError(message) {
    elements.loadingSection.classList.add('hidden');
    document.getElementById('error-message').textContent = message;
    elements.errorSection.classList.remove('hidden');
}

export { displayRecommendation, showError }; 
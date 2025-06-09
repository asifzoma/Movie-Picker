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

// Display the recommendation
function displayRecommendation(movie, journey) {
    elements.loadingSection.classList.add('hidden');
    elements.resultSection.classList.remove('hidden');
    
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
    
    // Build the journey display
    elements.journeyElement.innerHTML = '<h4 class="text-xl font-bold mb-2 text-purple-400">Your Cinematic Journey</h4>';
    
    journey.forEach(step => {
        const stepElement = document.createElement('div');
        stepElement.className = 'flex items-center text-gray-300 mb-2';
        
        let icon = '';
        let text = '';
        
        switch(step.type) {
            case 'start':
                icon = '<i class="fas fa-play-circle text-xl mr-3 text-blue-400"></i>';
                text = `We started with "${step.movieTitle}" (${step.year}), your highest-rated selection`;
                break;
            case 'writer':
                icon = '<i class="fas fa-pen-fancy text-xl mr-3 text-green-400"></i>';
                text = `Found writer ${step.personName} from "${step.movieTitle}"`;
                break;
            case 'best_writer_movie':
                icon = '<i class="fas fa-star text-xl mr-3 text-yellow-400"></i>';
                text = `${step.personName}'s best work is "${step.movieTitle}" (${step.year})`;
                break;
            case 'director':
                icon = '<i class="fas fa-video text-xl mr-3 text-indigo-400"></i>';
                text = `Discovered director ${step.personName} from "${step.movieTitle}"`;
                break;
            case 'final_recommendation':
                icon = '<i class="fas fa-film text-xl mr-3 text-pink-400"></i>';
                text = `${step.personName}'s highest-rated film is "${step.movieTitle}" (${step.year})`;
                break;
            case 'fallback':
                icon = '<i class="fas fa-random text-xl mr-3 text-orange-400"></i>';
                text = `Since connections got tricky, we found a movie similar to "${step.seedMovieTitle}"`;
                break;
            case 'final_fallback':
                icon = '<i class="fas fa-fire text-xl mr-3 text-orange-400"></i>';
                text = `As a last resort, we're recommending this popular film`;
                break;
        }
        
        stepElement.innerHTML = `${icon}<span>${text}</span>`;
        elements.journeyElement.appendChild(stepElement);
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
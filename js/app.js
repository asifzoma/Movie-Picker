import { elements } from './config.js';
import { selectedMovies, setupSearch } from './movieSearch.js';
import { displayRecommendation, showError } from './recommendation.js';

// Initialize search functionality
setupSearch(elements.childhoodInput, 'childhood');
setupSearch(elements.recommendInput, 'recommend');
setupSearch(elements.watchedInput, 'watched');

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
            displayRecommendation(data.recommendation, data.journey);
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
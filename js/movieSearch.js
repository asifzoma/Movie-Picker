import { IMAGE_BASE_URL, elements } from './config.js';

// Global variables to store selected movies
const selectedMovies = {
    childhood: null,
    recommend: null,
    watched: null
};

// Movie search function with debounce
function searchMovies(query, dropdownId) {
    console.log('Searching for:', query);
    console.log('Using image base URL:', IMAGE_BASE_URL);
    
    if (query.length < 2) {
        document.getElementById(`${dropdownId}-dropdown`).classList.add('hidden');
        return;
    }
    
    const dropdown = document.getElementById(`${dropdownId}-dropdown`);
    
    // Show loading state
    dropdown.innerHTML = '<div class="p-3 text-gray-400">Searching...</div>';
    dropdown.classList.remove('hidden');
    
    fetch(`api.php?action=search&query=${encodeURIComponent(query)}`)
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text); // Log the raw response
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse JSON:', text);
                throw new Error('Invalid JSON response');
            }
        })
        .then(data => {
            console.log('Parsed data:', data);
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            dropdown.innerHTML = '';
            
            if (data.results && data.results.length > 0) {
                data.results.slice(0, 5).forEach(movie => {
                    console.log('Processing movie:', movie);
                    const item = document.createElement('div');
                    item.className = 'p-3 hover:bg-gray-700 cursor-pointer search-item';
                    item.innerHTML = `
                        <div class="flex items-center">
                            ${movie.poster_path ? 
                                `<img src="${IMAGE_BASE_URL}${movie.poster_path}" alt="${movie.title}" class="w-12 h-16 object-cover rounded mr-3" onerror="this.onerror=null; this.src='https://via.placeholder.com/48x64?text=No+Image';">` :
                                `<div class="w-12 h-16 bg-gray-700 rounded mr-3 flex items-center justify-center text-gray-400">
                                    <i class="fas fa-image"></i>
                                </div>`}
                            <div>
                                <div class="font-medium">${movie.title}</div>
                                <div class="text-sm text-gray-400">
                                    ${movie.release_date ? movie.release_date.split('-')[0] : 'Unknown year'}
                                </div>
                            </div>
                        </div>
                    `;
                    
                    item.addEventListener('click', () => {
                        const input = document.getElementById(dropdownId);
                        input.value = movie.title;
                        
                        // Create or update the selected movie display
                        let selectedDisplay = input.parentElement.querySelector('.selected-movie');
                        if (!selectedDisplay) {
                            selectedDisplay = document.createElement('div');
                            selectedDisplay.className = 'selected-movie mt-2 p-2 bg-gray-800 rounded-lg';
                            input.parentElement.appendChild(selectedDisplay);
                        }
                        
                        selectedDisplay.innerHTML = `
                            ${movie.poster_path ? 
                                `<img src="${IMAGE_BASE_URL}${movie.poster_path}" alt="${movie.title}" class="w-10 h-15 object-cover rounded mr-3" onerror="this.onerror=null; this.src='https://via.placeholder.com/40x60?text=No+Image';">` :
                                `<div class="w-10 h-15 bg-gray-700 rounded flex items-center justify-center text-gray-400">
                                    <i class="fas fa-image"></i>
                                </div>`}
                            <div>
                                <div class="font-medium">${movie.title}</div>
                                <div class="text-sm text-gray-400">${movie.release_date ? movie.release_date.split('-')[0] : 'Unknown year'}</div>
                            </div>
                        `;
                        
                        selectedMovies[dropdownId] = {
                            id: movie.id,
                            title: movie.title,
                            year: movie.release_date ? movie.release_date.split('-')[0] : 'Unknown',
                            vote_average: movie.vote_average,
                            vote_count: movie.vote_count,
                            poster_path: movie.poster_path
                        };
                        console.log('Selected movie:', selectedMovies[dropdownId]);
                        dropdown.classList.add('hidden');
                    });
                    
                    dropdown.appendChild(item);
                });
                dropdown.classList.remove('hidden');
            } else {
                const item = document.createElement('div');
                item.className = 'p-3 text-gray-400';
                item.textContent = 'No movies found';
                dropdown.appendChild(item);
                dropdown.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error searching movies:', error);
            dropdown.innerHTML = `<div class="p-3 text-red-400">Error: ${error.message}</div>`;
            dropdown.classList.remove('hidden');
        });
}

// Set up search functionality for each input
function setupSearch(inputElement, dropdownId) {
    let timeout;
    
    inputElement.addEventListener('input', () => {
        clearTimeout(timeout);
        const query = inputElement.value.trim();
        if (selectedMovies[dropdownId] && selectedMovies[dropdownId].title !== query) {
            selectedMovies[dropdownId] = null;
            const selectedDisplay = inputElement.parentElement.querySelector('.selected-movie');
            if (selectedDisplay) {
                selectedDisplay.remove();
            }
        }
        timeout = setTimeout(() => searchMovies(query, dropdownId), 300);
    });
    
    inputElement.addEventListener('blur', () => {
        setTimeout(() => {
            document.getElementById(`${dropdownId}-dropdown`).classList.add('hidden');
        }, 200);
    });
    
    inputElement.addEventListener('focus', () => {
        if (inputElement.value.trim().length >= 2) {
            searchMovies(inputElement.value.trim(), dropdownId);
        }
    });
}

export { selectedMovies, setupSearch }; 
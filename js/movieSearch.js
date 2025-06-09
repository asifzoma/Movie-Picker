import { IMAGE_BASE_URL, elements } from './config.js';

// Global variables to store selected movies
const selectedMovies = {
    childhood: null,
    recommend: null,
    watched: null
};

// Movie search function with debounce
function searchMovies(query, dropdownId) {
    if (query.length < 2) {
        document.getElementById(`${dropdownId}-dropdown`).classList.add('hidden');
        return;
    }
    
    fetch(`api.php?action=search&query=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            const dropdown = document.getElementById(`${dropdownId}-dropdown`);
            dropdown.innerHTML = '';
            
            if (data.results && data.results.length > 0) {
                data.results.slice(0, 5).forEach(movie => {
                    const item = document.createElement('div');
                    item.className = 'p-3 hover:bg-gray-700 cursor-pointer search-item';
                    item.innerHTML = `
                        <div class="flex items-center">
                            ${movie.poster_path ? 
                                `<img src="${IMAGE_BASE_URL}${movie.poster_path}" alt="${movie.title}" class="w-12 h-16 object-cover rounded mr-3">` :
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
                            selectedDisplay.className = 'selected-movie';
                            input.parentElement.appendChild(selectedDisplay);
                        }
                        
                        selectedDisplay.innerHTML = `
                            ${movie.poster_path ? 
                                `<img src="${IMAGE_BASE_URL}${movie.poster_path}" alt="${movie.title}">` :
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
            document.getElementById(`${dropdownId}-dropdown`).classList.add('hidden');
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
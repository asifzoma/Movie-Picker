<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Momo Movies - Film Recommendations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;700&display=swap');
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #0a0a0a;
            color: #ffffff;
        }
        
        .bebas {
            font-family: 'Bebas Neue', sans-serif;
        }
        
        .film-card {
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.8);
            background: linear-gradient(to bottom right, #1a1a1a, #0d0d0d);
            border: 1px solid #333;
        }
        
        .film-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.9);
            border-color: #555;
        }
        
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #222;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #444;
            border-radius: 4px;
        }
        
        .search-dropdown {
            max-height: 300px;
            overflow-y: auto;
            z-index: 50;
        }
        
        .search-item:hover {
            background-color: #333;
        }
        
        @keyframes filmReel {
            0% { transform: translateY(0); }
            100% { transform: translateY(-50%); }
        }
        
        .film-reel {
            animation: filmReel 30s linear infinite;
        }
        
        .gradient-text {
            background: linear-gradient(to right, #f43f5e, #d946ef);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
    </style>
</head>
<body class="min-h-screen">
    <div class="relative overflow-hidden">
        <!-- Film reel animation in background -->
        <div class="absolute inset-0 opacity-10 pointer-events-none">
            <div class="h-[200vh] w-full flex flex-col items-center film-reel">
                <div class="h-40 w-full bg-white opacity-40 mb-32"></div>
                <div class="h-40 w-full bg-white opacity-40 mb-32"></div>
                <div class="h-40 w-full bg-white opacity-40 mb-32"></div>
                <div class="h-40 w-full bg-white opacity-40 mb-32"></div>
                <div class="h-40 w-full bg-white opacity-40 mb-32"></div>
            </div>
        </div>
        
        <div class="container mx-auto px-4 py-12 relative z-10">
            <!-- Header -->
            <header class="text-center mb-16">
                <h1 class="bebas text-6xl md:text-8xl mb-2 tracking-wider gradient-text">Momo Movies</h1>
                <p class="text-xl md:text-2xl text-gray-300">Discover your next favorite film through cinematic connections</p>
                <div class="mt-8 h-0.5 bg-gradient-to-r from-transparent via-pink-500 to-transparent opacity-50 w-3/4 mx-auto"></div>
            </header>
            
            <!-- Main form -->
            <main class="max-w-3xl mx-auto">
                <div id="form-section" class="bg-gray-900 bg-opacity-90 rounded-xl p-6 md:p-8 shadow-xl border border-gray-800">
                    <h2 class="bebas text-3xl md:text-5xl mb-6 text-center">Tell Us About Your Movie Tastes</h2>
                    
                    <form id="movie-form" class="space-y-8">
                        <!-- Childhood favorite -->
                        <div>
                            <label for="childhood" class="block text-lg font-medium mb-2 text-gray-300">
                                <i class="fas fa-child mr-2 text-pink-500"></i>Your favorite childhood movie
                            </label>
                            <div class="relative">
                                <input type="text" id="childhood" placeholder="Search for a movie..." 
                                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                                <div id="childhood-dropdown" class="absolute left-0 right-0 mt-1 bg-gray-800 rounded-lg shadow-lg hidden search-dropdown"></div>
                            </div>
                        </div>
                        
                        <!-- Recommended film -->
                        <div>
                            <label for="recommend" class="block text-lg font-medium mb-2 text-gray-300">
                                <i class="fas fa-hand-holding-heart mr-2 text-purple-500"></i>A movie you'd recommend to others
                            </label>
                            <div class="relative">
                                <input type="text" id="recommend" placeholder="Search for a movie..."
                                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <div id="recommend-dropdown" class="absolute left-0 right-0 mt-1 bg-gray-800 rounded-lg shadow-lg hidden search-dropdown"></div>
                            </div>
                        </div>
                        
                        <!-- Most watched -->
                        <div>
                            <label for="watched" class="block text-lg font-medium mb-2 text-gray-300">
                                <i class="fas fa-redo mr-2 text-blue-500"></i>Your most-watched movie
                            </label>
                            <div class="relative">
                                <input type="text" id="watched" placeholder="Search for a movie..."
                                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <div id="watched-dropdown" class="absolute left-0 right-0 mt-1 bg-gray-800 rounded-lg shadow-lg hidden search-dropdown"></div>
                            </div>
                        </div>
                        
                        <button type="submit" class="w-full bg-gradient-to-r from-pink-500 to-purple-600 hover:from-pink-600 hover:to-purple-700 text-white font-bold py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-800 focus:ring-offset-gray-900">
                            <i class="fas fa-film mr-2"></i> Generate My Recommendation
                        </button>
                    </form>
                </div>
                
                <!-- Recommendation result -->
                <div id="result-section" class="hidden mt-12">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="bebas text-4xl md:text-5xl">Your Momo Movie</h2>
                        <button id="try-again" class="text-gray-300 hover:text-white flex items-center">
                            <i class="fas fa-redo mr-2"></i> Try Again
                        </button>
                    </div>
                    
                    <div id="recommendation" class="bg-gray-900 bg-opacity-90 rounded-xl p-6 md:p-8 shadow-xl border border-gray-800 film-card">
                        <div class="flex flex-col md:flex-row gap-8">
                            <div id="poster-container" class="w-full md:w-1/3 flex-shrink-0">
                                <img id="movie-poster" src="https://via.placeholder.com/300x450" alt="Recommended movie" class="w-full rounded-lg shadow-lg">
                                <p id="movie-rating" class="mt-4 flex items-center">
                                    <i class="fas fa-star text-yellow-400 mr-2"></i>
                                    <span id="rating-value" class="font-bold">-</span>/10
                                    <span id="vote-count" class="text-gray-400 ml-2 text-sm">(0 votes)</span>
                                </p>
                            </div>
                            
                            <div class="flex-grow">
                                <h3 id="movie-title" class="bebas text-4xl md:text-5xl mb-2">Movie Title</h3>
                                <p id="movie-year" class="text-lg text-gray-300 mb-4">Year</p>
                                
                                <div class="mb-6">
                                    <h4 class="text-xl font-bold mb-2 text-pink-400">Why We Chose This For You</h4>
                                    <p id="explanation" class="text-gray-300 leading-relaxed">
                                        Your unique cinematic journey led us to this recommendation. Starting from your selections, we traced through connections of writers and directors to find this hidden gem.
                                    </p>
                                </div>
                                
                                <div id="journey" class="space-y-4">
                                    <h4 class="text-xl font-bold mb-2 text-purple-400">Your Cinematic Journey</h4>
                                    <div class="flex items-center text-gray-300">
                                        <i class="fas fa-long-arrow-alt-right text-xl mr-3 text-blue-400"></i>
                                        <span>Starting point</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Loading state -->
                <div id="loading-section" class="hidden mt-12 text-center py-20">
                    <div class="animate-pulse">
                        <i class="fas fa-film text-5xl mb-6 text-pink-500"></i>
                        <h3 class="bebas text-3xl mb-4">Crafting Your Recommendation</h3>
                        <p class="text-gray-400">Analyzing your movie choices...</p>
                    </div>
                </div>
                
                <!-- Error state -->
                <div id="error-section" class="hidden mt-12 bg-red-900 bg-opacity-30 rounded-xl p-6 border border-red-700">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-red-400 text-2xl mr-4 mt-1"></i>
                        <div>
                            <h3 class="bebas text-2xl text-red-200 mb-2">Oops! Something Went Wrong</h3>
                            <p id="error-message" class="text-gray-300">We couldn't generate a recommendation. Please try again later.</p>
                            <button id="error-retry" class="mt-4 px-4 py-2 bg-red-700 hover:bg-red-600 rounded-lg text-white transition-colors">
                                <i class="fas fa-redo mr-2"></i> Try Again
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        
        <!-- Footer -->
        <footer class="border-t border-gray-800 mt-20 py-8 text-center text-gray-500 text-sm">
            <div class="container mx-auto px-4">
                <p>© <?php echo date('Y'); ?> Momo Movies | Powered by TMDb API</p>
                <p class="mt-2">Created with <i class="fas fa-heart text-red-400"></i> for film lovers</p>
            </div>
        </footer>
    </div>

    <script>
        const IMAGE_BASE_URL = '<?php echo TMDB_IMAGE_BASE_URL; ?>';
        
        // DOM elements
        const form = document.getElementById('movie-form');
        const formSection = document.getElementById('form-section');
        const resultSection = document.getElementById('result-section');
        const loadingSection = document.getElementById('loading-section');
        const errorSection = document.getElementById('error-section');
        const tryAgainBtn = document.getElementById('try-again');
        const errorRetryBtn = document.getElementById('error-retry');
        
        // Search inputs and dropdowns
        const childhoodInput = document.getElementById('childhood');
        const childhoodDropdown = document.getElementById('childhood-dropdown');
        const recommendInput = document.getElementById('recommend');
        const recommendDropdown = document.getElementById('recommend-dropdown');
        const watchedInput = document.getElementById('watched');
        const watchedDropdown = document.getElementById('watched-dropdown');
        
        // Recommendation display elements
        const moviePoster = document.getElementById('movie-poster');
        const movieTitle = document.getElementById('movie-title');
        const movieYear = document.getElementById('movie-year');
        const ratingValue = document.getElementById('rating-value');
        const voteCount = document.getElementById('vote-count');
        const explanation = document.getElementById('explanation');
        const journeyElement = document.getElementById('journey');
        
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
                                document.getElementById(dropdownId).value = movie.title;
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
        
        setupSearch(childhoodInput, 'childhood');
        setupSearch(recommendInput, 'recommend');
        setupSearch(watchedInput, 'watched');
        
        // Form submission handler
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Validate all movies are selected
            if (!selectedMovies.childhood || !selectedMovies.recommend || !selectedMovies.watched) {
                showError('Please select movies for all three fields');
                return;
            }
            
            // Show loading state
            formSection.classList.add('hidden');
            resultSection.classList.add('hidden');
            errorSection.classList.add('hidden');
            loadingSection.classList.remove('hidden');
            
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
        
        // Display the recommendation
        function displayRecommendation(movie, journey) {
            loadingSection.classList.add('hidden');
            resultSection.classList.remove('hidden');
            
            // Set movie details
            movieTitle.textContent = movie.title;
            movieYear.textContent = movie.release_date ? movie.release_date.split('-')[0] : 'Unknown year';
            ratingValue.textContent = movie.vote_average ? movie.vote_average.toFixed(1) : 'N/A';
            voteCount.textContent = `(${movie.vote_count ? movie.vote_count.toLocaleString() : 0} votes)`;
            
            // Set poster image
            if (movie.poster_path) {
                moviePoster.src = IMAGE_BASE_URL + movie.poster_path;
                moviePoster.alt = movie.title + ' poster';
            } else {
                moviePoster.src = 'https://via.placeholder.com/300x450/1a1a1a/ffffff?text=No+Poster';
            }
            
            // Build the journey display
            journeyElement.innerHTML = '<h4 class="text-xl font-bold mb-2 text-purple-400">Your Cinematic Journey</h4>';
            
            journey.forEach((step, index) => {
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
                journeyElement.appendChild(stepElement);
            });
            
            // Generate fun explanation
            generateExplanation(movie, journey);
        }
        
        // Generate a fun explanation for the recommendation
        function generateExplanation(movie, journey) {
            const explanations = [
                `Based on your unique cinematic DNA, we carefully selected "${movie.title}" as your next must-watch. It represents the perfect intersection of your taste and creative connections.`,
                `The cinematic universe connected your choices to this gem! "${movie.title}" is the result of following the creative thread from your favorite films.`,
                `Through the magic of movie connections, we've found the ideal film that evolves from your preferences. "${movie.title}" is cinema at its finest.`,
                `Your movie taste is as unique as your fingerprint. We analyzed the patterns and discovered "${movie.title}" as your perfect match.`,
                `Like following a trail of breadcrumbs (but much tastier), we traced your movie preferences to this stunning recommendation. "${movie.title}" awaits!`
            ];
            
            // Pick a random explanation
            explanation.textContent = explanations[Math.floor(Math.random() * explanations.length)];
        }
        
        // Show error message
        function showError(message) {
            loadingSection.classList.add('hidden');
            document.getElementById('error-message').textContent = message;
            errorSection.classList.remove('hidden');
        }
        
        // Try again button handlers
        tryAgainBtn.addEventListener('click', () => {
            resultSection.classList.add('hidden');
            formSection.classList.remove('hidden');
            // Clear form
            form.reset();
            Object.keys(selectedMovies).forEach(key => selectedMovies[key] = null);
        });
        
        errorRetryBtn.addEventListener('click', () => {
            errorSection.classList.add('hidden');
            formSection.classList.remove('hidden');
        });
    </script>
</body>
</html> 
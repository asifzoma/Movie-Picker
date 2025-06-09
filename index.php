<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="tmdb-image-base-url" content="<?php echo TMDB_IMAGE_BASE_URL; ?>">
    <title>Momo Movies - Film Recommendations</title>
    <link rel="stylesheet" href="css/output.css">
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
                    <div class="animate-pulse space-y-4">
                        <div class="flex justify-center">
                            <i class="fas fa-film text-6xl text-pink-500 animate-spin"></i>
                        </div>
                        <h3 class="bebas text-3xl mb-2">Crafting Your Recommendation</h3>
                        <p class="text-gray-400">Analyzing your movie choices...</p>
                        <div class="flex justify-center space-x-2">
                            <div class="w-2 h-2 bg-pink-500 rounded-full animate-bounce" style="animation-delay: 0s"></div>
                            <div class="w-2 h-2 bg-purple-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                            <div class="w-2 h-2 bg-blue-500 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                        </div>
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

    <script type="module" src="js/app.js"></script>
</body>
</html> 
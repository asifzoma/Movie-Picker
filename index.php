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
        
        .saved-movies-container {
            scrollbar-width: thin;
            scrollbar-color: #4c1d95 #1f2937;
        }
        
        .saved-movies-container::-webkit-scrollbar {
            width: 6px;
        }
        
        .saved-movies-container::-webkit-scrollbar-track {
            background: #1f2937;
            border-radius: 3px;
        }
        
        .saved-movies-container::-webkit-scrollbar-thumb {
            background: #4c1d95;
            border-radius: 3px;
        }
        
        .saved-movies-container::-webkit-scrollbar-thumb:hover {
            background: #5b21b6;
        }
        
        .saved-movie-card {
            transition: all 0.2s ease;
        }
        
        .saved-movie-card:hover {
            transform: translateX(2px);
            background-color: #374151;
        }
        
        /* Horizontal slider for saved movies */
        .saved-movies-slider-container {
            position: relative;
            width: 100%;
            overflow: hidden;
        }
        
        .saved-movies-slider {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            scroll-behavior: smooth;
            padding: 0.5rem 0;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }
        
        .saved-movies-slider::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
        
        .saved-movie-card {
            flex: 0 0 auto;
            width: 150px;
            background: #1f2937;
            border-radius: 12px;
            padding: 0.75rem;
            position: relative;
            transition: all 0.2s ease;
            border: 1px solid #374151;
        }
        
        .saved-movie-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            border-color: #8b5cf6;
        }
        
        .saved-movie-poster {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        
        .saved-movie-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #f3f4f6;
            margin-bottom: 0.25rem;
            line-height: 1.2;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .saved-movie-year {
            font-size: 0.75rem;
            color: #9ca3af;
        }
        
        .saved-movie-delete {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            width: 24px;
            height: 24px;
            background: rgba(239, 68, 68, 0.9);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 0.75rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            opacity: 0;
        }
        
        .saved-movie-card:hover .saved-movie-delete {
            opacity: 1;
        }
        
        .saved-movie-delete:hover {
            background: #dc2626;
            transform: scale(1.1);
        }
        
        .scroll-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: rgba(139, 92, 246, 0.8);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            z-index: 10;
        }
        
        .scroll-btn:hover {
            background: rgba(139, 92, 246, 1);
            transform: translateY(-50%) scale(1.1);
        }
        
        .scroll-left {
            left: 0.5rem;
        }
        
        .scroll-right {
            right: 0.5rem;
        }
        
        /* New movie card layout with side buttons */
        .movie-card-layout {
            display: flex;
            align-items: center;
            gap: 1rem;
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .action-side {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .left-side {
            order: 1;
        }
        
        .right-side {
            order: 3;
        }
        
        .movie-content {
            order: 2;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        /* Override old movie-actions styles */
        .movie-actions {
            display: none !important;
        }
        
        /* Responsive design for mobile */
        @media (max-width: 768px) {
            .movie-card-layout {
                flex-direction: column;
                gap: 1.5rem;
            }
            
            .action-side {
                order: unset;
            }
            
            .left-side {
                order: 1;
            }
            
            .right-side {
                order: 3;
            }
            
            .movie-content {
                order: 2;
            }
            
            .action-btn {
                width: 70px !important;
                height: 70px !important;
                font-size: 28px !important;
            }
        }
        
        /* Enhanced like/dislike buttons - more prominent */
        .action-btn {
            width: 80px !important;
            height: 80px !important;
            font-size: 32px !important;
            border-radius: 50% !important;
            border: 3px solid transparent !important;
            transition: all 0.3s ease !important;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3) !important;
        }
        
        .action-btn:hover {
            transform: scale(1.15) !important;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.4) !important;
        }
        
        .action-btn.dislike {
            background: linear-gradient(135deg, #ef4444, #dc2626) !important;
            color: white !important;
            border-color: #dc2626 !important;
        }
        
        .action-btn.dislike:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c) !important;
            border-color: #b91c1c !important;
        }
        
        .action-btn.like {
            background: linear-gradient(135deg, #10b981, #059669) !important;
            color: white !important;
            border-color: #059669 !important;
        }
        
        .action-btn.like:hover {
            background: linear-gradient(135deg, #059669, #047857) !important;
            border-color: #047857 !important;
        }
        
        /* Reduced poster height with full poster display */
        #movie-poster {
            max-height: 225px !important; /* Half of original 450px */
            object-fit: contain !important; /* Show full poster instead of cropping */
            width: 100% !important;
            background-color: #1a1a1a !important; /* Dark background for letterboxing */
        }
        
        #poster-container {
            max-height: 225px !important;
            overflow: hidden !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
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
            <!-- Age Warning -->
            <div class="bg-red-900 bg-opacity-90 border border-red-700 rounded-lg p-4 mb-8 text-center">
                <div class="flex flex-col items-center space-y-2">
                    <div class="flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-400 text-xl mr-3"></i>
                        <p class="text-red-100 font-semibold">
                            <strong>⚠️ Important Disclaimers:</strong>
                        </p>
                    </div>
                    <div class="text-red-100 text-sm space-y-1">
                        <p><strong>Recommendation Disclaimer:</strong> All films shown are algorithmic recommendations based on your input. We do not guarantee these are perfect matches for your preferences.</p>
                        <p><strong>Content Ratings:</strong> Please check individual movie ratings and reviews to ensure they are suitable for younger viewers before watching.</p>
                    </div>
                </div>
            </div>
            
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
                                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-pink-500 focus:border-transparent pr-12">
                                <!-- Remove button (X) in top-right corner -->
                                <button type="button" id="childhood-remove" onclick="removeSelectedMovie('childhood')" 
                                    class="absolute right-2 top-1/2 transform -translate-y-1/2 text-red-400 hover:text-red-300 transition-colors hidden">
                                    <i class="fas fa-times-circle text-xl"></i>
                                </button>
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
                                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent pr-12">
                                <!-- Remove button (X) in top-right corner -->
                                <button type="button" id="recommend-remove" onclick="removeSelectedMovie('recommend')" 
                                    class="absolute right-2 top-1/2 transform -translate-y-1/2 text-red-400 hover:text-red-300 transition-colors hidden">
                                    <i class="fas fa-times-circle text-xl"></i>
                                </button>
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
                                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-12">
                                <!-- Remove button (X) in top-right corner -->
                                <button type="button" id="watched-remove" onclick="removeSelectedMovie('watched')" 
                                    class="absolute right-2 top-1/2 transform -translate-y-1/2 text-red-400 hover:text-red-300 transition-colors hidden">
                                    <i class="fas fa-times-circle text-xl"></i>
                                </button>
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
                    
                    <!-- Swipe container for Tinder-like interface -->
                    <div class="swipe-container">
                        <div id="recommendation" class="swipe-card">
                            <div class="movie-card-layout">
                                <!-- Dislike button on the left -->
                                <div class="action-side left-side">
                                    <button class="action-btn dislike" id="dislike-btn" aria-label="Dislike movie">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                
                                <!-- Movie content in the center -->
                                <div class="movie-content">
                                    <div id="poster-container" class="w-full">
                                        <img id="movie-poster" src="https://via.placeholder.com/300x450" alt="Recommended movie" class="w-full rounded-lg shadow-lg">
                                        <p id="movie-rating" class="mt-3 flex items-center">
                                            <i class="fas fa-star text-yellow-400 mr-2"></i>
                                            <span id="rating-value" class="font-bold">-</span>/10
                                            <span id="vote-count" class="text-gray-400 ml-2 text-sm">(0 votes)</span>
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <h3 id="movie-title" class="bebas text-xl mb-1">Movie Title</h3>
                                        <p id="movie-year" class="text-sm text-gray-300 mb-2">Year</p>
                                        
                                        <div class="mb-3">
                                            <h4 class="text-base font-bold mb-1 text-pink-400">Synopsis</h4>
                                            <p id="movie-overview" class="text-xs text-gray-300 leading-relaxed line-clamp-3"></p>
                                        </div>

                                        <div class="mb-3">
                                            <p id="explanation" class="text-xs text-purple-400 italic leading-relaxed">
                                                Your unique cinematic journey led us to this recommendation.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Like button on the right -->
                                <div class="action-side right-side">
                                    <button class="action-btn like" id="like-btn" aria-label="Like movie">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden stack for next movies -->
                    <div id="movie-stack" class="hidden"></div>
                    
                    <!-- Saved movies tray -->
                    <div class="saved-movies mt-8">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-bold text-purple-400">Your Liked Movies</h3>
                            <button onclick="clearAllLikedMovies()" class="text-red-400 hover:text-red-300 text-sm">
                                <i class="fas fa-trash mr-1"></i> Clear All
                            </button>
                        </div>
                        <div class="saved-movies-slider-container">
                            <div id="saved-movies-container" class="saved-movies-slider">
                                <!-- Saved movies will be added here -->
                            </div>
                            <button id="scroll-left" class="scroll-btn scroll-left">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button id="scroll-right" class="scroll-btn scroll-right">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        <div class="mt-4 text-center">
                            <button onclick="shareLikedMovies()" class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-6 py-3 rounded-lg text-sm font-medium shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-share-alt mr-2"></i> Share Movies
                            </button>
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
                
                <!-- Error section -->
                <div id="error-section" class="hidden mt-12 text-center py-20" style="display: none !important;">
                    <div class="space-y-4">
                        <div class="flex justify-center">
                            <i class="fas fa-exclamation-circle text-6xl text-red-500"></i>
                        </div>
                        <h3 class="bebas text-3xl mb-2">Oops! Something Went Wrong</h3>
                        <p id="error-message" class="text-gray-400">Failed to generate a recommendation. Please try again.</p>
                        <button onclick="window.location.href = window.location.pathname" class="mt-4 bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg transition-all duration-300">
                            <i class="fas fa-redo mr-2"></i> Try Again
                        </button>
                    </div>
                </div>
            </main>
        </div>
        
        <!-- Share Modal -->
        <div id="share-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
            <div class="bg-gray-900 rounded-xl shadow-2xl max-w-md w-full mx-4 border border-gray-700">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-white">Share Your Movie Collection</h3>
                        <button onclick="closeShareModal()" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <!-- Copy to Clipboard -->
                        <button onclick="copyToClipboard()" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-4 rounded-lg flex items-center justify-center transition-colors">
                            <i class="fas fa-copy mr-3"></i>
                            Copy to Clipboard
                        </button>
                        
                        <!-- Email Share -->
                        <button onclick="shareViaEmail()" class="w-full bg-red-600 hover:bg-red-700 text-white py-3 px-4 rounded-lg flex items-center justify-center transition-colors">
                            <i class="fas fa-envelope mr-3"></i>
                            Share via Email
                        </button>
                        
                        <!-- Twitter Share -->
                        <button onclick="shareViaTwitter()" class="w-full bg-blue-400 hover:bg-blue-500 text-white py-3 px-4 rounded-lg flex items-center justify-center transition-colors">
                            <i class="fab fa-twitter mr-3"></i>
                            Share on Twitter
                        </button>
                        
                        <!-- Facebook Share -->
                        <button onclick="shareViaFacebook()" class="w-full bg-blue-800 hover:bg-blue-900 text-white py-3 px-4 rounded-lg flex items-center justify-center transition-colors">
                            <i class="fab fa-facebook mr-3"></i>
                            Share on Facebook
                        </button>
                        
                        <!-- WhatsApp Share -->
                        <button onclick="shareViaWhatsApp()" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-lg flex items-center justify-center transition-colors">
                            <i class="fab fa-whatsapp mr-3"></i>
                            Share on WhatsApp
                        </button>
                        
                        <!-- Native Share (mobile) -->
                        <button id="native-share-btn" onclick="shareNative()" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-3 px-4 rounded-lg flex items-center justify-center transition-colors hidden">
                            <i class="fas fa-mobile-alt mr-3"></i>
                            Share
                        </button>
                    </div>
                    
                    <div class="mt-6 pt-4 border-t border-gray-700">
                        <p class="text-sm text-gray-400 text-center">
                            Share your personalized movie collection with friends and family!
                        </p>
                    </div>
                </div>
            </div>
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
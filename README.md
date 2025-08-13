# Momo Movies - Movie Recommendation System

A beautiful and interactive movie recommendation system that helps users discover new films based on their movie preferences. The system uses the TMDB API to create connections between movies through writers and directors.

## Features

- Beautiful, modern UI with smooth animations
- Real-time movie search with auto-complete
- Intelligent recommendation algorithm based on movie connections
- Detailed explanation of the recommendation process
- Responsive design that works on all devices
- Visual journey showing how recommendations are made

## Requirements

- PHP 7.4 or higher
- cURL PHP extension
- Web server (Apache, Nginx, etc.)
- HTTPS for production use (due to TMDb API requirements)

## Setup

1. Clone this repository to your web server directory:
```bash
git clone https://github.com/yourusername/momo-movies.git
cd momo-movies
```

2. Make sure your web server has write permissions for the project directory.

3. Configure your web server to serve the project directory.

4. **Configure your TMDB API credentials:**
   ```bash
   # Copy the example environment file
   cp .env.example .env
   
   # Edit .env and add your actual API credentials
   TMDB_API_KEY=your_actual_api_key_here
   TMDB_API_READ_ACCESS_TOKEN=your_actual_read_access_token_here
   ```

5. Access the application through your web browser:
```
http://localhost/momo-movies
```

## Project Structure

- `index.php` - Main application file with UI
- `config.php` - Configuration settings and environment loading
- `api.php` - API endpoint for AJAX requests
- `RecommendationEngine.php` - Core recommendation algorithm
- `SessionManager.php` - Session and data management
- `.env` - Environment variables (not tracked by git)
- `.env.example` - Example environment file

## Credits

- Powered by [The Movie Database (TMDb) API](https://www.themoviedb.org/)
- Uses [Tailwind CSS](https://tailwindcss.com/) for styling
- Uses [Font Awesome](https://fontawesome.com/) for icons
- Fonts: Bebas Neue and Montserrat from Google Fonts

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Security

This application follows security best practices:

✅ **API keys are stored in environment variables** (not in code)  
✅ **Environment files are ignored by git**  
✅ **No sensitive data is committed to version control**  
✅ **Proper input validation and sanitization**  
✅ **HTTPS recommended for production**  

For production deployment, ensure:
- Use HTTPS
- Set up proper rate limiting
- Configure secure session handling
- Implement proper error handling
- Use environment-specific configurations

## Support

For support, please open an issue in the GitHub repository or contact the maintainers. 
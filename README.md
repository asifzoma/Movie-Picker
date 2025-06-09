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

4. (Optional) Update the TMDB API key in `config.php` if you want to use your own:
```php
define('TMDB_API_KEY', 'your_api_key_here');
```

5. Access the application through your web browser:
```
http://localhost/momo-movies
```

## Project Structure

- `index.php` - Main application file with UI
- `config.php` - Configuration settings
- `api.php` - API endpoint for AJAX requests
- `tmdb_api.php` - TMDB API helper functions

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

The TMDB API key is exposed in the frontend JavaScript code. In a production environment, you should:

1. Use environment variables to store the API key
2. Implement proper rate limiting
3. Add CSRF protection
4. Add input validation and sanitization
5. Use HTTPS
6. Implement proper error handling

## Support

For support, please open an issue in the GitHub repository or contact the maintainers. 
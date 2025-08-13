# Movie Picker - cPanel Deployment Guide

## Pre-Deployment Checklist

### 1. Environment Setup
- ✅ `.env` file is configured with your TMDB API credentials
- ✅ `APP_ENV=production` and `APP_DEBUG=false` in `.env`
- ✅ All API keys and tokens are valid

### 2. File Structure
```
Movie-Picker/
├── index.php              # Main application file
├── api.php                # API endpoints
├── config.php             # Configuration and environment loading
├── SessionManager.php     # Session management
├── RecommendationEngine.php # Movie recommendation logic
├── .env                   # Environment variables (API keys)
├── .env.example           # Example environment file
├── css/                   # Compiled CSS files
├── js/                    # JavaScript files
├── data/                  # Data storage directory
└── styles/                # Source SCSS files
```

### 3. cPanel Requirements
- PHP 7.4+ (8.0+ recommended)
- JSON extension enabled
- cURL extension enabled (optional, falls back to file_get_contents)
- Write permissions on `data/` directory

## Deployment Steps

### Step 1: Prepare Files
1. Remove these files/folders (not needed for production):
   - `composer.json`
   - `composer.lock`
   - `vendor/` (entire folder)
   - `node_modules/` (entire folder)
   - `package.json`
   - `package-lock.json`
   - `tailwind.config.js`
   - `php_errors.log`
   - `debug.html`
   - `test.html`

### Step 2: Upload to cPanel
1. Create a ZIP file of your project (excluding the files above)
2. Upload via cPanel File Manager or FTP
3. Extract in your web root directory

### Step 3: Set Permissions
1. Set `data/` directory to 755
2. Ensure `data/` directory is writable by the web server
3. Set `.env` file to 644 (readable by web server)

### Step 4: Test
1. Visit your domain
2. Test movie search functionality
3. Test recommendation generation
4. Check error logs if issues occur

## Troubleshooting

### Common Issues

#### 1. "Failed to connect to TMDB API"
- Check your `.env` file has correct API credentials
- Verify API keys are active in TMDB dashboard
- Check if your hosting provider allows external HTTP requests

#### 2. "Permission denied" errors
- Ensure `data/` directory has write permissions (755)
- Check if `.env` file is readable (644)

#### 3. Session not working
- Verify `data/` directory is writable
- Check PHP session configuration in cPanel

#### 4. 500 Internal Server Error
- Check error logs in cPanel
- Verify PHP version compatibility
- Ensure all required files are uploaded

### Error Logs
- Check cPanel error logs
- Look for PHP errors in the browser console
- Verify file paths are correct

## Security Notes

- `.env` file contains sensitive API keys - keep it secure
- `data/` directory contains user session data
- Consider using HTTPS for production
- API keys are visible in client-side code (this is normal for TMDB public API)

## Performance Tips

- The application uses native PHP functions for optimal performance
- No external dependencies mean faster loading
- Session data is stored locally for quick access
- Consider enabling PHP OPcache in cPanel for better performance

## Support

If you encounter issues:
1. Check cPanel error logs
2. Verify all files are uploaded correctly
3. Test with a simple PHP file first
4. Contact your hosting provider for PHP configuration issues

@echo off
echo 🎬 Movie Picker - Preparing for cPanel Deployment
echo ==================================================

REM Remove Composer files
echo 🗑️  Removing Composer dependencies...
if exist vendor\ rmdir /s /q vendor
if exist composer.json del composer.json
if exist composer.lock del composer.lock

REM Remove Node.js files
echo 🗑️  Removing Node.js dependencies...
if exist node_modules\ rmdir /s /q node_modules
if exist package.json del package.json
if exist package-lock.json del package-lock.json
if exist tailwind.config.js del tailwind.config.js

REM Remove development files
echo 🗑️  Removing development files...
if exist php_errors.log del php_errors.log
if exist debug.html del debug.html
if exist test.html del test.html
if exist .gitattributes del .gitattributes

REM Create production-ready directory
echo 📁 Creating production directory...
if not exist ..\Movie-Picker-Production\ mkdir ..\Movie-Picker-Production
copy index.php ..\Movie-Picker-Production\
copy api.php ..\Movie-Picker-Production\
copy config.php ..\Movie-Picker-Production\
copy SessionManager.php ..\Movie-Picker-Production\
copy RecommendationEngine.php ..\Movie-Picker-Production\
copy .env ..\Movie-Picker-Production\
copy .env.example ..\Movie-Picker-Production\
copy DEPLOYMENT.md ..\Movie-Picker-Production\

REM Copy directories
xcopy css\ ..\Movie-Picker-Production\css\ /E /I /Y
xcopy js\ ..\Movie-Picker-Production\js\ /E /I /Y
xcopy data\ ..\Movie-Picker-Production\data\ /E /I /Y
xcopy styles\ ..\Movie-Picker-Production\styles\ /E /I /Y

echo.
echo ✅ Deployment package ready!
echo 📦 Production files are in: ..\Movie-Picker-Production\
echo 📋 See DEPLOYMENT.md for deployment instructions
echo.
echo 🚀 Ready to upload to cPanel!
pause

@echo off
REM ==========================================
REM SDLC Project Tracker - Asset Setup Script
REM This script downloads Bootstrap and Chart.js
REM ==========================================

echo ==========================================
echo SDLC Project Tracker - Asset Setup
echo ==========================================
echo.

REM Create directories
echo Creating directories...
if not exist css mkdir css
if not exist js mkdir js

REM Download Bootstrap CSS
echo Downloading Bootstrap CSS...
curl -o css/bootstrap.min.css https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css

REM Download Bootstrap JS
echo Downloading Bootstrap JavaScript...
curl -o js/bootstrap.bundle.min.js https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js

REM Download Chart.js
echo Downloading Chart.js...
curl -o js/chart.umd.js https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js

echo.
echo ==========================================
echo Setup Complete!
echo ==========================================
echo.
echo Files downloaded:
echo   [OK] css/bootstrap.min.css
echo   [OK] js/bootstrap.bundle.min.js
echo   [OK] js/chart.umd.js
echo.
echo Next steps:
echo 1. Configure database credentials in db.php
echo 2. Import database.sql into MySQL
echo 3. Access the application via your web browser
echo.
echo For detailed instructions, see README.md
echo.
pause
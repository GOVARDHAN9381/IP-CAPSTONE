@echo off
echo ============================================
echo  CollabIQ -- AI Collaboration Platform
echo ============================================
echo.
echo Starting PHP server on http://localhost:8000 ...
echo.

:: Open Browser
start "" "http://localhost:8000/"

echo ============================================
echo  CollabIQ is RUNNING at:
echo  http://localhost:8000/
echo.
echo  DEMO CREDENTIALS:
echo  Student: govardhan@student.edu / student123
echo  Faculty: faculty@ipcapstone.edu / faculty123
echo ============================================
echo.
echo  NOTE: Keep this window open while using the app.
echo  Press Ctrl+C to stop the server.
echo.

php -S localhost:8000 -t "%~dp0."

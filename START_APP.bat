@echo off
echo ============================================
echo  CollabIQ -- Starting Server
echo ============================================

set XAMPP=c:\IPCAPSTONE\xampp
set HTDOCS=%XAMPP%\htdocs\ipcapstone

:: Kill any running Apache instances
taskkill /F /IM httpd.exe >nul 2>&1
del "%XAMPP%\apache\logs\httpd.pid" >nul 2>&1
timeout /t 1 >nul

:: -- Sync latest project files to htdocs --
echo Syncing project files to htdocs...
xcopy /E /Y /Q "c:\IPCAPSTONE\api\*"     "%HTDOCS%\api\" >nul
xcopy /E /Y /Q "c:\IPCAPSTONE\assets\*"  "%HTDOCS%\assets\" >nul
xcopy /E /Y /Q "c:\IPCAPSTONE\auth\*"    "%HTDOCS%\auth\" >nul
xcopy /E /Y /Q "c:\IPCAPSTONE\config\*"  "%HTDOCS%\config\" >nul
xcopy /E /Y /Q "c:\IPCAPSTONE\faculty\*" "%HTDOCS%\faculty\" >nul
xcopy /E /Y /Q "c:\IPCAPSTONE\project\*" "%HTDOCS%\project\" >nul
xcopy /E /Y /Q "c:\IPCAPSTONE\student\*" "%HTDOCS%\student\" >nul
copy /Y "c:\IPCAPSTONE\index.php" "%HTDOCS%\index.php" >nul
echo Files synced!

:: -- Start Apache --
echo Starting Apache Web Server...
start "" /B "%XAMPP%\apache\bin\httpd.exe"
timeout /t 3 >nul

:: -- Open Browser --
echo Opening CollabIQ in browser...
start "" "http://localhost:8080/ipcapstone/"

echo.
echo ============================================
echo  CollabIQ is running at:
echo  http://localhost:8080/ipcapstone/
echo.
echo  DEMO CREDENTIALS:
echo  Student: govardhan@student.edu / student123
echo  Faculty: faculty@ipcapstone.edu / faculty123
echo ============================================
echo.
echo  NOTE: Keep this window open during your session.
echo  Press any key to STOP the server.
pause >nul
taskkill /F /IM httpd.exe >nul 2>&1
echo Server stopped.

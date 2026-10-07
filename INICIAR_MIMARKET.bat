@echo off
cd /d "%~dp0"
where php >nul 2>&1
if errorlevel 1 (
 echo PHP no se encuentra en PATH.
 pause
 exit /b 1
)
start "" http://localhost:8000
php -S localhost:8000 -t public
pause

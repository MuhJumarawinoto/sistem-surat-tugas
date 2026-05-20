@echo off
SETLOCAL

REM Set PHP 8.3 path
set PHP_PATH=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64

REM Add PHP 8.3 to PATH temporarily
set PATH=%PHP_PATH%;%PATH%

echo Starting Laravel server with PHP 8.3...
echo.
echo PHP: %PHP_PATH%\php.exe
echo Server: http://localhost:8000
echo.
echo Tekan Ctrl+C untuk berhenti
echo.

REM Check PHP version
php -v | findstr "PHP"

echo.
echo Starting server...
echo.

php artisan serve

ENDLOCAL
pause

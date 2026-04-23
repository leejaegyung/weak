@echo off
set PHP=C:\Users\leejk\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe
set APP=C:\weeklyrpt\app
set NGINX=C:\weeklyrpt\nginx

:: Nginx
tasklist /fi "imagename eq nginx.exe" 2>nul | find /i "nginx.exe" >nul
if %errorlevel% equ 0 (
    "%NGINX%\nginx.exe" -s reload
) else (
    start "" "%NGINX%\nginx.exe"
)

:: Laravel
cd /d "%APP%"
start "Laravel 8001" "%PHP%" artisan serve --host=127.0.0.1 --port=8001

echo.
echo  http://localhost:7700
echo  http://192.168.0.187:7700
echo  admin / admin1234
echo.
pause

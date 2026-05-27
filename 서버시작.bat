@echo off
chcp 65001 >nul
set PHP=C:\Users\leejk\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe
set APP=C:\weeklyrpt\app
set NGINX=C:\weeklyrpt\nginx

:: [1] Kill existing PHP processes
tasklist /fi "imagename eq php.exe" 2>nul | find /i "php.exe" >nul
if %errorlevel% equ 0 (
    taskkill /f /im php.exe >nul 2>&1
    timeout /t 3 /nobreak >nul
)

:: [2] Nginx start or reload
tasklist /fi "imagename eq nginx.exe" 2>nul | find /i "nginx.exe" >nul
if %errorlevel% equ 0 (
    "%NGINX%\nginx.exe" -p "%NGINX%" -s reload
) else (
    start "" "%NGINX%\nginx.exe" -p "%NGINX%"
)

:: [3] Laravel server
start "Laravel :8001" /D "%APP%" "%PHP%" artisan serve --host=127.0.0.1 --port=8001

:: [4] Laravel scheduler
start "Scheduler" /D "%APP%" "%PHP%" artisan schedule:work

:: [5] Port check - retry up to 5 times (3s each)
set RETRY=0
:WAIT
set /a RETRY+=1
timeout /t 3 /nobreak >nul
netstat -ano | find ":8001" | find "LISTENING" >nul
if %errorlevel% equ 0 goto OK
if %RETRY% lss 5 goto WAIT

echo.
echo  [ERROR] Laravel failed to start. Check "Laravel :8001" window.
echo.
pause
exit /b 1

:OK
echo.
echo  ========================================
echo   [OK] Server started!
echo   http://localhost:7700
echo   http://192.168.0.187:7700
echo  ========================================
echo.
pause

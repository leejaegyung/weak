@echo off
set PHP=C:\Users\leejk\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe
set APP=C:\weeklyrpt\app
set NGINX=C:\weeklyrpt\nginx

:: [1] 기존 PHP 프로세스 정리 (포트 충돌 방지)
tasklist /fi "imagename eq php.exe" 2>nul | find /i "php.exe" >nul
if %errorlevel% equ 0 (
    taskkill /f /im php.exe >nul 2>&1
    timeout /t 2 /nobreak >nul
)

:: [2] Nginx 시작 또는 reload
tasklist /fi "imagename eq nginx.exe" 2>nul | find /i "nginx.exe" >nul
if %errorlevel% equ 0 (
    "%NGINX%\nginx.exe" -p "%NGINX%" -s reload
) else (
    start "" "%NGINX%\nginx.exe" -p "%NGINX%"
)

:: [3] Laravel 서버 - /D 로 작업 디렉토리 명시
start "Laravel Server :8001" /D "%APP%" "%PHP%" artisan serve --host=127.0.0.1 --port=8001
timeout /t 4 /nobreak >nul

:: [4] Laravel 스케줄러 - /D 로 작업 디렉토리 명시
start "Laravel Scheduler" /D "%APP%" "%PHP%" artisan schedule:work
timeout /t 2 /nobreak >nul

:: [5] 포트 8001 리스닝 확인
netstat -ano | find ":8001" | find "LISTENING" >nul
if %errorlevel% equ 0 (
    echo.
    echo  [OK] Server started successfully!
    echo  http://localhost:7700
    echo  http://192.168.0.187:7700
    echo.
) else (
    echo.
    echo  [ERROR] Failed to start Laravel server!
    echo  Check the "Laravel Server :8001" window for errors.
    echo.
)
pause

@echo off
title Weekly Report Server

:: nginx start/reload
tasklist /fi "imagename eq nginx.exe" 2>nul | find /i "nginx.exe" >nul
if not errorlevel 1 (
    C:\weeklyrpt\nginx\nginx.exe -s reload
) else (
    start "" C:\weeklyrpt\nginx\nginx.exe
)

:: FastAPI - check python first, then run
start "FastAPI" cmd /k "cd /d C:\weeklyrpt\backend && python --version && echo Starting FastAPI... && python main.py"

echo.
echo Local  : http://localhost:7700
echo Network: http://10.92.21.64:7700
echo.
pause

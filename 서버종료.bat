@echo off
title Weekly Report Server - Stop

C:\weeklyrpt\nginx\nginx.exe -s stop 2>nul
taskkill /fi "windowtitle eq FastAPI" /f >nul 2>&1
taskkill /im python.exe /f >nul 2>&1

echo Server stopped.
pause

@echo off
chcp 65001 >nul
title 주간업무보고 서버 종료

echo  [Nginx] 종료 중...
C:\weeklyrpt\nginx\nginx.exe -p C:\weeklyrpt\nginx -s stop >nul 2>&1

echo  [Laravel] 종료 중...
taskkill /fi "windowtitle eq Laravel 8001" /f >nul 2>&1

echo  [완료] 서버가 종료되었습니다.
echo.
pause

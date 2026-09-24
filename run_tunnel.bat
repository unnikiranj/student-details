@echo off
title StudentDetails Cloudflare Tunnel
echo ==============================================
echo  Cleaning up old tunnel sessions...
echo ==============================================
taskkill /F /IM cloudflared.exe >nul 2>&1
timeout /t 1 /nobreak >nul

echo.
echo Starting Cloudflare Tunnel on port 80...
echo Connecting instantly - no CRL issues!
echo.
echo DO NOT CLOSE this window while using the app!
echo The public URL will appear below:
echo ==============================================
"%USERPROFILE%\cloudflared.exe" tunnel --url http://localhost:80 --no-autoupdate

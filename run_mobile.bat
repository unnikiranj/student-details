@echo off
echo =======================================================
echo    PVPSIT Student Portal - Mobile & Android Launcher   
echo =======================================================
echo.
echo [1] Launch Live Android App Simulator (Browser)
echo [2] Connect Physical Phone via USB (ADB Reverse Port 80)
echo [3] Install Native APK to Connected Phone (ADB Install)
echo [4] Check Connected Android Devices
echo [5] Run REST API Test Suite
echo [6] Exit
echo.
set /p choice="Select an option (1-6): "

if "%choice%"=="1" (
    echo Opening Android Web Simulator...
    start http://localhost/studentdetails/mobile_preview.php
) else if "%choice%"=="2" (
    echo Setting up USB Reverse Port Forwarding (Port 80)...
    "C:\Users\Dell\AppData\Local\Android\Sdk\platform-tools\adb.exe" reverse tcp:80 tcp:80
    echo Done! Your connected phone can now access http://localhost/studentdetails/
    pause
) else if "%choice%"=="3" (
    echo Installing Native APK to connected phone...
    "C:\Users\Dell\AppData\Local\Android\Sdk\platform-tools\adb.exe" install -r PVPSITStudentApp\app\build\outputs\apk\debug\app-debug.apk
    pause
) else if "%choice%"=="4" (
    echo Checking connected devices...
    "C:\Users\Dell\AppData\Local\Android\Sdk\platform-tools\adb.exe" devices
    pause
) else if "%choice%"=="5" (
    echo Running API Test Suite...
    php test_api_v1.php
    pause
) else (
    echo Exiting...
)

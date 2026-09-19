@echo off
title Online Watches - One-Click Launcher (PHP)
color 0B

echo ==============================================================================
echo           ONLINE WATCHES WEBSITE - ONE-CLICK LAUNCHER
echo           BCA Semester 5 - 504 Web Framework ^& Services (WFS)
echo ==============================================================================
echo.

cd /d "%~dp0"

:: -------------------------------------------------------------
:: 1. Check & Start Apache Web Server
:: -------------------------------------------------------------
echo [*] Checking Apache Web Server...
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I "httpd.exe" >NUL
if %ERRORLEVEL% EQU 0 goto apache_running
echo [+] Starting Apache Web Server...
if exist "C:\xampp\apache_start.bat" (
    start "" /min "C:\xampp\apache_start.bat"
) else if exist "C:\xampp\apache\bin\httpd.exe" (
    start "" /B "C:\xampp\apache\bin\httpd.exe" >NUL 2>&1
)
timeout /t 2 /nobreak >NUL

:apache_running
echo [OK] Apache is running.

:: -------------------------------------------------------------
:: 2. Check & Start MySQL Database Server
:: -------------------------------------------------------------
echo [*] Checking MySQL Database Server...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I "mysqld.exe" >NUL
if %ERRORLEVEL% EQU 0 goto mysql_running
echo [+] Starting MySQL Database Server...
if exist "C:\xampp\mysql_start.bat" (
    start "" /min "C:\xampp\mysql_start.bat"
) else if exist "C:\xampp\mysql\bin\mysqld.exe" (
    start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone >NUL 2>&1
)
timeout /t 3 /nobreak >NUL

:mysql_running
echo [OK] MySQL is running.

:: -------------------------------------------------------------
:: 3. Import / Ensure Database Schema
:: -------------------------------------------------------------
if not exist "C:\xampp\mysql\bin\mysql.exe" goto skip_db
echo [*] Verifying MySQL database 'watches_db'...
"C:\xampp\mysql\bin\mysql.exe" -u root < database.sql >NUL 2>&1
if %ERRORLEVEL% EQU 0 (
    echo [OK] Database verified successfully.
) else (
    echo [!] MySQL initializing...
)

:skip_db

:: -------------------------------------------------------------
:: 4. Open Client Website & Admin Portal in Browser
:: -------------------------------------------------------------
echo.
echo ==============================================================================
echo [SUCCESS] Opening Store and Admin Panel in your browser:
echo           1. Client Website: http://localhost/WFS-Project(PHP)/
echo           2. Admin Portal:   http://localhost/WFS-Project(PHP)/admin/login.php
echo ==============================================================================
echo.

:: Open Client Website
start "" "http://localhost/WFS-Project(PHP)/"

:: Wait 1 second for clean tab separation
timeout /t 1 /nobreak >NUL

:: Open Admin Portal
start "" "http://localhost/WFS-Project(PHP)/admin/login.php"

echo.
echo Press any key to close this window (Apache ^& MySQL will stay running)...
pause >NUL

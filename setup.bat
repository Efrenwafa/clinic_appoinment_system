@echo off
setlocal enabledelayedexpansion

echo =====================================================================
echo  IT 107 IAS 1 Final Project: Clinic Appointment and Monitoring System
echo  One-Click Automated Setup and Database Import Script
echo =====================================================================
echo.

:: 1. Detect MySQL executable
set "MYSQL_PATH="
if exist "C:\xampp\mysql\bin\mysql.exe" (
    set "MYSQL_PATH=C:\xampp\mysql\bin\mysql.exe"
) else (
    where mysql >nul 2>&1
    if !errorlevel! equ 0 (
        set "MYSQL_PATH=mysql"
    )
)

if "%MYSQL_PATH%"=="" (
    echo [ERROR] MySQL was not found at C:\xampp\mysql\bin\mysql.exe or in system PATH.
    echo Please make sure XAMPP is installed and MySQL is running.
    echo.
    pause
    exit /b 1
)

:: 2. Ensure config.php exists
echo [1/3] Checking configuration file...
if not exist "config.php" (
    if exist "config.sample.php" (
        copy "config.sample.php" "config.php" >nul
        echo [INFO] Created 'config.php' from 'config.sample.php'.
    ) else (
        echo [WARNING] Neither config.php nor config.sample.php was found.
    )
) else (
    echo [OK] config.php is ready.
)

:: 3. Create logs directory if missing
echo [2/3] Checking runtime logs directory...
if not exist "logs" (
    mkdir "logs"
    echo [INFO] Created 'logs/' directory.
) else (
    echo [OK] logs directory is ready.
)

:: 4. Import database
echo [3/3] Importing database into MySQL...
echo Attempting database import using root user...

set "SQL_FILE=database\full_database_dump.sql"
if not exist "!SQL_FILE!" (
    set "SQL_FILE=database\schema.sql"
)

echo Importing !SQL_FILE!...
"%MYSQL_PATH%" -u root -e "CREATE DATABASE IF NOT EXISTS clinic_appointment_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if !errorlevel! neq 0 (
    echo [WARNING] Direct login with empty password failed. If your root has a password, enter it when prompted:
    "%MYSQL_PATH%" -u root -p -e "CREATE DATABASE IF NOT EXISTS clinic_appointment_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    "%MYSQL_PATH%" -u root -p clinic_appointment_db < "!SQL_FILE!"
) else (
    "%MYSQL_PATH%" -u root clinic_appointment_db < "!SQL_FILE!"
)

if !errorlevel! equ 0 (
    echo.
    echo =====================================================================
    echo [SUCCESS] Setup completed successfully!
    echo.
    echo Available Pre-Seeded Accounts:
    echo   1. Administrator:  admin_monitor  /  Admin@2026!
    echo   2. Patient Juan:   patient_juan   /  Patient@2026!
    echo   3. Patient Maria:  patient_maria  /  Patient@2026!
    echo.
    echo Open in your browser:
    echo   http://localhost/clinic_appoinment_system
    echo =====================================================================
) else (
    echo.
    echo [ERROR] Database import encountered an issue. Please verify MySQL service is running in XAMPP.
)

echo.
pause

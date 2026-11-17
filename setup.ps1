# IT 107 IAS 1 Final Project: One-Click Setup & Migration Script
Write-Host "=====================================================================" -ForegroundColor Green
Write-Host " IT 107 IAS 1: Secure Clinic Appointment and Monitoring System" -ForegroundColor Cyan
Write-Host " Cross-Machine Setup and Migration Helper" -ForegroundColor White
Write-Host "=====================================================================" -ForegroundColor Green
Write-Host ""

# 1. Detect MySQL
$mysqlPath = $null
if (Test-Path "C:\xampp\mysql\bin\mysql.exe") {
    $mysqlPath = "C:\xampp\mysql\bin\mysql.exe"
} else {
    $found = Get-Command mysql -ErrorAction SilentlyContinue
    if ($found) { $mysqlPath = $found.Source }
}

if (-not $mysqlPath) {
    Write-Host "[ERROR] MySQL binary not found. Please install/start XAMPP." -ForegroundColor Red
    pause
    exit 1
}

# 2. Config setup
Write-Host "[1/4] Checking configuration file..." -ForegroundColor Yellow
if (-not (Test-Path "config.php")) {
    if (Test-Path "config.sample.php") {
        Copy-Item "config.sample.php" "config.php"
        Write-Host "      Created 'config.php' from 'config.sample.php'." -ForegroundColor Green
    }
} else {
    Write-Host "      'config.php' already exists and is configured." -ForegroundColor Green
}

# 3. Logs directory
Write-Host "[2/4] Ensuring runtime logs directory exists..." -ForegroundColor Yellow
if (-not (Test-Path "logs")) {
    New-Item -ItemType Directory -Path "logs" | Out-Null
    Write-Host "      Created 'logs/' directory." -ForegroundColor Green
} else {
    Write-Host "      'logs/' directory is present." -ForegroundColor Green
}

# 4. Database import
Write-Host "[3/4] Importing database schema & seed data..." -ForegroundColor Yellow
$sqlFile = if (Test-Path "database\full_database_dump.sql") { "database\full_database_dump.sql" } else { "database\schema.sql" }

try {
    & $mysqlPath -u root -e "CREATE DATABASE IF NOT EXISTS clinic_appointment_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    Get-Content $sqlFile | & $mysqlPath -u root clinic_appointment_db
    Write-Host "      Database 'clinic_appointment_db' imported successfully." -ForegroundColor Green
} catch {
    Write-Host "      Error running automated import: $_" -ForegroundColor Red
}

# 5. Run Self Test if PHP is available
Write-Host "[4/4] Verifying installation with system self-test..." -ForegroundColor Yellow
$phpPath = if (Test-Path "C:\xampp\php\php.exe") { "C:\xampp\php\php.exe" } else { (Get-Command php -ErrorAction SilentlyContinue)?.Source }
if ($phpPath) {
    & $phpPath tests/self_test.php
} else {
    Write-Host "      PHP CLI not in standard path; skipped CLI self-test." -ForegroundColor DarkYellow
}

Write-Host ""
Write-Host "=====================================================================" -ForegroundColor Green
Write-Host "SETUP COMPLETE! Open in your browser:" -ForegroundColor Cyan
Write-Host "http://localhost/clinic_appoinment_system" -ForegroundColor White
Write-Host "=====================================================================" -ForegroundColor Green

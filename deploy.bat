@echo off
REM FotoFix Deployment Script for Windows (to be run on Linux server)

echo Starting FotoFix deployment...

REM Set variables
set PROJECT_DIR=/var/www/fotofix
set BACKUP_DIR=/var/backups/fotofix
for /f "tokens=2 delims==" %%a in ('wmic OS Get localdatetime /value') do set "dt=%%a"
set "YY=%dt:~2,2%" & set "YYYY=%dt:~0,4%" & set "MM=%dt:~4,2%" & set "DD=%dt:~6,2%"
set "HH=%dt:~8,2%" & set "Min=%dt:~10,2%" & set "Sec=%dt:~12,2%"
set "DATE=%YYYY%%MM%%DD%_%HH%%Min%%Sec%"

REM Create backup directory
mkdir %BACKUP_DIR% 2>nul

REM Backup existing installation if it exists
if exist "%PROJECT_DIR%" (
    echo Backing up existing installation...
    tar -czf "%BACKUP_DIR%/fotofix_backup_%DATE%.tar.gz" -C /var/www fotofix
)

REM Create project directory
mkdir %PROJECT_DIR% 2>nul

REM Copy files (assuming we're running from the project root)
echo Copying files...
xcopy /E /I /Y . %PROJECT_DIR%\

REM Set proper permissions
echo Setting permissions...
icacls %PROJECT_DIR% /grant www-data:F /T
icacls %PROJECT_DIR% /grant www-data:(OI)(CI)F /T

REM Create required directories
echo Creating required directories...
mkdir %PROJECT_DIR%\images\temp 2>nul
mkdir %PROJECT_DIR%\images\enhanced 2>nul
mkdir %PROJECT_DIR%\images\preview 2>nul

REM Install PHP dependencies
echo Installing PHP dependencies...
cd %PROJECT_DIR%
composer install --no-dev --optimize-autoloader

echo Deployment completed successfully!
echo Please remember to:
echo 1. Update API keys in %PROJECT_DIR%\api\config.php
echo 2. Configure your web server virtual host
echo 3. Set up SSL certificate for secure payments
echo 4. Test the application thoroughly

pause

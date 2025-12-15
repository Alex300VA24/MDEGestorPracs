@echo off
title Instalador de Gestor de Voluntarios
setlocal enabledelayedexpansion

:: ==== Configuración de rutas dinámicas ==== 
set XAMPP_PATH=%~dp0\..\..
set PROJECT_PATH=%~dp0

echo ======================================
echo      Instalador de Aplicacion WEB
echo   (PHP 7.4 + XAMPP + SQL Server)
echo ======================================
echo.

:: ==== Verificar XAMPP ==== 
if not exist "%XAMPP_PATH%\xampp_start.exe" (
    echo [ERROR] No se encontró XAMPP en %XAMPP_PATH%\xampp_start.exe
    echo Asegúrate de que XAMPP esté instalado correctamente.
    pause
    exit /b
)
echo [OK] XAMPP encontrado.

:: ==== Verificar PHP ==== 
if not exist "%XAMPP_PATH%\php\php.exe" (
    echo [ERROR] PHP no existe en %XAMPP_PATH%\php\php.exe
    pause
    exit /b
)
echo [OK] PHP encontrado.

:: ==== Verificar SQLCMD ==== 
where sqlcmd >nul 2>&1
if errorlevel 1 (
    echo [ERROR] No se encontró SQLCMD.
    echo Instala Microsoft SQL Server Command Line Utilities.
    pause
    exit /b
)
echo [OK] SQLCMD encontrado.

echo.
echo ======================================
echo  CONFIGURACIÓN DE BASE DE DATOS
echo ======================================

:: ======= Pedir usuario =======
set /p DB_USER=Ingresa el usuario de SQL Server (sa/u otro): 

:: ======= Pedir contraseña con input invisible =======
echo Ingresa la contraseña (no se mostrará):
set "DB_PASS="
for /f "delims=" %%a in ('powershell -Command "$p = Read-Host -AsSecureString; $BSTR=[System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($p); [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($BSTR)"') do set DB_PASS=%%a

echo.
echo Usuario: %DB_USER%
echo Contraseña recibida correctamente.
echo.

:: ======= Inicializar XAMPP =======
echo Iniciando XAMPP...
start "" "%XAMPP_PATH%\xampp_start.exe"
timeout /t 4 >nul

:: ======= Crear base de datos =======
echo Verificando o creando base de datos...

sqlcmd -S localhost -U %DB_USER% -P %DB_PASS% -Q "IF NOT EXISTS (SELECT name FROM sys.databases WHERE name='GESTIONPRACTICANTES') CREATE DATABASE GESTIONPRACTICANTES;"
if errorlevel 1 (
    echo [ERROR] No se pudo conectar a SQL Server.
    echo Verifica usuario/clave o que SQL Server esté iniciado.
    pause
    exit /b
)

echo Base de datos lista.

:: ======= Ejecutar script SQL =======
echo Ejecutando script de inicialización...
sqlcmd -S localhost -U %DB_USER% -P %DB_PASS% -d GESTIONPRACTICANTES -i "%PROJECT_PATH%\script.sql"

echo Script ejecutado.


:: ======= Ejecutar triggers =======
echo Instalando triggers...
sqlcmd -S localhost -U %DB_USER% -P %DB_PASS% -d GESTIONPRACTICANTES -i C:\xampp7.4\htdocs\MDEGestorPracs\triggers.sql
if errorlevel 1 (
    echo [ADVERTENCIA] Hubo un problema al instalar los triggers.
    echo La base de datos se creo correctamente pero los triggers pueden no funcionar.
) else (
    echo Triggers instalados correctamente.
)

:: ======= Crear archivo .env =======
echo Creando archivo .env...
(
echo # Database Configuration
echo DB_HOST=localhost
echo DB_NAME=GESTIONPRACTICANTES
echo DB_USER=%DB_USER%
echo DB_PASS=%DB_PASS%
echo DB_PORT=1433
echo.
echo # Application Settings
echo APP_ENV=development
echo APP_DEBUG=true
echo APP_URL=http://localhost/MDEGestorPracs/public/
echo.
echo # Session Settings
echo SESSION_LIFETIME=7200
) > "%PROJECT_PATH%\.env"

echo Archivo .env generado correctamente.

:: ==== Abrir navegador ==== 
echo Abriendo aplicación...
start "" http://localhost/MDEGestorPracs/public/login

echo ======================================
echo    INSTALACIÓN COMPLETA
echo ======================================
pause
exit /b
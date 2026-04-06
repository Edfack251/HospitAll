@echo off
setlocal enabledelayedexpansion
title Configuración Completa HospitAll
color 0b

cd /d "%~dp0"

echo.
echo ============================================================
echo      HospitAll - Asistente de Configuración y Diagnóstico
echo ============================================================
echo.

:: 1. Verificar PHP
echo [1/5] Verificando entorno PHP...
set PHP_PATH=C:\xampp\php\php.exe
if not exist "%PHP_PATH%" (
    where php >nul 2>nul
    if %ERRORLEVEL% equ 0 (
        for /f "delims=" %%i in ('where php') do set PHP_PATH=%%i
    ) else (
        echo [ERROR] No se encuentra PHP. Instala XAMPP en C:\xampp o añade PHP al PATH.
        pause & exit /b 1
    )
)
echo [OK] PHP detectado: !PHP_PATH!
echo.

:: 2. Configurar .env
echo [2/5] Verificando archivo de configuración (.env)...
if not exist ".env" (
    copy .env.example .env >nul
    echo [OK] Archivo .env creado.
) else (
    echo [OK] Archivo .env ya existe.
)
echo.

:: 3. Instalar dependencias
echo [3/5] Verificando librerías (Composer)...
if not exist "vendor" (
    if exist "composer.phar" (
        "!PHP_PATH!" composer.phar install
    ) else (
        echo [ALERTA] No se encontró la carpeta 'vendor' ni 'composer.phar'. 
        echo El sistema podría fallar si faltan dependencias.
    )
) else (
    echo [OK] Librerías instaladas.
)
echo.

:: 4. Verificar Base de Datos
echo [4/5] Comprobando conexión a Base de Datos...
set /p IMPORT_DB="¿Deseas intentar importar la base de datos ahora? (S/N): "
if /i "%IMPORT_DB%"=="S" (
    echo Intentando importar db\database_schema.sql...
    C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS hospitall;"
    C:\xampp\mysql\bin\mysql.exe -u root hospitall < db\database_schema.sql
    echo [OK] Proceso de importación finalizado.
) else (
    echo [OK] Salto de importación de DB.
)
echo.

:: 5. Finalizar y Abrir
echo [5/5] ¡Configuración completada!
echo ============================================================
echo IMPORTANTE: Si el error 404 persiste, por favor LIMPIA EL CACHÉ 
echo de tu navegador o usa una VENTANA DE INCÓGNITO.
echo ============================================================
echo.
set /p OPEN_NOW="¿Deseas abrir el sistema en el navegador ahora? (S/N): "
if /i "%OPEN_NOW%"=="S" (
    start "" "http://localhost/HospitAll%%20V1/"
)

echo.
echo Gracias por usar HospitAll.
pause
exit /b 0

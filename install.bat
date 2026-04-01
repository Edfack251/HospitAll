@echo off
title Instalador de HospitAll V1
color 0b

:: Aseguramos que el punto de partida sea la carpeta donde se encuentra el script
cd /d "%~dp0"

echo.
echo ========================================================
echo    HospitAll - Instalacion y Verificacion del Sistema
echo ========================================================
echo.

set PASS_COUNT=0
set WARN_COUNT=0
set ERR_COUNT=0

:: Paso 1: Verificar PHP
echo [ 1/9 ] Verificando PHP...
where php >nul 2>nul
if %ERRORLEVEL% equ 0 set PHP_EXE=php
if defined PHP_EXE goto php_ok

if exist "C:\xampp\php\php.exe" set PHP_EXE="C:\xampp\php\php.exe"
if defined PHP_EXE goto php_ok

echo [ ERR ] ERROR: PHP no encontrado. Instala XAMPP.
set /a ERR_COUNT+=1
pause
exit /b 1

:php_ok
echo [ OK ] PHP encontrado: %PHP_EXE%
set /a PASS_COUNT+=1

echo.
:: Paso 2: Verificar Apache y MySQL
echo [ 2/9 ] Verificando Apache y MySQL...

tasklist /NH /FI "IMAGENAME eq httpd.exe" | find /I "httpd.exe" >nul
if %ERRORLEVEL% equ 0 echo [ OK ] Apache se esta ejecutando.
if %ERRORLEVEL% equ 0 set /a PASS_COUNT+=1
if %ERRORLEVEL% equ 0 goto check_mysql

echo [ ALERT ] Apache no esta corriendo. Intentando iniciar...
net start apache2.4 >nul 2>nul
tasklist /NH /FI "IMAGENAME eq httpd.exe" | find /I "httpd.exe" >nul
if %ERRORLEVEL% equ 0 echo [ OK ] Apache iniciado con exito.
if %ERRORLEVEL% equ 0 set /a PASS_COUNT+=1
if %ERRORLEVEL% neq 0 echo [ ALERT ] No se pudo iniciar Apache. Inicialo manualmente.
if %ERRORLEVEL% neq 0 set /a WARN_COUNT+=1

:check_mysql
tasklist /NH /FI "IMAGENAME eq mysqld.exe" | find /I "mysqld.exe" >nul
if %ERRORLEVEL% equ 0 echo [ OK ] MySQL se esta ejecutando.
if %ERRORLEVEL% equ 0 set /a PASS_COUNT+=1
if %ERRORLEVEL% equ 0 goto step3

echo [ ALERT ] MySQL no esta corriendo. Intentando iniciar...
net start mysql >nul 2>nul
tasklist /NH /FI "IMAGENAME eq mysqld.exe" | find /I "mysqld.exe" >nul
if %ERRORLEVEL% equ 0 echo [ OK ] MySQL iniciado con exito.
if %ERRORLEVEL% equ 0 set /a PASS_COUNT+=1
if %ERRORLEVEL% neq 0 echo [ ALERT ] No se pudo iniciar MySQL. Inicialo manualmente.
if %ERRORLEVEL% neq 0 set /a WARN_COUNT+=1

:step3
echo.
:: Paso 3: Verificar Composer
echo [ 3/9 ] Verificando dependencias (Composer)...
if exist "%~dp0vendor" echo [ OK ] Directorio vendor ya existe.
if exist "%~dp0vendor" set /a PASS_COUNT+=1
if exist "%~dp0vendor" goto step4

echo [ INFO ] Directorio vendor no encontrado. Instalando...
if exist "%~dp0composer.phar" (
    %PHP_EXE% "%~dp0composer.phar" install --no-interaction
    if %ERRORLEVEL% equ 0 echo [ OK ] Dependencias instaladas (phar).
    if %ERRORLEVEL% equ 0 set /a PASS_COUNT+=1
    if %ERRORLEVEL% equ 0 goto step4
)

where composer >nul 2>nul
if %ERRORLEVEL% equ 0 (
    composer install --no-interaction
    if %ERRORLEVEL% equ 0 echo [ OK ] Dependencias instaladas (global).
    if %ERRORLEVEL% equ 0 set /a PASS_COUNT+=1
    if %ERRORLEVEL% equ 0 goto step4
)

echo [ ERR ] Composer no encontrado o fallo instalacion.
set /a ERR_COUNT+=1
pause
exit /b 1

:step4
echo.
:: Paso 4: Configurar .env
echo [ 4/9 ] Verificando archivo .env...
if exist "%~dp0.env" echo [ OK ] Archivo .env detectado.
if exist "%~dp0.env" set /a PASS_COUNT+=1
if exist "%~dp0.env" goto step5

if exist "%~dp0.env.example" (
    copy "%~dp0.env.example" "%~dp0.env" >nul
    echo [ ALERT ] Se creo .env desde .env.example.
    echo     Edita tu configuracion de base de datos en .env
    set /a WARN_COUNT+=1
    pause
    goto step5
)

echo [ ERR ] No se encontro .env.example.
set /a ERR_COUNT+=1

:step5
echo.
:: Paso 5: Sincronizar XAMPP
echo [ 5/9 ] Sincronizando con XAMPP htdocs...
if exist "C:\xampp\htdocs" goto sync_now
echo [ ERR ] No se encontro C:\xampp\htdocs
set /a ERR_COUNT+=1
goto step6

:sync_now
powershell -ExecutionPolicy Bypass -File scripts\sync-to-xampp.ps1
echo [ OK ] Sincronizacion completada.
set /a PASS_COUNT+=1

:step6
echo.
:: Paso 6: Base de datos
echo [ 6/9 ] Base de datos...
echo [ INFO ] Si es la primera vez, importa el esquema:
echo     mysql -u root -p hospitall ^< db\database_schema.sql
set /a WARN_COUNT+=1

:step7
echo.
:: Paso 7: Directorios Uploads
echo [ 7/9 ] Creando directorios de uploads en XAMPP...
set "TARGET_DIR=C:\xampp\htdocs\HospitAll V1"
if not exist "%TARGET_DIR%" echo [ ALERT ] Carpeta destino no encontrada.
if not exist "%TARGET_DIR%" set /a WARN_COUNT+=1
if not exist "%TARGET_DIR%" goto step8

if not exist "%TARGET_DIR%\public\uploads\imagenes" mkdir "%TARGET_DIR%\public\uploads\imagenes"
if not exist "%TARGET_DIR%\public\uploads\lab_results" mkdir "%TARGET_DIR%\public\uploads\lab_results"
echo [ OK ] Directorios verificados en el destino.
set /a PASS_COUNT+=1

:step8
echo.
:: Paso 8: Verificar acceso
echo [ 8/9 ] Verificando respuesta del servidor...
curl -s -o nul -w "%%{http_code}" http://localhost/HospitAll%%20V1/public/ | find "200" >nul
if %ERRORLEVEL% equ 0 echo [ OK ] El sistema responde correctamente (200 OK).
if %ERRORLEVEL% equ 0 set /a PASS_COUNT+=1
if %ERRORLEVEL% neq 0 echo [ ALERT ] El sistema no respondio 200 OK aun.
if %ERRORLEVEL% neq 0 set /a WARN_COUNT+=1

:resumen
echo.
echo ========================================================
echo   RESUMEN: %PASS_COUNT% OK, %WARN_COUNT% ALERTAS, %ERR_COUNT% ERRORES
echo ========================================================
echo.

:: Paso 9: Abrir navegador
echo [ 9/9 ] Finalizando...
choice /c SN /m "Deseas abrir el sistema en el navegador ahora?"
if %ERRORLEVEL% equ 1 start http://localhost/HospitAll%%20V1/public/

echo.
echo Presiona cualquier tecla para salir...
pause >nul
exit /b 0

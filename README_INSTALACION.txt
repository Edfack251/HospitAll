HospitAll V1 - Guía de Instalación Rápida
================================================================================

INTRODUCCIÓN
HospitAll es un Sistema de Información Hospitalaria desarrollado en PHP 8.3.
Esta guía ayudará a que cualquier usuario con XAMPP instalado pueda tener
el sistema funcionando en minutos.

REQUISITOS MÍNIMOS
1. XAMPP (Versión con PHP 8.2 o superior, se recomienda 8.3).
2. Apache con módulo mod_rewrite activo (habilitado por defecto en XAMPP).
3. MySQL / MariaDB (puerto 3306).
4. El proyecto debe estar en su propia carpeta (ej: C:\HospitAll V1\).

PASOS PARA LA INSTALACIÓN
-------------------------
1. Ejecutar el script automático:
   Haz doble clic en el archivo `install.bat` ubicado en la raíz del proyecto.
   Este script se encargará de:
   - Verificar que PHP y XAMPP estén correctamente instalados.
   - Instalar las dependencias de Composer (si es necesario).
   - Crear el archivo de configuración `.env`.
   - Sincronizar todos los archivos a `C:\xampp\htdocs\HospitAll V1\`.

2. Configurar la base de datos:
   - Abre tu MySQL (phpMyAdmin: http://localhost/phpmyadmin).
   - Crea una base de datos llamada `hospitall`.
   - Importa el archivo `db/database_schema.sql` (esto creará tablas y datos iniciales).
   - Comando sugerido: `mysql -u root -p hospitall < db\database_schema.sql`

3. Editar el archivo de configuración:
   - Abre el archivo `.env` en la raíz del proyecto con un editor de texto.
   - Asegúrate de que las credenciales de base de datos coincidan con las tuyas:
     DB_DATABASE=hospitall
     DB_USERNAME=root
     DB_PASSWORD= (o tu contraseña si tienes una)

4. Acceder al sistema:
   - Abre tu navegador y ve a: http://localhost/HospitAll V1/public/
   - Si no hay un usuario creado, puedes insertar uno manualmente en la base de datos:
     INSERT INTO usuarios (nombre, apellido, correo_electronico, password, rol_id) 
     VALUES ('Admin', 'Sistema', 'admin@hospitall.com', '$2y$10$vI84WZ1OQC.S.OCY0G6P9.97Zz0S.X4z9rW5S7/g3Z3v1f6nI4/2m', 1);
     (La contraseña es: admin123)

SOLUCIÓN A PROBLEMAS COMUNES
----------------------------
- ERROR PHP NO ENCONTRADO:
  Asegúrate de que XAMPP esté instalado en `C:\xampp\`. Si está en otro lugar,
  añade la ruta de `php.exe` a tus variables de entorno (PATH).

- ERROR AL SINCRONIZAR:
  Si PowerShell bloquea el script de sincronización, ejecútalo como administrador
  y concede los permisos necesarios o actualice la ruta en `scripts\sync-to-xampp.ps1`.

- ERROR 404 AL ACCEDER A LA URL:
  Verifica que Apache tenga el módulo `rewrite_module` activo y que la directiva
  `AllowOverride All` esté configurada para el directorio `htdocs` en el archivo
  `httpd.conf` de Apache.

- PROBLEMAS CON COMPOSER:
  Si el comando no se encuentra disponible puedes descargar `composer.phar` 
  en la raíz del proyecto y el script lo detectara automáticamente.

--------------------------------------------------------------------------------
Para soporte técnico, contacte con el administrador del sistema.

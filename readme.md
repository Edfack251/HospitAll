# HospitAll

HospitAll es un Sistema de Información Hospitalaria (HIS) desarrollado en PHP, que gestiona el ciclo clínico completo: desde el registro de pacientes hasta la atención médica, farmacia, laboratorio, imágenes diagnósticas y facturación.

---

## Tecnologías

- **Backend**: PHP 8.3
- **Base de datos**: MySQL / MariaDB
- **Frontend**: HTML5, JavaScript, Tailwind CSS, DataTables
- **Dependencias PHP**: `dompdf` (PDF), `phpdotenv` (entorno), `phpunit` (tests)
- **Servidor**: Apache con `mod_rewrite`

---

## Arquitectura

El sistema sigue un patrón MVC modular con separación por capas:

```
/
├── index.php                  → Redirige a public/
├── public/
│   ├── index.php              → Router central y punto de entrada real
│   ├── .htaccess              → Rewrite rules
│   ├── css/                   → Estilos globales y de módulos
│   └── js/                    → JavaScript por módulo
├── app/
│   ├── Controllers/           → 22 controladores (uno por dominio)
│   ├── Services/              → 20 servicios (lógica de negocio)
│   ├── Repositories/          → 18 repositorios (acceso a datos)
│   ├── Core/
│   │   ├── Router.php         → Router centralizado
│   │   ├── Validator.php      → Validación de inputs
│   │   └── ErrorHandler.php   → Manejo de errores (JSON para API, HTML para web)
│   ├── Helpers/
│   │   ├── AuthHelper.php     → Verificación de sesión y rol
│   │   ├── CsrfHelper.php     → Tokens CSRF
│   │   ├── PrivacyHelper.php  → Enmascaramiento de cédulas (PII)
│   │   └── UrlHelper.php      → Generación de URLs absolutas
│   ├── Middlewares/           → Auth, CSRF, Role
│   ├── Policies/              → PolicyManager (control de acceso por recurso)
│   └── Config/                → Conexión PDO y configuración
├── views/
│   ├── layout/                → Header, footer, navbar compartidos
│   ├── pages/                 → 33 vistas funcionales
│   ├── admin_dashboard.php    → Dashboard de administrador
│   └── doctor_dashboard.php   → Dashboard de médico
├── db/
│   └── database_schema.sql    → Esquema completo (tablas, FK, datos iniciales, soft delete)
├── tests/                     → Tests PHPUnit
├── scripts/                   → Scripts utilitarios (sync XAMPP, test watcher)
└── tools/                     → Herramientas de desarrollo manual
```

---

## Módulos funcionales

| Módulo | Descripción |
|--------|-------------|
| **Autenticación** | Login, registro, sesión por roles, CSRF |
| **Pacientes** | CRUD completo, soft delete, portal del paciente |
| **Médicos** | CRUD, agenda, dashboard personalizado |
| **Citas médicas** | Programación con slots de tiempo, reprogramación, atención |
| **Episodios clínicos** | Agrupación de atenciones por episodio |
| **Historial clínico** | Diagnósticos, tratamientos, estudios, prescripciones |
| **Farmacia** | Inventario de medicamentos, dispensación, soft delete |
| **Laboratorio** | Órdenes, resultados, carga de PDFs |
| **Imágenes diagnósticas** | Órdenes y resultados de estudios por imagen |
| **Facturación** | Facturas multi-ítem (consultas, labs, medicamentos), pago |
| **Enfermería** | Dashboard, asignación de emergencias |
| **Emergencias** | Atención de urgencias integrada con el flujo médico |
| **Administración** | Gestión de usuarios, roles, reportes, logs de auditoría |
| **Recepción** | Dashboard de gestión de citas y pacientes |

---

## Roles del sistema

| Rol | Acceso principal |
|-----|-----------------|
| `admin` | Todo el sistema, usuarios, logs, reportes |
| `medico` | Citas, historial clínico, prescripciones, su agenda |
| `enfermera` | Dashboard de enfermería, emergencias |
| `recepcionista` | Citas, pacientes, facturación |
| `laboratorio` | Órdenes de laboratorio, carga de resultados |
| `imagenes` | Órdenes de imágenes, resultados |
| `farmacia` | Inventario, dispensación |
| `paciente` | Portal personal, sus citas e historial |

---

## Instalación

### Rápido (Script Automático)

Si tienes XAMPP instalado en la ruta por defecto (`C:\xampp`), puedes automatizar todo el proceso ejecutando:

```bash
install.bat
```

Este script verificará PHP, servicios de Apache/MySQL, instalará dependencias, creará el `.env` y sincronizará los archivos a `htdocs`.

### Manual (Paso a paso)

#### Requisitos

- PHP 8.2+ (se recomienda 8.3)
- MySQL o MariaDB
- Apache con `mod_rewrite` activo
- Composer

### 1. Activar mod_rewrite en Apache

En `httpd.conf` (XAMPP: `C:\xampp\apache\conf\httpd.conf`):

```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

En el bloque `<Directory "C:/xampp/htdocs">`:

```apache
AllowOverride All
```

### 2. Instalar dependencias

```bash
cd "C:\ruta\HospitAll V1"
composer install
```

### 3. Configurar el entorno

```bash
copy .env.example .env
```

Editar `.env`:

```env
APP_ENV=local

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hospitall
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

### 4. Crear la base de datos

```sql
CREATE DATABASE hospitall CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Importar el esquema

```bash
mysql -u root -p hospitall < db/database_schema.sql
```

El archivo `database_schema.sql` incluye tablas, relaciones FK, datos iniciales de roles y servicios, y soporte de soft delete. No se requieren pasos adicionales.

### 6. Acceder al sistema

```
http://localhost/HospitAll%20V1/public/
```

Si la carpeta tiene otro nombre, ajustar el `RewriteBase` en `public/.htaccess`.

---

## Extensiones PHP necesarias

En `php.ini`, verificar que estén activas:

```ini
extension=pdo_mysql
extension=mbstring
extension=json
extension=fileinfo
extension=openssl
```

---

## Tests

```bash
# Ejecutar una vez
composer test

# Vigilar cambios y ejecutar automáticamente
composer test:watch
```

---

## Identidad visual

| Token | Valor |
|-------|-------|
| Primario | `#007BFF` |
| Secundario | `#6C757D` |
| Acento (éxito) | `#28A745` |
| Fondo | `#F8F9FA` |
| Texto | `#212529` |

---

## Errores frecuentes

| Error | Causa probable |
|-------|----------------|
| `404 Not Found` | `mod_rewrite` desactivado, `AllowOverride` faltante o nombre de carpeta incorrecto |
| `vendor/autoload.php not found` | No se ejecutó `composer install` |
| Error de conexión a BD | Credenciales incorrectas en `.env` |
| Extension not found | Extensión desactivada en `php.ini` |
| URL incorrecta en subdirectorio | Usar siempre `UrlHelper::url()` para generar rutas |

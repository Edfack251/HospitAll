# Contexto del sistema HospitAll

Este documento sirve de referencia técnica y operativa para el asistente AI en el desarrollo y mantenimiento de HospitAll.

---

## Descripción general

HospitAll es un Sistema de Información Hospitalaria (HIS) desarrollado en PHP 8.3 con arquitectura MVC modular. Gestiona el ciclo clínico completo: registro de pacientes, citas, atención médica, prescripciones, laboratorio, imágenes, farmacia y facturación.

---

## Arquitectura técnica

### Capas

| Capa | Directorio | Responsabilidad |
|------|-----------|----------------|
| Entrada | `index.php` raíz | Redirige a `public/` con path relativo |
| Router | `public/index.php` | Define todas las rutas, aplica middlewares |
| Controllers | `app/Controllers/` | Recibe request, delega a Service, responde |
| Services | `app/Services/` | Lógica de negocio, reglas, validaciones |
| Repositories | `app/Repositories/` | Acceso a datos vía PDO |
| Helpers | `app/Helpers/` | `AuthHelper`, `CsrfHelper`, `PrivacyHelper`, `UrlHelper` |
| Middlewares | `app/Middlewares/` | `AuthMiddleware`, `CsrfMiddleware`, `RoleMiddleware` |
| Policies | `app/Policies/` | `PolicyManager` — control de acceso por recurso y rol |
| Core | `app/Core/` | `Router`, `Validator`, `ErrorHandler`, `Exceptions/` |
| Vistas | `views/pages/` | 33 vistas PHP |

### Puntos críticos

- **Generación de URLs**: siempre usar `UrlHelper::url()`. Nunca hardcodear paths. Rutas relativas rompen en subdirectorios.
- **Redirects**: siempre usar URLs absolutas desde la raíz (ej. `/HospitAll V1/public/login`), nunca relativas.
- **XAMPP**: Apache sirve desde `C:\xampp\htdocs\`. Los cambios del workspace no se reflejan hasta sincronizar con `scripts/sync-to-xampp.ps1`.
- **RewriteBase**: configurado en `public/.htaccess` para el subdirectorio. Si cambia el nombre de la carpeta, hay que ajustarlo.
- **ErrorHandler**: detecta si la petición es API (`/api/*`) y responde JSON `{status, message}`. Para web responde HTML.

---

## Base de datos

- **Motor**: MySQL / MariaDB
- **Schema**: `db/database_schema.sql` — contiene tablas, FKs, datos iniciales de roles y servicios, y soft delete.
- **Soft delete**: columna `deleted_at` en: pacientes, usuarios, medicos, medicamentos, ordenes_laboratorio, prescripciones. Implementado en `BaseRepository`.
- **Instalación nueva**: solo importar `db/database_schema.sql`. No se requieren pasos adicionales.

---

## Seguridad implementada

- **CSRF**: tokens por formulario via `CsrfHelper` / `CsrfMiddleware`
- **Autenticación**: sesiones PHP con `AuthHelper`
- **Autorización**: control por rol con `RoleMiddleware` y `PolicyManager`
- **Privacidad PII**: cédulas enmascaradas en vistas con `PrivacyHelper::maskCedula()`
- **Errores seguros**: `ErrorHandler` nunca expone stack traces ni queries SQL al cliente (CWE-209)
- **Contraseñas**: almacenadas con `password_hash()` (bcrypt)

---

## Roles del sistema

| Rol | Constante en sesión |
|-----|---------------------|
| Administrador | `admin` |
| Médico | `medico` |
| Enfermera | `enfermera` |
| Recepcionista | `recepcionista` |
| Laboratorio | `laboratorio` |
| Imágenes | `imagenes` |
| Farmacia | `farmacia` |
| Paciente | `paciente` |

Sesión: `$_SESSION['user']['role']` — usar siempre esta clave, nunca `user_role` ni `rol`.

---

## Módulos y controladores principales

| Dominio | Controller | Service | Repository |
|---------|-----------|---------|-----------|
| Autenticación | `AuthController` | `AuthService` | `AuthRepository` |
| Pacientes | `PatientsController` | `PatientsService` | `PatientRepository` |
| Médicos | `DoctorsController` | `DoctorsService` | `DoctorRepository` |
| Citas | `AppointmentsController` | `AppointmentsService` | `AppointmentRepository` |
| Episodios clínicos | `ClinicalEpisodeController` | `ClinicalEpisodeService` | `ClinicalEpisodeRepository` |
| Historial clínico | `ClinicalHistoryController` | `ClinicalHistoryService` | `ClinicalHistoryRepository` |
| Farmacia | `PharmacyController` | `PharmacyService` | `PharmacyRepository` |
| Laboratorio | `LaboratoryController` | `LaboratoryService` | `LaboratoryRepository` |
| Imágenes | `ImagingController` | `ImagingService` | `ImagingRepository` |
| Facturación | `BillingController` | `BillingService` | `BillingRepository` |
| Enfermería | `NursingController` | `NursingService` | `NursingRepository` |
| Emergencias | `EmergencyCareController` | `EmergencyCareService` | `EmergencyRepository` |
| Portal paciente | `PatientPortalController` | `PatientPortalService` | `PatientPortalRepository` |
| Usuarios | `UsersController` | `UsersService` | `UserRepository` |
| Dashboard admin | `AdminDashboardController` | `AdminDashboardService` | `DashboardRepository` |
| Reportes | `AdminReportController` | `AdminReportService` | — |
| Logs | `LogController` | `LogService` | `LogRepository` |
| Flujo paciente | `PatientFlowController` | — | — |
| Prescripciones | `PrescriptionsController` | `PrescriptionService` | `PrescriptionRepository` |

---

## Reglas de negocio críticas

1. **Citas**: toda cita debe estar vinculada a un paciente y médico existente. No se permiten slots solapados para el mismo médico.
2. **Facturación**: `BillingService` es el único responsable de crear y modificar facturas. Ningún otro Service debe insertar en `factura_detalle` directamente.
3. **Precios**: nunca hardcodear. Siempre consultar la tabla `servicios`.
4. **Episodios clínicos**: las atenciones se agrupan en episodios. Un episodio se genera antes de la primera atención del encuentro.
5. **Soft delete**: los registros soft-deleted no deben aparecer en listados normales. Usar `BaseRepository::getAll()` que filtra `deleted_at IS NULL`.
6. **Schema**: todo cambio en la BD debe reflejarse en `database_schema.sql` antes de ejecutar queries.
7. **Decisiones de negocio**: nunca tomar decisiones de dominio sin consultar al owner del proyecto.

---

## Lecciones aprendidas

- **Búsqueda proactiva**: antes de asumir que una herramienta no está disponible (ej. MySQL CLI), buscarla en rutas comunes como XAMPP.
- **Sentence case**: en textos de UI y documentación, solo la primera palabra en mayúscula salvo nombres propios o acrónimos.
- **Plan primero**: nunca iniciar implementación sin presentar un plan en `tasks/todo.md` y obtener aprobación explícita del usuario.
- **Separación de dominios**: `AppointmentsService` no contiene lógica financiera. La delega a `BillingService`.
- **Sin magic numbers**: precios y constantes de negocio siempre desde la base de datos.
- **Integridad del schema**: todo cambio en BD va primero a `database_schema.sql`.
- **Ubicación real en XAMPP**: el workspace está en `c:\HospitAll V1\`. Apache sirve desde `C:\xampp\htdocs\HospitAll V1\`. Siempre sincronizar antes de probar.
- **Redirects en subdirectorios**: usar URLs absolutas desde la raíz del servidor. Nunca `header("Location: login")` relativo.
- **UrlHelper**: usar `UrlHelper::url()` para todo redirect y `action` de formulario.
- **RewriteBase en .htaccess**: requerido cuando la app está en un subdirectorio.

---

## Comandos útiles

```bash
# Instalar dependencias
composer install

# Ejecutar tests
composer test

# Vigilar cambios y correr tests automáticamente
composer test:watch

# Sincronizar con XAMPP
powershell -ExecutionPolicy Bypass -File scripts\sync-to-xampp.ps1
```

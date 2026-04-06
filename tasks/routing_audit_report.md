# Reporte de Auditoría de Arquitectura de Routing - HospitAll V1

## Resumen del Análisis
Se ha evaluado la estructura de enrutamiento actual del proyecto, la cual presenta un sistema híbrido:
1. **Router centralizado (`app/Core/Router.php`)**: Funcional y capaz de gestionar rutas apuntando a Controllers y ejecutando Middlewares.
2. **Endpoints legacy (`public/api/*.php`)**: 15 archivos que actúan como receptores de formularios y peticiones de la API.

La gran mayoría de los endpoints legacy funcionan simplemente como "wrappers": instancian un Controller existente y delegan la ejecución. Sin embargo, algunos archivos aún contienen lógica de negocio directa (SQL o llamadas a Services sin pasar por un Controller).

## Inventario de Endpoints Legacy (`public/api/`)

| Endpoint | Lógica que ejecuta | Delegación actual | Controller/Service Recomendado para Migración |
|----------|--------------------|-------------------|-----------------------------------------------|
| `appointments.php` | Wrapper (CSRF, Auth) -> `AppointmentsController@schedule` | `AppointmentsController` | Migrable al router directo a `AppointmentsController@schedule` |
| `appointments_save_attention.php` | Wrapper (CSRF, Auth) -> `AppointmentsController@saveAttention` | `AppointmentsController` | Migrable al router directo a `AppointmentsController@saveAttention` |
| `billing_create_api.php` | Wrapper (CSRF, Auth) -> `BillingController@createInvoice` | `BillingController` | Migrable al router directo a `BillingController@createInvoice` |
| `billing_pay_api.php` | Wrapper (CSRF, Auth) -> `BillingController@pay` | `BillingController` | Migrable al router directo a `BillingController@pay` |
| **`doctors.php`** | **Lógica Directa** (INSERT medicos con PDO) | **Ninguna** | **Crear** `DoctorsController@store` y delegar al central. |
| **`doctors_edit.php`** | **Lógica Directa** (UPDATE medicos con PDO) | **Ninguna** | **Crear** `DoctorsController@update` y delegar al central. |
| **`laboratory_bill_api.php`** | **Lógica Directa** (Transacción PDO + `BillingService`) | `BillingService` | Debería ser movido a `LaboratoryController@bill` o `BillingController`. |
| `laboratory_upload_result.php` | Wrapper (CSRF) -> `LaboratoryController@uploadResult` | `LaboratoryController` | Migrable al router directo a `LaboratoryController@uploadResult` |
| `login.php` | Wrapper (CSRF) -> `AuthController@login` | `AuthController` | Migrable al router directo a `AuthController@login` |
| **`logs_export.php`** | **Lógica Directa** (CSV Output + `LogService`) | `LogService` | **Crear** `LogController@export` y mover la lógica de generación del CSV allí. |
| `patients_api.php` | Wrapper (CSRF, Auth) -> `PatientsController@create` | `PatientsController` | Migrable al router directo a `PatientsController@create` |
| `patients_edit_api.php` | Wrapper (CSRF, Auth) -> `PatientsController@update` | `PatientsController` | Migrable al router directo a `PatientsController@update` |
| `pharmacy_dispense_api.php` | Wrapper (CSRF, Auth) -> `PharmacyController@dispense` | `PharmacyController` | Migrable al router directo a `PharmacyController@dispense` |
| `pharmacy_process_dispense.php` | Wrapper (CSRF, Auth) -> `PharmacyController@processDispense` | `PharmacyController` | Migrable al router directo a `PharmacyController@processDispense` |
| `register.php` | Wrapper (CSRF) -> `RegisterController@register` | `RegisterController` | Migrable al router directo a `RegisterController@register` |

## Plan de Migración Recomendado

La migración debe realizarse de forma progresiva (fase por fase) para asegurar que el sistema no se rompa:

### Fase 1: Creación de Middlewares para el Router
Como los archivos legacy realizan validaciones de `AuthHelper` y `CsrfHelper`, estas validaciones deben incorporarse al sistema de rutas.
1. Crear/Adaptar `CsrfMiddleware` para inyectarlo en todas las rutas POST del `Router`.
2. Crear/Adaptar `AuthMiddleware` (y un `RoleMiddleware` parametrizado) para reemplazar `AuthHelper::requireLogin()` y `AuthHelper::checkRole()`.

### Fase 2: Migración de Endpoints Directos (Los que no tienen Controller)
1. **Módulo Médicos**: Crear `DoctorsController` y `DoctorsService`. Mover la lógica de `doctors.php` y `doctors_edit.php` (INSERT y UPDATE) a estos archivos y enrutarlos en `index.php`.
2. **Módulo Facturación Lab**: Mover la lógica transaccional de `laboratory_bill_api.php` al `BillingController` (o `LaboratoryController`).
3. **Módulo Auditoría**: Crear `LogController` e implementar la acción `export()` para mover la generación del CSV de `logs_export.php`.

### Fase 3: Enrutamiento de los Wrappers Existentes
1. Añadir todas las rutas POST en `index.php` usando la instancia del `Router`. Ejemplo:
   `$router->add('/api/appointments', 'AppointmentsController@schedule', ['AuthMiddleware', 'CsrfMiddleware']);`
2. Modificar los formularios HTML en las vistas (`views/`) para que apunten a las nuevas URLs (`/api/appointments`) en lugar de los archivos `.php` antiguos.

### Fase 4: Limpieza
1. Eliminar completamente el directorio `public/api/` una vez que todas las llamadas estén enrutadas por el `index.php`.

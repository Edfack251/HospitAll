# HospitAll Tasks

## Test automation

Tests run automatically via:
- **Rule Cursor** (`.cursor/rules/hospitall-tests.mdc`): el asistente ejecuta PHPUnit tras modificar código.
- **Watcher manual**: `composer test:watch` o `powershell -ExecutionPolicy Bypass -File scripts\watch-and-test.ps1`

Comandos:
- `composer test` o `C:\xampp\php\php.exe vendor\bin\phpunit` – ejecutar pruebas una vez.
- `composer test:watch` – vigilar cambios en `app/`, `tests/`, `config/`, `public/api/` y ejecutar PHPUnit al guardar.

---

# Bug Fixing and Synchronization Plan

## Issues to Fix
1. 404 Not Found on patient history (doctor profile).
2. Blank page on Clinical Flow.
3. 404 Not Found on invoices (receptionist portal) and "Unexpected Error" on confirm payment.
4. "Unexpected Error" on PDF upload (laboratory).
5. "Unexpected Error" on viewing PDFs (laboratory).
6. 404 Not Found on new appointment scheduling.

## Analysis
- **Outdated XAMPP**: The XAMPP server has outdated Controller and Service files (missing `handle*` methods).
- **Redundant index.php**: There is an `index.php` at the root that might be interfering with non-api routes.
- **Routing Inconsistency**: Some links might be missing `UrlHelper` or pointing to incorrect paths.

## Steps
- [ ] **Phase 1: Synchronization**
    - [x] Analyze discrepancy between workspace and XAMPP.
    - [ ] Sync `app/`, `views/`, `public/`, and `index.php` to `C:\xampp\htdocs\HospitAll V1\`.
    - [ ] Add a temporary redirect or remove logic from the root `index.php` if it's blocking valid `public/` routes.
- [ ] **Phase 2: Fix 404 Errors**
    - [ ] Verify `patient_portal` and `appointments_schedule` routes in `public/index.php`.
    - [ ] Ensure all links in `patients.php` and `dashboard.php` use `UrlHelper::url()`.
    - [ ] Check `patient_portal.php` authorization logic (the `allowed_patient_id` session issue).
- [ ] **Phase 3: Fix "Unexpected Error" (Issue 3, 4, 5)**
    - [ ] Verify that `BillingController@handlePay` and `LaboratoryController@handleUpload` exist and work after sync.
    - [ ] Check PDF viewing logic in `laboratory.php`.
- [ ] **Phase 4: Fix Blank Clinical Flow (Issue 2)**
    - [ ] Debug `patient_flow_dashboard.php` and `PatientFlowController@getData`.
- [ ] **Verification**
    - [ ] Test all reported flows using a browser or curl if possible.

---

# Plan de mejoras técnicas (análisis consolidado)

Plan que integra las recomendaciones de ambos análisis (este asistente + IA previa). Priorizado por impacto y dependencias.

---

## Fase 0: Estabilización (precondición)

*Debe completarse antes de refactorizaciones mayores.*

- [x] **0.1 Sincronizar con XAMPP**
    - [x] Script `scripts/sync-to-xampp.ps1`
    - [ ] Verificar DocumentRoot apunta a `public/` o que la raíz redirige correctamente
- [x] **0.2 Unificar punto de entrada**
    - [x] Raíz `index.php` → redirect 302 a `public/` + path
    - [ ] DocumentRoot → `public/` (recomendado)
- [x] **0.3 Unificar rutas API**
    - [x] handle* en Pharmacy, Users; rutas restore/logs/patient-portal/admin en public/index.php
    - [x] Eliminar inconsistencia create vs handleCreate
- [ ] **0.4 Resolver bugs reportados**
    - [ ] 404 historial paciente, facturas, citas
    - [ ] Página en blanco en Clinical Flow
    - [ ] "Unexpected Error" en PDF upload/view y confirmación de pago

---

## Fase 1: Infraestructura y arquitectura

- [ ] **1.1 Inyección de dependencias (DI)**
    - [ ] Introducir contenedor simple (p. ej. Pimple o clase Container mínima)
    - [ ] Registrar factories para Services y Repositories
    - [ ] Controllers reciben dependencias por constructor (no `new Service`)
    - [ ] Facilita mocks en tests
- [ ] **1.2 Reducir estado global**
    - [ ] Crear `UserSession` (o `AuthContext`) con datos de sesión
    - [ ] Pasar `UserSession` a Services que necesiten contexto
    - [ ] Evitar `global $pdo` en vistas; inyectar o usar helper
- [ ] **1.3 Objetos Request / Response**
    - [ ] Clase `Request` que encapsule `$_POST`, `$_GET`, headers
    - [ ] Clase `Response` para respuestas (JSON/HTML/redirect)
    - [ ] Controllers usan `Request`/`Response` en lugar de superglobales

---

## Fase 2: Errores y seguridad

- [x] **2.1 Manejo de errores por tipo de petición**
    - [x] ErrorHandler detecta si la petición es API (`/api/*`)
    - [x] API → JSON `{status, message}` con código 500
    - [x] Web → vista HTML de error (como antes)
- [ ] **2.2 Unificar manejo de excepciones**
    - [ ] Un solo flujo: try/catch central o middleware de excepciones
    - [ ] Eliminar `die()` en `index.php` raíz; usar ErrorHandler
- [ ] **2.3 Cifrado de datos en reposo (PII)**
    - [ ] Evaluar cifrado AES-256 para columnas sensibles (cédula, teléfono, dirección)
    - [ ] Cifrar/descifrar en capa Service
    - [ ] Mantener PrivacyHelper para enmascaramiento en vistas
    - [ ] Documentar decisiones de implementación

---

## Fase 3: Consistencia y estándares

- [ ] **3.1 Estandarizar sesión y roles**
    - [ ] Unificar `user_role` vs `user['role']` y `rol` vs `role`
    - [ ] Definir convención y aplicarla (p. ej. solo `$_SESSION['user']['role']`)
    - [ ] Actualizar PolicyManager, AuthHelper, vistas
- [ ] **3.2 Estrategia de Repositories**
    - [ ] Documentar qué entidades usan soft delete
    - [ ] Repos que necesiten soft delete → extender BaseRepository
    - [ ] Repos sin soft delete → documentar motivo o evaluar inclusión
- [ ] **3.3 Consistencia de idioma en código**
    - [ ] Elegir idioma base: inglés (código) / español (dominio)
    - [ ] Crear guía de nombres (variables, columnas, métodos)
    - [ ] Refactorizar gradualmente nombres inconsistentes

---

## Fase 4: Calidad y mantenibilidad

- [ ] **4.1 Validación centralizada**
    - [ ] Usar `Validator` en todos los endpoints que reciben input
    - [ ] Definir reglas por dominio (paciente, cita, factura, etc.)
- [ ] **4.2 Type hints y PHPDoc**
    - [ ] Tipar parámetros y retornos en Services y Repositories
    - [ ] PHPDoc en métodos públicos
- [ ] **4.3 Tests**
    - [ ] Tests unitarios para Services (con mocks tras DI)
    - [ ] Tests de integración para Controllers
    - [ ] Aumentar cobertura según prioridad de dominio
- [ ] **4.4 Logging estructurado**
    - [ ] Definir formato (p. ej. JSON) para entornos de producción
    - [ ] Considerar integración con herramientas de monitoreo

---

## Fase 5: Router y routing (opcional)

- [ ] **5.1 Parámetros dinámicos**
    - [ ] Soporte para rutas tipo `/patients/:id`
- [ ] **5.2 Métodos HTTP**
    - [ ] Diferenciar GET/POST/PUT/DELETE donde aplique

---

## Dependencias entre fases

```
Fase 0 (Estabilización) ──► obligatoria para todo
        │
        ▼
Fase 1 (DI, Request/Response) ──► base para Fase 4.3 (tests)
        │
        ▼
Fase 2 (Errores, seguridad)
        │
        ▼
Fase 3 (Consistencia)
        │
        ▼
Fase 4 (Calidad)
        │
        ▼
Fase 5 (Router) ──► independiente, puede hacerse en paralelo
```

---

## Resumen de prioridades

| Prioridad | Item | Origen |
|----------|------|--------|
| P0 | Unificar entry point y rutas API | Análisis 1 |
| P0 | Sincronizar con XAMPP, resolver bugs 404/errores | Análisis 1 |
| P1 | Inyección de dependencias | Ambos |
| P1 | Reducir estado global / UserSession | Análisis 2 |
| P1 | Errores API → JSON | Ambos |
| P2 | Cifrado PII en reposo | Análisis 2 |
| P2 | Consistencia sesión/roles e idioma | Ambos |
| P3 | Request/Response, Validator, tests, logging | Ambos |

---

# Plan de simplificación del proyecto

Objetivo: eliminar archivos innecesarios y código redundante sin afectar la funcionalidad.

---

## Fase A: Archivos candidatos a eliminar

### A.1 Vistas no usadas (riesgo bajo)

- [x] Eliminar `views/pages/patient_portal_fixed.php`
    - Versión alternativa de patient_portal; no referenciada en rutas.
- [x] Eliminar `views/pages/verify_psr4.php`
    - Script temporal de verificación PSR-4; sin rutas ni referencias.

### A.2 Scripts de desarrollo / debug (riesgo bajo)

- [x] Eliminar `public/debug.php`
    - Muestra SCRIPT_NAME y REQUEST_URI; solo útil para debug. No dejar en producción.
- [x] Mover `verify_flow.php` a `tools/verify_flow.php`
    - Script manual de flujo de citas. Si se conserva: mover a `tools/` o `scripts/`.
- [ ] Mover o eliminar `tools/test_policies.php`
    - Verificación manual de PolicyManager. Útil en desarrollo; opcional.

### A.3 Scripts de prueba manual en `tests/` (riesgo medio)

- [x] Eliminar `tests/test_soft_delete.php`
- [x] Eliminar `tests/test_restore.php`
- [x] Eliminar `tests/verify_patient_dashboard.php`

Nota: no son tests PHPUnit (no extienden TestCase); PHPUnit los ignora. Eliminarlos reduce ruido o, mejor, migrarlos a tests formales.

---

## Fase B: Código y componentes a evaluar

### B.1 Validator obligatorio en servicios

- [x] Reglas `numeric` y `date` en Validator
- [x] PatientsService::create, AuthService::registerPatient, ClinicalEpisodeService::generateEpisode
- [x] AppointmentsService::schedule, UsersService::create, UsersService::update

### B.2 Migraciones SQL

- [x] Crear `db/README.md` con orden de ejecución y dependencias FK.
- [x] Documentar: `database_schema.sql` → `add_soft_delete_20260312.sql` → opcional `migration.sql`.
- [ ] Mantener: `database_schema.sql`, `add_soft_delete_20260312.sql`, `migrate_phase2.sql`, `migration.sql`, `init_billing.sql` (si no están integrados en el esquema principal).

---

## Fase C: No eliminar

Elementos necesarios para el funcionamiento:

- Vistas referenciadas en rutas.
- `app/` (Controllers, Services, Repositories, Core, Helpers, Middlewares, Policies).
- `index.php`, `public/index.php`.
- `vendor/`, `composer.json`, `phpunit.xml`.
- `tests/bootstrap.php`, `tests/Integration/PatientsServiceTest.php`.
- `scripts/sync-to-xampp.ps1`, `scripts/watch-and-test.ps1`.
- `tasks/`, `.cursor/rules/`.

---

## Orden sugerido de ejecución

1. Fase A.1 (vistas no usadas)
2. Fase A.2 (scripts de debug)
3. Fase A.3 (scripts manuales en tests) – verificar PHPUnit tras cambios
4. Fase B (Validator y SQL) – evaluación, sin eliminación automática

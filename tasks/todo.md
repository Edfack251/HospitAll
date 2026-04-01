# Tareas de Verificación

- [x] Verificación 1: Buscar ruta "doctor/dashboard" en `public/index.php` <!-- id: 0 -->
- [x] Verificación 2: Comprobar existencia de `views/pages/doctor_dashboard.php` y ausencia de `views/doctor_dashboard.php` <!-- id: 1 -->
- [x] Verificación 3: Identificar carga de vista en `DoctorDashboardController.php` <!-- id: 2 -->
- [x] Verificación 4: Trazar flujo completo (AuthController -> index.php -> Controller -> Vista) <!-- id: 3 -->

## Notas de Revisión
- Verificación 1: La ruta `/api/doctor/dashboard` está registrada en la línea 201 de `public/index.php`.
- Verificación 2: La vista existe en `views/pages/doctor_dashboard.php` y no en la ubicación antigua.
- Verificación 3: el controlador invoca la vista desde `views/pages/doctor_dashboard.php` (línea 33).
- Verificación 4: El flujo es completo y consistente; `AuthController` redirige a la ruta exacta registrada que usa el controlador correcto para cargar la vista existente.

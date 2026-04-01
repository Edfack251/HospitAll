# Diagnóstico de Loop en Perfil Médico

- [x] Verificación 1: Mostrar las primeras 15 líneas de `views/pages/doctor_dashboard.php` <!-- id: 0 -->
- [x] Verificación 2: Revisar permisos de 'medico' en `PolicyManager.php` <!-- id: 1 -->
- [x] Verificación 3: Analizar código completo de `AuthHelper::checkRole()` <!-- id: 2 -->
- [x] Verificación 4: Simular el request con script PHP <!-- id: 3 -->
- [x] Verificación 5: Identificar destino de redirección en caso de falla de `checkRole()` <!-- id: 4 -->

## Notas de Revisión
1. **Contenido de la vista:** En la línea 6 de `doctor_dashboard.php`, se invoca `AuthHelper::checkRole(['medico', 'administrador']);`.
2. **PolicyManager vs in_array:** `PolicyManager` es insensible a mayúsculas (`strtolower`), pero `AuthHelper::checkRole` usa `in_array`, que sí es sensible.
3. **El culpable:** Si la base de datos devuelve "Medico" (con mayúscula), `checkRole` falla (`in_array("Medico", ["medico", "administrador"])` es false).
4. **Consecuencia:** `checkRole` redirige a `login?error=unauthorized`. Si el login redirige de vuelta al dashboard (loop).
5. **Simulación:** Confirmado que `in_array` falla con "Medico", mientras que `PolicyManager` aprueba.

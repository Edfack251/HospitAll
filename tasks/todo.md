# Fase 2: Gestión administrativa de usuarios y roles

- [ ] **Activación del sistema de roles**
    - [ ] Asegurar que la tabla `roles` contenga exactamente los roles: administrador, medico, recepcionista, tecnico_laboratorio, paciente.
    - [ ] Verificar que cada usuario esté asociado a un único rol.
- [ ] **Ajuste de controladores básicos**
    - [ ] Modificar `AuthController.php` para implementar la redirección post-login basada en roles.
    - [ ] Modificar `RegisterController.php` para forzar la asignación del rol `paciente` y asegurar la vinculación correcta.
- [ ] **Gestión de usuarios para administradores**
    - [ ] Crear la vista `public/users.php` para listar y buscar usuarios.
    - [ ] Crear la vista `public/users_create.php` para la creación manual de usuarios administrativos y médicos.
    - [ ] Crear la vista `public/users_edit.php` para la edición de perfiles y roles.
- [ ] **Control de acceso y navegación**
    - [ ] Implementar la visualización condicional de enlaces en el sidebar (`views/layout/header.php`) según el rol del usuario.
    - [ ] Añadir validaciones de sesión y rol al inicio de cada archivo protegido para prevenir accesos no autorizados.
- [ ] **Validación final**
    - [ ] Probar el flujo completo de login y redirección para cada uno de los 5 roles.
    - [ ] Verificar que las restricciones de acceso funcionen correctamente (ej. paciente intentando entrar a laboratorio).

## Revisión
*Plan en espera de aprobación*

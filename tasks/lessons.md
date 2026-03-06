# Lecciones aprendidas

- **Búsqueda proactiva de herramientas**: Antes de asumir que una herramienta como MySQL no está disponible, debo buscar el binario en rutas comunes (como XAMPP) para permitir acceso directo a la base de datos sin depender de scripts externos.
- **Cumplimiento de sentence case**: Debo asegurar que en listas y títulos solo la primera palabra esté en mayúscula, evitando el uso de mayúsculas en palabras secundarias a menos que sean nombres propios o acrónimos.
- **Planificación obligatoria**: Nunca debo iniciar la fase de implementación sin antes haber presentado un plan detallado en `tasks/todo.md` y haber obtenido la aprobación explícita del usuario, especialmente en tareas no triviales que afecten la arquitectura o múltiples archivos.
- **Separación de responsabilidades**: Nunca mezclar dominios ajenos. Por ejemplo, `AppointmentsService` (Dominio médico) no debe contener lógica financiera, sino delegarla al `BillingService`.
- **Evitar magic numbers**: Precios y constantes de negocio nunca deben estar hardcodeados. Siempre consultar a base de datos (ej. tabla `servicios`).
- **Integridad del esquema**: Todo cambio en bd (roles, tablas) debe plasmarse en `database_schema.sql` antes de correr queries huérfanos.
- **Consultas de negocio**: No tomar decisiones de negocio libremente sin presentarlo primero al owner.

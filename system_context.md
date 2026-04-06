# Referencia del Sistema HospitAll

Este documento sirve como contexto técnico y funcional para el desarrollo y mantenimiento del sistema HospitAll.

## Resumen del Proyecto
HospitAll es un Sistema de Información Hospitalaria (HIS) desarrollado para gestionar el ciclo de vida básico de las citas médicas, integrando la administración de pacientes, médicos y usuarios del sistema.

### Arquitectura Técnica
El sistema implementa una arquitectura modular con separación clara de responsabilidades:
- **Punto de Entrada**: `index.php` raíz redirige a la capa pública.
- **Capa Pública (`public/`)**: Contiene las vistas y la API de procesamiento.
- **Capa de Aplicación (`app/`)**: Centraliza la lógica de control, modelos y configuración.
- **Capa de Presentación (`views/`)**: Layouts compartidos para consistencia visual.
- **Capa de Datos (`db/`)**: Esquema centralizado de SQL.

## Entidades Principales

### Pacientes
Almacena los datos personales y de contacto de las personas que reciben atención.
- **Campos**: nombre, apellido, fecha_nacimiento, genero, direccion, telefono, correo_electronico.

### Médicos
Profesionales de la salud registrados en el sistema.
- **Campos**: nombre, apellido, especialidad, telefono, correo_electronico.

### Citas Médicas
Relacionan a un paciente con un médico en un horario específico.
- **Campos**: Fecha, Hora, Médico, Paciente, Estado.
- **Estados posibles**: Pendiente, Completada, Cancelada.

### Usuarios y Autenticación
Gestión de acceso al sistema basada en roles.
- **Campos**: nombre, apellido, correo_electronico, password (hash), rol_id.

## Stack Tecnológico
- **Frontend**: HTML5, Tailwind CSS, JavaScript, DataTables.
- **Backend**: PHP 8.3.
- **Base de Datos**: MySQL.

## Identidad Visual
- **Primario**: `#007BFF` (Azul informativo)
- **Secundario**: `#6C757D` (Gris funcional)
- **Acento**: `#28A745` (Verde éxito)
- **Fondo**: `#F8F9FA` (Blanco hueso)
- **Texto**: `#212529` (Gris oscuro)

## Reglas de Negocio Críticas
1.  **Integridad**: Cada cita debe estar vinculada obligatoriamente a un médico y un paciente existente.
2.  **Validación**: No se deben permitir citas solapadas para el mismo médico en el mismo horario.
3.  **Seguridad**: El acceso a las funcionalidades de administración está restringido por el rol del usuario.

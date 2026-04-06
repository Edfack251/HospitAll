# Proyecto de gestión de hospital

El proyecto consiste en la gestión de citas médicas y la administración básica de información hospitalaria.

# Descripción del proyecto

Este proyecto se llama HospitAll.

HospitAll es un Sistema de Información Hospitalaria (HIS) diseñado para gestionar citas médicas, pacientes, médicos y usuarios, aplicando control de acceso por roles y una estructura modular que permita su crecimiento futuro.

# Objetivo del proyecto

El objetivo del proyecto es gestionar las citas médicas de los pacientes, centralizando la información clínica básica y facilitando la organización de los procesos hospitalarios de forma segura y eficiente.

# Funcionalidades

1. Gestión de citas

   Pacientes
   - Nombre
   - Apellido
   - Fecha de nacimiento
   - Género
   - Dirección
   - Teléfono
   - Correo electrónico

   Médicos
   - Nombre
   - Apellido
   - Especialidad
   - Teléfono
   - Correo electrónico

   Citas
   - Fecha
   - Hora
   - Médico
   - Paciente
   - Estado

   Autenticación
   - Login
   - Registro

   Usuarios
   - Nombre
   - Apellido
   - Correo electrónico
   - Contraseña
   - Rol

# Tecnologías a utilizar

- PHP 8.3
- MySQL
- HTML
- Tailwind CSS
- JavaScript
- DataTables: https://datatables.net/

# Arquitectura del Proyecto

El sistema sigue una estructura modular para separar la lógica, las vistas y el acceso público:

- **`/` (Raíz)**: Contiene el punto de entrada principal (`index.php`) que gestiona el inicio del sistema.
- **`public/`**: Carpeta para archivos accesibles desde el navegador.
    - `api/`: Endpoints para operaciones asincrónicas y procesamiento de formularios.
    - Pantallas de gestión (pacientes, médicos, citas, dashboard).
- **`app/`**: Lógica interna del sistema.
    - `controllers/`: Manejo de flujo y autenticación.
    - `models/`: Definición de entidades y lógica de datos.
    - `config/`: Configuración global y conexión a DB.
- **`views/`**: Componentes de interfaz.
    - `layout/`: Plantillas reutilizables (cabecera y pie de página).
- **`db/`**: Definición del esquema de la base de datos.

# Estilos

- Color primario: #007BFF
- Color secundario: #6C757D
- Color de acento: #28A745
- Color de fondo: #F8F9FA
- Color de texto: #212529

# DB conexión

DB_CONNECTION=mysql  
DB_HOST=127.0.0.1  
DB_PORT=3306  
DB_DATABASE=hospitall  
DB_USERNAME=root  
DB_PASSWORD=

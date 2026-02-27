-- Estructura de la base de datos para HospitAll

CREATE DATABASE IF NOT EXISTS hospitall CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hospitall;

-- Tabla de Roles
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de Usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    correo_electronico VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabla de Pacientes
CREATE TABLE pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNIQUE,
    identificacion VARCHAR(20) NOT NULL UNIQUE,
    identificacion_tipo VARCHAR(50) DEFAULT 'Cédula',
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    genero ENUM('Masculino', 'Femenino', 'Otro') NOT NULL,
    direccion TEXT,
    telefono VARCHAR(20),
    correo_electronico VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabla de Médicos
CREATE TABLE medicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    especialidad VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    correo_electronico VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla de Citas
CREATE TABLE citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    medico_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    estado ENUM('Programada', 'Confirmada', 'En espera', 'Atendida', 'Cancelada', 'No asistió') DEFAULT 'Programada',
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de Historial Clínico
CREATE TABLE historial_clinico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cita_id INT NOT NULL,
    paciente_id INT NOT NULL,
    medico_id INT NOT NULL,
    diagnostico TEXT,
    tratamiento TEXT,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de Órdenes de Laboratorio
CREATE TABLE ordenes_laboratorio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    historial_id INT NOT NULL,
    descripcion TEXT,
    estado ENUM('Pendiente', 'Completada') DEFAULT 'Pendiente',
    resultado TEXT,
    archivo_pdf VARCHAR(255),
    fecha_resultado DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (historial_id) REFERENCES historial_clinico(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de Auditoría (Logs)
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    accion VARCHAR(100) NOT NULL,
    modulo VARCHAR(100) NOT NULL,
    descripcion TEXT,
    ip VARCHAR(45),
    nivel ENUM('INFO', 'WARNING', 'ERROR') DEFAULT 'INFO',
    metodo_http VARCHAR(10),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Inserción de Roles por defecto
INSERT INTO roles (nombre, descripcion) VALUES 
('Administrador', 'Acceso total al sistema'),
('Recepcionista', 'Gestión de citas y pacientes'),
('Medico', 'Atención médica y agenda'),
('Tecnico_laboratorio', 'Gestión de órdenes y resultados de laboratorio'),
('Paciente', 'Acceso a su portal personal');

-- Usuario administrador inicial (password: admin123 - el hash debe generarse en la app)
-- INSERT INTO usuarios (nombre, apellido, correo, password, rol_id) VALUES ('Admin', 'Sistemas', 'admin@hospitall.com', '$2y$10$EXAMPLE_HASH', 1);

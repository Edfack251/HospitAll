-- Estructura de la base de datos para HospitAll

CREATE DATABASE IF NOT EXISTS hospitall CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hospitall;

-- 1. Tabla de Roles
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tabla de Usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    correo_electronico VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_usuarios_deleted_at ON usuarios(deleted_at);

-- 3. Tabla de Pacientes
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
    deleted_at DATETIME NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_pacientes_deleted_at ON pacientes(deleted_at);

-- 4. Tabla de Pacientes_Identidad (Datos Sensibles)
CREATE TABLE pacientes_identidad (
    paciente_id INT PRIMARY KEY,
    lugar_nacimiento VARCHAR(100),
    estado_civil ENUM('Soltero', 'Casado', 'Divorciado', 'Viudo', 'Unión Libre') DEFAULT 'Soltero',
    ocupacion VARCHAR(100),
    religion VARCHAR(50),
    grupo_sanguineo VARCHAR(10),
    discapacidad VARCHAR(255),
    alergias TEXT,
    contacto_emergencia_nombre VARCHAR(100),
    contacto_emergencia_telefono VARCHAR(20),
    contacto_emergencia_parentesco VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Tabla de Médicos
CREATE TABLE medicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    especialidad VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    correo_electronico VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB;

CREATE INDEX idx_medicos_deleted_at ON medicos(deleted_at);

-- 5.5. Tabla de Episodios Clínicos
CREATE TABLE episodios_clinicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    descripcion_problema VARCHAR(255) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_cierre DATE NULL,
    estado ENUM('Abierto', 'Cerrado') DEFAULT 'Abierto',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Tabla de Citas
CREATE TABLE citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    medico_id INT NOT NULL,
    episodio_id INT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    estado ENUM('Programada', 'Confirmada', 'En espera', 'Atendida', 'Cancelada', 'No asistió') DEFAULT 'Programada',
    estado_clinico VARCHAR(50) DEFAULT 'check_in',
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE CASCADE,
    FOREIGN KEY (episodio_id) REFERENCES episodios_clinicos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 7. Tabla de Atenciones
CREATE TABLE atenciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cita_id INT NOT NULL,
    medico_id INT NOT NULL,
    paciente_id INT NOT NULL,
    fecha_atencion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    motivo_consulta TEXT,
    sintomas TEXT,
    diagnostico TEXT,
    tratamiento TEXT,
    observaciones TEXT,
    presion_arterial VARCHAR(20),
    frecuencia_cardiaca INT,
    temperatura DECIMAL(4,2),
    peso DECIMAL(5,2),
    estatura DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE CASCADE,
    FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Historial Clínico (Mantenido para compatibilidad con código actual)
CREATE TABLE historial_clinico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cita_id INT NOT NULL,
    paciente_id INT NOT NULL,
    medico_id INT NOT NULL,
    episodio_id INT NULL,
    diagnostico TEXT,
    tratamiento TEXT,
    observaciones TEXT,
    adenda_de_id INT NULL,
    activo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE CASCADE,
    FOREIGN KEY (episodio_id) REFERENCES episodios_clinicos(id) ON DELETE SET NULL,
    FOREIGN KEY (adenda_de_id) REFERENCES historial_clinico(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Tabla de Servicios Facturables (Reestructurada)
CREATE TABLE servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 9. Tabla de Facturas
CREATE TABLE facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado ENUM('Pendiente','Pagada','Cancelada') NOT NULL DEFAULT 'Pendiente',
    metodo_pago VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. Tabla de Factura Detalle
CREATE TABLE factura_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT NOT NULL,
    servicio_id INT DEFAULT NULL,
    tipo_item ENUM('consulta','laboratorio','medicamento','otro') NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 11. Tabla de Órdenes de Laboratorio
CREATE TABLE ordenes_laboratorio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    historial_id INT NOT NULL,
    descripcion TEXT,
    estado ENUM('Pendiente', 'Completada') DEFAULT 'Pendiente',
    resultado TEXT,
    archivo_pdf VARCHAR(255),
    fecha_resultado DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    FOREIGN KEY (historial_id) REFERENCES historial_clinico(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_ordenes_laboratorio_deleted_at ON ordenes_laboratorio(deleted_at);

-- 12. Tabla de Orden Laboratorio Detalle
CREATE TABLE orden_laboratorio_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orden_id INT NOT NULL,
    examen_solicitado VARCHAR(150) NOT NULL,
    resultado_examen VARCHAR(255),
    rango_referencia VARCHAR(150),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (orden_id) REFERENCES ordenes_laboratorio(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 13. Tabla de Medicamentos
CREATE TABLE medicamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    presentacion VARCHAR(100),
    concentracion VARCHAR(100),
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    fecha_vencimiento DATE,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB;

CREATE INDEX idx_medicamentos_deleted_at ON medicamentos(deleted_at);

-- 14. Tabla de Movimientos Inventario
CREATE TABLE movimientos_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicamento_id INT NOT NULL,
    tipo_movimiento ENUM('Entrada', 'Salida', 'Ajuste') NOT NULL,
    cantidad INT NOT NULL,
    motivo VARCHAR(255),
    usuario_id INT,
    fecha_movimiento DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicamento_id) REFERENCES medicamentos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 15. Tabla de Prescripciones
CREATE TABLE prescripciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cita_id INT NOT NULL,
    medico_id INT NOT NULL,
    paciente_id INT NOT NULL,
    fecha_prescripcion DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('Pendiente', 'Dispensada', 'Cancelada') DEFAULT 'Pendiente',
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE CASCADE,
    FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_prescripciones_deleted_at ON prescripciones(deleted_at);

-- 16. Tabla de Prescripcion Detalle
CREATE TABLE prescripcion_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescripcion_id INT NOT NULL,
    medicamento_id INT,
    medicamento_texto VARCHAR(255),
    dosis VARCHAR(100) NOT NULL,
    frecuencia VARCHAR(100) NOT NULL,
    duracion VARCHAR(100) NOT NULL,
    cantidad_requerida INT,
    indicaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prescripcion_id) REFERENCES prescripciones(id) ON DELETE CASCADE,
    FOREIGN KEY (medicamento_id) REFERENCES medicamentos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 17. Tabla de Ventas Farmacia
CREATE TABLE ventas_farmacia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT,
    usuario_id INT NOT NULL,
    factura_id INT,
    fecha_venta DATETIME DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado ENUM('Completada', 'Anulada') DEFAULT 'Completada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 18. Tabla de Ventas Farmacia Detalle
CREATE TABLE ventas_farmacia_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    medicamento_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venta_id) REFERENCES ventas_farmacia(id) ON DELETE CASCADE,
    FOREIGN KEY (medicamento_id) REFERENCES medicamentos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de Auditoría (Logs)
CREATE TABLE logs (
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

-- Inserción de Roles por defecto (Minúsculas para compatibilidad con el sistema)
INSERT INTO roles (nombre, descripcion) VALUES 
('administrador', 'Acceso total al sistema'),
('recepcionista', 'Gestión de citas y pacientes'),
('medico', 'Atención médica y agenda'),
('tecnico_laboratorio', 'Gestión de órdenes y resultados de laboratorio'),
('tecnico_imagenes', 'Gestión de estudios de imágenes diagnósticas'),
('farmaceutico', 'Gestión de inventario y despacho de medicamentos'),
('paciente', 'Acceso a su portal personal'),
('enfermera', 'Gestión de triaje y cuidados de enfermería');

-- Inserción de Servicios Iniciales
INSERT INTO servicios (codigo, nombre, precio, tipo, activo) VALUES
('CONS_GEN', 'Consulta General', 1500.00, 'consulta', TRUE),
('LAB_STD', 'Laboratorio Standard', 3500.00, 'laboratorio', TRUE);

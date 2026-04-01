SET FOREIGN_KEY_CHECKS=0;

-- Estructura de la base de datos para HospitAll

CREATE DATABASE IF NOT EXISTS hospitall CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hospitall;

-- 1. Tabla de Roles
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tabla de Usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    correo_electronico VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol_id INT,
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE SET NULL,
    INDEX idx_usuarios_deleted_at (deleted_at)
) ENGINE=InnoDB;

-- 3. Tabla de Pacientes
CREATE TABLE IF NOT EXISTS pacientes (
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
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_pacientes_deleted_at (deleted_at)
) ENGINE=InnoDB;

-- 4. Tabla de Pacientes_Identidad (Datos Sensibles)
CREATE TABLE IF NOT EXISTS pacientes_identidad (
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
CREATE TABLE IF NOT EXISTS medicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    especialidad VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    correo_electronico VARCHAR(150),
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_medicos_deleted_at (deleted_at)
) ENGINE=InnoDB;

-- 5.5. Tabla de Episodios Clínicos
CREATE TABLE IF NOT EXISTS episodios_clinicos (
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
CREATE TABLE IF NOT EXISTS citas (
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
CREATE TABLE IF NOT EXISTS atenciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cita_id INT NULL,
    emergencia_id INT DEFAULT NULL,
    walkin_id INT DEFAULT NULL,
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

-- 7.5. Tabla de Visitas Walk-in
CREATE TABLE IF NOT EXISTS visitas_walkin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    turno_id INT NOT NULL,
    area ENUM('consulta', 'laboratorio', 'farmacia', 'imagenes') NOT NULL,
    medico_id INT DEFAULT NULL,
    estado ENUM('en_espera', 'en_atencion', 'atendido', 'derivado') DEFAULT 'en_espera',
    fecha DATE NOT NULL DEFAULT (CURDATE()),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atendido_at DATETIME DEFAULT NULL,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (turno_id) REFERENCES turnos(id) ON DELETE CASCADE,
    FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Historial Clínico (Mantenido para compatibilidad con código actual)
CREATE TABLE IF NOT EXISTS historial_clinico (
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
CREATE TABLE IF NOT EXISTS servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 9. Tabla de Facturas
CREATE TABLE IF NOT EXISTS facturas (
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
CREATE TABLE IF NOT EXISTS factura_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    factura_id INT NOT NULL,
    servicio_id INT DEFAULT NULL,
    tipo_item ENUM('consulta','laboratorio','medicamento','imagenes','otro') NOT NULL,
    descripcion VARCHAR(255) NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 11. Tabla de Órdenes de Laboratorio
CREATE TABLE IF NOT EXISTS ordenes_laboratorio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    historial_id INT NULL,
    walkin_id INT DEFAULT NULL,
    descripcion TEXT,
    estado ENUM('Pendiente', 'En proceso', 'Completada') DEFAULT 'Pendiente',
    resultado TEXT,
    archivo_pdf VARCHAR(255),
    fecha_resultado DATETIME,
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (historial_id) REFERENCES historial_clinico(id) ON DELETE CASCADE,
    FOREIGN KEY (walkin_id) REFERENCES visitas_walkin(id) ON DELETE SET NULL,
    INDEX idx_ordenes_laboratorio_deleted_at (deleted_at)
) ENGINE=InnoDB;

-- 12. Tabla de Orden Laboratorio Detalle
CREATE TABLE IF NOT EXISTS orden_laboratorio_detalle (
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
CREATE TABLE IF NOT EXISTS medicamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    presentacion VARCHAR(100),
    concentracion VARCHAR(100),
    lote VARCHAR(100) DEFAULT NULL,
    proveedor VARCHAR(150) DEFAULT NULL,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    fecha_vencimiento DATE,
    activo BOOLEAN DEFAULT TRUE,
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_medicamentos_deleted_at (deleted_at)
) ENGINE=InnoDB;

-- 14. Tabla de Movimientos Inventario
CREATE TABLE IF NOT EXISTS movimientos_inventario (
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
CREATE TABLE IF NOT EXISTS prescripciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cita_id INT NOT NULL,
    medico_id INT NOT NULL,
    paciente_id INT NOT NULL,
    fecha_prescripcion DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('Pendiente', 'Dispensada', 'Cancelada') DEFAULT 'Pendiente',
    observaciones TEXT,
    deleted_at DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE CASCADE,
    FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    INDEX idx_prescripciones_deleted_at (deleted_at)
) ENGINE=InnoDB;

-- 16. Tabla de Prescripcion Detalle
CREATE TABLE IF NOT EXISTS prescripcion_detalle (
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
CREATE TABLE IF NOT EXISTS ventas_farmacia (
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
CREATE TABLE IF NOT EXISTS ventas_farmacia_detalle (
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


-- Tabla de Observaciones de Enfermería
CREATE TABLE IF NOT EXISTS observaciones_enfermeria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cita_id INT NOT NULL,
    paciente_id INT NOT NULL,
    usuario_id INT NOT NULL,
    observaciones TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de Emergencias
CREATE TABLE IF NOT EXISTS emergencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    medico_id INT DEFAULT NULL,
    usuario_id INT NOT NULL,
    nivel_triage ENUM('Rojo', 'Naranja', 'Amarillo', 'Verde') NOT NULL,
    motivo_ingreso TEXT NOT NULL,
    estado ENUM('En espera', 'En atención', 'Atendido', 'Transferido')
        DEFAULT 'En espera',
    fecha_ingreso DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de Órdenes de Imágenes (Técnico de Imágenes Médicas)
CREATE TABLE IF NOT EXISTS ordenes_imagenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    historial_id INT NULL,
    walkin_id INT DEFAULT NULL,
    tipo_estudio VARCHAR(100) NOT NULL,
    descripcion TEXT,
    estado ENUM('Pendiente', 'En proceso', 'Completada') DEFAULT 'Pendiente',
    resultado TEXT,
    archivo_imagen VARCHAR(255),
    fecha_resultado DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (historial_id) REFERENCES historial_clinico(id) ON DELETE CASCADE,
    FOREIGN KEY (walkin_id) REFERENCES visitas_walkin(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabla de Orden Imágenes Detalle
CREATE TABLE IF NOT EXISTS orden_imagenes_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orden_id INT NOT NULL,
    estudio_solicitado VARCHAR(150) NOT NULL,
    resultado_estudio TEXT,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (orden_id) REFERENCES ordenes_imagenes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla de Asignaciones de Enfermería
CREATE TABLE IF NOT EXISTS asignaciones_enfermeria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    internamiento_id INT NOT NULL,
    paciente_id INT NOT NULL,
    enfermera_id INT NOT NULL,
    asignado_por INT NOT NULL,
    fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (internamiento_id) REFERENCES internamientos(id) ON DELETE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (enfermera_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (asignado_por) REFERENCES usuarios(id) ON DELETE CASCADE
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

-- FK atenciones.emergencia_id (requiere que emergencias exista)
ALTER TABLE atenciones ADD CONSTRAINT fk_atenciones_emergencia
    FOREIGN KEY (emergencia_id) REFERENCES emergencias(id) ON DELETE CASCADE;

-- FK atenciones.walkin_id (requiere que visitas_walkin exista)
ALTER TABLE atenciones ADD CONSTRAINT fk_atenciones_walkin_id
    FOREIGN KEY (walkin_id) REFERENCES visitas_walkin(id) ON DELETE SET NULL;

-- Inserción de Roles por defecto
INSERT INTO roles (nombre, descripcion) VALUES 
('administrador', 'Acceso total al sistema'),
('recepcionista', 'Gestión de citas y pacientes'),
('medico', 'Atención médica y agenda'),
('tecnico_laboratorio', 'Gestión de órdenes y resultados de laboratorio'),
('tecnico_imagenes', 'Gestión de solicitudes y resultados de estudios de imágenes médicas'),
('farmaceutico', 'Gestión de inventario y despacho de medicamentos'),
('paciente', 'Acceso a su portal personal'),
('enfermera', 'Toma de signos vitales, registro de observaciones y monitoreo de pacientes asignados');

-- Inserción de Servicios Iniciales
INSERT INTO servicios (codigo, nombre, precio, tipo, activo) VALUES
('CONS_GEN', 'Consulta General', 1500.00, 'consulta', TRUE),
('LAB_STD', 'Laboratorio Standard', 3500.00, 'laboratorio', TRUE);

-- 19. Tabla de Turnos
CREATE TABLE IF NOT EXISTS turnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(10) NOT NULL,
    area ENUM('consulta', 'laboratorio', 'farmacia', 'imagenes') NOT NULL,
    tipo ENUM('preferencial', 'general') NOT NULL DEFAULT 'general',
    paciente_id INT DEFAULT NULL,
    cita_id INT DEFAULT NULL,
    estado ENUM('esperando', 'llamado', 'atendido', 'cancelado')
        DEFAULT 'esperando',
    generado_por INT NOT NULL,
    fecha DATE NOT NULL DEFAULT (CURDATE()),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    llamado_at DATETIME DEFAULT NULL,
    atendido_at DATETIME DEFAULT NULL,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE SET NULL,
    FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE SET NULL,
    FOREIGN KEY (generado_por) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 20. Tabla de Habitaciones
CREATE TABLE IF NOT EXISTS habitaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) NOT NULL UNIQUE,
    piso VARCHAR(20),
    tipo ENUM('general', 'privada', 'UCI', 'pediatria')
        NOT NULL DEFAULT 'general',
    capacidad INT NOT NULL DEFAULT 1,
    activa BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_habitaciones_deleted_at (deleted_at)
) ENGINE=InnoDB;

-- 21. Tabla de Camas
CREATE TABLE IF NOT EXISTS camas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    habitacion_id INT NOT NULL,
    numero VARCHAR(20) NOT NULL,
    estado ENUM('disponible', 'ocupada', 'en_limpieza')
        DEFAULT 'disponible',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (habitacion_id)
        REFERENCES habitaciones(id) ON DELETE CASCADE,
    INDEX idx_camas_deleted_at (deleted_at)
) ENGINE=InnoDB;

-- 22. Tabla de Internamientos
CREATE TABLE IF NOT EXISTS internamientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    medico_id INT NOT NULL,
    cama_id INT NOT NULL,
    origen ENUM('emergencia', 'consulta') NOT NULL,
    emergencia_id INT DEFAULT NULL,
    cita_id INT DEFAULT NULL,
    motivo_internamiento TEXT NOT NULL,
    diagnostico_ingreso TEXT,
    estado ENUM('activo', 'alta', 'transferido')
        DEFAULT 'activo',
    fecha_ingreso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_alta DATETIME DEFAULT NULL,
    medico_alta_id INT DEFAULT NULL,
    observaciones_alta TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (paciente_id)
        REFERENCES pacientes(id) ON DELETE CASCADE,
    FOREIGN KEY (medico_id) REFERENCES medicos(id) ON DELETE CASCADE,
    FOREIGN KEY (cama_id) REFERENCES camas(id) ON DELETE CASCADE,
    FOREIGN KEY (emergencia_id) REFERENCES emergencias(id) ON DELETE SET NULL,
    FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE SET NULL,
    FOREIGN KEY (medico_alta_id) REFERENCES medicos(id) ON DELETE SET NULL,
    INDEX idx_internamientos_deleted_at (deleted_at)
) ENGINE=InnoDB;

-- 23. Tabla de Rondas de Enfermería
CREATE TABLE IF NOT EXISTS rondas_enfermeria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    internamiento_id INT NOT NULL,
    enfermera_id INT NOT NULL,
    presion_arterial VARCHAR(20),
    frecuencia_cardiaca INT,
    temperatura DECIMAL(4,2),
    peso DECIMAL(5,2),
    saturacion_oxigeno DECIMAL(4,1),
    observaciones TEXT,
    medicamentos_administrados TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (internamiento_id)
        REFERENCES internamientos(id) ON DELETE CASCADE,
    FOREIGN KEY (enfermera_id)
        REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_rondas_deleted_at (deleted_at)
) ENGINE=InnoDB;

-- 24. Tabla de Evoluciones Médicas
CREATE TABLE IF NOT EXISTS evoluciones_medicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    internamiento_id INT NOT NULL,
    medico_id INT NOT NULL,
    evolucion TEXT NOT NULL,
    indicaciones TEXT,
    diagnostico_actualizado TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (internamiento_id)
        REFERENCES internamientos(id) ON DELETE CASCADE,
    FOREIGN KEY (medico_id)
        REFERENCES medicos(id) ON DELETE CASCADE,
    INDEX idx_evoluciones_deleted_at (deleted_at)
) ENGINE=InnoDB;

-- Datos iniciales de habitaciones y camas
INSERT INTO habitaciones (numero, piso, tipo, capacidad) VALUES
('101', '1', 'general', 4),
('102', '1', 'general', 4),
('201', '2', 'privada', 1),
('202', '2', 'privada', 1),
('UCI-1', '3', 'UCI', 6);

INSERT INTO camas (habitacion_id, numero, estado) VALUES
(1, '101-A', 'disponible'), (1, '101-B', 'disponible'),
(1, '101-C', 'disponible'), (1, '101-D', 'disponible'),
(2, '102-A', 'disponible'), (2, '102-B', 'disponible'),
(2, '102-C', 'disponible'), (2, '102-D', 'disponible'),
(3, '201-A', 'disponible'),
(4, '202-A', 'disponible'),
(5, 'UCI-1A', 'disponible'), (5, 'UCI-1B', 'disponible'),
(5, 'UCI-1C', 'disponible'), (5, 'UCI-1D', 'disponible'),
(5, 'UCI-1E', 'disponible'), (5, 'UCI-1F', 'disponible');

-- ==========================================
-- SEEDER DE USUARIOS Y DATOS DE PRUEBA
-- ==========================================

-- Habilitar uso de variables
SET @hash := '$2y$10$vH4SWbTJd5m7eChNAqyGR.BWkJjus2EkCIZ5WWX1PSozZZgF7xL9i';

-- Buscar el ID de cada rol
SET @rol_admin := (SELECT id FROM roles WHERE nombre = 'administrador' LIMIT 1);
SET @rol_recepcionista := (SELECT id FROM roles WHERE nombre = 'recepcionista' LIMIT 1);
SET @rol_medico := (SELECT id FROM roles WHERE nombre = 'medico' LIMIT 1);
SET @rol_lab := (SELECT id FROM roles WHERE nombre = 'tecnico_laboratorio' LIMIT 1);
SET @rol_img := (SELECT id FROM roles WHERE nombre = 'tecnico_imagenes' LIMIT 1);
SET @rol_farm := (SELECT id FROM roles WHERE nombre = 'farmaceutico' LIMIT 1);
SET @rol_paciente := (SELECT id FROM roles WHERE nombre = 'paciente' LIMIT 1);
SET @rol_enfermera := (SELECT id FROM roles WHERE nombre = 'enfermera' LIMIT 1);

-- 1. Administrador
INSERT INTO usuarios (nombre, apellido, correo_electronico, password, rol_id)
VALUES ('Admin', 'Sistema', 'admin@hospitall.com', @hash, @rol_admin)
ON DUPLICATE KEY UPDATE password=VALUES(password), rol_id=VALUES(rol_id);

-- 2. Médico
INSERT INTO usuarios (nombre, apellido, correo_electronico, password, rol_id)
VALUES ('Juan', 'Pérez', 'medico@hospitall.com', @hash, @rol_medico)
ON DUPLICATE KEY UPDATE password=VALUES(password), rol_id=VALUES(rol_id);

-- Insertar en la tabla medicos
INSERT INTO medicos (nombre, apellido, especialidad, telefono, correo_electronico)
SELECT 'Juan', 'Pérez', 'Medicina General', '809-555-0001', 'medico@hospitall.com'
WHERE NOT EXISTS (SELECT 1 FROM medicos WHERE correo_electronico = 'medico@hospitall.com');

-- 3. Recepcionista
INSERT INTO usuarios (nombre, apellido, correo_electronico, password, rol_id)
VALUES ('María', 'González', 'recepcion@hospitall.com', @hash, @rol_recepcionista)
ON DUPLICATE KEY UPDATE password=VALUES(password), rol_id=VALUES(rol_id);

-- 4. Enfermera
INSERT INTO usuarios (nombre, apellido, correo_electronico, password, rol_id)
VALUES ('Ana', 'Martínez', 'enfermera@hospitall.com', @hash, @rol_enfermera)
ON DUPLICATE KEY UPDATE password=VALUES(password), rol_id=VALUES(rol_id);

-- 5. Técnico Laboratorio
INSERT INTO usuarios (nombre, apellido, correo_electronico, password, rol_id)
VALUES ('Carlos', 'Ramírez', 'laboratorio@hospitall.com', @hash, @rol_lab)
ON DUPLICATE KEY UPDATE password=VALUES(password), rol_id=VALUES(rol_id);

-- 6. Técnico Imágenes
INSERT INTO usuarios (nombre, apellido, correo_electronico, password, rol_id)
VALUES ('Luis', 'Herrera', 'imagenes@hospitall.com', @hash, @rol_img)
ON DUPLICATE KEY UPDATE password=VALUES(password), rol_id=VALUES(rol_id);

-- 7. Farmacéutico
INSERT INTO usuarios (nombre, apellido, correo_electronico, password, rol_id)
VALUES ('Rosa', 'Díaz', 'farmacia@hospitall.com', @hash, @rol_farm)
ON DUPLICATE KEY UPDATE password=VALUES(password), rol_id=VALUES(rol_id);

-- 8. Paciente
INSERT INTO usuarios (nombre, apellido, correo_electronico, password, rol_id)
VALUES ('Pedro', 'Sánchez', 'paciente@hospitall.com', @hash, @rol_paciente)
ON DUPLICATE KEY UPDATE password=VALUES(password), rol_id=VALUES(rol_id);

-- Obtener el usuario_id del paciente
SET @usuario_paciente_id := (SELECT id FROM usuarios WHERE correo_electronico = 'paciente@hospitall.com');

-- Insertar en la tabla pacientes
INSERT INTO pacientes (usuario_id, identificacion, nombre, apellido, fecha_nacimiento, genero, telefono, correo_electronico)
VALUES (@usuario_paciente_id, '001-0000001-1', 'Pedro', 'Sánchez', '1990-01-15', 'Masculino', '809-555-0002', 'paciente@hospitall.com')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), usuario_id=VALUES(usuario_id);

SET FOREIGN_KEY_CHECKS=1;

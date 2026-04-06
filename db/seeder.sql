-- ============================================================
-- HospitAll - Seeder completo (demo universitario)
-- Datos dominicanos realistas
-- ============================================================

USE hospitall;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET @hash = '$2y$10$9I2z12HJSIYe8jbIbYuNveJkuFoNA5M6txwjLPCc17wzv/2sWV/pm';

-- ============================================================
-- LIMPIEZA: Vaciar tablas en orden inverso de dependencias
-- ============================================================
DELETE FROM ventas_farmacia_detalle;
DELETE FROM ventas_farmacia;
DELETE FROM factura_detalle;
DELETE FROM facturas;
DELETE FROM orden_laboratorio_detalle;
DELETE FROM ordenes_laboratorio;
DELETE FROM prescripcion_detalle;
DELETE FROM prescripciones;
DELETE FROM historial_clinico;
DELETE FROM atenciones;
DELETE FROM citas;
DELETE FROM episodios_clinicos;
DELETE FROM movimientos_inventario;
DELETE FROM medicamentos;
DELETE FROM pacientes_identidad;
DELETE FROM pacientes;
DELETE FROM medicos;
DELETE FROM usuarios;
DELETE FROM logs;

-- Resetear auto-increment
ALTER TABLE usuarios AUTO_INCREMENT = 1;
ALTER TABLE pacientes AUTO_INCREMENT = 1;
ALTER TABLE medicos AUTO_INCREMENT = 1;
ALTER TABLE episodios_clinicos AUTO_INCREMENT = 1;
ALTER TABLE citas AUTO_INCREMENT = 1;
ALTER TABLE atenciones AUTO_INCREMENT = 1;
ALTER TABLE historial_clinico AUTO_INCREMENT = 1;
ALTER TABLE medicamentos AUTO_INCREMENT = 1;
ALTER TABLE movimientos_inventario AUTO_INCREMENT = 1;
ALTER TABLE prescripciones AUTO_INCREMENT = 1;
ALTER TABLE prescripcion_detalle AUTO_INCREMENT = 1;
ALTER TABLE ordenes_laboratorio AUTO_INCREMENT = 1;
ALTER TABLE orden_laboratorio_detalle AUTO_INCREMENT = 1;
ALTER TABLE facturas AUTO_INCREMENT = 1;
ALTER TABLE factura_detalle AUTO_INCREMENT = 1;
ALTER TABLE ventas_farmacia AUTO_INCREMENT = 1;
ALTER TABLE ventas_farmacia_detalle AUTO_INCREMENT = 1;

-- ============================================================
-- PASO 2: Usuarios (12 usuarios)
-- Roles resueltos dinámicamente por nombre
-- ============================================================
INSERT INTO usuarios (id, nombre, apellido, correo_electronico, password, rol_id) VALUES
(1, 'Admin', 'Sistema', 'admin@hospitall.do', @hash, (SELECT id FROM roles WHERE nombre = 'administrador')),
(2, 'María', 'González', 'maria.gonzalez@hospitall.do', @hash, (SELECT id FROM roles WHERE nombre = 'recepcionista')),
(3, 'Rafael', 'Méndez', 'rafael.mendez@hospitall.do', @hash, (SELECT id FROM roles WHERE nombre = 'medico')),
(4, 'Carmen', 'Reyes', 'carmen.reyes@hospitall.do', @hash, (SELECT id FROM roles WHERE nombre = 'medico')),
(5, 'José', 'Taveras', 'jose.taveras@hospitall.do', @hash, (SELECT id FROM roles WHERE nombre = 'medico')),
(6, 'Ana', 'Martínez', 'ana.martinez@hospitall.do', @hash, (SELECT id FROM roles WHERE nombre = 'tecnico_laboratorio')),
(7, 'Luis', 'Hernández', 'luis.hernandez@hospitall.do', @hash, (SELECT id FROM roles WHERE nombre = 'farmaceutico')),
(8, 'Pedro', 'Sánchez', 'pedro.sanchez@correo.do', @hash, (SELECT id FROM roles WHERE nombre = 'paciente')),
(9, 'Rosa', 'Almonte', 'rosa.almonte@correo.do', @hash, (SELECT id FROM roles WHERE nombre = 'paciente')),
(10, 'Miguel', 'Castillo', 'miguel.castillo@correo.do', @hash, (SELECT id FROM roles WHERE nombre = 'paciente')),
(11, 'Luz', 'Peña', 'luz.pena@correo.do', @hash, (SELECT id FROM roles WHERE nombre = 'paciente')),
(12, 'Carlos', 'Bautista', 'carlos.bautista@correo.do', @hash, (SELECT id FROM roles WHERE nombre = 'paciente'));


-- ============================================================
-- PASO 3: Médicos
-- ============================================================
INSERT INTO medicos (id, nombre, apellido, especialidad, telefono, correo_electronico) VALUES
(1, 'Rafael', 'Méndez', 'Medicina General', '809-555-1001', 'rafael.mendez@hospitall.do'),
(2, 'Carmen', 'Reyes', 'Pediatría', '809-555-1002', 'carmen.reyes@hospitall.do'),
(3, 'José', 'Taveras', 'Cardiología', '809-555-1003', 'jose.taveras@hospitall.do');

-- ============================================================
-- PASO 4: Pacientes (20 pacientes, 5 con usuario)
-- ============================================================
INSERT INTO pacientes (id, usuario_id, identificacion, identificacion_tipo, nombre, apellido, fecha_nacimiento, genero, direccion, telefono, correo_electronico) VALUES
(1,  8,    '001-1234567-1', 'Cédula', 'Pedro',     'Sánchez',     '1985-03-15', 'Masculino', 'Calle Duarte #45, Santo Domingo Este',          '809-555-2001', 'pedro.sanchez@correo.do'),
(2,  9,    '001-2345678-2', 'Cédula', 'Rosa',      'Almonte',     '1990-07-22', 'Femenino',  'Av. 27 de Febrero #120, Santo Domingo',          '809-555-2002', 'rosa.almonte@correo.do'),
(3,  10,   '001-3456789-3', 'Cédula', 'Miguel',    'Castillo',    '1978-11-08', 'Masculino', 'Calle El Sol #78, Santiago de los Caballeros',   '809-555-2003', 'miguel.castillo@correo.do'),
(4,  11,   '001-4567890-4', 'Cédula', 'Luz',       'Peña',        '1965-01-30', 'Femenino',  'Av. España #200, La Romana',                     '809-555-2004', 'luz.pena@correo.do'),
(5,  12,   '001-5678901-5', 'Cédula', 'Carlos',    'Bautista',    '2000-05-12', 'Masculino', 'Calle Mella #33, San Pedro de Macorís',          '809-555-2005', 'carlos.bautista@correo.do'),
(6,  NULL, '001-6789012-6', 'Cédula', 'Juana',     'De la Cruz',  '1955-09-18', 'Femenino',  'Calle Sánchez #90, Santo Domingo Norte',         '809-555-2006', 'juana.delacruz@correo.do'),
(7,  NULL, '001-7890123-7', 'Cédula', 'Francisco', 'Núñez',       '1972-04-25', 'Masculino', 'Av. Independencia #55, Santiago',                 '809-555-2007', 'francisco.nunez@correo.do'),
(8,  NULL, '001-8901234-8', 'Cédula', 'Altagracia','Matos',       '1988-12-03', 'Femenino',  'Calle Las Flores #12, Santo Domingo Oeste',      '809-555-2008', 'altagracia.matos@correo.do'),
(9,  NULL, '001-9012345-9', 'Cédula', 'Ramón',     'Polanco',     '1960-06-14', 'Masculino', 'Av. Máximo Gómez #88, Santo Domingo',            '809-555-2009', 'ramon.polanco@correo.do'),
(10, NULL, '001-0123456-0', 'Cédula', 'Mercedes',  'Jiménez',     '1995-02-28', 'Femenino',  'Calle Restauración #67, La Romana',              '809-555-2010', 'mercedes.jimenez@correo.do'),
(11, NULL, '002-1234567-1', 'Cédula', 'Antonio',   'Félix',       '1982-08-20', 'Masculino', 'Av. Hermanas Mirabal #40, Santiago',              '809-555-2011', 'antonio.felix@correo.do'),
(12, NULL, '002-2345678-2', 'Cédula', 'Yolanda',   'Vásquez',     '1970-10-05', 'Femenino',  'Calle Padre Billini #15, Santo Domingo',         '809-555-2012', 'yolanda.vasquez@correo.do'),
(13, NULL, '002-3456789-3', 'Cédula', 'Héctor',    'Rosario',     '1948-03-22', 'Masculino', 'Av. San Martín #102, San Pedro de Macorís',      '809-555-2013', 'hector.rosario@correo.do'),
(14, NULL, '002-4567890-4', 'Cédula', 'Dulce',     'Taveras',     '2008-07-17', 'Femenino',  'Calle Beller #28, Santiago',                      '809-555-2014', 'dulce.taveras@correo.do'),
(15, NULL, '002-5678901-5', 'Cédula', 'Andrés',    'Medina',      '1975-11-11', 'Masculino', 'Av. Luperón #350, Santo Domingo Oeste',          '809-555-2015', 'andres.medina@correo.do'),
(16, NULL, '002-6789012-6', 'Cédula', 'Esmeralda', 'Ortiz',       '1992-01-09', 'Femenino',  'Calle José Martí #44, La Romana',                '809-555-2016', 'esmeralda.ortiz@correo.do'),
(17, NULL, '002-7890123-7', 'Cédula', 'Fernando',  'Guzmán',      '1968-05-30', 'Masculino', 'Av. Churchill #180, Santo Domingo',              '809-555-2017', 'fernando.guzman@correo.do'),
(18, NULL, '002-8901234-8', 'Cédula', 'Milagros',  'Santana',     '1980-09-14', 'Femenino',  'Calle Duarte #22, San Pedro de Macorís',         '809-555-2018', 'milagros.santana@correo.do'),
(19, NULL, '002-9012345-9', 'Cédula', 'Rafael',    'De los Santos','1958-12-25', 'Masculino', 'Av. Tiradentes #95, Santo Domingo',              '809-555-2019', 'rafael.delossantos@correo.do'),
(20, NULL, '003-0123456-0', 'Cédula', 'Nathalie',  'Peralta',     '2005-04-02', 'Femenino',  'Calle El Conde #60, Santiago',                    '809-555-2020', 'nathalie.peralta@correo.do');

-- ============================================================
-- PASO 5: Pacientes identidad
-- ============================================================
INSERT INTO pacientes_identidad (paciente_id, lugar_nacimiento, estado_civil, ocupacion, religion, grupo_sanguineo, discapacidad, alergias, contacto_emergencia_nombre, contacto_emergencia_telefono, contacto_emergencia_parentesco) VALUES
(1,  'Santo Domingo', 'Casado',      'Ingeniero civil',   'Católico',     'O+',  NULL, 'Penicilina',               'Ana Sánchez',         '809-555-3001', 'Esposa'),
(2,  'Santo Domingo', 'Soltero',     'Contadora',         'Católico',     'A+',  NULL, NULL,                       'Juan Almonte',        '809-555-3002', 'Padre'),
(3,  'Santiago',      'Casado',      'Comerciante',       'Evangélico',   'B+',  NULL, 'Aspirina',                 'Lucía Castillo',      '809-555-3003', 'Esposa'),
(4,  'La Romana',     'Viudo',       'Ama de casa',       'Católico',     'O-',  NULL, 'Sulfas',                   'Mario Peña',          '809-555-3004', 'Hijo'),
(5,  'San Pedro',     'Soltero',     'Estudiante',        'Católico',     'A+',  NULL, NULL,                       'María Bautista',      '809-555-3005', 'Madre'),
(6,  'Santo Domingo', 'Viudo',       'Jubilada',          'Católico',     'AB+', NULL, 'Penicilina, Ibuprofeno',   'Teresa Martínez',     '809-555-3006', 'Hija'),
(7,  'Santiago',      'Divorciado',  'Mecánico',          'Católico',     'O+',  NULL, NULL,                       'Sandra Núñez',        '809-555-3007', 'Hermana'),
(8,  'Santo Domingo', 'Unión Libre', 'Abogada',           'Católico',     'A-',  NULL, NULL,                       'Roberto Matos',       '809-555-3008', 'Esposo'),
(9,  'Santo Domingo', 'Casado',      'Jubilado',          'Católico',     'B-',  NULL, 'Aspirina, Penicilina',     'Carmen Polanco',      '809-555-3009', 'Esposa'),
(10, 'La Romana',     'Soltero',     'Enfermera',         'Evangélico',   'O+',  NULL, NULL,                       'José Jiménez',        '809-555-3010', 'Padre'),
(11, 'Santiago',      'Casado',      'Electricista',      'Católico',     'A+',  NULL, NULL,                       'Miriam Félix',        '809-555-3011', 'Esposa'),
(12, 'Santo Domingo', 'Divorciado',  'Maestra',           'Católico',     'B+',  NULL, 'Sulfas',                   'Pedro Vásquez',       '809-555-3012', 'Hijo'),
(13, 'San Pedro',     'Viudo',       'Jubilado',          'Católico',     'AB-', 'Movilidad reducida', NULL,       'Lidia Rosario',       '809-555-3013', 'Hija'),
(14, 'Santiago',      'Soltero',     'Estudiante',        'Católico',     'O+',  NULL, NULL,                       'Roberto Taveras',     '809-555-3014', 'Padre'),
(15, 'Santo Domingo', 'Casado',      'Chofer',            'Evangélico',   'A-',  NULL, 'Penicilina',               'Luisa Medina',        '809-555-3015', 'Esposa'),
(16, 'La Romana',     'Soltero',     'Secretaria',        'Católico',     'O+',  NULL, NULL,                       'Raúl Ortiz',          '809-555-3016', 'Padre'),
(17, 'Santo Domingo', 'Casado',      'Empresario',        'Católico',     'B+',  NULL, NULL,                       'Gloria Guzmán',       '809-555-3017', 'Esposa'),
(18, 'San Pedro',     'Unión Libre', 'Estilista',         'Católico',     'A+',  NULL, 'Aspirina',                 'Jorge Santana',       '809-555-3018', 'Hermano'),
(19, 'Santo Domingo', 'Viudo',       'Jubilado',          'Católico',     'O-',  NULL, 'Sulfas, Aspirina',         'Marta De los Santos', '809-555-3019', 'Hija'),
(20, 'Santiago',      'Soltero',     'Estudiante',        'Católico',     'AB+', NULL, NULL,                       'Sergio Peralta',      '809-555-3020', 'Padre');

-- ============================================================
-- PASO 6: Medicamentos (15 medicamentos)
-- ============================================================
INSERT INTO medicamentos (id, codigo, nombre, descripcion, presentacion, concentracion, precio, stock, fecha_vencimiento, activo) VALUES
(1,  'MED-001', 'Amoxicilina',       'Antibiótico de amplio espectro',         'Tableta',    '500mg',  150.00, 120, '2027-06-15', TRUE),
(2,  'MED-002', 'Paracetamol',       'Analgésico y antipirético',              'Tableta',    '500mg',   50.00, 200, '2028-03-20', TRUE),
(3,  'MED-003', 'Ibuprofeno',        'Antiinflamatorio no esteroideo',         'Tableta',    '400mg',   75.00, 180, '2027-09-10', TRUE),
(4,  'MED-004', 'Metformina',        'Antidiabético oral',                     'Tableta',    '850mg',  120.00, 150, '2027-12-01', TRUE),
(5,  'MED-005', 'Enalapril',         'Inhibidor de la ECA',                    'Tableta',    '10mg',    95.00, 100, '2028-01-15', TRUE),
(6,  'MED-006', 'Omeprazol',         'Inhibidor de bomba de protones',         'Cápsula',    '20mg',    85.00, 160, '2027-08-22', TRUE),
(7,  'MED-007', 'Losartán',          'Antagonista de receptores de angiotensina','Tableta',   '50mg',   110.00, 130, '2028-05-10', TRUE),
(8,  'MED-008', 'Atorvastatina',     'Estatina para control de colesterol',    'Tableta',    '20mg',   180.00,  90, '2027-11-30', TRUE),
(9,  'MED-009', 'Azitromicina',      'Antibiótico macrólido',                  'Tableta',    '500mg',  250.00,  80, '2027-07-18', TRUE),
(10, 'MED-010', 'Diclofenac',        'Antiinflamatorio y analgésico',          'Tableta',    '50mg',    65.00, 170, '2028-02-28', TRUE),
(11, 'MED-011', 'Salbutamol',        'Broncodilatador de acción rápida',       'Inhalador',  '100mcg', 350.00,  60, '2027-10-05', TRUE),
(12, 'MED-012', 'Insulina NPH',      'Insulina de acción intermedia',         'Inyectable', '100UI',  800.00,  50, '2026-12-20', TRUE),
(13, 'MED-013', 'Ceftriaxona',       'Cefalosporina de tercera generación',   'Inyectable', '1g',     450.00,  70, '2027-04-15', TRUE),
(14, 'MED-014', 'Hidroclorotiazida', 'Diurético tiazídico',                   'Tableta',    '25mg',    60.00, 140, '2028-06-30', TRUE),
(15, 'MED-015', 'Clonazepam',        'Benzodiazepina anticonvulsivante',      'Tableta',    '0.5mg',  200.00, 100, '2027-05-25', TRUE);

-- ============================================================
-- PASO 6b: Movimientos de inventario (entrada inicial)
-- ============================================================
INSERT INTO movimientos_inventario (medicamento_id, tipo_movimiento, cantidad, motivo, usuario_id, fecha_movimiento) VALUES
(1,  'Entrada', 120, 'Stock inicial', 7, DATE_SUB(CURDATE(), INTERVAL 60 DAY)),
(2,  'Entrada', 200, 'Stock inicial', 7, DATE_SUB(CURDATE(), INTERVAL 60 DAY)),
(3,  'Entrada', 180, 'Stock inicial', 7, DATE_SUB(CURDATE(), INTERVAL 60 DAY)),
(4,  'Entrada', 150, 'Stock inicial', 7, DATE_SUB(CURDATE(), INTERVAL 60 DAY)),
(5,  'Entrada', 100, 'Stock inicial', 7, DATE_SUB(CURDATE(), INTERVAL 60 DAY)),
(6,  'Entrada', 160, 'Stock inicial', 7, DATE_SUB(CURDATE(), INTERVAL 60 DAY)),
(7,  'Entrada', 130, 'Stock inicial', 7, DATE_SUB(CURDATE(), INTERVAL 60 DAY)),
(8,  'Entrada',  90, 'Stock inicial', 7, DATE_SUB(CURDATE(), INTERVAL 60 DAY)),
(9,  'Entrada',  80, 'Stock inicial', 7, DATE_SUB(CURDATE(), INTERVAL 60 DAY)),
(10, 'Entrada', 170, 'Stock inicial', 7, DATE_SUB(CURDATE(), INTERVAL 60 DAY)),
(11, 'Entrada',  60, 'Stock inicial', 7, DATE_SUB(CURDATE(), INTERVAL 60 DAY)),
(12, 'Entrada',  50, 'Stock inicial', 7, DATE_SUB(CURDATE(), INTERVAL 60 DAY)),
(13, 'Entrada',  70, 'Stock inicial', 7, DATE_SUB(CURDATE(), INTERVAL 60 DAY)),
(14, 'Entrada', 140, 'Stock inicial', 7, DATE_SUB(CURDATE(), INTERVAL 60 DAY)),
(15, 'Entrada', 100, 'Stock inicial', 7, DATE_SUB(CURDATE(), INTERVAL 60 DAY));

-- ============================================================
-- PASO 7: Episodios clínicos (15 episodios)
-- ============================================================
INSERT INTO episodios_clinicos (id, paciente_id, descripcion_problema, fecha_inicio, fecha_cierre, estado) VALUES
(1,  1,  'Hipertensión arterial',             DATE_SUB(CURDATE(), INTERVAL 90 DAY),  NULL, 'Abierto'),
(2,  3,  'Diabetes mellitus tipo 2',          DATE_SUB(CURDATE(), INTERVAL 120 DAY), NULL, 'Abierto'),
(3,  2,  'Infección respiratoria aguda',      DATE_SUB(CURDATE(), INTERVAL 15 DAY),  DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Cerrado'),
(4,  9,  'Gastritis crónica',                 DATE_SUB(CURDATE(), INTERVAL 60 DAY),  NULL, 'Abierto'),
(5,  7,  'Lumbalgia mecánica',                DATE_SUB(CURDATE(), INTERVAL 20 DAY),  DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'Cerrado'),
(6,  14, 'Control pediátrico de rutina',      DATE_SUB(CURDATE(), INTERVAL 10 DAY),  DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'Cerrado'),
(7,  19, 'Arritmia cardíaca',                 DATE_SUB(CURDATE(), INTERVAL 45 DAY),  NULL, 'Abierto'),
(8,  5,  'Asma bronquial',                    DATE_SUB(CURDATE(), INTERVAL 30 DAY),  NULL, 'Abierto'),
(9,  4,  'Artrosis de rodilla',               DATE_SUB(CURDATE(), INTERVAL 80 DAY),  NULL, 'Abierto'),
(10, 6,  'Hipertensión arterial descompensada', DATE_SUB(CURDATE(), INTERVAL 25 DAY), NULL, 'Abierto'),
(11, 11, 'Infección urinaria',                DATE_SUB(CURDATE(), INTERVAL 12 DAY),  DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Cerrado'),
(12, 15, 'Cefalea tensional crónica',         DATE_SUB(CURDATE(), INTERVAL 40 DAY),  NULL, 'Abierto'),
(13, 8,  'Dermatitis alérgica',               DATE_SUB(CURDATE(), INTERVAL 7 DAY),   NULL, 'Abierto'),
(14, 17, 'Dislipidemia',                      DATE_SUB(CURDATE(), INTERVAL 50 DAY),  NULL, 'Abierto'),
(15, 10, 'Control prenatal',                  DATE_SUB(CURDATE(), INTERVAL 35 DAY),  NULL, 'Abierto');

-- ============================================================
-- PASO 7b: Servicios adicionales
-- ============================================================
INSERT INTO servicios (codigo, nombre, precio, tipo, activo) VALUES
('CONS_ESP', 'Consulta especializada', 2500.00, 'consulta', TRUE),
('CONS_PED', 'Consulta pediátrica', 1800.00, 'consulta', TRUE),
('LAB_HEM', 'Hemograma completo', 800.00, 'laboratorio', TRUE),
('LAB_GLU', 'Glucosa en sangre', 500.00, 'laboratorio', TRUE),
('LAB_LIP', 'Perfil lipídico', 1500.00, 'laboratorio', TRUE),
('LAB_HEP', 'Pruebas hepáticas', 1800.00, 'laboratorio', TRUE),
('LAB_CRE', 'Creatinina', 600.00, 'laboratorio', TRUE)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- ============================================================
-- PASO 8: Citas (30 citas)
-- ============================================================
INSERT INTO citas (id, paciente_id, medico_id, episodio_id, fecha, hora, estado, observaciones) VALUES
-- 10 atendidas (últimos 30 días)
(1,  1,  1, 1,  DATE_SUB(CURDATE(), INTERVAL 25 DAY), '08:00:00', 'Atendida',    'Control de hipertensión'),
(2,  3,  1, 2,  DATE_SUB(CURDATE(), INTERVAL 22 DAY), '09:00:00', 'Atendida',    'Seguimiento diabetes'),
(3,  2,  1, 3,  DATE_SUB(CURDATE(), INTERVAL 15 DAY), '10:00:00', 'Atendida',    'Infección respiratoria'),
(4,  9,  1, 4,  DATE_SUB(CURDATE(), INTERVAL 18 DAY), '11:00:00', 'Atendida',    'Dolor epigástrico'),
(5,  7,  1, 5,  DATE_SUB(CURDATE(), INTERVAL 12 DAY), '08:30:00', 'Atendida',    'Dolor lumbar'),
(6,  14, 2, 6,  DATE_SUB(CURDATE(), INTERVAL 10 DAY), '09:30:00', 'Atendida',    'Control de peso y talla'),
(7,  19, 3, 7,  DATE_SUB(CURDATE(), INTERVAL 8 DAY),  '10:30:00', 'Atendida',    'Palpitaciones frecuentes'),
(8,  5,  1, 8,  DATE_SUB(CURDATE(), INTERVAL 5 DAY),  '11:30:00', 'Atendida',    'Crisis asmática'),
(9,  6,  3, 10, DATE_SUB(CURDATE(), INTERVAL 3 DAY),  '08:00:00', 'Atendida',    'Presión arterial elevada'),
(10, 4,  1, 9,  DATE_SUB(CURDATE(), INTERVAL 2 DAY),  '09:00:00', 'Atendida',    'Dolor articular'),
-- 5 programadas (hoy y mañana)
(11, 11, 1, 11, CURDATE(),                             '14:00:00', 'Programada',  'Seguimiento infección urinaria'),
(12, 15, 1, 12, CURDATE(),                             '15:00:00', 'Programada',  'Cefalea persistente'),
(13, 8,  2, 13, CURDATE(),                             '14:30:00', 'Programada',  'Erupción cutánea'),
(14, 17, 3, 14, DATE_ADD(CURDATE(), INTERVAL 1 DAY),   '09:00:00', 'Programada',  'Control de colesterol'),
(15, 10, 2, 15, DATE_ADD(CURDATE(), INTERVAL 1 DAY),   '10:00:00', 'Programada',  'Control prenatal'),
-- 5 confirmadas (esta semana)
(16, 12, 1, NULL, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '08:00:00', 'Confirmada',  'Consulta general'),
(17, 16, 2, NULL, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '09:00:00', 'Confirmada',  'Chequeo de rutina'),
(18, 18, 1, NULL, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '10:00:00', 'Confirmada',  'Dolor de espalda'),
(19, 13, 3, NULL, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '11:00:00', 'Confirmada',  'Evaluación cardiovascular'),
(20, 20, 2, NULL, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '08:30:00', 'Confirmada',  'Consulta pediátrica'),
-- 5 canceladas
(21, 1,  1, 1,    DATE_SUB(CURDATE(), INTERVAL 30 DAY), '08:00:00', 'Cancelada',   'Paciente canceló por trabajo'),
(22, 3,  1, 2,    DATE_SUB(CURDATE(), INTERVAL 28 DAY), '09:00:00', 'Cancelada',   'Reagendada para otra fecha'),
(23, 5,  2, NULL, DATE_SUB(CURDATE(), INTERVAL 20 DAY), '10:00:00', 'Cancelada',   'Médico no disponible'),
(24, 8,  1, NULL, DATE_SUB(CURDATE(), INTERVAL 16 DAY), '11:00:00', 'Cancelada',   'Paciente se sintió mejor'),
(25, 12, 3, NULL, DATE_SUB(CURDATE(), INTERVAL 14 DAY), '14:00:00', 'Cancelada',   'Emergencia familiar'),
-- 5 no asistió
(26, 9,  1, 4,    DATE_SUB(CURDATE(), INTERVAL 26 DAY), '08:00:00', 'No asistió',  NULL),
(27, 7,  1, NULL, DATE_SUB(CURDATE(), INTERVAL 24 DAY), '09:00:00', 'No asistió',  NULL),
(28, 16, 2, NULL, DATE_SUB(CURDATE(), INTERVAL 19 DAY), '10:00:00', 'No asistió',  NULL),
(29, 11, 3, NULL, DATE_SUB(CURDATE(), INTERVAL 17 DAY), '11:00:00', 'No asistió',  NULL),
(30, 18, 1, NULL, DATE_SUB(CURDATE(), INTERVAL 13 DAY), '14:00:00', 'No asistió',  NULL);

-- ============================================================
-- PASO 9: Atenciones (10 para citas atendidas)
-- ============================================================
INSERT INTO atenciones (id, cita_id, medico_id, paciente_id, fecha_atencion, motivo_consulta, sintomas, diagnostico, tratamiento, observaciones, presion_arterial, frecuencia_cardiaca, temperatura, peso, estatura) VALUES
(1,  1,  1, 1,  DATE_SUB(CURDATE(), INTERVAL 25 DAY), 'Control de hipertensión',      'Cefalea ocasional, mareos',             'Hipertensión arterial estadio I',            'Enalapril 10mg cada 12h',                      'Paciente estable, continuar tratamiento',         '140/90', 78, 36.80, 82.50, 1.75),
(2,  2,  1, 3,  DATE_SUB(CURDATE(), INTERVAL 22 DAY), 'Seguimiento diabetes tipo 2',  'Poliuria, polidipsia leve',             'Diabetes mellitus tipo 2 controlada',        'Metformina 850mg con desayuno y cena',         'Glucosa en ayunas 135 mg/dL',                     '130/85', 72, 36.50, 90.00, 1.70),
(3,  3,  1, 2,  DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'Infección respiratoria',        'Tos, fiebre, malestar general',         'Infección respiratoria aguda viral',         'Paracetamol 500mg c/8h, hidratación',          'Reposo por 5 días',                               '110/70', 88, 37.80, 65.00, 1.62),
(4,  4,  1, 9,  DATE_SUB(CURDATE(), INTERVAL 18 DAY), 'Dolor epigástrico',             'Ardor estomacal, náuseas',              'Gastritis crónica reagudizada',              'Omeprazol 20mg en ayunas',                     'Dieta blanda, evitar irritantes',                 '125/80', 70, 36.60, 78.00, 1.68),
(5,  5,  1, 7,  DATE_SUB(CURDATE(), INTERVAL 12 DAY), 'Dolor lumbar',                  'Dolor agudo al moverse, rigidez',       'Lumbalgia mecánica aguda',                   'Diclofenac 50mg c/8h por 5 días',              'Reposo relativo, fisioterapia',                   '120/75', 74, 36.70, 85.00, 1.78),
(6,  6,  2, 14, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'Control pediátrico',            'Sin síntomas, control de rutina',       'Desarrollo normal para la edad',             'Continuar alimentación balanceada',            'Peso y talla adecuados para edad',                '100/65', 90, 36.50, 45.00, 1.55),
(7,  7,  3, 19, DATE_SUB(CURDATE(), INTERVAL 8 DAY),  'Palpitaciones',                 'Palpitaciones, disnea de esfuerzo',     'Fibrilación auricular paroxística',          'Referido a electrocardiograma',                'Requiere seguimiento cardiológico estrecho',      '135/88', 92, 36.90, 75.00, 1.65),
(8,  8,  1, 5,  DATE_SUB(CURDATE(), INTERVAL 5 DAY),  'Crisis asmática',               'Dificultad respiratoria, sibilancias',  'Asma bronquial exacerbación moderada',       'Salbutamol inhalado, prednisona 5 días',       'Mejoría tras nebulización',                       '115/72', 96, 36.60, 70.00, 1.72),
(9,  9,  3, 6,  DATE_SUB(CURDATE(), INTERVAL 3 DAY),  'Presión arterial elevada',      'Cefalea intensa, visión borrosa',       'Hipertensión arterial estadio II',           'Losartán 50mg + Hidroclorotiazida 25mg',       'Control semanal obligatorio',                     '160/100', 85, 37.00, 72.00, 1.58),
(10, 10, 1, 4,  DATE_SUB(CURDATE(), INTERVAL 2 DAY),  'Dolor articular',               'Dolor en rodilla, inflamación',         'Artrosis de rodilla bilateral',              'Ibuprofeno 400mg c/8h, ejercicios',            'Solicitar radiografía de rodillas',               '130/82', 68, 36.50, 70.00, 1.60);

-- ============================================================
-- PASO 9b: Historial clínico
-- ============================================================
INSERT INTO historial_clinico (id, cita_id, paciente_id, medico_id, episodio_id, diagnostico, tratamiento, observaciones) VALUES
(1,  1,  1,  1, 1,  'Hipertensión arterial estadio I',       'Enalapril 10mg c/12h. Dieta hiposódica.',                  'Mantener actividad física moderada'),
(2,  2,  3,  1, 2,  'Diabetes mellitus tipo 2 controlada',   'Metformina 850mg con desayuno y cena.',                    'Control de glucosa en ayunas mensual'),
(3,  3,  2,  1, 3,  'Infección respiratoria aguda viral',    'Paracetamol 500mg c/8h. Reposo e hidratación.',            'Evolución favorable en 5 días'),
(4,  4,  9,  1, 4,  'Gastritis crónica reagudizada',         'Omeprazol 20mg en ayunas por 30 días.',                    'Dieta blanda por 2 semanas'),
(5,  5,  7,  1, 5,  'Lumbalgia mecánica aguda',              'Diclofenac 50mg c/8h por 5 días. Fisioterapia.',           'Referir a fisioterapia si persiste'),
(6,  6,  14, 2, 6,  'Desarrollo normal para la edad',        'Vitaminas, alimentación balanceada.',                      'Próximo control en 6 meses'),
(7,  7,  19, 3, 7,  'Fibrilación auricular paroxística',     'Solicitar electrocardiograma y ecocardiograma.',           'Seguimiento cardiológico quincenal'),
(8,  8,  5,  1, 8,  'Asma bronquial exacerbación moderada',  'Salbutamol inhalador 2 puff c/6h. Prednisona 20mg 5 días.','Evitar alérgenos y humo'),
(9,  9,  6,  3, 10, 'Hipertensión arterial estadio II',      'Losartán 50mg AM + Hidroclorotiazida 25mg AM.',            'Control semanal de presión arterial'),
(10, 10, 4,  1, 9,  'Artrosis de rodilla bilateral',         'Ibuprofeno 400mg c/8h. Ejercicios de fortalecimiento.',    'Solicitar Rx de rodillas');

-- ============================================================
-- PASO 10: Órdenes de laboratorio
-- ============================================================
INSERT INTO ordenes_laboratorio (id, historial_id, descripcion, estado, resultado, fecha_resultado) VALUES
(1, 1, 'Perfil renal y electrolitos',              'Completada', 'Creatinina 1.0 mg/dL, BUN 18 mg/dL. Electrolitos normales.', DATE_SUB(CURDATE(), INTERVAL 23 DAY)),
(2, 2, 'Glucosa en ayunas y HbA1c',                'Completada', 'Glucosa 135 mg/dL, HbA1c 7.2%. Control aceptable.',           DATE_SUB(CURDATE(), INTERVAL 20 DAY)),
(3, 3, 'Hemograma completo',                       'Completada', 'Leucocitos 12,500/mm3, linfocitos predominantes. Compatible con infección viral.', DATE_SUB(CURDATE(), INTERVAL 13 DAY)),
(4, 7, 'Electrocardiograma y troponinas',           'Pendiente',  NULL, NULL),
(5, 9, 'Perfil renal y electrolitos',               'Pendiente',  NULL, NULL),
(6, 10,'Radiografía de rodillas bilateral',          'Pendiente',  NULL, NULL),
(7, 4, 'Pruebas hepáticas y H. pylori',             'Completada', 'TGO 25 U/L, TGP 30 U/L normales. H. pylori negativo.',       DATE_SUB(CURDATE(), INTERVAL 16 DAY)),
(8, 8, 'Espirometría y gasometría arterial',         'Completada', 'FEV1 72% del predicho. Patrón obstructivo moderado.',         DATE_SUB(CURDATE(), INTERVAL 3 DAY));

-- Detalle de órdenes completadas
INSERT INTO orden_laboratorio_detalle (orden_id, examen_solicitado, resultado_examen, rango_referencia, observaciones) VALUES
(1, 'Creatinina',       '1.0 mg/dL',      '0.7-1.3 mg/dL',    'Normal'),
(1, 'BUN',              '18 mg/dL',        '7-20 mg/dL',       'Normal'),
(1, 'Sodio',            '140 mEq/L',       '136-145 mEq/L',    'Normal'),
(1, 'Potasio',          '4.2 mEq/L',       '3.5-5.0 mEq/L',   'Normal'),
(2, 'Glucosa en ayunas','135 mg/dL',        '70-100 mg/dL',     'Elevada'),
(2, 'HbA1c',            '7.2%',            'Menor a 6.5%',     'Control aceptable pero mejorable'),
(3, 'Hemograma',        'Leucocitos 12,500','4,500-11,000/mm3', 'Leucocitosis leve'),
(3, 'Diferencial',      'Linfocitos 55%',  '20-40%',           'Predominio linfocitario - compatible con viral'),
(7, 'TGO (AST)',        '25 U/L',          '10-40 U/L',        'Normal'),
(7, 'TGP (ALT)',        '30 U/L',          '7-56 U/L',         'Normal'),
(7, 'H. pylori IgG',    'Negativo',        'Negativo',          'Sin infección por H. pylori'),
(8, 'FEV1',             '72% predicho',    'Mayor a 80%',       'Obstrucción moderada'),
(8, 'FVC',              '85% predicho',    'Mayor a 80%',       'Normal'),
(8, 'FEV1/FVC',         '0.65',            'Mayor a 0.70',      'Reducido - patrón obstructivo');

-- ============================================================
-- PASO 11: Prescripciones
-- ============================================================
INSERT INTO prescripciones (id, cita_id, medico_id, paciente_id, fecha_prescripcion, estado, observaciones) VALUES
(1, 1,  1, 1,  DATE_SUB(CURDATE(), INTERVAL 25 DAY), 'Dispensada',  'Tratamiento antihipertensivo'),
(2, 2,  1, 3,  DATE_SUB(CURDATE(), INTERVAL 22 DAY), 'Dispensada',  'Tratamiento antidiabético'),
(3, 3,  1, 2,  DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'Dispensada',  'Tratamiento sintomático'),
(4, 4,  1, 9,  DATE_SUB(CURDATE(), INTERVAL 18 DAY), 'Pendiente',   'Tratamiento gastroprotector'),
(5, 5,  1, 7,  DATE_SUB(CURDATE(), INTERVAL 12 DAY), 'Pendiente',   'Analgésico y antiinflamatorio'),
(6, 8,  1, 5,  DATE_SUB(CURDATE(), INTERVAL 5 DAY),  'Pendiente',   'Tratamiento para crisis asmática'),
(7, 9,  3, 6,  DATE_SUB(CURDATE(), INTERVAL 3 DAY),  'Pendiente',   'Tratamiento antihipertensivo combinado'),
(8, 10, 1, 4,  DATE_SUB(CURDATE(), INTERVAL 2 DAY),  'Cancelada',   'Paciente prefiere tratamiento alternativo');

-- Detalle de prescripciones
INSERT INTO prescripcion_detalle (prescripcion_id, medicamento_id, medicamento_texto, dosis, frecuencia, duracion, cantidad_requerida, indicaciones) VALUES
(1, 5,  'Enalapril 10mg',         '10mg',  'Cada 12 horas',  '30 días', 60, 'Tomar con agua, en ayunas por la mañana'),
(2, 4,  'Metformina 850mg',       '850mg', 'Con desayuno y cena', '30 días', 60, 'Tomar con alimentos para evitar molestias gástricas'),
(3, 2,  'Paracetamol 500mg',      '500mg', 'Cada 8 horas',   '5 días',  15, 'No exceder 4g diarios'),
(4, 6,  'Omeprazol 20mg',         '20mg',  'En ayunas',      '30 días', 30, 'Tomar 30 min antes del desayuno'),
(5, 10, 'Diclofenac 50mg',        '50mg',  'Cada 8 horas',   '5 días',  15, 'Tomar con alimentos, no con estómago vacío'),
(6, 11, 'Salbutamol inhalador',   '2 puff','Cada 6 horas',   '15 días',  1, 'Agitar antes de usar, enjuagar boca después'),
(7, 7,  'Losartán 50mg',          '50mg',  'Una vez al día',  '30 días', 30, 'Tomar por la mañana'),
(7, 14, 'Hidroclorotiazida 25mg', '25mg',  'Una vez al día',  '30 días', 30, 'Tomar junto con Losartán'),
(8, 3,  'Ibuprofeno 400mg',       '400mg', 'Cada 8 horas',   '7 días',  21, 'Prescripción cancelada por paciente');

-- ============================================================
-- PASO 12: Facturas
-- ============================================================
INSERT INTO facturas (id, paciente_id, fecha, total, estado, metodo_pago) VALUES
(1,  1,  DATE_SUB(CURDATE(), INTERVAL 25 DAY), 1500.00, 'Pagada',    'Efectivo'),
(2,  3,  DATE_SUB(CURDATE(), INTERVAL 22 DAY), 2000.00, 'Pagada',    'Tarjeta'),
(3,  2,  DATE_SUB(CURDATE(), INTERVAL 15 DAY), 2300.00, 'Pagada',    'Seguro Médico'),
(4,  9,  DATE_SUB(CURDATE(), INTERVAL 18 DAY), 3300.00, 'Pagada',    'Efectivo'),
(5,  7,  DATE_SUB(CURDATE(), INTERVAL 12 DAY), 1500.00, 'Pagada',    'Tarjeta'),
(6,  14, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 1800.00, 'Pagada',    'Seguro Médico'),
(7,  19, DATE_SUB(CURDATE(), INTERVAL 8 DAY),  2500.00, 'Pendiente', NULL),
(8,  5,  DATE_SUB(CURDATE(), INTERVAL 5 DAY),  1850.00, 'Pendiente', NULL),
(9,  6,  DATE_SUB(CURDATE(), INTERVAL 3 DAY),  1500.00, 'Pendiente', NULL),
(10, 4,  DATE_SUB(CURDATE(), INTERVAL 2 DAY),  1500.00, 'Pendiente', NULL);

-- Detalle de facturas
INSERT INTO factura_detalle (factura_id, servicio_id, tipo_item, descripcion, cantidad, precio, subtotal) VALUES
(1,  1, 'consulta',     'Consulta general - control hipertensión',   1, 1500.00, 1500.00),
(2,  1, 'consulta',     'Consulta general - seguimiento diabetes',   1, 1500.00, 1500.00),
(2,  NULL, 'laboratorio', 'Glucosa en ayunas y HbA1c',               1,  500.00,  500.00),
(3,  1, 'consulta',     'Consulta general - infección respiratoria', 1, 1500.00, 1500.00),
(3,  NULL, 'laboratorio', 'Hemograma completo',                      1,  800.00,  800.00),
(4,  1, 'consulta',     'Consulta general - gastritis',              1, 1500.00, 1500.00),
(4,  NULL, 'laboratorio', 'Pruebas hepáticas y H. pylori',           1, 1800.00, 1800.00),
(5,  1, 'consulta',     'Consulta general - lumbalgia',              1, 1500.00, 1500.00),
(6,  NULL, 'consulta',  'Consulta pediátrica',                       1, 1800.00, 1800.00),
(7,  NULL, 'consulta',  'Consulta especializada - cardiología',      1, 2500.00, 2500.00),
(8,  1, 'consulta',     'Consulta general - crisis asmática',        1, 1500.00, 1500.00),
(8,  NULL, 'medicamento','Salbutamol inhalador',                     1,  350.00,  350.00),
(9,  NULL, 'consulta',  'Consulta especializada - cardiología',      1, 1500.00, 1500.00),
(10, 1, 'consulta',     'Consulta general - dolor articular',        1, 1500.00, 1500.00);

-- ============================================================
-- PASO 13: Ventas farmacia (3 ventas para prescripciones dispensadas)
-- ============================================================
INSERT INTO ventas_farmacia (id, paciente_id, usuario_id, factura_id, fecha_venta, total, estado) VALUES
(1, 1, 7, NULL, DATE_SUB(CURDATE(), INTERVAL 25 DAY), 570.00,  'Completada'),
(2, 3, 7, NULL, DATE_SUB(CURDATE(), INTERVAL 22 DAY), 720.00,  'Completada'),
(3, 2, 7, NULL, DATE_SUB(CURDATE(), INTERVAL 15 DAY),  50.00,  'Completada');

INSERT INTO ventas_farmacia_detalle (venta_id, medicamento_id, cantidad, precio_unitario, subtotal) VALUES
(1, 5,  60, 9.50,  570.00),
(2, 4,  60, 12.00, 720.00),
(3, 2,  15, 3.33,   50.00);

-- ============================================================
-- PASO 14: Logs de auditoría (entradas de ejemplo)
-- ============================================================
INSERT INTO logs (usuario_id, accion, modulo, descripcion, ip, nivel, metodo_http) VALUES
(1, 'LOGIN',              'Autenticación', 'Inicio de sesión exitoso - Admin Sistema',       '127.0.0.1', 'INFO', 'POST'),
(2, 'LOGIN',              'Autenticación', 'Inicio de sesión exitoso - María González',      '127.0.0.1', 'INFO', 'POST'),
(3, 'LOGIN',              'Autenticación', 'Inicio de sesión exitoso - Dr. Rafael Méndez',   '127.0.0.1', 'INFO', 'POST'),
(7, 'DISPENSAR',           'Farmacia',     'Dispensación de Enalapril 10mg - 60 unidades',   '127.0.0.1', 'INFO', 'POST'),
(7, 'DISPENSAR',           'Farmacia',     'Dispensación de Metformina 850mg - 60 unidades', '127.0.0.1', 'INFO', 'POST'),
(1, 'CREAR_USUARIO',       'Usuarios',     'Nuevo usuario creado: carmen.reyes@hospitall.do','127.0.0.1', 'INFO', 'POST'),
(6, 'REGISTRAR_RESULTADO', 'Laboratorio',  'Resultado registrado para orden #1',            '127.0.0.1', 'INFO', 'POST'),
(2, 'CREAR_CITA',          'Citas',        'Cita creada para paciente Pedro Sánchez',       '127.0.0.1', 'INFO', 'POST');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Fin del seeder
-- ============================================================

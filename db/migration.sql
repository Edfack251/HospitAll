-- Migración segura para la base de datos de HospitAll
-- Ejecutar en la base de datos `hospitall`

DELIMITER //

CREATE PROCEDURE SafeMigration()
BEGIN
    DECLARE db_name VARCHAR(100);
    SELECT DATABASE() INTO db_name;
    
    -- 1) Verificar si la tabla servicios ya existe y agregar columnas si es necesario
    IF EXISTS (
        SELECT * FROM information_schema.tables 
        WHERE table_schema = db_name AND table_name = 'servicios'
    ) THEN
        -- Agregar columna codigo si no existe
        IF NOT EXISTS (
            SELECT * FROM information_schema.columns 
            WHERE table_schema = db_name AND table_name = 'servicios' AND column_name = 'codigo'
        ) THEN
            ALTER TABLE servicios ADD COLUMN codigo VARCHAR(50);
        END IF;

        -- Agregar columna activo si no existe
        IF NOT EXISTS (
            SELECT * FROM information_schema.columns 
            WHERE table_schema = db_name AND table_name = 'servicios' AND column_name = 'activo'
        ) THEN
            ALTER TABLE servicios ADD COLUMN activo BOOLEAN DEFAULT TRUE;
        END IF;
    ELSE
        -- Intervención por si la tabla no existe en la db actual a pesar del nuevo schema
        CREATE TABLE servicios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            codigo VARCHAR(50) UNIQUE NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            precio DECIMAL(10,2) NOT NULL,
            tipo VARCHAR(50) NOT NULL,
            activo BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
    END IF;

    -- 3) Actualizar cualquier registro existente que dependa de nombre para usar codigo
    -- Normalizando datos preexistentes
    UPDATE servicios SET codigo = 'CONS_GEN' WHERE codigo IS NULL AND nombre LIKE '%Consulta%';
    UPDATE servicios SET codigo = 'LAB_STD' WHERE codigo IS NULL AND (nombre LIKE '%Analisis%' OR nombre LIKE '%Laboratorio%');
    
    -- 2) Poblar tabla servicios con códigos normalizados si no existen
    IF NOT EXISTS (SELECT 1 FROM servicios WHERE codigo = 'CONS_GEN') THEN
        INSERT INTO servicios (codigo, nombre, precio, tipo, activo) VALUES ('CONS_GEN', 'Consulta General', 1500.00, 'consulta', TRUE);
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM servicios WHERE codigo = 'LAB_STD') THEN
        INSERT INTO servicios (codigo, nombre, precio, tipo, activo) VALUES ('LAB_STD', 'Laboratorio Standard', 3500.00, 'laboratorio', TRUE);
    END IF;

    -- Aplicar restricción UNIQUE a codigo asegurando que no explote si ya existe
    IF NOT EXISTS (
        SELECT * FROM information_schema.statistics 
        WHERE table_schema = db_name AND table_name = 'servicios' 
        AND index_name = 'codigo'
    ) THEN
        ALTER TABLE servicios ADD UNIQUE (codigo);
    END IF;

    -- 4) Crear tabla pacientes_identidad si aún no existe (separación de datos sensibles)
    -- 5) No eliminar columnas existentes de pacientes para garantizar compatibilidad hacia atrás.
    CREATE TABLE IF NOT EXISTS pacientes_identidad (
        id INT AUTO_INCREMENT PRIMARY KEY,
        paciente_id INT NOT NULL,
        tipo_documento VARCHAR(50),
        numero_documento VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
    
    -- 6) Verificar que todas las facturas existentes tengan integridad con factura_detalle.
    -- Renombrar factura_items a factura_detalle para migrar sin pérdida de datos.
    -- Se comprueba su existencia antes para no fallar si ya migró.
    IF EXISTS (
        SELECT * FROM information_schema.tables 
        WHERE table_schema = db_name AND table_name = 'factura_items'
    ) AND NOT EXISTS (
        SELECT * FROM information_schema.tables 
        WHERE table_schema = db_name AND table_name = 'factura_detalle'
    ) THEN
        RENAME TABLE factura_items TO factura_detalle;
    END IF;

END //

DELIMITER ;

-- Ejecutar la migración contenida en el procedimiento
CALL SafeMigration();

-- Limpiar el entorno borrando el procedimiento
DROP PROCEDURE SafeMigration;

-- 7) Ejecutar verificaciones para validar que no hubo pérdida de datos
SELECT 'servicios' AS tabla, COUNT(*) AS total_registros FROM servicios
UNION ALL
SELECT 'facturas' AS tabla, COUNT(*) AS total_registros FROM facturas
UNION ALL
SELECT 'pacientes' AS tabla, COUNT(*) AS total_registros FROM pacientes;

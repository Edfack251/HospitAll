-- migrate_phase2.sql
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

ALTER TABLE citas 
ADD COLUMN episodio_id INT NULL AFTER medico_id,
ADD COLUMN estado_clinico VARCHAR(50) DEFAULT 'check_in' AFTER estado,
ADD CONSTRAINT fk_citas_episodio FOREIGN KEY (episodio_id) REFERENCES episodios_clinicos(id) ON DELETE SET NULL;

ALTER TABLE historial_clinico
ADD COLUMN episodio_id INT NULL AFTER medico_id,
ADD COLUMN adenda_de_id INT NULL AFTER observaciones,
ADD COLUMN activo BOOLEAN DEFAULT 1 AFTER adenda_de_id,
ADD CONSTRAINT fk_historial_episodio FOREIGN KEY (episodio_id) REFERENCES episodios_clinicos(id) ON DELETE SET NULL,
ADD CONSTRAINT fk_historial_adenda FOREIGN KEY (adenda_de_id) REFERENCES historial_clinico(id) ON DELETE CASCADE;

-- Migration: Add Soft Delete column and indexes to critical tables
-- Tables: pacientes, usuarios, medicos, medicamentos, ordenes_laboratorio, prescripciones

ALTER TABLE pacientes ADD COLUMN deleted_at DATETIME NULL;
CREATE INDEX idx_pacientes_deleted_at ON pacientes(deleted_at);

ALTER TABLE usuarios ADD COLUMN deleted_at DATETIME NULL;
CREATE INDEX idx_usuarios_deleted_at ON usuarios(deleted_at);

ALTER TABLE medicos ADD COLUMN deleted_at DATETIME NULL;
CREATE INDEX idx_medicos_deleted_at ON medicos(deleted_at);

ALTER TABLE medicamentos ADD COLUMN deleted_at DATETIME NULL;
CREATE INDEX idx_medicamentos_deleted_at ON medicamentos(deleted_at);

ALTER TABLE ordenes_laboratorio ADD COLUMN deleted_at DATETIME NULL;
CREATE INDEX idx_ordenes_laboratorio_deleted_at ON ordenes_laboratorio(deleted_at);

ALTER TABLE prescripciones ADD COLUMN deleted_at DATETIME NULL;
CREATE INDEX idx_prescripciones_deleted_at ON prescripciones(deleted_at);

<?php
namespace App\Repositories;

use PDO;
use Exception;

class HospitalizationRepository extends BaseRepository
{
    /**
     * Obtiene todas las camas disponibles con su información de habitación.
     */
    public function getCamasDisponibles(): array
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT c.*, h.numero as habitacion_numero, h.piso, h.tipo as habitacion_tipo 
                FROM camas c 
                JOIN habitaciones h ON c.habitacion_id = h.id 
                WHERE c.estado = 'disponible' AND c.deleted_at IS NULL 
                ORDER BY h.numero ASC, c.numero ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el detalle de una cama por ID.
     */
    public function getCamaById(int $id): ?array
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT c.*, h.numero as habitacion_numero, h.piso, h.tipo as habitacion_tipo 
                FROM camas c 
                JOIN habitaciones h ON c.habitacion_id = h.id 
                WHERE c.id = ? AND c.deleted_at IS NULL";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Registra un nuevo internamiento y actualiza el estado de la cama.
     */
    public function internarPaciente(array $data): int
    {
        try {
            $this->pdo->beginTransaction();

            $sql = "INSERT INTO internamientos (
                        paciente_id, medico_id, cama_id, origen, 
                        emergencia_id, cita_id, motivo_internamiento, 
                        diagnostico_ingreso, estado, fecha_ingreso
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'activo', NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['paciente_id'],
                $data['medico_id'],
                $data['cama_id'],
                $data['origen'],
                $data['emergencia_id'] ?? null,
                $data['cita_id'] ?? null,
                $data['motivo_internamiento'],
                $data['diagnostico_ingreso'] ?? null
            ]);

            $internamiento_id = (int)$this->pdo->lastInsertId();

            // Actualizar estado de la cama
            $sqlCama = "UPDATE camas SET estado = 'ocupada' WHERE id = ?";
            $stmtCama = $this->pdo->prepare($sqlCama);
            $stmtCama->execute([$data['cama_id']]);

            $this->pdo->commit();
            return $internamiento_id;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Obtiene todos los internamientos activos.
     */
    public function getInternamientosActivos(?int $medico_id = null): array
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT i.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, 
                       p.identificacion as paciente_cedula,
                       m.nombre as medico_nombre, m.apellido as medico_apellido,
                       c.numero as cama_numero, h.numero as habitacion_numero,
                       DATEDIFF(NOW(), i.fecha_ingreso) as dias_internado
                FROM internamientos i
                JOIN pacientes p ON i.paciente_id = p.id
                JOIN medicos m ON i.medico_id = m.id
                JOIN camas c ON i.cama_id = c.id
                JOIN habitaciones h ON c.habitacion_id = h.id
                WHERE i.estado = 'activo' AND i.deleted_at IS NULL";
        
        $params = [];
        if ($medico_id !== null) {
            $sql .= " AND i.medico_id = ?";
            $params[] = $medico_id;
        }

        $sql .= " ORDER BY i.fecha_ingreso ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el detalle completo de un internamiento.
     */
    public function getInternamientoById(int $id): ?array
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT i.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, 
                       p.identificacion as paciente_identificacion,
                       m.nombre as medico_nombre, m.apellido as medico_apellido,
                       c.numero as cama_numero, h.numero as habitacion_numero, h.tipo as habitacion_tipo
                FROM internamientos i
                JOIN pacientes p ON i.paciente_id = p.id
                JOIN medicos m ON i.medico_id = m.id
                JOIN camas c ON i.cama_id = c.id
                JOIN habitaciones h ON c.habitacion_id = h.id
                WHERE i.id = ? AND i.deleted_at IS NULL";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Verifica si un paciente tiene un internamiento activo.
     */
    public function getInternamientoActivoPaciente(int $paciente_id): ?array
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT * FROM internamientos 
                WHERE paciente_id = ? AND estado = 'activo' AND deleted_at IS NULL 
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$paciente_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Registra una ronda de enfermería.
     */
    public function registrarRonda(array $data): int
    {
        $sql = "INSERT INTO rondas_enfermeria (
                    internamiento_id, enfermera_id, presion_arterial, 
                    frecuencia_cardiaca, temperatura, peso, 
                    saturacion_oxigeno, observaciones, medicamentos_administrados
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['internamiento_id'],
            $data['enfermera_id'],
            $data['presion_arterial'] ?? null,
            $data['frecuencia_cardiaca'] ?? null,
            $data['temperatura'] ?? null,
            $data['peso'] ?? null,
            $data['saturacion_oxigeno'] ?? null,
            $data['observaciones'] ?? null,
            $data['medicamentos_administrados'] ?? null
        ]);
        
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Obtiene las rondas de un internamiento.
     */
    public function getRondasInternamiento(int $internamiento_id): array
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT r.*, u.nombre as enfermera_nombre, u.apellido as enfermera_apellido
                FROM rondas_enfermeria r
                JOIN usuarios u ON r.enfermera_id = u.id
                WHERE r.internamiento_id = ? AND r.deleted_at IS NULL
                ORDER BY r.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$internamiento_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registra una evolución médica.
     */
    public function registrarEvolucion(array $data): int
    {
        $sql = "INSERT INTO evoluciones_medicas (
                    internamiento_id, medico_id, evolucion, 
                    indicaciones, diagnostico_actualizado
                ) VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['internamiento_id'],
            $data['medico_id'],
            $data['evolucion'],
            $data['indicaciones'] ?? null,
            $data['diagnostico_actualizado'] ?? null
        ]);
        
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Obtiene las evoluciones de un internamiento.
     */
    public function getEvolucionesInternamiento(int $internamiento_id): array
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT e.*, m.nombre as medico_nombre, m.apellido as medico_apellido
                FROM evoluciones_medicas e
                JOIN medicos m ON e.medico_id = m.id
                WHERE e.internamiento_id = ? AND e.deleted_at IS NULL
                ORDER BY e.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$internamiento_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Procesa el alta médica de un paciente.
     */
    public function darAlta(int $internamiento_id, int $medico_id, string $observaciones): bool
    {
        try {
            $this->pdo->beginTransaction();

            // 1. Obtener ID de la cama antes de actualizar el internamiento
            $sqlInfo = "SELECT cama_id FROM internamientos WHERE id = ?";
            $stmtInfo = $this->pdo->prepare($sqlInfo);
            $stmtInfo->execute([$internamiento_id]);
            $internamiento = $stmtInfo->fetch(PDO::FETCH_ASSOC);

            if (!$internamiento) {
                throw new Exception("Internamiento no encontrado.");
            }

            $cama_id = $internamiento['cama_id'];

            // 2. Actualizar internamiento
            $sqlAlta = "UPDATE internamientos SET 
                            estado = 'alta', 
                            fecha_alta = NOW(), 
                            medico_alta_id = ?, 
                            observaciones_alta = ?
                        WHERE id = ?";
            $stmtAlta = $this->pdo->prepare($sqlAlta);
            $stmtAlta->execute([$medico_id, $observaciones, $internamiento_id]);

            // 3. Poner cama en limpieza
            $sqlCama = "UPDATE camas SET estado = 'en_limpieza' WHERE id = ?";
            $stmtCama = $this->pdo->prepare($sqlCama);
            $stmtCama->execute([$cama_id]);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Obtiene el historial de internamientos de un paciente.
     */
    public function getHistorialInternamientos(int $paciente_id): array
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT i.*, m.nombre as medico_nombre, m.apellido as medico_apellido,
                       c.numero as cama_numero, h.numero as habitacion_numero
                FROM internamientos i
                JOIN medicos m ON i.medico_id = m.id
                JOIN camas c ON i.cama_id = c.id
                JOIN habitaciones h ON c.habitacion_id = h.id
                WHERE i.paciente_id = ? AND i.deleted_at IS NULL
                ORDER BY i.fecha_ingreso DESC LIMIT 500";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$paciente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

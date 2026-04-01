<?php
namespace App\Repositories;

use PDO;

class NursingRepository extends BaseRepository
{
    /**
     * Citas de hoy con estado Programada, Confirmada o En espera.
     * Incluye flag signos_registrados según LEFT JOIN a atenciones.
     */
    public function getPacientesAsignados(?int $enfermera_id = null): array
    {
        $sql = "SELECT c.id as cita_id, c.paciente_id, c.medico_id, c.fecha, c.hora, c.estado,
                p.nombre as paciente_nombre, p.apellido as paciente_apellido,
                m.nombre as medico_nombre, m.apellido as medico_apellido,
                (a.id IS NOT NULL AND (
                    a.presion_arterial IS NOT NULL OR a.frecuencia_cardiaca IS NOT NULL
                    OR a.temperatura IS NOT NULL OR a.peso IS NOT NULL OR a.estatura IS NOT NULL
                )) as signos_registrados
                FROM citas c
                JOIN pacientes p ON c.paciente_id = p.id
                JOIN medicos m ON c.medico_id = m.id
                LEFT JOIN atenciones a ON a.cita_id = c.id";
        
        if ($enfermera_id !== null) {
            $sql .= " JOIN asignaciones_enfermeria ae ON ae.cita_id = c.id AND ae.enfermera_id = :enfermera_id";
        }

        $sql .= " WHERE c.fecha = CURDATE()
                AND c.estado IN ('Programada', 'Confirmada', 'En espera', 'En consulta')
                ORDER BY c.hora ASC";
        
        $stmt = $this->pdo->prepare($sql);
        if ($enfermera_id !== null) {
            $stmt->bindValue(':enfermera_id', $enfermera_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['signos_registrados'] = (bool) $row['signos_registrados'];
        }
        return $rows;
    }

    /**
     * Obtiene los signos vitales de una cita desde atenciones.
     */
    public function getSignosVitalesCita(int $cita_id): ?array
    {
        $sql = "SELECT presion_arterial, frecuencia_cardiaca, temperatura, peso, estatura
                FROM atenciones WHERE cita_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$cita_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Inserta o actualiza signos vitales en atenciones.
     */
    public function registrarSignosVitales(int $cita_id, int $medico_id, int $paciente_id, array $data): bool
    {
        $sql = "SELECT id FROM atenciones WHERE cita_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$cita_id]);
        $existe = $stmt->fetch(PDO::FETCH_ASSOC);

        $pa = $data['presion_arterial'] ?? null;
        $fc = isset($data['frecuencia_cardiaca']) && $data['frecuencia_cardiaca'] !== '' ? (int) $data['frecuencia_cardiaca'] : null;
        $temp = isset($data['temperatura']) && $data['temperatura'] !== '' ? (float) $data['temperatura'] : null;
        $peso = isset($data['peso']) && $data['peso'] !== '' ? (float) $data['peso'] : null;
        $est = isset($data['estatura']) && $data['estatura'] !== '' ? (float) $data['estatura'] : null;

        if ($existe) {
            $sql = "UPDATE atenciones SET
                    presion_arterial = ?,
                    frecuencia_cardiaca = ?,
                    temperatura = ?,
                    peso = ?,
                    estatura = ?
                    WHERE cita_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$pa, $fc, $temp, $peso, $est, $cita_id]);
        }

        $sql = "INSERT INTO atenciones (cita_id, medico_id, paciente_id, presion_arterial, frecuencia_cardiaca, temperatura, peso, estatura)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$cita_id, $medico_id, $paciente_id, $pa, $fc, $temp, $peso, $est]);
    }

    /**
     * Registra una observación de enfermería.
     */
    public function registrarObservacion(int $cita_id, int $paciente_id, int $usuario_id, string $observaciones): bool
    {
        $sql = "INSERT INTO observaciones_enfermeria (cita_id, paciente_id, usuario_id, observaciones)
                VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$cita_id, $paciente_id, $usuario_id, $observaciones]);
    }

    /**
     * Obtiene observaciones de enfermería por paciente.
     */
    public function getObservacionesPaciente(int $paciente_id): array
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT * FROM observaciones_enfermeria WHERE paciente_id = ? ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$paciente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registra un ingreso de emergencia.
     */
    public function registrarEmergencia(int $paciente_id, int $usuario_id, string $nivel_triage, string $motivo_ingreso): int
    {
        $sql = "INSERT INTO emergencias (paciente_id, medico_id, usuario_id, nivel_triage, motivo_ingreso, estado)
                VALUES (?, NULL, ?, ?, ?, 'En espera')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$paciente_id, $usuario_id, $nivel_triage, $motivo_ingreso]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Asigna un médico a una emergencia y cambia estado a En atención.
     */
    public function asignarMedico(int $emergencia_id, int $medico_id): bool
    {
        $sql = "UPDATE emergencias SET medico_id = ?, estado = 'En atención' WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$medico_id, $emergencia_id]);
    }

    /**
     * Emergencias activas (En espera, En atención) con JOIN a pacientes y medicos.
     */
    public function getEmergenciasActivas(): array
    {
        $sql = "SELECT e.id, e.paciente_id, e.medico_id, e.nivel_triage, e.motivo_ingreso, e.estado, e.fecha_ingreso,
                p.nombre as paciente_nombre, p.apellido as paciente_apellido,
                m.nombre as medico_nombre, m.apellido as medico_apellido
                FROM emergencias e
                JOIN pacientes p ON e.paciente_id = p.id
                LEFT JOIN medicos m ON e.medico_id = m.id
                WHERE e.estado IN ('En espera', 'En atención')
                ORDER BY FIELD(e.nivel_triage, 'Rojo', 'Naranja', 'Amarillo', 'Verde'), e.fecha_ingreso ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Todas las emergencias del día.
     */
    public function getEmergenciasHoy(): array
    {
        $sql = "SELECT e.id, e.paciente_id, e.medico_id, e.nivel_triage, e.motivo_ingreso, e.estado, e.fecha_ingreso,
                p.nombre as paciente_nombre, p.apellido as paciente_apellido,
                m.nombre as medico_nombre, m.apellido as medico_apellido
                FROM emergencias e
                JOIN pacientes p ON e.paciente_id = p.id
                LEFT JOIN medicos m ON e.medico_id = m.id
                WHERE DATE(e.fecha_ingreso) = CURDATE()
                ORDER BY FIELD(e.nivel_triage, 'Rojo', 'Naranja', 'Amarillo', 'Verde'), e.fecha_ingreso ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista de médicos para asignar en emergencias.
     */
    public function getMedicosDisponibles(): array
    {
        $sql = "SELECT id, nombre, apellido, especialidad FROM medicos ORDER BY nombre ASC";
        $sql = $this->applySoftDeleteFilter($sql, 'medicos');
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Actualiza el estado de una emergencia.
     */
    public function actualizarEstadoEmergencia(int $id, string $estado): bool
    {
        $sql = "UPDATE emergencias SET estado = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$estado, $id]);
    }

    /**
     * Obtiene emergencia por ID para validar transiciones.
     */
    public function getEmergenciaById(int $id): ?array
    {
        $sql = "SELECT id, estado FROM emergencias WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Pacientes para el select del modal de emergencias.
     */
    public function getPacientesParaSelect(): array
    {
        $sql = "SELECT id, nombre, apellido, identificacion FROM pacientes ORDER BY nombre ASC";
        $sql = $this->applySoftDeleteFilter($sql, 'pacientes');
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

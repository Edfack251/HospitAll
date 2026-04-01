<?php
namespace App\Repositories;

use PDO;

class NursingAssignmentRepository
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Asigna una enfermera a un internamiento.
     */
    public function asignar(int $internamiento_id, int $enfermera_id, int $asignado_por): bool
    {
        // 1. Obtener el paciente_id desde el internamiento
        $sql = "SELECT paciente_id FROM internamientos WHERE id = ? AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$internamiento_id]);
        $internamiento = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$internamiento) {
            return false;
        }

        $paciente_id = $internamiento['paciente_id'];

        // 2. Insertar la asignación
        $sql = "INSERT INTO asignaciones_enfermeria (internamiento_id, paciente_id, enfermera_id, asignado_por) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$internamiento_id, $paciente_id, $enfermera_id, $asignado_por]);
    }

    /**
     * Obtiene las asignaciones activas (pacientes internados actualmente).
     */
    public function getAsignacionesDia($fecha = null): array
    {
        $sql = "SELECT ae.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido,
                       u.nombre as enfermera_nombre, u.apellido as enfermera_apellido,
                       h.numero as habitacion_numero, c.numero as cama_numero,
                       m.nombre as medico_nombre, m.apellido as medico_apellido,
                       i.fecha_ingreso
                FROM asignaciones_enfermeria ae
                JOIN internamientos i ON ae.internamiento_id = i.id
                JOIN pacientes p ON ae.paciente_id = p.id
                JOIN usuarios u ON ae.enfermera_id = u.id
                JOIN medicos m ON i.medico_id = m.id
                JOIN camas c ON i.cama_id = c.id
                JOIN habitaciones h ON c.habitacion_id = h.id
                WHERE i.estado = 'activo' AND i.deleted_at IS NULL
                ORDER BY i.fecha_ingreso DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene internamientos activos que aún no tienen enfermera asignada.
     */
    public function getInternamientosSinAsignar(): array
    {
        $sql = "SELECT i.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido,
                       h.numero as habitacion_numero, c.numero as cama_numero,
                       m.nombre as medico_nombre, m.apellido as medico_apellido
                FROM internamientos i
                JOIN pacientes p ON i.paciente_id = p.id
                JOIN medicos m ON i.medico_id = m.id
                JOIN camas c ON i.cama_id = c.id
                JOIN habitaciones h ON c.habitacion_id = h.id
                LEFT JOIN asignaciones_enfermeria ae ON i.id = ae.internamiento_id
                WHERE i.estado = 'activo' AND i.deleted_at IS NULL
                AND ae.id IS NULL
                ORDER BY i.fecha_ingreso ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEnfermerasDisponibles(): array
    {
        $sql = "SELECT u.id, u.nombre, u.apellido 
                FROM usuarios u
                JOIN roles r ON u.rol_id = r.id
                WHERE r.nombre = 'Enfermera' AND u.deleted_at IS NULL";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function quitarAsignacion(int $id): bool
    {
        $sql = "DELETE FROM asignaciones_enfermeria WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }
}

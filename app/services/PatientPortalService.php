<?php
namespace App\Services;

use Exception;
use PDO;
use PDOException;

class PatientPortalService
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene todos los datos necesarios para el portal del paciente.
     * 
     * @param int $paciente_id ID del paciente
     * @return array [citas_proximas, historial, laboratorio]
     */
    public function getPatientPortalData(int $paciente_id): array
    {
        try {
            // 1. Obtener citas próximas (Programadas, Confirmadas, En espera)
            $stmt_citas = $this->pdo->prepare("SELECT c.*, m.nombre as medico_nombre, m.apellido as medico_apellido
                FROM citas c
                JOIN medicos m ON c.medico_id = m.id
                WHERE c.paciente_id = ? AND c.estado NOT IN ('Atendida', 'Cancelada', 'No asistió')
                ORDER BY c.fecha ASC, c.hora ASC");
            $stmt_citas->execute([$paciente_id]);
            $citas_proximas = $stmt_citas->fetchAll();

            // 2. Obtener historial clínico con conteo de órdenes pendientes
            $stmt_historial = $this->pdo->prepare("SELECT h.*, m.nombre as medico_nombre, m.apellido as medico_apellido, c.fecha,
                    (SELECT COUNT(*) FROM ordenes_laboratorio ol WHERE ol.historial_id = h.id AND ol.estado = 'Pendiente') as ordenes_pendientes
                FROM historial_clinico h
                JOIN medicos m ON h.medico_id = m.id
                JOIN citas c ON h.cita_id = c.id
                WHERE h.paciente_id = ?
                ORDER BY c.fecha DESC");
            $stmt_historial->execute([$paciente_id]);
            $historial = $stmt_historial->fetchAll();

            // 3. Obtener órdenes de laboratorio
            $stmt_lab = $this->pdo->prepare("SELECT ol.*, c.fecha
                FROM ordenes_laboratorio ol
                JOIN historial_clinico h ON ol.historial_id = h.id
                JOIN citas c ON h.cita_id = c.id
                WHERE h.paciente_id = ?
                ORDER BY c.fecha DESC");
            $stmt_lab->execute([$paciente_id]);
            $laboratorio = $stmt_lab->fetchAll();

            // 4. Obtener prescripciones médicas
            $stmt_prescrip = $this->pdo->prepare("SELECT p.*, m.nombre as medico_nombre, m.apellido as medico_apellido, c.fecha
                FROM prescripciones p
                JOIN medicos m ON p.medico_id = m.id
                JOIN citas c ON p.cita_id = c.id
                WHERE p.paciente_id = ?
                ORDER BY c.fecha DESC");
            $stmt_prescrip->execute([$paciente_id]);
            $prescripciones = $stmt_prescrip->fetchAll();

            return [
                'citas_proximas' => $citas_proximas,
                'historial' => $historial,
                'laboratorio' => $laboratorio,
                'prescripciones' => $prescripciones
            ];
        } catch (PDOException $e) {
            error_log("Error PatientPortalService::getPatientPortalData: " . $e->getMessage());
            // En caso de error, retornamos arrays vacíos para no romper la vista
            return [
                'citas_proximas' => [],
                'historial' => [],
                'laboratorio' => [],
                'prescripciones' => []
            ];
        }
    }
}

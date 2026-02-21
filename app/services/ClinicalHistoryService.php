<?php

class ClinicalHistoryService
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get the search list for patients.
     * @return array
     */
    public function getPatientSearchList(): array
    {
        $stmt = $this->pdo->query("SELECT p.*, (SELECT MAX(fecha) FROM citas WHERE paciente_id = p.id) as última_cita 
                                  FROM pacientes p ORDER BY nombre ASC");
        return $stmt->fetchAll();
    }

    /**
     * Get full clinical history for a patient.
     * @param int $patient_id
     * @return array
     */
    public function getPatientFullHistory(int $patient_id): array
    {
        $data = [
            'patient_data' => null,
            'history' => [],
            'labs' => []
        ];

        // 1. Get basic patient data
        $stmt = $this->pdo->prepare("SELECT * FROM pacientes WHERE id = ?");
        $stmt->execute([$patient_id]);
        $patient_data = $stmt->fetch();

        if ($patient_data) {
            // Calculate age
            $cumpleanos = new DateTime($patient_data['fecha_nacimiento']);
            $hoy = new DateTime();
            $patient_data['edad'] = $hoy->diff($cumpleanos)->y;
            $data['patient_data'] = $patient_data;

            // 2. Get consultations history
            $stmt_hist = $this->pdo->prepare("SELECT h.*, m.nombre as medico_nombre, m.apellido as medico_apellido, c.fecha
                                             FROM historial_clinico h
                                             JOIN medicos m ON h.medico_id = m.id
                                             JOIN citas c ON h.cita_id = c.id
                                             WHERE h.paciente_id = ?
                                             ORDER BY c.fecha DESC");
            $stmt_hist->execute([$patient_id]);
            $data['history'] = $stmt_hist->fetchAll();

            // 3. Get laboratory orders
            $stmt_lab = $this->pdo->prepare("SELECT ol.*, c.fecha as fecha_cita
                                            FROM ordenes_laboratorio ol
                                            JOIN historial_clinico h ON ol.historial_id = h.id
                                            JOIN citas c ON h.cita_id = c.id
                                            WHERE h.paciente_id = ?
                                            ORDER BY ol.created_at DESC");
            $stmt_lab->execute([$patient_id]);
            $data['labs'] = $stmt_lab->fetchAll();
        }

        return $data;
    }
}

<?php
namespace App\Repositories;

use PDO;
use Exception;

class ClinicalHistoryRepository
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getPatientSearchList()
    {
        $stmt = $this->pdo->query("SELECT p.*, (SELECT MAX(fecha) FROM citas WHERE paciente_id = p.id) as última_cita 
                                  FROM pacientes p ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBasicPatientData($patient_id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM pacientes WHERE id = ?");
        $stmt->execute([$patient_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getConsultationsHistory($patient_id)
    {
        $stmt_hist = $this->pdo->prepare("SELECT h.*, m.nombre as medico_nombre, m.apellido as medico_apellido, c.fecha
                                         FROM historial_clinico h
                                         JOIN medicos m ON h.medico_id = m.id
                                         JOIN citas c ON h.cita_id = c.id
                                         WHERE h.paciente_id = ? AND h.activo = 1
                                         ORDER BY c.fecha DESC");
        $stmt_hist->execute([$patient_id]);
        return $stmt_hist->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLaboratoryOrders($patient_id)
    {
        $stmt_lab = $this->pdo->prepare("SELECT ol.*, c.fecha as fecha_cita
                                        FROM ordenes_laboratorio ol
                                        JOIN historial_clinico h ON ol.historial_id = h.id
                                        JOIN citas c ON h.cita_id = c.id
                                        WHERE h.paciente_id = ?
                                        ORDER BY ol.created_at DESC");
        $stmt_lab->execute([$patient_id]);
        return $stmt_lab->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getByCitaId($cita_id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM historial_clinico WHERE cita_id = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$cita_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getFullHistoryChainByEncounter($cita_id)
    {
        // En un modelo append-only, podríamos querer ver todas las versiones o solo la cadena
        $stmt = $this->pdo->prepare("SELECT h.*, m.nombre as medico_nombre, m.apellido as medico_apellido 
                                     FROM historial_clinico h
                                     JOIN medicos m ON h.medico_id = m.id
                                     WHERE h.cita_id = ? 
                                     ORDER BY h.created_at ASC");
        $stmt->execute([$cita_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($cita_id, $paciente_id, $medico_id, $diagnostico, $tratamiento, $observaciones)
    {
        $sql = "INSERT INTO historial_clinico (cita_id, paciente_id, medico_id, diagnostico, tratamiento, observaciones, activo) 
                VALUES (?, ?, ?, ?, ?, ?, 1)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$cita_id, $paciente_id, $medico_id, $diagnostico, $tratamiento, $observaciones]);
        return $this->pdo->lastInsertId();
    }

    public function createAdenda($parent_id, $diagnostico, $tratamiento, $observaciones)
    {
        // Obtener datos del padre para mantener consistencia
        $stmt = $this->pdo->prepare("SELECT cita_id, paciente_id, medico_id FROM historial_clinico WHERE id = ?");
        $stmt->execute([$parent_id]);
        $parent = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$parent)
            return false;

        $sql = "INSERT INTO historial_clinico (cita_id, paciente_id, medico_id, diagnostico, tratamiento, observaciones, adenda_de_id, activo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $parent['cita_id'],
            $parent['paciente_id'],
            $parent['medico_id'],
            $diagnostico,
            $tratamiento,
            $observaciones,
            $parent_id
        ]);
        return $this->pdo->lastInsertId();
    }

    public function markAsObsolete($id)
    {
        $stmt = $this->pdo->prepare("UPDATE historial_clinico SET activo = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getConsultasRecientes($medico_id, $limit = 5)
    {
        $sql = "SELECT hc.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.identificacion as paciente_identificacion, c.fecha as fecha_cita
                FROM historial_clinico hc
                JOIN pacientes p ON hc.paciente_id = p.id
                JOIN citas c ON hc.cita_id = c.id
                WHERE hc.medico_id = :medico_id AND hc.activo = 1
                ORDER BY hc.created_at DESC
                LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':medico_id', (int) $medico_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el historial clínico completo consolidado de un paciente.
     * Incluye datos de identidad, últimas consultas, prescripciones y laboratorios.
     */
    public function getFullClinicalHistoryByPatientId($patient_id, $limit = 15)
    {
        // 1. Datos básicos e identidad
        $stmt_p = $this->pdo->prepare("SELECT p.*, pi.grupo_sanguineo, pi.alergias, pi.discapacidad, pi.contacto_emergencia_nombre, pi.contacto_emergencia_telefono
                                       FROM pacientes p 
                                       LEFT JOIN pacientes_identidad pi ON p.id = pi.paciente_id 
                                       WHERE p.id = ?");
        $stmt_p->execute([$patient_id]);
        $patient = $stmt_p->fetch(PDO::FETCH_ASSOC);

        if (!$patient)
            return null;

        // 2. Consultas recientes
        $stmt_h = $this->pdo->prepare("SELECT h.*, m.nombre as medico_nombre, m.apellido as medico_apellido, c.fecha as fecha_cita
                                       FROM historial_clinico h
                                       JOIN medicos m ON h.medico_id = m.id
                                       JOIN citas c ON h.cita_id = c.id
                                       WHERE h.paciente_id = ? AND h.activo = 1
                                       ORDER BY c.fecha DESC LIMIT ?");
        $stmt_h->bindValue(1, $patient_id, PDO::PARAM_INT);
        $stmt_h->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt_h->execute();
        $history = $stmt_h->fetchAll(PDO::FETCH_ASSOC);

        // Enriquecer con prescripciones y laboratorios por cada consulta
        foreach ($history as &$entry) {
            // Prescripciones
            $stmt_pres = $this->pdo->prepare("SELECT pr.*, pd.medicamento_texto, pd.dosis, pd.frecuencia, pd.duracion, m.nombre as medicamento_nombre
                                              FROM prescripciones pr
                                              JOIN prescripcion_detalle pd ON pr.id = pd.prescripcion_id
                                              LEFT JOIN medicamentos m ON pd.medicamento_id = m.id
                                              WHERE pr.cita_id = ?");
            $stmt_pres->execute([$entry['cita_id']]);
            $entry['prescriptions'] = $stmt_pres->fetchAll(PDO::FETCH_ASSOC);

            // Órdenes de laboratorio
            $stmt_lab = $this->pdo->prepare("SELECT ol.*, old.examen_solicitado, old.resultado_examen, old.rango_referencia
                                            FROM ordenes_laboratorio ol
                                            LEFT JOIN orden_laboratorio_detalle old ON ol.id = old.orden_id
                                            WHERE ol.historial_id = ?");
            $stmt_lab->execute([$entry['id']]);
            $entry['laboratories'] = $stmt_lab->fetchAll(PDO::FETCH_ASSOC);
        }

        return [
            'patient' => $patient,
            'history' => $history
        ];
    }

    /**
     * Verifica si un médico ha atendido previamente a un paciente.
     */
    public function hasDoctorTreatedPatient($medico_id, $paciente_id)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM citas WHERE medico_id = ? AND paciente_id = ? AND estado = 'Atendida'");
        $stmt->execute([$medico_id, $paciente_id]);
        return $stmt->fetchColumn() > 0;
    }
}

<?php
namespace App\Repositories;

use PDO;

class ClinicalEpisodeRepository
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($data)
    {
        $sql = "INSERT INTO episodios_clinicos (paciente_id, descripcion_problema, fecha_inicio, estado) 
                VALUES (?, ?, ?, 'Abierto')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['paciente_id'],
            $data['descripcion_problema'],
            $data['fecha_inicio'] ?? date('Y-m-d')
        ]);
        return $this->pdo->lastInsertId();
    }

    public function triggerClose($id)
    {
        $sql = "UPDATE episodios_clinicos SET estado = 'Cerrado', fecha_cierre = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([date('Y-m-d'), $id]);
    }

    public function getByPatient($paciente_id)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT * FROM episodios_clinicos WHERE paciente_id = ? ORDER BY estado ASC, fecha_inicio DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$paciente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function assignAppointmentToEpisode($cita_id, $episodio_id)
    {
        $sql = "UPDATE citas SET episodio_id = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$episodio_id, $cita_id]);
    }

    public function getAllByPatientWithTimeline($paciente_id)
    {
        $sql = "SELECT 
                    e.id AS ep_id, e.descripcion_problema, e.fecha_inicio, e.fecha_cierre, e.estado AS ep_estado,
                    c.id AS cita_id, c.fecha, c.hora, c.estado AS cita_estado,
                    m.nombre AS medico_nombre, m.apellido AS medico_apellido,
                    a.diagnostico, a.tratamiento,
                    h.id AS historial_id
                FROM episodios_clinicos e
                LEFT JOIN citas c ON c.episodio_id = e.id
                LEFT JOIN medicos m ON c.medico_id = m.id
                LEFT JOIN atenciones a ON a.cita_id = c.id
                LEFT JOIN historial_clinico h ON h.cita_id = c.id
                WHERE e.paciente_id = ?
                ORDER BY e.estado ASC, e.fecha_inicio DESC, c.fecha ASC, c.hora ASC LIMIT 500";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$paciente_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $episodios = [];
        $historialIds = [];

        foreach ($rows as $row) {
            $epId = $row['ep_id'];

            if (!isset($episodios[$epId])) {
                $episodios[$epId] = [
                    'id' => $epId,
                    'paciente_id' => $paciente_id,
                    'descripcion_problema' => $row['descripcion_problema'],
                    'fecha_inicio' => $row['fecha_inicio'],
                    'fecha_cierre' => $row['fecha_cierre'],
                    'estado' => $row['ep_estado'],
                    'timeline' => []
                ];
            }

            if ($row['cita_id']) {
                $citaId = $row['cita_id'];

                if (!isset($episodios[$epId]['timeline'][$citaId])) {
                    $citaData = [
                        'id' => $citaId,
                        'fecha' => $row['fecha'],
                        'hora' => $row['hora'],
                        'estado' => $row['cita_estado'],
                        'medico_nombre' => $row['medico_nombre'],
                        'medico_apellido' => $row['medico_apellido'],
                        'atencion' => null,
                        'laboratorios' => []
                    ];

                    if ($row['diagnostico'] || $row['tratamiento']) {
                        $citaData['atencion'] = [
                            'diagnostico' => $row['diagnostico'],
                            'tratamiento' => $row['tratamiento']
                        ];
                    }

                    $citaData['_historial_id'] = $row['historial_id'];
                    $episodios[$epId]['timeline'][$citaId] = $citaData;

                    if ($row['historial_id']) {
                        $historialIds[] = $row['historial_id'];
                    }
                }
            }
        }

        foreach ($episodios as &$ep) {
            $ep['timeline'] = array_values($ep['timeline']);
        }

        $historialIds = array_unique(array_filter($historialIds));
        if (!empty($historialIds)) {
            $placeholders = str_repeat('?,', count($historialIds) - 1) . '?';
            // TODO: Refactorizar SELECT * cuando se estabilice la vista
            $sqlLab = "SELECT * FROM ordenes_laboratorio WHERE historial_id IN ($placeholders)";
            $stmtLab = $this->pdo->prepare($sqlLab);
            $stmtLab->execute(array_values($historialIds));
            $labsDb = $stmtLab->fetchAll(PDO::FETCH_ASSOC);

            $labsByHistorial = [];
            foreach ($labsDb as $lab) {
                $labsByHistorial[$lab['historial_id']][] = $lab;
            }

            foreach ($episodios as &$ep) {
                foreach ($ep['timeline'] as &$cita) {
                    if (!empty($cita['_historial_id']) && isset($labsByHistorial[$cita['_historial_id']])) {
                        $cita['laboratorios'] = $labsByHistorial[$cita['_historial_id']];
                    }
                    unset($cita['_historial_id']);
                }
            }
        }

        return array_values($episodios);
    }
}

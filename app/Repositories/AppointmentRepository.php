<?php
namespace App\Repositories;

use PDO;

class AppointmentRepository
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getTodayAppointmentsCount()
    {
        return $this->pdo->query("SELECT COUNT(*) FROM citas WHERE DATE(fecha) = CURDATE() AND estado != 'Cancelada'")->fetchColumn();
    }

    public function getTodayAttendedCount()
    {
        return $this->pdo->query("SELECT COUNT(*) FROM citas WHERE DATE(fecha) = CURDATE() AND estado_clinico IN ('alta', 'OBSERVACION', 'completada')")->fetchColumn();
    }

    public function getAll($limit = null, $offset = 0)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.identificacion as paciente_identificacion, p.id as paciente_id_real,
                m.nombre as medico_nombre, m.apellido as medico_apellido, m.especialidad
                FROM citas c
                JOIN pacientes p ON c.paciente_id = p.id
                JOIN medicos m ON c.medico_id = m.id
                ORDER BY c.fecha DESC, c.hora DESC";

        if ($limit !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $this->pdo->query($sql);
        }
        return $stmt->fetchAll();
    }

    public function getActiveFlowAppointments()
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.identificacion as paciente_identificacion, p.id as paciente_id_real,
                m.nombre as medico_nombre, m.apellido as medico_apellido, m.especialidad
                FROM citas c
                JOIN pacientes p ON c.paciente_id = p.id
                JOIN medicos m ON c.medico_id = m.id
                WHERE c.fecha = CURDATE() AND c.estado != 'Cancelada' 
                AND (c.estado_clinico IS NULL OR c.estado_clinico != 'alta')
                ORDER BY c.hora ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getById($id)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $stmt = $this->pdo->prepare("SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido,
                p.fecha_nacimiento, p.genero, p.identificacion, p.identificacion_tipo,
                m.nombre as medico_nombre, m.apellido as medico_apellido
                FROM citas c
                JOIN pacientes p ON c.paciente_id = p.id
                JOIN medicos m ON c.medico_id = m.id
                WHERE c.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function schedule($data)
    {
        $estado = $data['estado'] ?? 'Programada';
        $estado_clinico = $data['estado_clinico'] ?? 'check_in';

        $sql = "INSERT INTO citas (paciente_id, medico_id, episodio_id, fecha, hora, observaciones, estado, estado_clinico) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $res = $stmt->execute([
            $data['paciente_id'],
            $data['medico_id'],
            (!empty($data['episodio_id']) ? $data['episodio_id'] : null),
            $data['fecha'],
            $data['hora'],
            $data['observaciones'],
            $estado,
            $estado_clinico
        ]);

        return $res ? (int)$this->pdo->lastInsertId() : false;
    }

    public function updateMedico(int $cita_id, int $medico_id): bool
    {
        $sql = "UPDATE citas SET medico_id = ? WHERE id = ?";
        return $this->pdo->prepare($sql)->execute([$medico_id, $cita_id]);
    }

    public function checkConflict($medico_id, $fecha, $hora, $exclude_cita_id = null)
    {
        $sql = "SELECT COUNT(*) FROM citas WHERE medico_id = ? AND fecha = ? AND hora = ? AND estado != 'Cancelada'";
        $params = [$medico_id, $fecha, $hora];
        if ($exclude_cita_id !== null) {
            $sql .= " AND id != ?";
            $params[] = $exclude_cita_id;
        }
        $stmt_val = $this->pdo->prepare($sql);
        $stmt_val->execute($params);
        return $stmt_val->fetchColumn() > 0;
    }

    public function updateFechaHora($id, $fecha, $hora)
    {
        $stmt = $this->pdo->prepare("UPDATE citas SET fecha = ?, hora = ?, estado = 'Programada' WHERE id = ?");
        return $stmt->execute([$fecha, $hora, $id]);
    }

    public function updateStatus($id, $nuevo_estado)
    {
        $stmt = $this->pdo->prepare("UPDATE citas SET estado = ? WHERE id = ?");
        return $stmt->execute([$nuevo_estado, $id]);
    }

    public function getStatus($id)
    {
        $stmt = $this->pdo->prepare("SELECT estado FROM citas WHERE id = ?");
        $stmt->execute([$id]);
        $cita = $stmt->fetch();
        return $cita ? $cita['estado'] : false;
    }

    public function updateEstadoClinico($id, $estado_clinico)
    {
        $stmt = $this->pdo->prepare("UPDATE citas SET estado_clinico = ? WHERE id = ?");
        return $stmt->execute([$estado_clinico, $id]);
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM citas WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getCitasMedicoHoy($medico_id)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.identificacion as paciente_identificacion 
                FROM citas c
                JOIN pacientes p ON c.paciente_id = p.id
                WHERE c.medico_id = ? AND DATE(c.fecha) = CURDATE() AND c.estado != 'Cancelada'
                ORDER BY c.hora ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$medico_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPacientesConsulta($medico_id)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.identificacion as paciente_identificacion 
                FROM citas c
                JOIN pacientes p ON c.paciente_id = p.id
                WHERE c.medico_id = ? AND DATE(c.fecha) = CURDATE() 
                AND c.estado_clinico IN ('check_in', 'en_espera', 'en_consulta') 
                AND c.estado NOT IN ('Atendida', 'Cancelada', 'No asistió')
                ORDER BY c.hora ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$medico_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMonthlyAppointments($year, $month)
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-d', strtotime("$startDate +1 month"));

        $sql = "SELECT COUNT(*) FROM citas 
                WHERE fecha >= ? AND fecha < ? 
                AND estado != 'Cancelada'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Todas las citas de hoy con JOIN a pacientes y médicos.
     */
    public function getCitasHoy()
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.id as paciente_id, p.identificacion as paciente_identificacion,
                m.nombre as medico_nombre, m.apellido as medico_apellido
                FROM citas c
                JOIN pacientes p ON c.paciente_id = p.id
                JOIN medicos m ON c.medico_id = m.id
                WHERE c.fecha = CURDATE() AND c.estado != 'Cancelada'
                ORDER BY c.hora ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Citas de hoy filtradas por un estado.
     */
    public function getCitasPorEstadoHoy($estado)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.id as paciente_id, p.identificacion as paciente_identificacion,
                m.nombre as medico_nombre, m.apellido as medico_apellido
                FROM citas c
                JOIN pacientes p ON c.paciente_id = p.id
                JOIN medicos m ON c.medico_id = m.id
                WHERE c.fecha = CURDATE() AND c.estado = ?
                ORDER BY c.hora ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$estado]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Citas de hoy filtradas por múltiples estados.
     */
    public function getCitasPorEstadosHoy(array $estados)
    {
        if (empty($estados)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($estados), '?'));
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.id as paciente_id, p.identificacion as paciente_identificacion,
                m.nombre as medico_nombre, m.apellido as medico_apellido
                FROM citas c
                JOIN pacientes p ON c.paciente_id = p.id
                JOIN medicos m ON c.medico_id = m.id
                WHERE c.fecha = CURDATE() AND c.estado IN ($placeholders)
                ORDER BY c.hora ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($estados);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Citas de hoy con hora mayor a la hora actual.
     */
    public function getProximasCitasHoy()
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.id as paciente_id, p.identificacion as paciente_identificacion,
                m.nombre as medico_nombre, m.apellido as medico_apellido
                FROM citas c
                JOIN pacientes p ON c.paciente_id = p.id
                JOIN medicos m ON c.medico_id = m.id
                WHERE c.fecha = CURDATE() AND c.hora > CURTIME() AND c.estado != 'Cancelada'
                ORDER BY c.hora ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Próximas citas (incluye hoy y días futuros). Para el dashboard recepcionista.
     */
    public function getProximasCitasFuturas($limite = 15)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.id as paciente_id, p.identificacion as paciente_identificacion,
                m.nombre as medico_nombre, m.apellido as medico_apellido
                FROM citas c
                JOIN pacientes p ON c.paciente_id = p.id
                JOIN medicos m ON c.medico_id = m.id
                WHERE (c.fecha > CURDATE() OR (c.fecha = CURDATE() AND c.hora > CURTIME()))
                AND c.estado != 'Cancelada'
                ORDER BY c.fecha ASC, c.hora ASC
                LIMIT " . (int) $limite;
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las horas ocupadas de un médico en una fecha específica.
     * Excluye citas canceladas y no asistidas.
     */
    public function getHorariosOcupados(int $medico_id, string $fecha): array
    {
        $sql = "SELECT hora FROM citas 
                WHERE medico_id = ? AND fecha = ? 
                AND estado NOT IN ('Cancelada', 'No asistió')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$medico_id, $fecha]);
        return array_map(function ($row) {
            return date('H:i', strtotime($row['hora']));
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getAppointmentsByDate($fecha)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.identificacion as paciente_identificacion,
                       m.nombre as medico_nombre, m.apellido as medico_apellido,
                       (SELECT COUNT(*) FROM asignaciones_enfermeria ae WHERE ae.cita_id = c.id) as asignado
                FROM citas c
                JOIN pacientes p ON c.paciente_id = p.id
                JOIN medicos m ON c.medico_id = m.id
                WHERE DATE(c.fecha) = ? AND c.estado != 'Cancelada'
                ORDER BY c.hora ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$fecha]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

<?php
namespace App\Repositories;

use PDO;

class AppointmentsRepository
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
        $sql = "SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.identificacion as paciente_identificacion, p.id as paciente_id_real,
                m.nombre as medico_nombre, m.apellido as medico_apellido, m.especialidad
                FROM citas c
                JOIN pacientes p ON c.paciente_id = p.id
                JOIN medicos m ON c.medico_id = m.id
                WHERE DATE(c.fecha) = CURDATE() AND c.estado != 'Cancelada' AND c.estado_clinico != 'alta'
                ORDER BY c.hora ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getById($id)
    {
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
        $sql = "INSERT INTO citas (paciente_id, medico_id, episodio_id, fecha, hora, observaciones, estado) 
                VALUES (?, ?, ?, ?, ?, ?, 'Programada')";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['paciente_id'],
            $data['medico_id'],
            (!empty($data['episodio_id']) ? $data['episodio_id'] : null),
            $data['fecha'],
            $data['hora'],
            $data['observaciones']
        ]);
    }

    public function checkConflict($medico_id, $fecha, $hora)
    {
        $stmt_val = $this->pdo->prepare("SELECT COUNT(*) FROM citas WHERE medico_id = ? AND fecha = ? AND hora = ? AND estado != 'Cancelada'");
        $stmt_val->execute([$medico_id, $fecha, $hora]);
        return $stmt_val->fetchColumn() > 0;
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
        $sql = "SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.identificacion as paciente_identificacion 
                FROM citas c
                JOIN pacientes p ON c.paciente_id = p.id
                WHERE c.medico_id = ? AND DATE(c.fecha) = CURDATE() 
                AND c.estado_clinico IN ('check_in', 'en_espera', 'en_consulta') 
                AND c.estado != 'Cancelada'
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
}

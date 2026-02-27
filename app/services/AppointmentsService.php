<?php
namespace App\Services;

use Exception;
use PDO;
use PDOException;

class AppointmentsService
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll()
    {
        $sql = "SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido,
                m.nombre as medico_nombre, m.apellido as medico_apellido, m.especialidad
                FROM citas c
                JOIN pacientes p ON c.paciente_id = p.id
                JOIN medicos m ON c.medico_id = m.id
                ORDER BY c.fecha DESC, c.hora DESC";
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

    public function getHistorialPrevio($cita_id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM historial_clinico WHERE cita_id = ?");
        $stmt->execute([$cita_id]);
        return $stmt->fetch();
    }

    public function getResultadosLab($paciente_id)
    {
        $stmt = $this->pdo->prepare("SELECT ol.*, c.fecha as fecha_cita, m.nombre as medico_nombre, m.apellido as medico_apellido
                FROM ordenes_laboratorio ol
                JOIN historial_clinico h ON ol.historial_id = h.id
                JOIN citas c ON h.cita_id = c.id
                JOIN medicos m ON c.medico_id = m.id
                WHERE h.paciente_id = ? AND ol.estado = 'Completada'
                ORDER BY ol.fecha_resultado DESC");
        $stmt->execute([$paciente_id]);
        return $stmt->fetchAll();
    }

    public function getPacientes()
    {
        return $this->pdo->query("SELECT id, nombre, apellido FROM pacientes ORDER BY nombre ASC")->fetchAll();
    }

    public function getMedicos()
    {
        return $this->pdo->query("SELECT id, nombre, apellido, especialidad FROM medicos ORDER BY nombre ASC")->fetchAll();
    }

    public function schedule($data)
    {
        // Validar disponibilidad
        $stmt_val = $this->pdo->prepare("SELECT COUNT(*) FROM citas WHERE medico_id = ? AND fecha = ? AND hora = ? AND estado != 'Cancelada'");
        $stmt_val->execute([$data['medico_id'], $data['fecha'], $data['hora']]);
        if ($stmt_val->fetchColumn() > 0) {
            throw new Exception("El médico ya tiene una cita agendada para esa fecha y hora.");
        }

        $sql = "INSERT INTO citas (paciente_id, medico_id, fecha, hora, observaciones, estado) 
                VALUES (?, ?, ?, ?, ?, 'Programada')";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['paciente_id'],
            $data['medico_id'],
            $data['fecha'],
            $data['hora'],
            $data['observaciones']
        ]);
    }

    public function updateStatus($id, $nuevo_estado)
    {
        $stmt = $this->pdo->prepare("SELECT estado FROM citas WHERE id = ?");
        $stmt->execute([$id]);
        $cita = $stmt->fetch();

        if (!$cita)
            return false;

        $estado_actual = $cita['estado'];
        if ($estado_actual === 'Pendiente' || empty($estado_actual)) {
            $estado_actual = 'Programada';
        }

        $transiciones = [
            'Programada' => ['Confirmada', 'Cancelada', 'Programada'],
            'Confirmada' => ['En espera', 'Programada'],
            'En espera' => ['Atendida', 'No asistió'],
        ];

        if (isset($transiciones[$estado_actual]) && in_array($nuevo_estado, $transiciones[$estado_actual])) {
            $stmt = $this->pdo->prepare("UPDATE citas SET estado = ? WHERE id = ?");
            $result = $stmt->execute([$nuevo_estado, $id]);
            if ($result) {
                // Auditoría: Cambio de estado
                $nivel = ($nuevo_estado === 'Cancelada') ? 'WARNING' : 'INFO';
                $logService = new LogService($this->pdo);
                $logService->register($_SESSION['usuario_id'], 'Cambio de estado', 'Citas', "Cita ID: $id, Nuevo estado: $nuevo_estado", $nivel);
            }
            return $result;
        }

        return false;
    }

    public function saveAttention($data)
    {
        try {
            $this->pdo->beginTransaction();

            $enviar_lab = $data['enviar_lab'];
            $diag_final = $enviar_lab ? "(Pendiente por resultados de laboratorio)" : $data['diagnostico'];
            $treat_final = $enviar_lab ? "(Pendiente por resultados de laboratorio)" : $data['tratamiento'];

            $stmt_check = $this->pdo->prepare("SELECT id FROM historial_clinico WHERE cita_id = ?");
            $stmt_check->execute([$data['cita_id']]);
            $historial_existente = $stmt_check->fetch();

            if ($historial_existente) {
                $sql_historial = "UPDATE historial_clinico SET diagnostico = ?, tratamiento = ?, observaciones = ? WHERE id = ?";
                $stmt_historial = $this->pdo->prepare($sql_historial);
                $stmt_historial->execute([$diag_final, $treat_final, $data['observaciones_clinicas'], $historial_existente['id']]);
                $historial_id = $historial_existente['id'];
            } else {
                $sql_historial = "INSERT INTO historial_clinico (cita_id, paciente_id, medico_id, diagnostico, tratamiento, observaciones) 
                                  VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_historial = $this->pdo->prepare($sql_historial);
                $stmt_historial->execute([$data['cita_id'], $data['paciente_id'], $data['medico_id'], $diag_final, $treat_final, $data['observaciones_clinicas']]);
                $historial_id = $this->pdo->lastInsertId();
            }

            if ($enviar_lab) {
                $stmt_lab_check = $this->pdo->prepare("SELECT id FROM ordenes_laboratorio WHERE historial_id = ? AND estado = 'Pendiente'");
                $stmt_lab_check->execute([$historial_id]);
                if (!$stmt_lab_check->fetch()) {
                    $sql_lab = "INSERT INTO ordenes_laboratorio (historial_id, descripcion, estado) VALUES (?, ?, 'Pendiente')";
                    $stmt_lab = $this->pdo->prepare($sql_lab);
                    $stmt_lab->execute([$historial_id, $data['laboratorio_descripcion']]);
                }
            }

            $stmt_update = $this->pdo->prepare("UPDATE citas SET estado = 'Atendida' WHERE id = ?");
            $stmt_update->execute([$data['cita_id']]);

            $this->pdo->commit();

            // Auditoría: Guardar atención médica
            $accion = $historial_existente ? 'Modificación de historial clínico' : 'Guardar atención médica';
            $nivel = $historial_existente ? 'WARNING' : 'INFO';

            $logService = new LogService($this->pdo);
            $logService->register($_SESSION['usuario_id'], $accion, 'Citas/Atención', "Cita ID: $data[cita_id], Paciente ID: $data[paciente_id]", $nivel);

            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function delete($id)
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM citas WHERE id = ?");
            $res = $stmt->execute([$id]);

            if ($res) {
                $logService = new LogService($this->pdo);
                $logService->register($_SESSION['usuario_id'], 'Eliminación de cita', 'Citas', "ID: $id", 'ERROR');
            }
            return $res;
        } catch (Exception $e) {
            error_log("Error AppointmentsService::delete: " . $e->getMessage());
            return false;
        }
    }
}

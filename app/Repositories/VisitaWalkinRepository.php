<?php
namespace App\Repositories;

use PDO;

class VisitaWalkinRepository extends BaseRepository
{
    public function getById(int $id)
    {
        $sql = "SELECT vw.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.identificacion,
                       t.numero as turno_numero 
                FROM visitas_walkin vw
                JOIN pacientes p ON vw.paciente_id = p.id
                JOIN turnos t ON vw.turno_id = t.id
                WHERE vw.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarEstado(int $id, string $estado, ?int $medico_id = null)
    {
        if ($medico_id) {
            $sql = "UPDATE visitas_walkin SET estado = ?, medico_id = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$estado, $medico_id, $id]);
        } else {
            $sql = "UPDATE visitas_walkin SET estado = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$estado, $id]);
        }
    }

    public function create(int $paciente_id, int $turno_id, string $area)
    {
        $sql = "INSERT INTO visitas_walkin (paciente_id, turno_id, area, estado, fecha)
                VALUES (?, ?, ?, 'en_espera', CURDATE())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$paciente_id, $turno_id, $area]);
        return $this->pdo->lastInsertId();
    }

    public function getByPacienteHoy(int $paciente_id)
    {
        $sql = "SELECT * FROM visitas_walkin WHERE paciente_id = ? AND fecha = CURDATE() ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$paciente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAsAtendido(int $id)
    {
        $sql = "UPDATE visitas_walkin SET estado = 'atendido', atendido_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }
}

<?php
namespace App\Repositories;

use PDO;
use Exception;

class LaboratoryRepository extends BaseRepository
{
    public function getPendingResultsCount()
    {
        $sql = $this->applySoftDeleteFilter("SELECT COUNT(*) FROM ordenes_laboratorio WHERE estado = 'Pendiente'");
        return $this->pdo->query($sql)->fetchColumn();
    }

    // Ya existía un refactor parcial de Laboratorio, pero este método quedó en LaboratoryService
    public function getAllOrders($limit = null, $offset = 0)
    {
        $sql = "SELECT ol.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.id as paciente_id_real,
                m.nombre as medico_nombre, m.apellido as medico_apellido,
                hc.diagnostico
                FROM ordenes_laboratorio ol
                JOIN historial_clinico hc ON ol.historial_id = hc.id
                JOIN pacientes p ON hc.paciente_id = p.id
                JOIN medicos m ON hc.medico_id = m.id";
        
        $sql = $this->applySoftDeleteFilter($sql, 'ol');
        $sql = $this->applySoftDeleteFilter($sql, 'p');
        $sql = $this->applySoftDeleteFilter($sql, 'm');
        
        $sql .= " ORDER BY ol.estado ASC, ol.created_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $this->pdo->query($sql);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function uploadResultWithFile($orden_id, $resultado, $file_path)
    {
        $sql = "UPDATE ordenes_laboratorio SET resultado = ?, archivo_pdf = ?, estado = 'Completada', fecha_resultado = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$resultado, $file_path, $orden_id]);
    }

    public function uploadResultWithoutFile($orden_id, $resultado)
    {
        $sql = "UPDATE ordenes_laboratorio SET resultado = ?, estado = 'Completada', fecha_resultado = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$resultado, $orden_id]);
    }
    public function hasPendingOrder($historial_id)
    {
        $sql = $this->applySoftDeleteFilter("SELECT COUNT(*) FROM ordenes_laboratorio WHERE historial_id = ? AND estado = 'Pendiente'");
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$historial_id]);
        return $stmt->fetchColumn() > 0;
    }

    public function createPending($historial_id, $descripcion)
    {
        $sql = "INSERT INTO ordenes_laboratorio (historial_id, descripcion, estado) VALUES (?, ?, 'Pendiente')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$historial_id, $descripcion]);
        return $this->pdo->lastInsertId();
    }

    public function getCompletedByPaciente($paciente_id)
    {
        $sql = "SELECT ol.*, c.fecha as fecha_cita
                FROM ordenes_laboratorio ol
                JOIN historial_clinico h ON ol.historial_id = h.id
                JOIN citas c ON h.cita_id = c.id
                WHERE h.paciente_id = ? AND ol.estado = 'Completada'";
        
        $sql = $this->applySoftDeleteFilter($sql, 'ol');
        
        $sql .= " ORDER BY ol.fecha_resultado DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$paciente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getResultadosPendientes($medico_id, $limit = 5)
    {
        $sql = "SELECT ol.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido 
                FROM ordenes_laboratorio ol
                JOIN historial_clinico hc ON ol.historial_id = hc.id
                JOIN pacientes p ON hc.paciente_id = p.id
                WHERE hc.medico_id = :medico_id AND ol.estado = 'Pendiente'";
        
        $sql = $this->applySoftDeleteFilter($sql, 'ol');
        $sql = $this->applySoftDeleteFilter($sql, 'p');
        
        $sql .= " ORDER BY ol.created_at ASC LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':medico_id', (int) $medico_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteOrder($id)
    {
        return $this->softDelete('ordenes_laboratorio', $id);
    }

    /**
     * Restaura una orden de laboratorio previamente eliminada (soft delete).
     */
    public function restoreOrder($id)
    {
        return $this->restoreRecord('ordenes_laboratorio', (int) $id);
    }

    /**
     * Obtiene una orden por id incluyendo registros eliminados lógicamente.
     */
    public function getByIdIncludingDeleted($id)
    {
        return $this->findByIdIncludingDeleted('ordenes_laboratorio', (int) $id);
    }

    /**
     * Lista las órdenes de laboratorio eliminadas lógicamente.
     */
    public function getDeleted()
    {
        return $this->getDeletedRecords(
            'ordenes_laboratorio',
            'id, historial_id, estado, deleted_at',
            'deleted_at DESC'
        );
    }

    public function getMonthlyLabTests($year, $month)
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-d', strtotime("$startDate +1 month"));

        $sql = "SELECT COUNT(*) FROM ordenes_laboratorio 
                WHERE created_at >= ? AND created_at < ? 
                AND estado = 'Completada'";
        $sql = $this->applySoftDeleteFilter($sql);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        return (int)$stmt->fetchColumn();
    }
}

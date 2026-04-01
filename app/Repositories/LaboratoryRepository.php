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
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT ol.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.id as paciente_id_real,
                m.nombre as medico_nombre, m.apellido as medico_apellido,
                hc.diagnostico
                FROM ordenes_laboratorio ol
                LEFT JOIN historial_clinico hc ON ol.historial_id = hc.id
                LEFT JOIN visitas_walkin vw ON ol.walkin_id = vw.id
                JOIN pacientes p ON (hc.paciente_id = p.id OR vw.paciente_id = p.id)
                LEFT JOIN medicos m ON (hc.medico_id = m.id OR vw.medico_id = m.id)";
        
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

    public function createPending($historial_id, $descripcion, array $examenes = [])
    {
        $sql = "INSERT INTO ordenes_laboratorio (historial_id, descripcion, estado) VALUES (?, ?, 'Pendiente')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$historial_id, $descripcion]);
        $orden_id = $this->pdo->lastInsertId();

        if (!empty($examenes)) {
            $sqlDet = "INSERT INTO orden_laboratorio_detalle (orden_id, examen_solicitado) VALUES (?, ?)";
            $stmtDet = $this->pdo->prepare($sqlDet);
            foreach ($examenes as $examen) {
                $stmtDet->execute([$orden_id, trim($examen)]);
            }
        }

        return $orden_id;
    }

    public function createPendingWalkin($walkin_id, $descripcion, array $examenes = [])
    {
        $sql = "INSERT INTO ordenes_laboratorio (walkin_id, historial_id, descripcion, estado) VALUES (?, NULL, ?, 'Pendiente')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$walkin_id, $descripcion]);
        $orden_id = $this->pdo->lastInsertId();

        if (!empty($examenes)) {
            $sqlDet = "INSERT INTO orden_laboratorio_detalle (orden_id, examen_solicitado) VALUES (?, ?)";
            $stmtDet = $this->pdo->prepare($sqlDet);
            foreach ($examenes as $examen) {
                $stmtDet->execute([$orden_id, trim($examen)]);
            }
        }

        return $orden_id;
    }

    public function getCompletedByPaciente($paciente_id)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
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
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT ol.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido 
                FROM ordenes_laboratorio ol
                LEFT JOIN historial_clinico hc ON ol.historial_id = hc.id
                LEFT JOIN visitas_walkin vw ON ol.walkin_id = vw.id
                JOIN pacientes p ON (hc.paciente_id = p.id OR vw.paciente_id = p.id)
                WHERE (hc.medico_id = :medico_id1 OR hc.medico_id = 0 OR vw.medico_id = :medico_id2 OR vw.medico_id = 0) AND ol.estado = 'Pendiente'";
        
        $sql = $this->applySoftDeleteFilter($sql, 'ol');
        $sql = $this->applySoftDeleteFilter($sql, 'p');
        
        $sql .= " ORDER BY ol.created_at ASC LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':medico_id1', (int) $medico_id, PDO::PARAM_INT);
        $stmt->bindValue(':medico_id2', (int) $medico_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getResultadosPendientesCount($medico_id)
    {
        $sql = "SELECT COUNT(*) FROM ordenes_laboratorio ol
                LEFT JOIN historial_clinico hc ON ol.historial_id = hc.id
                LEFT JOIN visitas_walkin vw ON ol.walkin_id = vw.id
                WHERE (hc.medico_id = ? OR vw.medico_id = ?) AND ol.estado = 'Pendiente'";
        $sql = $this->applySoftDeleteFilter($sql, 'ol');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$medico_id, $medico_id]);
        return (int)$stmt->fetchColumn();
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

    /**
     * Órdenes con estado Pendiente para dashboard técnico de laboratorio.
     */
    public function getOrdenesPendientes()
    {
        $examenesSelect = $this->getExamenesSubquery();
        $sql = "SELECT ol.id, ol.historial_id, ol.estado, ol.created_at, ol.fecha_resultado,
                p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.id as paciente_id,
                $examenesSelect as examenes
                FROM ordenes_laboratorio ol
                LEFT JOIN historial_clinico hc ON ol.historial_id = hc.id
                LEFT JOIN visitas_walkin vw ON ol.walkin_id = vw.id
                JOIN pacientes p ON (hc.paciente_id = p.id OR vw.paciente_id = p.id)
                WHERE ol.estado = 'Pendiente'";
        $sql = $this->applySoftDeleteFilter($sql, 'ol');
        $sql = $this->applySoftDeleteFilter($sql, 'p');
        $sql .= " ORDER BY ol.created_at ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Órdenes con estado En proceso para dashboard técnico de laboratorio.
     */
    public function getOrdenesEnProceso()
    {
        $examenesSelect = $this->getExamenesSubquery();
        $sql = "SELECT ol.id, ol.historial_id, ol.estado, ol.created_at, ol.fecha_resultado,
                p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.id as paciente_id,
                $examenesSelect as examenes
                FROM ordenes_laboratorio ol
                LEFT JOIN historial_clinico hc ON ol.historial_id = hc.id
                LEFT JOIN visitas_walkin vw ON ol.walkin_id = vw.id
                JOIN pacientes p ON (hc.paciente_id = p.id OR vw.paciente_id = p.id)
                WHERE ol.estado = 'En proceso'";
        $sql = $this->applySoftDeleteFilter($sql, 'ol');
        $sql = $this->applySoftDeleteFilter($sql, 'p');
        $sql .= " ORDER BY ol.created_at ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Órdenes completadas hoy para dashboard técnico de laboratorio.
     */
    public function getOrdenesCompletadasHoy()
    {
        $examenesSelect = $this->getExamenesSubquery();
        $sql = "SELECT ol.id, ol.historial_id, ol.estado, ol.created_at, ol.fecha_resultado,
                p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.id as paciente_id,
                $examenesSelect as examenes
                FROM ordenes_laboratorio ol
                LEFT JOIN historial_clinico hc ON ol.historial_id = hc.id
                LEFT JOIN visitas_walkin vw ON ol.walkin_id = vw.id
                JOIN pacientes p ON (hc.paciente_id = p.id OR vw.paciente_id = p.id)
                WHERE ol.estado = 'Completada' AND DATE(ol.fecha_resultado) = CURDATE()";
        $sql = $this->applySoftDeleteFilter($sql, 'ol');
        $sql = $this->applySoftDeleteFilter($sql, 'p');
        $sql .= " ORDER BY ol.fecha_resultado DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @var string|null Cache del subquery de examenes */
    private static $examenesSubqueryCache = null;

    /**
     * Devuelve la expresión SQL para obtener exámenes. Usa orden_laboratorio_detalle si existe;
     * si no, usa ol.descripcion para compatibilidad con BBDD sin esa tabla.
     */
    private function getExamenesSubquery(): string
    {
        if (self::$examenesSubqueryCache !== null) {
            return self::$examenesSubqueryCache;
        }
        try {
            $this->pdo->query("SELECT 1 FROM orden_laboratorio_detalle LIMIT 1");
            self::$examenesSubqueryCache = "(SELECT GROUP_CONCAT(examen_solicitado SEPARATOR ', ') FROM orden_laboratorio_detalle WHERE orden_id = ol.id)";
        } catch (\PDOException $e) {
            self::$examenesSubqueryCache = "COALESCE(ol.descripcion, '-')";
        }
        return self::$examenesSubqueryCache;
    }

    /**
     * Obtiene el detalle completo de una orden con sus exámenes.
     */
    public function getOrdenById(int $id)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT ol.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.id as paciente_id
                FROM ordenes_laboratorio ol
                LEFT JOIN historial_clinico hc ON ol.historial_id = hc.id
                LEFT JOIN visitas_walkin vw ON ol.walkin_id = vw.id
                JOIN pacientes p ON (hc.paciente_id = p.id OR vw.paciente_id = p.id)
                WHERE ol.id = ?";
        $sql = $this->applySoftDeleteFilter($sql, 'ol');
        $sql = $this->applySoftDeleteFilter($sql, 'p');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $orden = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$orden) {
            return null;
        }
        try {
            // TODO: Refactorizar SELECT * cuando se estabilice la vista
            $sqlDet = "SELECT * FROM orden_laboratorio_detalle WHERE orden_id = ? ORDER BY id";
            $stmtDet = $this->pdo->prepare($sqlDet);
            $stmtDet->execute([$id]);
            $orden['examenes'] = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $orden['examenes'] = [];
        }
        return $orden;
    }

    /**
     * Actualiza el estado de una orden. Solo permite: Pendiente → En proceso → Completada.
     */
    public function actualizarEstadoOrden(int $id, string $estado): bool
    {
        $validos = ['En proceso', 'Completada'];
        if (!in_array($estado, $validos, true)) {
            throw new Exception("Estado no permitido: " . $estado);
        }
        $orden = $this->getOrdenById($id);
        if (!$orden) {
            throw new Exception("Orden no encontrada.");
        }
        $actual = $orden['estado'];
        $transiciones = [
            'Pendiente' => ['En proceso'],
            'En proceso' => ['Completada'],
            'Completada' => []
        ];
        if (!isset($transiciones[$actual]) || !in_array($estado, $transiciones[$actual], true)) {
            throw new Exception("Transición no válida: de '$actual' a '$estado'.");
        }
        if ($estado === 'Completada') {
            $sql = "UPDATE ordenes_laboratorio SET estado = ?, fecha_resultado = NOW() WHERE id = ?";
        } else {
            $sql = "UPDATE ordenes_laboratorio SET estado = ? WHERE id = ?";
        }
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$estado, $id]);
    }
}

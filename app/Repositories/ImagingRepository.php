<?php
namespace App\Repositories;

use PDO;
use Exception;

class ImagingRepository extends BaseRepository
{
    /**
     * Órdenes con estado Pendiente para dashboard técnico de imágenes.
     */
    public function getOrdenesPendientes()
    {
        $sql = "SELECT oi.id, oi.historial_id, oi.tipo_estudio, oi.estado, oi.created_at, oi.fecha_resultado,
                p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.id as paciente_id,
                m.nombre as medico_nombre, m.apellido as medico_apellido,
                COALESCE(
                    (SELECT GROUP_CONCAT(estudio_solicitado SEPARATOR ', ') FROM orden_imagenes_detalle WHERE orden_id = oi.id),
                    oi.tipo_estudio,
                    '-'
                ) as estudios
                FROM ordenes_imagenes oi
                LEFT JOIN historial_clinico hc ON oi.historial_id = hc.id
                LEFT JOIN visitas_walkin vw ON oi.walkin_id = vw.id
                JOIN pacientes p ON (hc.paciente_id = p.id OR vw.paciente_id = p.id)
                LEFT JOIN medicos m ON (hc.medico_id = m.id OR vw.medico_id = m.id)
                WHERE oi.estado = 'Pendiente'
                ORDER BY oi.created_at ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Órdenes con estado En proceso para dashboard técnico de imágenes.
     */
    public function getOrdenesEnProceso()
    {
        $sql = "SELECT oi.id, oi.historial_id, oi.tipo_estudio, oi.estado, oi.created_at, oi.fecha_resultado,
                p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.id as paciente_id,
                m.nombre as medico_nombre, m.apellido as medico_apellido,
                COALESCE(
                    (SELECT GROUP_CONCAT(estudio_solicitado SEPARATOR ', ') FROM orden_imagenes_detalle WHERE orden_id = oi.id),
                    oi.tipo_estudio,
                    '-'
                ) as estudios
                FROM ordenes_imagenes oi
                LEFT JOIN historial_clinico hc ON oi.historial_id = hc.id
                LEFT JOIN visitas_walkin vw ON oi.walkin_id = vw.id
                JOIN pacientes p ON (hc.paciente_id = p.id OR vw.paciente_id = p.id)
                LEFT JOIN medicos m ON (hc.medico_id = m.id OR vw.medico_id = m.id)
                WHERE oi.estado = 'En proceso'
                ORDER BY oi.created_at ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Órdenes completadas hoy para dashboard técnico de imágenes.
     */
    public function getOrdenesCompletadasHoy()
    {
        $sql = "SELECT oi.id, oi.historial_id, oi.tipo_estudio, oi.estado, oi.created_at, oi.fecha_resultado,
                p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.id as paciente_id,
                m.nombre as medico_nombre, m.apellido as medico_apellido,
                COALESCE(
                    (SELECT GROUP_CONCAT(estudio_solicitado SEPARATOR ', ') FROM orden_imagenes_detalle WHERE orden_id = oi.id),
                    oi.tipo_estudio,
                    '-'
                ) as estudios
                FROM ordenes_imagenes oi
                LEFT JOIN historial_clinico hc ON oi.historial_id = hc.id
                LEFT JOIN visitas_walkin vw ON oi.walkin_id = vw.id
                JOIN pacientes p ON (hc.paciente_id = p.id OR vw.paciente_id = p.id)
                LEFT JOIN medicos m ON (hc.medico_id = m.id OR vw.medico_id = m.id)
                WHERE oi.estado = 'Completada' AND DATE(oi.fecha_resultado) = CURDATE()
                ORDER BY oi.fecha_resultado DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Todas las órdenes completadas para el registro histórico.
     */
    public function getOrdenesCompletadas()
    {
        $sql = "SELECT oi.id, oi.historial_id, oi.tipo_estudio, oi.estado, oi.created_at, oi.fecha_resultado, oi.archivo_imagen,
                p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.id as paciente_id,
                m.nombre as medico_nombre, m.apellido as medico_apellido,
                COALESCE(
                    (SELECT GROUP_CONCAT(estudio_solicitado SEPARATOR ', ') FROM orden_imagenes_detalle WHERE orden_id = oi.id),
                    oi.tipo_estudio,
                    '-'
                ) as estudios
                FROM ordenes_imagenes oi
                LEFT JOIN historial_clinico hc ON oi.historial_id = hc.id
                LEFT JOIN visitas_walkin vw ON oi.walkin_id = vw.id
                JOIN pacientes p ON (hc.paciente_id = p.id OR vw.paciente_id = p.id)
                LEFT JOIN medicos m ON (hc.medico_id = m.id OR vw.medico_id = m.id)
                WHERE oi.estado = 'Completada'
                ORDER BY oi.fecha_resultado DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrdenById(int $id)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT oi.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.id as paciente_id
                FROM ordenes_imagenes oi
                LEFT JOIN historial_clinico hc ON oi.historial_id = hc.id
                LEFT JOIN visitas_walkin vw ON oi.walkin_id = vw.id
                JOIN pacientes p ON (hc.paciente_id = p.id OR vw.paciente_id = p.id)
                WHERE oi.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $orden = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$orden) {
            return null;
        }
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sqlDet = "SELECT * FROM orden_imagenes_detalle WHERE orden_id = ? ORDER BY id";
        $stmtDet = $this->pdo->prepare($sqlDet);
        $stmtDet->execute([$id]);
        $orden['estudios'] = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
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
            $sql = "UPDATE ordenes_imagenes SET estado = ?, fecha_resultado = NOW() WHERE id = ?";
        } else {
            $sql = "UPDATE ordenes_imagenes SET estado = ? WHERE id = ?";
        }
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$estado, $id]);
    }

    /**
     * Actualiza el archivo de imagen y fecha_resultado de una orden.
     */
    public function subirArchivoOrden(int $id, string $rutaArchivo): bool
    {
        $sql = "UPDATE ordenes_imagenes SET archivo_imagen = ?, fecha_resultado = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$rutaArchivo, $id]);
    }

    /**
     * Crea órdenes de imágenes desde la consulta médica.
     * Por cada estudio se crea una orden cabecera + su detalle.
     */
    public function crearOrdenDesdeConsulta(int $historial_id, array $estudios): array
    {
        $sqlOrden = "INSERT INTO ordenes_imagenes (historial_id, tipo_estudio, estado) VALUES (?, ?, 'Pendiente')";
        $sqlDet = "INSERT INTO orden_imagenes_detalle (orden_id, estudio_solicitado) VALUES (?, ?)";
        $stmtOrden = $this->pdo->prepare($sqlOrden);
        $stmtDet = $this->pdo->prepare($sqlDet);

        $ids = [];

        foreach ($estudios as $estudio) {
            $estudio = trim($estudio);
            $stmtOrden->execute([$historial_id, $estudio]);
            $orden_id = $this->pdo->lastInsertId();
            $stmtDet->execute([$orden_id, $estudio]);
            $ids[] = $orden_id;
        }
        return $ids;
    }
}

<?php
namespace App\Repositories;

use PDO;

class PharmacyRepository extends BaseRepository
{
    public function getLowStockCount()
    {
        $sql = $this->applySoftDeleteFilter("SELECT COUNT(*) FROM medicamentos WHERE stock <= 5");
        return $this->pdo->query($sql)->fetchColumn();
    }

    public function getMedicamentoForUpdate($id)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = $this->applySoftDeleteFilter("SELECT * FROM medicamentos WHERE id = ? FOR UPDATE");
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un medicamento por id incluyendo registros eliminados lógicamente.
     */
    public function getByIdIncludingDeleted($id)
    {
        return $this->findByIdIncludingDeleted('medicamentos', (int) $id);
    }

    public function updateStock($id, $nuevo_stock)
    {
        $stmtUpdate = $this->pdo->prepare("UPDATE medicamentos SET stock = ? WHERE id = ?");
        return $stmtUpdate->execute([$nuevo_stock, $id]);
    }

    public function decreaseStock($id, $cantidad)
    {
        $stmt_upd_stock = $this->pdo->prepare("UPDATE medicamentos SET stock = stock - ? WHERE id = ?");
        return $stmt_upd_stock->execute([$cantidad, $id]);
    }

    public function createVenta($paciente_id, $usuario_id, $factura_id, $total = null)
    {
        if ($total !== null) {
            $stmt_venta = $this->pdo->prepare("INSERT INTO ventas_farmacia (paciente_id, usuario_id, factura_id, total) VALUES (?, ?, ?, ?)");
            $stmt_venta->execute([$paciente_id, $usuario_id, $factura_id, $total]);
        } else {
            $stmt_venta = $this->pdo->prepare("INSERT INTO ventas_farmacia (paciente_id, usuario_id, factura_id) VALUES (?, ?, ?)");
            $stmt_venta->execute([$paciente_id, $usuario_id, $factura_id]);
        }
        return $this->pdo->lastInsertId();
    }

    public function createVentaDetalle($venta_id, $medicamento_id, $cantidad, $precio_unitario, $subtotal)
    {
        $stmt_det_venta = $this->pdo->prepare("INSERT INTO ventas_farmacia_detalle (venta_id, medicamento_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
        return $stmt_det_venta->execute([$venta_id, $medicamento_id, $cantidad, $precio_unitario, $subtotal]);
    }

    public function createMovimiento($medicamento_id, $tipo, $cantidad, $motivo, $usuario_id)
    {
        $stmt_mov = $this->pdo->prepare("INSERT INTO movimientos_inventario (medicamento_id, tipo_movimiento, cantidad, motivo, usuario_id) VALUES (?, ?, ?, ?, ?)");
        return $stmt_mov->execute([$medicamento_id, $tipo, $cantidad, $motivo, $usuario_id]);
    }

    public function createMedicamento(array $data)
    {
        $sql = "INSERT INTO medicamentos (codigo, nombre, descripcion, presentacion, concentracion, lote, proveedor, precio, stock, fecha_vencimiento)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data['codigo'] ?? null,
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['presentacion'] ?? null,
            $data['concentracion'] ?? null,
            $data['lote'] ?? null,
            $data['proveedor'] ?? null,
            $data['precio'] ?? 0,
            $data['stock'] ?? 0,
            $data['fecha_vencimiento'] ?? null
        ]);
        return $this->pdo->lastInsertId();
    }

    public function updateMedicamento(int $id, array $data)
    {
        $sql = "UPDATE medicamentos SET nombre = ?, descripcion = ?, presentacion = ?, concentracion = ?,
                lote = ?, proveedor = ?, precio = ?, fecha_vencimiento = ?
                WHERE id = ? AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['presentacion'] ?? null,
            $data['concentracion'] ?? null,
            $data['lote'] ?? null,
            $data['proveedor'] ?? null,
            $data['precio'] ?? 0,
            $data['fecha_vencimiento'] ?? null,
            $id
        ]);
    }

    public function increaseStock(int $id, int $cantidad)
    {
        $stmt = $this->pdo->prepare("UPDATE medicamentos SET stock = stock + ? WHERE id = ? AND deleted_at IS NULL");
        return $stmt->execute([$cantidad, $id]);
    }

    public function getMedicamentoById(int $id)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = $this->applySoftDeleteFilter("SELECT * FROM medicamentos WHERE id = ?");
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllMedicamentos()
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = $this->applySoftDeleteFilter("SELECT * FROM medicamentos ORDER BY nombre ASC LIMIT 500");
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllPacientesBasic()
    {
        $sql = $this->applySoftDeleteFilter("SELECT id, nombre, apellido, identificacion FROM pacientes ORDER BY nombre ASC LIMIT 500");
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingPrescriptions($limit = 5, $offset = 0)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT p.*, 
                       pa.nombre as paciente_nombre, pa.apellido as paciente_apellido,
                       m.nombre as medico_nombre, m.apellido as medico_apellido,
                       GROUP_CONCAT(COALESCE(pd.medicamento_texto, '') SEPARATOR ', ') as medicamentos_summary
                FROM prescripciones p
                LEFT JOIN prescripcion_detalle pd ON p.id = pd.prescripcion_id
                JOIN pacientes pa ON p.paciente_id = pa.id
                JOIN medicos m ON p.medico_id = m.id
                WHERE p.estado = 'Pendiente'";
        
        $sql = $this->applySoftDeleteFilter($sql, 'p');
        $sql = $this->applySoftDeleteFilter($sql, 'pa');
        $sql = $this->applySoftDeleteFilter($sql, 'm');
        
        $sql .= " GROUP BY p.id
                ORDER BY p.fecha_prescripcion DESC";

        if ($limit !== null) {
            $sql .= " LIMIT " . (int) $limit;
            if ($offset > 0) {
                $sql .= " OFFSET " . (int) $offset;
            }
        }

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingPrescriptionsCount()
    {
        $sql = "SELECT COUNT(DISTINCT p.id)
                FROM prescripciones p
                JOIN pacientes pa ON p.paciente_id = pa.id
                JOIN medicos m ON p.medico_id = m.id
                WHERE p.estado = 'Pendiente'";
        
        $sql = $this->applySoftDeleteFilter($sql, 'p');
        $sql = $this->applySoftDeleteFilter($sql, 'pa');
        $sql = $this->applySoftDeleteFilter($sql, 'm');

        $stmt = $this->pdo->query($sql);
        return (int) $stmt->fetchColumn();
    }

    public function getPrescriptionAndDetails($id)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT p.*, pa.nombre as paciente_nombre, pa.apellido as paciente_apellido, pa.identificacion
                FROM prescripciones p 
                JOIN pacientes pa ON p.paciente_id = pa.id 
                WHERE p.id = ?";
        $sql = $this->applySoftDeleteFilter($sql, 'p');
        $sql = $this->applySoftDeleteFilter($sql, 'pa');
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $prescripcion = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($prescripcion) {
            $stmt_det = $this->pdo->prepare("SELECT pd.*, m.nombre as medicamento_nombre, m.precio, m.stock
                                             FROM prescripcion_detalle pd
                                             LEFT JOIN medicamentos m ON pd.medicamento_id = m.id
                                             WHERE pd.prescripcion_id = ?");
            // Note: We don't filter deleted medications in details of an old prescription because historical accuracy is needed.
            $stmt_det->execute([$id]);
            $prescripcion['detalles'] = $stmt_det->fetchAll(PDO::FETCH_ASSOC);
        }

        return $prescripcion;
    }

    public function updateVentaTotal($venta_id, $total)
    {
        $stmt_upd_venta = $this->pdo->prepare("UPDATE ventas_farmacia SET total = ? WHERE id = ?");
        return $stmt_upd_venta->execute([$total, $venta_id]);
    }

    public function updatePrescriptionState($id, $estado)
    {
        $stmt_upd_pres = $this->pdo->prepare("UPDATE prescripciones SET estado = ? WHERE id = ?");
        return $stmt_upd_pres->execute([$estado, $id]);
    }

    public function deleteMedicamento($id)
    {
        return $this->softDelete('medicamentos', $id);
    }

    /**
     * Restaura un medicamento previamente eliminado (soft delete).
     */
    public function restore($id)
    {
        return $this->restoreRecord('medicamentos', (int) $id);
    }

    /**
     * Lista los medicamentos eliminados lógicamente.
     */
    public function getDeleted()
    {
        return $this->getDeletedRecords(
            'medicamentos',
            'id, nombre, stock, deleted_at',
            'deleted_at DESC'
        );
    }

    public function getMonthlyDispensedMedications($year, $month)
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-d', strtotime("$startDate +1 month"));

        $sql = "SELECT COUNT(*) FROM ventas_farmacia 
                WHERE created_at >= ? AND created_at < ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        return (int)$stmt->fetchColumn();
    }

    public function getMovimientos(array $filtros = [])
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT m.*, med.nombre as medicamento_nombre, med.presentacion, med.concentracion,
                       u.nombre as usuario_nombre, u.apellido as usuario_apellido
                FROM movimientos_inventario m
                JOIN medicamentos med ON m.medicamento_id = med.id
                JOIN usuarios u ON m.usuario_id = u.id
                WHERE 1=1";
        
        $params = [];

        if (!empty($filtros['medicamento_id'])) {
            $sql .= " AND m.medicamento_id = ?";
            $params[] = $filtros['medicamento_id'];
        }

        if (!empty($filtros['tipo_movimiento'])) {
            $sql .= " AND m.tipo_movimiento = ?";
            $params[] = $filtros['tipo_movimiento'];
        }

        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND DATE(m.fecha_movimiento) >= ?";
            $params[] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND DATE(m.fecha_movimiento) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }

        $sql .= " ORDER BY m.fecha_movimiento DESC LIMIT 500";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getWithStock()
    {
        $sql = "SELECT id, nombre, stock_actual, precio_unidad FROM medicamentos WHERE stock_actual > 0 AND deleted_at IS NULL ORDER BY nombre ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

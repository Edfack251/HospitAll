<?php
namespace App\Services;

use Exception;
use PDO;
use PDOException;
use App\Helpers\AuthHelper;
use App\Services\BillingService;
use App\Services\LogService;

class PharmacyService
{
    private $pdo;
    private $billingService;

    public function __construct($pdo)
    {
        // Ambos roles pueden acceder
        AuthHelper::checkRole(['administrador', 'farmaceutico']);

        $this->pdo = $pdo;
        $this->billingService = new BillingService($pdo);
    }

    /**
     * Dispensa un medicamento específico a un paciente. 
     * Conlleva la reducción de inventario y la emisión automática de factura.
     *
     * @param int $paciente_id ID del paciente
     * @param int $medicamento_id ID del medicamento
     * @param int $cantidad Cantidad a dispensar
     * @return int ID de la factura generada
     * @throws Exception Si no hay stock o no existe el medicamento
     */
    public function dispense($paciente_id, $medicamento_id, $cantidad)
    {
        if ($cantidad <= 0) {
            throw new Exception("La cantidad a dispensar debe ser mayor a 0.");
        }

        try {
            // Utilizamos una transacción para mantener la consistencia entre stock y factura
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
            }

            // 1. Obtener datos del medicamento activando bloqueo de fila para evitar cruces (FOR UPDATE)
            $stmt = $this->pdo->prepare("SELECT nombre, precio, stock FROM medicamentos WHERE id = ? FOR UPDATE");
            $stmt->execute([$medicamento_id]);
            $medicamento = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$medicamento) {
                throw new Exception("El medicamento seleccionado no existe en la base de datos.");
            }

            // 2. Comprobar que en el inventario hay suficiente stock
            if ((int) $medicamento['stock'] < (int) $cantidad) {
                throw new Exception("Inventario insuficiente para: " . $medicamento['nombre'] . ". Stock actual: " . $medicamento['stock']);
            }

            // 3. Reducir inventario
            $nuevo_stock = (int) $medicamento['stock'] - (int) $cantidad;
            $stmtUpdate = $this->pdo->prepare("UPDATE medicamentos SET stock = ? WHERE id = ?");
            $stmtUpdate->execute([$nuevo_stock, $medicamento_id]);

            // 4. Generar factura automáticamente usando BillingService 
            $factura_id = $this->billingService->createInvoice($paciente_id);

            // 5. Crear el ítem en la factura
            $itemData = [
                'tipo_item' => 'medicamento',
                'descripcion' => "Dispensario - " . $medicamento['nombre'],
                'cantidad' => $cantidad,
                'precio' => $medicamento['precio']
            ];
            $this->billingService->addItem($factura_id, $itemData);

            // 6. Crear Registro en ventas_farmacia
            $stmt_venta = $this->pdo->prepare("INSERT INTO ventas_farmacia (paciente_id, usuario_id, factura_id, total) VALUES (?, ?, ?, ?)");
            $stmt_venta->execute([$paciente_id, $_SESSION['user_id'], $factura_id, ($cantidad * $medicamento['precio'])]);
            $venta_id = $this->pdo->lastInsertId();

            // 7. Crear Registro en ventas_farmacia_detalle
            $stmt_det_venta = $this->pdo->prepare("INSERT INTO ventas_farmacia_detalle (venta_id, medicamento_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt_det_venta->execute([$venta_id, $medicamento_id, $cantidad, $medicamento['precio'], ($cantidad * $medicamento['precio'])]);

            // 8. Registrar movimiento en movimientos_inventario
            $stmt_mov = $this->pdo->prepare("INSERT INTO movimientos_inventario (medicamento_id, tipo_movimiento, cantidad, motivo, usuario_id) VALUES (?, 'Salida', ?, ?, ?)");
            $stmt_mov->execute([$medicamento_id, $cantidad, "Venta Manual - Factura #" . $factura_id, $_SESSION['user_id']]);

            $this->pdo->commit();

            // 6. Registro de auditoría
            if (isset($_SESSION['user_id'])) {
                $logService = new LogService($this->pdo);
                $logService->register(
                    $_SESSION['user_id'],
                    'Despacho de Medicamento',
                    'Farmacia',
                    "Medicamento: {$medicamento['nombre']} (ID: $medicamento_id) | Cantidad enviada: {$cantidad} | Factura autogenerada ID: {$factura_id}",
                    'INFO'
                );
            }

            return $factura_id;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error PharmacyService::dispense: " . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Obtiene el inventario de medicamentos.
     */
    public function getInventory()
    {
        $stmt = $this->pdo->query("SELECT * FROM medicamentos ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene la lista de pacientes.
     */
    public function getPatients()
    {
        $stmt = $this->pdo->query("SELECT id, nombre, apellido, identificacion FROM pacientes ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las prescripciones pendientes de dispensación.
     */
    public function getPendingPrescriptions()
    {
        $sql = "SELECT p.*, 
                       pa.nombre as paciente_nombre, pa.apellido as paciente_apellido,
                       m.nombre as medico_nombre, m.apellido as medico_apellido,
                       GROUP_CONCAT(COALESCE(pd.medicamento_texto, '') SEPARATOR ', ') as medicamentos_summary
                FROM prescripciones p
                LEFT JOIN prescripcion_detalle pd ON p.id = pd.prescripcion_id
                JOIN pacientes pa ON p.paciente_id = pa.id
                JOIN medicos m ON p.medico_id = m.id
                WHERE p.estado = 'Pendiente'
                GROUP BY p.id
                ORDER BY p.fecha_prescripcion DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una prescripción por ID con sus detalles.
     */
    public function getPrescription($id)
    {
        $stmt = $this->pdo->prepare("SELECT p.*, pa.nombre as paciente_nombre, pa.apellido as paciente_apellido, pa.identificacion
                                     FROM prescripciones p 
                                     JOIN pacientes pa ON p.paciente_id = pa.id 
                                     WHERE p.id = ?");
        $stmt->execute([$id]);
        $prescripcion = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($prescripcion) {
            $stmt_det = $this->pdo->prepare("SELECT pd.*, m.nombre as medicamento_nombre, m.precio, m.stock
                                             FROM prescripcion_detalle pd
                                             LEFT JOIN medicamentos m ON pd.medicamento_id = m.id
                                             WHERE pd.prescripcion_id = ?");
            $stmt_det->execute([$id]);
            $prescripcion['detalles'] = $stmt_det->fetchAll(PDO::FETCH_ASSOC);
        }

        return $prescripcion;
    }

    /**
     * Procesa la dispensación completa de una prescripción.
     */
    public function dispensePrescription($prescripcion_id, $items_dispensados)
    {
        try {
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
            }

            $prescripcion = $this->getPrescription($prescripcion_id);
            if (!$prescripcion)
                throw new Exception("Prescripción no encontrada.");
            if ($prescripcion['estado'] !== 'Pendiente')
                throw new Exception("La prescripción ya no está pendiente.");

            // 1. Crear Factura Principal (usando BillingService)
            $factura_id = $this->billingService->createInvoice($prescripcion['paciente_id']);

            // 2. Crear Registro de Venta Farmacia
            $stmt_venta = $this->pdo->prepare("INSERT INTO ventas_farmacia (paciente_id, usuario_id, factura_id) VALUES (?, ?, ?)");
            $stmt_venta->execute([$prescripcion['paciente_id'], $_SESSION['user_id'], $factura_id]);
            $venta_id = $this->pdo->lastInsertId();

            $total_venta = 0;

            foreach ($items_dispensados as $item) {
                $medicamento_id = $item['medicamento_id'];
                $cantidad = $item['cantidad'];

                if ($cantidad <= 0)
                    continue;

                // Bloqueo de stock para actualización segura
                $stmt_med = $this->pdo->prepare("SELECT * FROM medicamentos WHERE id = ? FOR UPDATE");
                $stmt_med->execute([$medicamento_id]);
                $medicamento = $stmt_med->fetch(PDO::FETCH_ASSOC);

                if (!$medicamento)
                    throw new Exception("Medicamento ID $medicamento_id no existe.");
                if ($medicamento['stock'] < $cantidad)
                    throw new Exception("Stock insuficiente para: " . $medicamento['nombre']);

                $subtotal = $cantidad * $medicamento['precio'];
                $total_venta += $subtotal;

                // 3. Crear Detalle de Venta Farmacia
                $stmt_det_venta = $this->pdo->prepare("INSERT INTO ventas_farmacia_detalle (venta_id, medicamento_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
                $stmt_det_venta->execute([$venta_id, $medicamento_id, $cantidad, $medicamento['precio'], $subtotal]);

                // 4. Descontar stock en medicamentos
                $stmt_upd_stock = $this->pdo->prepare("UPDATE medicamentos SET stock = stock - ? WHERE id = ?");
                $stmt_upd_stock->execute([$cantidad, $medicamento_id]);

                // 5. Registrar movimiento en movimientos_inventario
                $stmt_mov = $this->pdo->prepare("INSERT INTO movimientos_inventario (medicamento_id, tipo_movimiento, cantidad, motivo, usuario_id) VALUES (?, 'Salida', ?, ?, ?)");
                $stmt_mov->execute([$medicamento_id, $cantidad, "Venta Farmacia - Prescripción #" . $prescripcion_id, $_SESSION['user_id']]);

                // 6. Agregar Ítem a la Factura (usando BillingService)
                $this->billingService->addItem($factura_id, [
                    'tipo_item' => 'medicamento',
                    'descripcion' => $medicamento['nombre'],
                    'cantidad' => $cantidad,
                    'precio' => $medicamento['precio']
                ]);
            }

            // Actualizar total de la venta farmacia
            $stmt_upd_venta = $this->pdo->prepare("UPDATE ventas_farmacia SET total = ? WHERE id = ?");
            $stmt_upd_venta->execute([$total_venta, $venta_id]);

            // Marcar prescripción como dispensada
            $stmt_upd_pres = $this->pdo->prepare("UPDATE prescripciones SET estado = 'Dispensada' WHERE id = ?");
            $stmt_upd_pres->execute([$prescripcion_id]);

            $this->pdo->commit();

            // Auditoría
            $logService = new LogService($this->pdo);
            $logService->register($_SESSION['user_id'], 'Dispensación Prescripción', 'Farmacia', "ID Prescripción: $prescripcion_id, Venta ID: $venta_id, Factura ID: $factura_id", 'INFO');

            return $factura_id;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction())
                $this->pdo->rollBack();
            error_log("Error PharmacyService::dispensePrescription: " . $e->getMessage());
            throw $e;
        }
    }
}

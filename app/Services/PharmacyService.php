<?php
namespace App\Services;

use Exception;
use PDO;
use PDOException;
use App\Helpers\AuthHelper;
use App\Services\BillingService;
use App\Services\LogService;
use App\Repositories\PharmacyRepository;

class PharmacyService
{
    private $pdo;
    private $billingService;
    private $repo;

    public function __construct($pdo)
    {
        // Ambos roles pueden acceder
        AuthHelper::checkRole(['administrador', 'farmaceutico']);

        $this->pdo = $pdo;
        $this->billingService = new BillingService($pdo);
        $this->repo = new PharmacyRepository($pdo);
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
            $medicamento = $this->repo->getMedicamentoForUpdate($medicamento_id);

            if (!$medicamento) {
                throw new Exception("El medicamento seleccionado no existe en la base de datos.");
            }

            // 2. Comprobar que en el inventario hay suficiente stock
            if ((int) $medicamento['stock'] < (int) $cantidad) {
                throw new Exception("Inventario insuficiente para: " . $medicamento['nombre'] . ". Stock actual: " . $medicamento['stock']);
            }

            // 3. Reducir inventario
            $nuevo_stock = (int) $medicamento['stock'] - (int) $cantidad;
            $this->repo->updateStock($medicamento_id, $nuevo_stock);

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
            $venta_id = $this->repo->createVenta($paciente_id, $_SESSION['user_id'], $factura_id, ($cantidad * $medicamento['precio']));

            // 7. Crear Registro en ventas_farmacia_detalle
            $this->repo->createVentaDetalle($venta_id, $medicamento_id, $cantidad, $medicamento['precio'], ($cantidad * $medicamento['precio']));

            // 8. Registrar movimiento en movimientos_inventario
            $this->repo->createMovimiento($medicamento_id, 'Salida', $cantidad, "Venta Manual - Factura #" . $factura_id, $_SESSION['user_id']);

            if ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

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
     * Registra un nuevo medicamento en el inventario.
     *
     * @param array $data Datos del medicamento
     * @param int $usuario_id ID del usuario que registra
     * @return int ID del medicamento creado
     * @throws Exception Si los datos son inválidos
     */
    public function registrarMedicamento(array $data, int $usuario_id): int
    {
        // Validaciones
        if (empty(trim($data['nombre'] ?? ''))) {
            throw new Exception("El nombre del medicamento es obligatorio.");
        }
        if (empty(trim($data['presentacion'] ?? ''))) {
            throw new Exception("La presentación es obligatoria.");
        }
        $precio = (float) ($data['precio'] ?? -1);
        if ($precio < 0) {
            throw new Exception("El precio debe ser mayor o igual a 0.");
        }
        $stock = (int) ($data['stock'] ?? 0);
        if ($stock < 0) {
            throw new Exception("El stock inicial debe ser mayor o igual a 0.");
        }
        if (!empty($data['fecha_vencimiento']) && strtotime($data['fecha_vencimiento']) < strtotime(date('Y-m-d'))) {
            throw new Exception("La fecha de vencimiento no puede ser anterior a hoy.");
        }

        // Generar código automático si no se provee
        if (empty(trim($data['codigo'] ?? ''))) {
            $data['codigo'] = 'MED-' . strtoupper(substr(trim($data['nombre']), 0, 4)) . '-' . time();
        }

        $data['precio'] = $precio;
        $data['stock'] = $stock;

        try {
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
            }

            $medicamento_id = $this->repo->createMedicamento($data);

            // Registrar movimiento de entrada si hay stock inicial
            if ($stock > 0) {
                $this->repo->createMovimiento($medicamento_id, 'Entrada', $stock, 'Registro inicial de medicamento', $usuario_id);
            }

            if ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

            // Auditoría
            $logService = new LogService($this->pdo);
            $logService->register(
                $usuario_id,
                'Registro de medicamento',
                'Farmacia',
                "ID: $medicamento_id, Nombre: {$data['nombre']}, Stock: $stock",
                'INFO'
            );

            return (int) $medicamento_id;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error PharmacyService::registrarMedicamento: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Edita los datos de un medicamento existente.
     *
     * @param int $id ID del medicamento
     * @param array $data Datos a actualizar
     * @return bool
     * @throws Exception Si el medicamento no existe o datos inválidos
     */
    public function editarMedicamento(int $id, array $data): bool
    {
        if (empty(trim($data['nombre'] ?? ''))) {
            throw new Exception("El nombre del medicamento es obligatorio.");
        }
        if (empty(trim($data['presentacion'] ?? ''))) {
            throw new Exception("La presentación es obligatoria.");
        }
        $precio = (float) ($data['precio'] ?? -1);
        if ($precio < 0) {
            throw new Exception("El precio debe ser mayor o igual a 0.");
        }

        $medicamento = $this->repo->getMedicamentoById($id);
        if (!$medicamento) {
            throw new Exception("Medicamento no encontrado.");
        }

        $data['precio'] = $precio;
        $result = $this->repo->updateMedicamento($id, $data);

        if ($result) {
            $logService = new LogService($this->pdo);
            $logService->register(
                $_SESSION['user_id'] ?? null,
                'Edición de medicamento',
                'Farmacia',
                "ID: $id, Nombre: {$data['nombre']}",
                'INFO'
            );
        }

        return $result;
    }

    /**
     * Ajusta el stock de un medicamento (entrada de inventario).
     *
     * @param int $id ID del medicamento
     * @param int $cantidad Cantidad a añadir
     * @param string $motivo Motivo del ajuste
     * @param int $usuario_id ID del usuario que realiza el ajuste
     * @return bool
     * @throws Exception Si los datos son inválidos
     */
    public function ajustarStock(int $id, int $cantidad, string $motivo, int $usuario_id): bool
    {
        if ($cantidad <= 0) {
            throw new Exception("La cantidad debe ser mayor a 0.");
        }
        if (empty(trim($motivo))) {
            throw new Exception("El motivo del ajuste es obligatorio.");
        }

        $medicamento = $this->repo->getMedicamentoById($id);
        if (!$medicamento) {
            throw new Exception("Medicamento no encontrado.");
        }

        try {
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
            }

            $this->repo->increaseStock($id, $cantidad);
            $this->repo->createMovimiento($id, 'Entrada', $cantidad, $motivo, $usuario_id);

            if ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

            $logService = new LogService($this->pdo);
            $logService->register(
                $usuario_id,
                'Ajuste de stock (entrada)',
                'Farmacia',
                "Medicamento ID: $id ({$medicamento['nombre']}), +$cantidad unidades, Motivo: $motivo",
                'INFO'
            );

            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error PharmacyService::ajustarStock: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene el inventario de medicamentos.
     */
    public function getInventory()
    {
        return $this->repo->getAllMedicamentos();
    }

    /**
     * Obtiene la lista de pacientes.
     */
    public function getPatients()
    {
        return $this->repo->getAllPacientesBasic();
    }

    /**
     * Obtiene las prescripciones pendientes de dispensación.
     */
    public function getPendingPrescriptions($limit = 5, $offset = 0)
    {
        return $this->repo->getPendingPrescriptions($limit, $offset);
    }

    public function getPendingPrescriptionsCount()
    {
        return $this->repo->getPendingPrescriptionsCount();
    }

    /**
     * Obtiene una prescripción por ID con sus detalles.
     */
    public function getPrescription($id)
    {
        return $this->repo->getPrescriptionAndDetails($id);
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
            $venta_id = $this->repo->createVenta($prescripcion['paciente_id'], $_SESSION['user_id'], $factura_id, null);

            $total_venta = 0;

            foreach ($items_dispensados as $item) {
                $medicamento_id = $item['medicamento_id'];
                $cantidad = $item['cantidad'];

                if ($cantidad <= 0)
                    continue;

                // Bloqueo de stock para actualización segura
                $medicamento = $this->repo->getMedicamentoForUpdate($medicamento_id);

                if (!$medicamento)
                    throw new Exception("Medicamento ID $medicamento_id no existe.");
                if ($medicamento['stock'] < $cantidad)
                    throw new Exception("Stock insuficiente para: " . $medicamento['nombre']);

                $subtotal = $cantidad * $medicamento['precio'];
                $total_venta += $subtotal;

                // 3. Crear Detalle de Venta Farmacia
                $this->repo->createVentaDetalle($venta_id, $medicamento_id, $cantidad, $medicamento['precio'], $subtotal);

                // 4. Descontar stock en medicamentos
                $this->repo->decreaseStock($medicamento_id, $cantidad);

                // 5. Registrar movimiento en movimientos_inventario
                $this->repo->createMovimiento($medicamento_id, 'Salida', $cantidad, "Venta Farmacia - Prescripción #" . $prescripcion_id, $_SESSION['user_id']);

                // 6. Agregar Ítem a la Factura (usando BillingService)
                $this->billingService->addItem($factura_id, [
                    'tipo_item' => 'medicamento',
                    'descripcion' => $medicamento['nombre'],
                    'cantidad' => $cantidad,
                    'precio' => $medicamento['precio']
                ]);
            }

            // Actualizar total de la venta farmacia
            $this->repo->updateVentaTotal($venta_id, $total_venta);

            // Marcar prescripción como dispensada
            $this->repo->updatePrescriptionState($prescripcion_id, 'Dispensada');

            if ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

            // Auditoría
            $logService = new LogService($this->pdo);
            $logService->register($_SESSION['user_id'], 'Dispensación Prescripción', 'Farmacia', "ID Prescripción: $prescripcion_id, Venta ID: $venta_id, Factura ID: $factura_id", 'INFO');

            return $factura_id;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction())
                $this->pdo->rollBack();
            error_log("Error PharmacyService::dispensePrescription: " . $e->getMessage());
        }
    }

    public function deleteMedicamento($id)
    {
        AuthHelper::checkRole(['administrador']); // Solo administradores pueden eliminar medicamentos

        try {
            $medicamento = $this->repo->getMedicamentoForUpdate($id);
            if (!$medicamento) {
                return false;
            }

            $this->checkMedicamentoDependencies($id);

            $res = $this->repo->deleteMedicamento($id);

            if ($res) {
                $logService = new LogService($this->pdo);
                $logService->register($_SESSION['user_id'], 'Eliminación lógica de medicamento', 'Farmacia', "ID: $id, Nombre: {$medicamento['nombre']}", 'ERROR');
            }
            return $res;
        } catch (Exception $e) {
            error_log("Error PharmacyService::deleteMedicamento: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Restaura un medicamento previamente eliminado (soft delete).
     */
    public function restoreMedicamento($id)
    {
        AuthHelper::checkRole(['administrador']); // Solo administradores pueden restaurar medicamentos

        $id = (int) $id;

        $medicamento = $this->repo->getByIdIncludingDeleted($id);
        if (!$medicamento) {
            throw new Exception("Medicamento no encontrado.");
        }

        if ($medicamento['deleted_at'] === null) {
            throw new Exception("El medicamento ya está activo, no se puede restaurar.");
        }

        // Política strict_block: evitar duplicados lógicos por nombre
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM medicamentos 
             WHERE nombre = ? AND deleted_at IS NULL AND id <> ?"
        );
        $stmt->execute([$medicamento['nombre'], $id]);
        if ((int) $stmt->fetchColumn() > 0) {
            $logService = new LogService($this->pdo);
            $logService->register(
                $_SESSION['user_id'] ?? null,
                'Intento fallido de restauración de medicamento',
                'Farmacia',
                "ID: {$id}, Nombre duplicado: {$medicamento['nombre']}",
                'WARNING'
            );
            throw new Exception("No se puede restaurar el medicamento porque ya existe otro activo con el mismo nombre.");
        }

        $res = $this->repo->restore($id);

        if ($res) {
            $logService = new LogService($this->pdo);
            $logService->register(
                $_SESSION['user_id'] ?? null,
                "Restauración de medicamento | ID: {$id}",
                'Farmacia',
                "ID: {$id}, Nombre: {$medicamento['nombre']}",
                'INFO'
            );
        }

        return $res;
    }

    private function checkMedicamentoDependencies($id)
    {
        // 1. Verificar si hay ventas/despachos asociados
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM ventas_farmacia_detalle WHERE medicamento_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("No se puede eliminar el medicamento porque tiene registros de ventas asociados.");
        }

        // 2. Verificar si está en prescripciones activas
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM prescripcion_detalle pd 
                                    JOIN prescripciones p ON pd.prescripcion_id = p.id 
                                    WHERE pd.medicamento_id = ? AND p.estado = 'Pendiente' AND p.deleted_at IS NULL");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("No se puede eliminar el medicamento porque está en prescripciones pendientes.");
        }
    }

    /**
     * Obtiene el historial de movimientos de inventario.
     */
    public function getMovimientos(array $filtros = [])
    {
        return $this->repo->getMovimientos($filtros);
    }

    public function getMedicamentosConStockParaRecetar()
    {
        return $this->repo->getWithStock();
    }
}

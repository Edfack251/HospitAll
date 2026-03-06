<?php
namespace App\Services;

use Exception;
use PDO;
use PDOException;
use App\Services\LogService;

class BillingService
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Inicia una nueva factura para un paciente con estado Pendiente.
     */
    public function createInvoice($paciente_id)
    {
        $isTransactionActiva = $this->pdo->inTransaction();
        try {
            if (!$isTransactionActiva) {
                $this->pdo->beginTransaction();
            }

            $stmt = $this->pdo->prepare("INSERT INTO facturas (paciente_id, total, estado) VALUES (?, 0.00, 'Pendiente')");
            $stmt->execute([$paciente_id]);
            $factura_id = $this->pdo->lastInsertId();

            if (isset($_SESSION['user_id'])) {
                $logService = new LogService($this->pdo);
                $logService->register($_SESSION['user_id'], 'Crear factura', 'Facturación', "Factura ID: $factura_id paciente ID: $paciente_id", 'INFO');
            }

            if (!$isTransactionActiva) {
                $this->pdo->commit();
            }

            return $factura_id;
        } catch (PDOException $e) {
            if (!$isTransactionActiva && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error BillingService::createInvoice: " . $e->getMessage());
            throw new Exception("No se pudo crear la factura.");
        }
    }

    /**
     * Crea una factura específica para consulta médica leyendo la tarifa configurada en 'servicios'
     */
    public function createConsultationInvoice($paciente_id)
    {
        try {
            $stmt = $this->pdo->query("SELECT id, codigo, nombre, precio FROM servicios WHERE tipo = 'consulta' AND codigo = 'CONS_GEN' AND activo = 1 LIMIT 1");
            $servicio = $stmt->fetch();

            if (!$servicio) {
                throw new Exception("No se encontró el servicio base de Consulta General (CONS_GEN) en la base de datos.");
            }

            $factura_id = $this->createInvoice($paciente_id);
            $this->addItem($factura_id, [
                'servicio_id' => $servicio['id'],
                'tipo_item' => 'consulta',
                'descripcion' => $servicio['nombre'],
                'cantidad' => 1,
                'precio' => $servicio['precio']
            ]);

            return $factura_id;
        } catch (Exception $e) {
            error_log("Error BillingService::createConsultationInvoice: " . $e->getMessage());
            throw new Exception("Error al emitir factura automática de consulta.");
        }
    }

    /**
     * Crea una factura específica para análisis de laboratorio leyendo la tarifa configurada en 'servicios'
     */
    public function createLaboratoryInvoice($paciente_id, $orden_id, $descripcion_lab = '')
    {
        try {
            $stmt = $this->pdo->query("SELECT id, codigo, nombre, precio FROM servicios WHERE tipo = 'laboratorio' AND codigo = 'LAB_STD' AND activo = 1 LIMIT 1");
            $servicio = $stmt->fetch();

            if (!$servicio) {
                throw new Exception("No se encontró el servicio base de Laboratorio Standard (LAB_STD) en la base de datos.");
            }

            $desc_final = 'Orden de Laboratorio #' . $orden_id . ($descripcion_lab ? ' - ' . $descripcion_lab : '');

            $factura_id = $this->createInvoice($paciente_id);
            $this->addItem($factura_id, [
                'servicio_id' => $servicio['id'],
                'tipo_item' => 'laboratorio',
                'descripcion' => $desc_final,
                'cantidad' => 1,
                'precio' => $servicio['precio']
            ]);

            return $factura_id;
        } catch (Exception $e) {
            error_log("Error BillingService::createLaboratoryInvoice: " . $e->getMessage());
            throw new Exception("Error al emitir factura automática de laboratorio.");
        }
    }

    /**
     * Añade un ítem a la factura y actualiza automáticamente el total.
     */
    public function addItem($factura_id, $data)
    {
        $isTransactionActiva = $this->pdo->inTransaction();
        try {
            if (!$isTransactionActiva) {
                $this->pdo->beginTransaction();
            }

            $cantidad = isset($data['cantidad']) ? (int) $data['cantidad'] : 1;
            $precio = (float) $data['precio'];
            $subtotal = $cantidad * $precio;

            $sql = "INSERT INTO factura_detalle (factura_id, servicio_id, tipo_item, descripcion, cantidad, precio, subtotal) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $factura_id,
                $data['servicio_id'] ?? null,
                $data['tipo_item'],
                $data['descripcion'],
                $cantidad,
                $precio,
                $subtotal
            ]);

            $this->calculateTotal($factura_id);

            if (!$isTransactionActiva) {
                $this->pdo->commit();
            }

            return true;
        } catch (PDOException $e) {
            if (!$isTransactionActiva && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error BillingService::addItem: " . $e->getMessage());
            throw new Exception("Error al añadir ítem a la factura.");
        }
    }

    /**
     * Recalcula el total de la factura sumando sus ítems.
     */
    public function calculateTotal($factura_id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT SUM(subtotal) FROM factura_detalle WHERE factura_id = ?");
            $stmt->execute([$factura_id]);
            $total = (float) $stmt->fetchColumn();

            $updateStmt = $this->pdo->prepare("UPDATE facturas SET total = ? WHERE id = ?");
            $updateStmt->execute([$total, $factura_id]);

            return $total;
        } catch (PDOException $e) {
            error_log("Error BillingService::calculateTotal: " . $e->getMessage());
            throw new Exception("Error al calcular el total.");
        }
    }

    /**
     * Marca la factura como Pagada registrando el método de pago.
     */
    public function markAsPaid($factura_id, $metodo_pago)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE facturas SET estado = 'Pagada', metodo_pago = ? WHERE id = ?");
            $res = $stmt->execute([$metodo_pago, $factura_id]);

            if ($res && isset($_SESSION['user_id'])) {
                $logService = new LogService($this->pdo);
                $logService->register($_SESSION['user_id'], 'Pago de factura', 'Facturación', "Factura ID: $factura_id pagada vía $metodo_pago", 'INFO');
            }

            return $res;
        } catch (PDOException $e) {
            error_log("Error BillingService::markAsPaid: " . $e->getMessage());
            throw new Exception("Error al procesar el pago de la factura.");
        }
    }

    /**
     * Obtiene una factura específica con sus datos y paciente.
     */
    public function getInvoice($factura_id)
    {
        $stmt = $this->pdo->prepare("SELECT f.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.identificacion 
                                     FROM facturas f JOIN pacientes p ON f.paciente_id = p.id WHERE f.id = ?");
        $stmt->execute([$factura_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los ítems de una factura.
     */
    public function getInvoiceItems($factura_id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM factura_detalle WHERE factura_id = ? ORDER BY id ASC");
        $stmt->execute([$factura_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna todas las facturas con soporte a paginación.
     */
    public function getAllInvoices($limit = null, $offset = 0)
    {
        $sql = "SELECT f.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.identificacion as paciente_identificacion, p.id as paciente_id_real 
                FROM facturas f 
                JOIN pacientes p ON f.paciente_id = p.id 
                ORDER BY f.created_at DESC";

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
}

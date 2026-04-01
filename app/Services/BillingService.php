<?php
namespace App\Services;

use App\Repositories\BillingRepository;
use Exception;
use PDO;
use PDOException;
use App\Services\LogService;

class BillingService
{
    private $pdo;
    private $repo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->repo = new BillingRepository($pdo);
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

            $factura_id = $this->repo->createInvoice($paciente_id);

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
            $servicio = $this->repo->getServiceBase('consulta', 'CONS_GEN');

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
            $servicio = $this->repo->getServiceBase('laboratorio', 'LAB_STD');

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
     * Crea una factura específica para estudios de imágenes leyendo la tarifa configurada en 'servicios'
     */
    public function createImagingInvoice($paciente_id, $orden_id, $descripcion_img = '')
    {
        try {
            $servicio = $this->repo->getServiceBase('imagenes', 'IMG_STD');

            if (!$servicio) {
                throw new Exception("No se encontró el servicio base de Imágenes Standard (IMG_STD) en la base de datos.");
            }

            $desc_final = 'Estudio de Imágenes #' . $orden_id . ($descripcion_img ? ' - ' . $descripcion_img : '');

            $factura_id = $this->createInvoice($paciente_id);
            $this->addItem($factura_id, [
                'servicio_id' => $servicio['id'],
                'tipo_item' => 'imagenes',
                'descripcion' => $desc_final,
                'cantidad' => 1,
                'precio' => $servicio['precio']
            ]);

            return $factura_id;
        } catch (Exception $e) {
            error_log("Error BillingService::createImagingInvoice: " . $e->getMessage());
            throw new Exception("Error al emitir factura automática de imágenes.");
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

            $this->repo->addItem($factura_id, $data, $cantidad, $precio, $subtotal);

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
            return $this->repo->calculateTotal($factura_id);
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
            $res = $this->repo->markAsPaid($factura_id, $metodo_pago);

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
        return $this->repo->getInvoice($factura_id);
    }

    /**
     * Obtiene los ítems de una factura.
     */
    public function getInvoiceItems($factura_id)
    {
        return $this->repo->getInvoiceItems($factura_id);
    }

    /**
     * IDs de órdenes de laboratorio que ya tienen factura emitida (no mostrar botón Cobrar).
     */
    public function getOrdenesLabFacturados()
    {
        return $this->repo->getOrdenesLabFacturados();
    }

    public function getOrdenesImgFacturadas()
    {
        return $this->repo->getOrdenesImgFacturadas();
    }

    /**
     * Retorna todas las facturas con soporte a paginación.
     */
    public function getAllInvoices($limit = null, $offset = 0)
    {
        return $this->repo->getAllInvoices($limit, $offset);
    }
}

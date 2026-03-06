<?php
namespace App\Controllers;

use App\Services\BillingService;
use Exception;

class BillingController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new BillingService($pdo);
    }

    /**
     * Retorna y formatea la vista general de facturas.
     */
    public function index()
    {
        return $this->service->getAllInvoices();
    }

    /**
     * Endpoint para crear una nueva factura en blanco.
     */
    public function createInvoice($paciente_id)
    {
        try {
            $id = $this->service->createInvoice($paciente_id);
            header("Location: ../billing.php?id=" . $id . "&success=invoice_created");
            exit();
        } catch (Exception $e) {
            header("Location: ../billing.php?error=1&msg=" . urlencode($e->getMessage()));
            exit();
        }
    }

    /**
     * Endpoint para agregar un item al cobro de una factura estructurada.
     */
    public function addItem($factura_id, $data)
    {
        try {
            $this->service->addItem($factura_id, $data);
            header("Location: ../billing_details.php?id=" . $factura_id . "&success=item_added");
            exit();
        } catch (Exception $e) {
            header("Location: ../billing_details.php?id=" . $factura_id . "&error=1&msg=" . urlencode($e->getMessage()));
            exit();
        }
    }

    /**
     * Acción para liquidar una factura (pagar)
     */
    public function pay($factura_id, $metodo_pago)
    {
        try {
            $this->service->markAsPaid($factura_id, $metodo_pago);
            header("Location: ../billing.php?success=invoice_paid");
            exit();
        } catch (Exception $e) {
            header("Location: ../billing.php?error=1&msg=" . urlencode($e->getMessage()));
            exit();
        }
    }

    /**
     * Obtener el detalle de una factura.
     */
    public function getDetails($factura_id)
    {
        return [
            'factura' => $this->service->getInvoice($factura_id),
            'items' => $this->service->getInvoiceItems($factura_id)
        ];
    }
}

<?php
namespace App\Controllers;

use App\Helpers\UrlHelper;
use App\Services\BillingService;
use App\Policies\PolicyManager;
use Exception;

class BillingController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new BillingService($pdo);
    }

    public function index()
    {
        PolicyManager::authorize($_SESSION['user'] ?? [], 'view_billing');
        return $this->service->getAllInvoices();
    }

    public function handleCreate()
    {
        $paciente_id = (int) ($_POST['paciente_id'] ?? 0);
        $this->createInvoice($paciente_id);
    }

    public function createInvoice($paciente_id)
    {
        try {
            PolicyManager::authorize($_SESSION['user'] ?? [], 'create_invoice');
            $id = $this->service->createInvoice($paciente_id);
            UrlHelper::redirect('billing', ['id' => $id, 'success' => 'invoice_created']);
        } catch (Exception $e) {
            UrlHelper::redirect('billing', ['error' => '1', 'msg' => $e->getMessage()]);
        }
    }

    public function handleAddItem()
    {
        $factura_id = (int) ($_POST['factura_id'] ?? 0);
        $this->addItem($factura_id, $_POST);
    }

    public function addItem($factura_id, $data)
    {
        try {
            $this->service->addItem($factura_id, $data);
            UrlHelper::redirect('billing_details', ['id' => $factura_id, 'success' => 'item_added']);
        } catch (Exception $e) {
            UrlHelper::redirect('billing_details', ['id' => $factura_id, 'error' => '1', 'msg' => $e->getMessage()]);
        }
    }

    public function handlePay()
    {
        $factura_id = (int) ($_POST['factura_id'] ?? 0);
        $metodo_pago = $_POST['metodo_pago'] ?? 'Efectivo';
        $this->pay($factura_id, $metodo_pago);
    }

    public function pay($factura_id, $metodo_pago)
    {
        try {
            $this->service->markAsPaid($factura_id, $metodo_pago);
            UrlHelper::redirect('billing', ['success' => 'invoice_paid']);
        } catch (Exception $e) {
            UrlHelper::redirect('billing', ['error' => '1', 'msg' => $e->getMessage()]);
        }
    }

    public function getDetails($factura_id)
    {
        return [
            'factura' => $this->service->getInvoice($factura_id),
            'items' => $this->service->getInvoiceItems($factura_id)
        ];
    }
}

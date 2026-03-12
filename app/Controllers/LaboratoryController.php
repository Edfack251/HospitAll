<?php
namespace App\Controllers;

use App\Core\ErrorHandler;
use App\Helpers\UrlHelper;
use App\Services\LaboratoryService;
use App\Policies\PolicyManager;
use Exception;

class LaboratoryController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new LaboratoryService($pdo);
    }

    public function index()
    {
        PolicyManager::authorize($_SESSION['user'] ?? [], 'view_laboratory');
        return $this->service->getAllOrders();
    }

    public function handleUpload()
    {
        $this->uploadResult($_POST, $_FILES);
    }

    public function uploadResult($data, $files)
    {
        try {
            PolicyManager::authorize($_SESSION['user'] ?? [], 'upload_lab_result');
            $this->service->uploadResult($data, $files);
            UrlHelper::redirect('laboratory', ['success_lab' => '1']);
        } catch (Exception $e) {
            ErrorHandler::handle($e);
        }
    }

    public function bill()
    {
        $paciente_id = (int) ($_POST['paciente_id'] ?? 0);
        $orden_id = (int) ($_POST['orden_id'] ?? 0);
        $descripcion_lab = $_POST['descripcion'] ?? 'Análisis Clínico Standard';

        if ($paciente_id <= 0 || $orden_id <= 0) {
            UrlHelper::redirect('laboratory', ['error' => '1', 'msg' => 'Parámetros de facturación inválidos.']);
        }

        global $pdo;

        try {
            PolicyManager::authorize($_SESSION['user'] ?? [], 'create_invoice');
            $pdo->beginTransaction();

            $billingService = new \App\Services\BillingService($pdo);
            $factura_id = $billingService->createLaboratoryInvoice($paciente_id, $orden_id, $descripcion_lab);

            $pdo->commit();

            UrlHelper::redirect('billing_details', ['id' => $factura_id, 'success' => 'item_added']);

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            UrlHelper::redirect('laboratory', ['error' => '1', 'msg' => $e->getMessage()]);
        }
    }

    public function deleteOrder($id)
    {
        try {
            PolicyManager::authorize($_SESSION['user'] ?? [], 'delete_lab_order');
            $res = $this->service->deleteOrder($id);
            if ($res) {
                UrlHelper::redirect('laboratory', ['deleted' => '1']);
            } else {
                UrlHelper::redirect('laboratory', ['error' => '1', 'msg' => 'No se pudo eliminar la orden de laboratorio.']);
            }
        } catch (Exception $e) {
            error_log("LaboratoryController::deleteOrder: " . $e->getMessage());
            UrlHelper::redirect('laboratory', ['error' => '1', 'msg' => $e->getMessage()]);
        }
    }

    /**
     * Endpoint API para restaurar una orden de laboratorio eliminada lógicamente.
     * Se espera recibir el ID por POST (api: /api/laboratory/order/restore).
     */
    public function restoreOrderApi()
    {
        try {
            PolicyManager::authorize($_SESSION['user'] ?? [], 'restore_lab_order');

            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception("ID de orden inválido.");
            }

            $res = $this->service->restoreOrder($id);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => (bool) $res,
                'message' => $res ? 'Orden de laboratorio restaurada correctamente.' : 'No se pudo restaurar la orden de laboratorio.'
            ]);
        } catch (Exception $e) {
            error_log("LaboratoryController::restoreOrderApi: " . $e->getMessage());
            header('Content-Type: application/json', true, 400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}

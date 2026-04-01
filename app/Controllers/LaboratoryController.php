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
        PolicyManager::authorize($_SESSION, 'view_laboratory');
        $billingService = new \App\Services\BillingService($GLOBALS['pdo']);
        return [
            'ordenes' => $this->service->getAllOrders(),
            'ordenesLabFacturados' => $billingService->getOrdenesLabFacturados()
        ];
    }

    public function handleUpload()
    {
        $this->uploadResult($_POST, $_FILES);
    }

    public function uploadResult($data, $files)
    {
        try {
            PolicyManager::authorize($_SESSION, 'upload_lab_result');
            $this->service->uploadResult($data, $files);
            $redirectTo = $data['redirect_after'] ?? 'laboratory';
            UrlHelper::redirect($redirectTo, ['success_lab' => '1']);
        } catch (Exception $e) {
            ErrorHandler::handle($e);
        }
    }

    public function createWalkinOrder()
    {
        try {
            PolicyManager::authorize($_SESSION, 'view_laboratory');
            
            $input = $_POST;
            $walkin_id = (int) ($input['walkin_id'] ?? 0);
            $descripcion = trim($input['descripcion'] ?? 'Orden manual de laboratorio');
            $examenesRaw = $input['examenes'] ?? '';
            $examenes = array_filter(array_map('trim', explode(',', $examenesRaw)));

            if ($walkin_id <= 0) {
                UrlHelper::redirect('dashboard_laboratory', ['error' => '1', 'msg' => 'ID de walkin inválido.']);
            }

            $this->service->createWalkinOrder($walkin_id, $descripcion, $examenes);

            UrlHelper::redirect('dashboard_laboratory', ['walkin_id' => $walkin_id, 'success_order' => '1']);
        } catch (Exception $e) {
            error_log('LaboratoryController::createWalkinOrder: ' . $e->getMessage());
            UrlHelper::redirect('dashboard_laboratory', ['error' => '1', 'msg' => $e->getMessage()]);
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
            PolicyManager::authorize($_SESSION, 'create_invoice');
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
            PolicyManager::authorize($_SESSION, 'delete_lab_order');
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
     * Endpoint API para actualizar el estado de una orden (Pendiente → En proceso → Completada).
     */
    public function updateEstado()
    {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $orden_id = (int) ($input['orden_id'] ?? 0);
            $nuevo_estado = trim($input['nuevo_estado'] ?? '');

            if ($orden_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'orden_id inválido.']);
                return;
            }
            if (!in_array($nuevo_estado, ['En proceso', 'Completada'], true)) {
                echo json_encode(['success' => false, 'error' => 'Estado no permitido.']);
                return;
            }

            $this->service->updateEstadoOrden($orden_id, $nuevo_estado);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            error_log('LaboratoryController::updateEstado: ' . $e->getMessage());
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Endpoint API para restaurar una orden de laboratorio eliminada lógicamente.
     * Se espera recibir el ID por POST (api: /api/laboratory/order/restore).
     */
    public function restoreOrderApi()
    {
        try {
            PolicyManager::authorize($_SESSION, 'restore_lab_order');

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

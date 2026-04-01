<?php
namespace App\Controllers;

use App\Core\ErrorHandler;
use App\Helpers\UrlHelper;
use App\Services\ImagingService;
use Exception;

class ImagingController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new ImagingService($pdo);
    }

    /**
     * Carga la vista principal del módulo (redirección al dashboard).
     */
    public function index()
    {
        \App\Policies\PolicyManager::authorize($_SESSION, 'view_imagenes');
        $billingService = new \App\Services\BillingService($GLOBALS['pdo']);
        $data = $this->service->getAllOrders();
        return [
            'pendientes' => $data['pendientes'],
            'completadas' => $data['completadas'],
            'ordenesImgFacturadas' => $billingService->getOrdenesImgFacturadas()
        ];
    }

    public function bill()
    {
        $paciente_id = (int) ($_POST['paciente_id'] ?? 0);
        $orden_id = (int) ($_POST['orden_id'] ?? 0);
        $descripcion_img = $_POST['descripcion'] ?? 'Estudio de Imágenes';

        if ($paciente_id <= 0 || $orden_id <= 0) {
            UrlHelper::redirect('imaging', ['error' => '1', 'msg' => 'Parámetros de facturación inválidos.']);
        }

        global $pdo;

        try {
            \App\Policies\PolicyManager::authorize($_SESSION, 'create_invoice');
            $pdo->beginTransaction();

            $billingService = new \App\Services\BillingService($pdo);
            $factura_id = $billingService->createImagingInvoice($paciente_id, $orden_id, $descripcion_img);

            $pdo->commit();

            UrlHelper::redirect('billing_details', ['id' => $factura_id, 'success' => 'item_added']);

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            UrlHelper::redirect('imaging', ['error' => '1', 'msg' => $e->getMessage()]);
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
            if ($e instanceof \PDOException || strpos($e->getMessage(), 'SQLSTATE') !== false) {
                error_log('[HospitAll] Error en ImagingController: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error interno del servidor. Intente de nuevo.']);
            } else {
                error_log('ImagingController::updateEstado: ' . $e->getMessage());
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Endpoint API para subir archivo de estudio de imágenes.
     */
    public function subirArchivo()
    {
        try {
            $orden_id = (int) ($_POST['orden_id'] ?? 0);
            if ($orden_id <= 0) {
                UrlHelper::redirect('dashboard_imaging', ['error' => '1', 'msg' => 'orden_id inválido.']);
                return;
            }

            $this->service->subirArchivo($orden_id, $_FILES);
            $redirectTo = $_POST['redirect_after'] ?? 'dashboard_imaging';
            UrlHelper::redirect($redirectTo, ['success_imagen' => '1']);
        } catch (Exception $e) {
            error_log('ImagingController::subirArchivo: ' . $e->getMessage());
            ErrorHandler::handle($e);
        }
    }
}

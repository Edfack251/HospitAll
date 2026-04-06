<?php
namespace App\Controllers;

use App\Helpers\UrlHelper;
use App\Services\PharmacyService;
use App\Policies\PolicyManager;
use Exception;
use PDO;

class PharmacyController
{
    private $service;
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->service = new PharmacyService($pdo);
    }

    public function getInventory()
    {
        PolicyManager::authorize($_SESSION['user'] ?? [], 'view_inventory');
        return $this->service->getInventory();
    }

    public function getPatients()
    {
        return $this->service->getPatients();
    }

    public function handleDispense()
    {
        $this->dispense($_POST);
    }

    public function dispense(array $data)
    {
        try {
            PolicyManager::authorize($_SESSION['user'] ?? [], 'dispense_medicine');
            $paciente_id = (int) $data['paciente_id'];
            $medicamento_id = (int) $data['medicamento_id'];
            $cantidad = (int) $data['cantidad'];

            $factura_id = $this->service->dispense($paciente_id, $medicamento_id, $cantidad);

            UrlHelper::redirect('pharmacy', ['success' => 'dispensed', 'factura_id' => $factura_id]);
        } catch (Exception $e) {
            UrlHelper::redirect('pharmacy', ['error' => '1', 'msg' => $e->getMessage()]);
        }
    }

    public function getPendingPrescriptions()
    {
        return $this->service->getPendingPrescriptions();
    }

    public function getPrescription($id)
    {
        return $this->service->getPrescription($id);
    }

    public function handleProcessDispense()
    {
        $this->processDispense($_POST);
    }

    public function processDispense(array $data)
    {
        try {
            PolicyManager::authorize($_SESSION['user'] ?? [], 'dispense_medicine');
            $prescripcion_id = (int) $data['prescripcion_id'];
            $items = $data['items'];

            $factura_id = $this->service->dispensePrescription($prescripcion_id, $items);

            UrlHelper::redirect('pharmacy', ['success' => 'dispensed', 'factura_id' => $factura_id]);
        } catch (Exception $e) {
            UrlHelper::redirect('pharmacy_dispense', ['id' => $data['prescripcion_id'], 'error' => '1', 'msg' => $e->getMessage()]);
        }
    }

    public function deleteMedicamento($id)
    {
        try {
            PolicyManager::authorize($_SESSION['user'] ?? [], 'delete_medicine');
            $res = $this->service->deleteMedicamento($id);
            if ($res) {
                UrlHelper::redirect('pharmacy', ['deleted' => '1']);
            } else {
                UrlHelper::redirect('pharmacy', ['error' => '1', 'msg' => 'No se pudo eliminar el medicamento.']);
            }
        } catch (Exception $e) {
            error_log("PharmacyController::deleteMedicamento: " . $e->getMessage());
            UrlHelper::redirect('pharmacy', ['error' => '1', 'msg' => $e->getMessage()]);
        }
    }

    /**
     * Endpoint API para restaurar un medicamento eliminado lógicamente.
     * Se espera recibir el ID por POST (api: /api/pharmacy/medicamento/restore).
     */
    public function restoreMedicamentoApi()
    {
        try {
            PolicyManager::authorize($_SESSION['user'] ?? [], 'restore_medicine');

            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception("ID de medicamento inválido.");
            }

            $res = $this->service->restoreMedicamento($id);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => (bool) $res,
                'message' => $res ? 'Medicamento restaurado correctamente.' : 'No se pudo restaurar el medicamento.'
            ]);
        } catch (Exception $e) {
            error_log("PharmacyController::restoreMedicamentoApi: " . $e->getMessage());
            header('Content-Type: application/json', true, 400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}

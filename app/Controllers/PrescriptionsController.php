<?php
namespace App\Controllers;

use App\Services\PrescriptionService;
use App\Policies\PolicyManager;
use Exception;

class PrescriptionsController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new PrescriptionService($pdo);
    }

    /**
     * Endpoint API para restaurar una prescripción eliminada lógicamente.
     * Se espera recibir el ID por POST (api: /api/prescriptions/restore).
     */
    public function restoreApi()
    {
        try {
            PolicyManager::authorize($_SESSION['user'] ?? [], 'restore_prescription');

            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception("ID de prescripción inválido.");
            }

            $res = $this->service->restorePrescription($id);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => (bool) $res,
                'message' => $res ? 'Prescripción restaurada correctamente.' : 'No se pudo restaurar la prescripción.'
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json', true, 400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}


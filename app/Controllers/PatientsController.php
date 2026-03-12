<?php
namespace App\Controllers;

use App\Helpers\UrlHelper;
use App\Services\PatientsService;
use App\Policies\PolicyManager;
use Exception;

class PatientsController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new PatientsService($pdo);
    }

    public function index($search = null, $isMedico = false)
    {
        PolicyManager::authorize($_SESSION['user'] ?? [], 'view_patient');

        if (!empty($search)) {
            if ($isMedico || preg_match('/^\d{3}-\d{7}-\d{1}$/', $search)) {
                $results = $this->service->searchByIdentification($search);
                if (!empty($results))
                    return $results;
            }
            return $this->service->search($search);
        }

        return [];
    }

    public function getById($id)
    {
        $paciente = $this->service->getById($id);
        if (!$paciente) {
            UrlHelper::redirect('patients');
        }
        return $paciente;
    }

    public function handleCreate()
    {
        $this->create($_POST);
    }

    public function create($data)
    {
        try {
            PolicyManager::authorize($_SESSION['user'] ?? [], 'create_patient');

            $this->service->create($data);
            UrlHelper::redirect('patients', ['success' => '1']);
        } catch (Exception $e) {
            error_log("PatientsController::create: " . $e->getMessage());
            UrlHelper::redirect('patients', ['error' => '1', 'msg' => $e->getMessage()]);
        }
    }

    public function handleUpdate()
    {
        $id = (int) ($_POST['id'] ?? 0);
        $this->update($id, $_POST);
    }

    public function update($id, $data)
    {
        try {
            PolicyManager::authorize($_SESSION['user'] ?? [], 'edit_patient');

            $this->service->update($id, $data);
            UrlHelper::redirect('patients', ['updated' => '1']);
        } catch (Exception $e) {
            error_log("PatientsController::update: " . $e->getMessage());
            UrlHelper::redirect('patients', ['error' => '1']);
        }
    }

    public function delete($id)
    {
        try {
            // Se usa el mismo permiso de editar o uno específico
            PolicyManager::authorize($_SESSION['user'] ?? [], 'delete_patient');

            $res = $this->service->delete($id);
            if ($res) {
                UrlHelper::redirect('patients', ['deleted' => '1']);
            } else {
                UrlHelper::redirect('patients', ['error' => '1', 'msg' => 'No se pudo eliminar el paciente.']);
            }
        } catch (Exception $e) {
            error_log("PatientsController::delete: " . $e->getMessage());
            UrlHelper::redirect('patients', ['error' => '1', 'msg' => $e->getMessage()]);
        }
    }

    /**
     * Endpoint API para restaurar un paciente eliminado lógicamente.
     * Se espera recibir el ID por POST (api: /api/patients/restore).
     */
    public function restoreApi()
    {
        try {
            PolicyManager::authorize($_SESSION['user'] ?? [], 'restore_patient');

            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception("ID de paciente inválido.");
            }

            $res = $this->service->restorePatient($id);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => (bool) $res,
                'message' => $res ? 'Paciente restaurado correctamente.' : 'No se pudo restaurar el paciente.'
            ]);
        } catch (Exception $e) {
            error_log("PatientsController::restoreApi: " . $e->getMessage());
            header('Content-Type: application/json', true, 400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}

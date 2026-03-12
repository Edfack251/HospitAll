<?php
namespace App\Controllers;

use App\Helpers\UrlHelper;
use App\Services\DoctorsService;
use App\Policies\PolicyManager;
use Exception;

class DoctorsController
{
    private $doctorsService;

    public function __construct($pdo)
    {
        $this->doctorsService = new DoctorsService($pdo);
    }

    public function store()
    {
        $data = [
            'nombre' => $_POST['nombre'] ?? '',
            'apellido' => $_POST['apellido'] ?? '',
            'especialidad' => $_POST['especialidad'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'correo_electronico' => $_POST['correo_electronico'] ?? ''
        ];

        try {
            PolicyManager::authorize($_SESSION['user'] ?? [], 'create_user'); // O un permiso específico para médicos
            $this->doctorsService->create($data);
            UrlHelper::redirect('doctors', ['success' => '1']);
        } catch (Exception $e) {
            UrlHelper::redirect('doctors', ['error' => '1', 'msg' => $e->getMessage()]);
        }
    }

    public function update()
    {
        $id = $_POST['id'] ?? '';
        $data = [
            'nombre' => $_POST['nombre'] ?? '',
            'apellido' => $_POST['apellido'] ?? '',
            'especialidad' => $_POST['especialidad'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'correo_electronico' => $_POST['correo_electronico'] ?? ''
        ];

        try {
            PolicyManager::authorize($_SESSION['user'] ?? [], 'edit_user');
            $this->doctorsService->update($id, $data);
            UrlHelper::redirect('doctors', ['updated' => '1']);
        } catch (Exception $e) {
            UrlHelper::redirect('doctors', ['error' => '1', 'msg' => $e->getMessage()]);
        }
    }

    public function delete($id)
    {
        try {
            PolicyManager::authorize($_SESSION['user'] ?? [], 'delete_doctor');
            $res = $this->doctorsService->delete($id);
            if ($res) {
                UrlHelper::redirect('doctors', ['deleted' => '1']);
            } else {
                UrlHelper::redirect('doctors', ['error' => '1', 'msg' => 'No se pudo eliminar el médico.']);
            }
        } catch (Exception $e) {
            error_log("DoctorsController::delete: " . $e->getMessage());
            UrlHelper::redirect('doctors', ['error' => '1', 'msg' => $e->getMessage()]);
        }
    }

    /**
     * Endpoint API para restaurar un médico eliminado lógicamente.
     * Se espera recibir el ID por POST (api: /api/doctors/restore).
     */
    public function restoreApi()
    {
        try {
            PolicyManager::authorize($_SESSION['user'] ?? [], 'restore_doctor');

            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception("ID de médico inválido.");
            }

            $res = $this->doctorsService->restoreDoctor($id);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => (bool) $res,
                'message' => $res ? 'Médico restaurado correctamente.' : 'No se pudo restaurar el médico.'
            ]);
        } catch (Exception $e) {
            error_log("DoctorsController::restoreApi: " . $e->getMessage());
            header('Content-Type: application/json', true, 400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}

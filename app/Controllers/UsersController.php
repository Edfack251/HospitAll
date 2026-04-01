<?php
namespace App\Controllers;

use App\Helpers\UrlHelper;
use App\Services\UsersService;
use App\Policies\PolicyManager;
use Exception;

class UsersController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new UsersService($pdo);
    }

    public function index()
    {
        PolicyManager::authorize($_SESSION['user'] ?? [], 'view_logs'); // Solo admin usualmente lista usuarios
        // Mostrar solo personal del hospital (medicos, farmaceuticos, recepcionistas, tecnicos, admins)
        return $this->service->getAll(20, 0, true);
    }

    public function getById($id)
    {
        $user = $this->service->getById($id);
        if (!$user) {
            UrlHelper::redirect('users');
        }
        return $user;
    }

    public function getFormData($excludePatient = false)
    {
        return [
            'roles' => $this->service->getRoles($excludePatient)
        ];
    }

    public function handleCreate()
    {
        $msg = $this->create($_POST);
        if (strpos($msg, 'correctamente') !== false) {
            UrlHelper::redirect('users', ['success' => 'created']);
        } else {
            UrlHelper::redirect('users_create', ['error' => '1', 'msg' => $msg]);
        }
    }

    public function handleUpdate()
    {
        $id = (int) ($_POST['id'] ?? 0);
        $msg = $this->update($id, $_POST);
        if (strpos($msg, 'correctamente') !== false) {
            UrlHelper::redirect('users', ['success' => 'updated']);
        } else {
            UrlHelper::redirect('users_edit', ['id' => $id, 'error' => '1', 'msg' => $msg]);
        }
    }

    public function create($data)
    {
        try {
            PolicyManager::authorize($_SESSION['user'] ?? [], 'create_user');
            $this->service->create($data);
            return "Usuario creado correctamente.";
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function update($id, $data)
    {
        try {
            PolicyManager::authorize($_SESSION['user'] ?? [], 'edit_user');
            $this->service->update($id, $data);
            return "Usuario actualizado correctamente.";
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function delete($id)
    {
        try {
            PolicyManager::authorize($_SESSION['user'] ?? [], 'delete_user');
            $res = $this->service->delete($id);
            if ($res) {
                return "Usuario eliminado correctamente.";
            }
            return "No se pudo eliminar el usuario.";
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Endpoint API para restaurar un usuario eliminado lógicamente.
     * Se espera recibir el ID por POST (api: /api/users/restore).
     */
    public function restoreApi()
    {
        try {
            PolicyManager::authorize($_SESSION['user'] ?? [], 'restore_user');

            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception("ID de usuario inválido.");
            }

            $res = $this->service->restoreUser($id);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => (bool) $res,
                'message' => $res ? 'Usuario restaurado correctamente.' : 'No se pudo restaurar el usuario.'
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

<?php
namespace App\Services;

use Exception;
use PDO;
use PDOException;
use App\Core\Validator;
use App\Repositories\UserRepository;

class UsersService
{
    private $pdo;
    private $repo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->repo = new UserRepository($pdo);
    }

    public function getAll($limit = null, $offset = 0, $staffOnly = false)
    {
        return $this->repo->getAll($limit, $offset, $staffOnly);
    }

    public function getById($id)
    {
        return $this->repo->getById($id);
    }

    public function getRoles($excludePatient = false)
    {
        return $this->repo->getRoles($excludePatient);
    }

    public function create($data)
    {
        Validator::validate($data, [
            'nombre' => 'required',
            'apellido' => 'required',
            'correo_electronico' => 'required|email',
            'password' => 'required|min:6',
            'rol_id' => 'required|numeric'
        ]);

        try {
            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $new_user_id = $this->repo->create($data, $password_hash);

            if ($new_user_id) {
                // Auditoría: Crear usuario
                $logService = new LogService($this->pdo);
                $logService->register($_SESSION['user_id'] ?? $new_user_id, 'Creación de usuario', 'Usuarios', "Email: {$data['correo_electronico']}, Rol ID: {$data['rol_id']}, Usuario Creado ID: {$new_user_id}", 'INFO');

                return $new_user_id;
            }
            return false;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new Exception("El correo electrónico ya está registrado.");
            }
            throw new Exception("Error al crear el usuario: " . $e->getMessage());
        }
    }

    public function update($id, $data)
    {
        Validator::validate($data, [
            'nombre' => 'required',
            'apellido' => 'required',
            'correo_electronico' => 'required|email',
            'rol_id' => 'required|numeric'
        ]);

        try {
            $res = $this->repo->update($id, $data);

            if ($res) {
                // Auditoría: Editar usuario (y detectar cambio de rol)
                $logService = new LogService($this->pdo);
                $accion = 'Edición de usuario';
                $nivel = 'INFO';

                // Si el rol cambió, nivel WARNING
                // Nota: Podríamos consultar el rol anterior para comparar, pero por simplicidad logguamos la edición
                $logService->register($_SESSION['user_id'], 'Edición de usuario', 'Usuarios', "Usuario ID: $id ($data[correo_electronico])", 'WARNING');
            }

            return $res;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                throw new Exception("El correo electrónico ya está registrado.");
            }
            throw new Exception("Error al actualizar el usuario: " . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            if ($id == ($_SESSION['user_id'] ?? 0)) {
                throw new Exception("No puedes eliminar tu propia cuenta.");
            }

            $user = $this->getById($id);
            if (!$user) {
                return false;
            }

            // Si es un paciente, verificar dependencias (podría hacerse aquí o delegar a PatientsService)
            // Por ahora, solo soft delete simple para usuarios
            $res = $this->repo->delete($id);

            if ($res) {
                $logService = new LogService($this->pdo);
                $logService->register($_SESSION['user_id'], 'Eliminación lógica de usuario', 'Usuarios', "ID: $id, Email: $user[correo_electronico]", 'ERROR');
            }
            return $res;
        } catch (Exception $e) {
            error_log("Error UsersService::delete: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Restaura un usuario previamente eliminado (soft delete).
     */
    public function restoreUser($id)
    {
        $id = (int) $id;

        $user = $this->repo->getByIdIncludingDeleted($id);
        if (!$user) {
            throw new Exception("Usuario no encontrado.");
        }

        if ($user['deleted_at'] === null) {
            throw new Exception("El usuario ya está activo, no se puede restaurar.");
        }

        // Política strict_block: correo electrónico único activo
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM usuarios 
             WHERE correo_electronico = ? AND deleted_at IS NULL AND id <> ?"
        );
        $stmt->execute([$user['correo_electronico'], $id]);
        if ((int) $stmt->fetchColumn() > 0) {
            $logService = new LogService($this->pdo);
            $logService->register(
                $_SESSION['user_id'] ?? null,
                'Intento fallido de restauración de usuario',
                'Usuarios',
                "ID: {$id}, Email duplicado: {$user['correo_electronico']}",
                'WARNING'
            );
            throw new Exception("No se puede restaurar el usuario porque ya existe otro activo con el mismo correo electrónico.");
        }

        $res = $this->repo->restore($id);

        if ($res) {
            $logService = new LogService($this->pdo);
            $logService->register(
                $_SESSION['user_id'] ?? null,
                "Restauración de usuario | ID: {$id}",
                'Usuarios',
                "ID: {$id}, Email: {$user['correo_electronico']}",
                'INFO'
            );
        }

        return $res;
    }
}

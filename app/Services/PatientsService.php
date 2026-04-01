<?php
namespace App\Services;

use Exception;
use PDO;
use PDOException;
use DateTime;
use App\Core\Validator;
use App\Repositories\PatientRepository;
use App\Repositories\UserRepository;

class PatientsService
{
    private $pdo;
    private $repo;
    private $userRepo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->repo = new PatientRepository($pdo);
        $this->userRepo = new UserRepository($pdo);
    }

    public function getAll($limit = null, $offset = 0)
    {
        return $this->repo->getAll($limit, $offset);
    }

    public function getById($id)
    {
        $paciente = $this->repo->getById($id);

        if ($paciente) {
            $identidad = $this->repo->getIdentityData($id);
            if ($identidad) {
                // Merge identity data into the patient array so views can consume them
                $paciente = array_merge($paciente, $identidad);
            }
        }

        return $paciente;
    }

    public function searchByIdentification($search)
    {
        return $this->repo->getByIdentificacion($search);
    }

    public function search($query)
    {
        return $this->repo->search($query);
    }

    public function create($data)
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

        Validator::validate($data, [
            'identificacion' => 'required',
            'nombre' => 'required',
            'apellido' => 'required',
            'correo_electronico' => 'required|email',
            'fecha_nacimiento' => 'required|date',
            'genero' => 'required',
            'password' => 'required|min:6'
        ]);

        try {
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
            }

            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);

            // Fetch patient role id
            $roles = $this->userRepo->getRoles();
            $rol_id = 1; // Default
            foreach ($roles as $role) {
                if (strtolower($role['nombre']) === 'paciente') {
                    $rol_id = $role['id'];
                    break;
                }
            }

            $user_data = [
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'correo_electronico' => $data['correo_electronico'],
                'rol_id' => $rol_id
            ];

            $usuario_id = $this->userRepo->create($user_data, $password_hash);
            if (!$usuario_id)
                throw new Exception("No se pudo crear el usuario.");

            $paciente_id = $this->repo->create($usuario_id, $data);

            // Guardar datos sensibles de identidad
            $this->repo->saveIdentityData($paciente_id, $data);

            if ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

            // Auditoría: Crear paciente
            $logService = new LogService($this->pdo);
            $logService->register($_SESSION['user_id'] ?? $usuario_id, 'Registro de paciente', 'Pacientes', "Nombre: {$data['nombre']} {$data['apellido']}, Cédula: {$data['identificacion']}", 'INFO');

            return true;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error PatientsService::create: " . $e->getMessage());
            if ($e->getCode() == 23000) {
                throw new Exception("El correo electrónico o la identificación ya están registrados.");
            }
            throw new Exception("Error al guardar el paciente: " . $e->getMessage());
        }
    }

    public function update($id, $data)
    {
        try {
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
            }

            // 1. Obtener usuario_id asociado
            $usuario_id = $this->repo->getUserIdByPatientId($id);

            if (!$usuario_id) {
                throw new Exception("Paciente no encontrado.");
            }

            // 2. Actualizar tabla pacientes
            $this->repo->update($id, $data);

            // 3. Actualizar tabla usuarios (sincronización)
            $user_data = [
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'correo_electronico' => $data['correo_electronico']
            ];

            // Get current user to keep role id
            $current_user = $this->userRepo->getById($usuario_id);
            $user_data['rol_id'] = $current_user['rol_id'];

            $this->userRepo->update($usuario_id, $user_data);

            // Actualizar datos sensibles de identidad
            $this->repo->updateIdentityData($id, $data);

            if ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

            // Auditoría: Editar paciente
            $logService = new LogService($this->pdo);
            $logService->register($_SESSION['user_id'], 'Edición de paciente', 'Pacientes', "Paciente ID: $id ({$data['nombre']} {$data['apellido']})", 'WARNING');

            return true;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error PatientsService::update: " . $e->getMessage());
            if ($e->getCode() == 23000) {
                throw new Exception("El correo electrónico o la identificación ya están registrados.");
            }
            throw new Exception("Error al actualizar el paciente.");
        }
    }

    public function delete($id)
    {
        try {
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
            }

            $paciente = $this->getById($id);
            if (!$paciente) {
                return false;
            }

            // Validaciones de seguridad (dependencias)
            $this->checkDependencies($id);

            $res = $this->repo->delete($id);

            if ($res) {
                // Eliminamos también el usuario asociado de forma lógica
                $this->userRepo->delete($paciente['usuario_id']);

                if ($this->pdo->inTransaction()) {
                    $this->pdo->commit();
                }

                $logService = new LogService($this->pdo);
                $logService->register($_SESSION['user_id'], 'Eliminación lógica de paciente', 'Pacientes', "ID: $id, Nombre: {$paciente['nombre']} {$paciente['apellido']}", 'ERROR');
            } else {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
            }
            return $res;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error PatientsService::delete: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Restaura un paciente previamente eliminado (soft delete) junto con su usuario asociado si aplica.
     */
    public function restorePatient($id)
    {
        $id = (int) $id;

        try {
            // Obtener paciente incluyendo eliminados
            $paciente = $this->repo->getByIdIncludingDeleted($id);
            if (!$paciente) {
                throw new Exception("Paciente no encontrado.");
            }

            if ($paciente['deleted_at'] === null) {
                throw new Exception("El paciente ya está activo, no se puede restaurar.");
            }

            // Política strict_block: misma identificación activa
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM pacientes 
                 WHERE identificacion = ? AND deleted_at IS NULL AND id <> ?"
            );
            $stmt->execute([$paciente['identificacion'], $id]);
            if ((int) $stmt->fetchColumn() > 0) {
                $logService = new LogService($this->pdo);
                $logService->register(
                    $_SESSION['user_id'] ?? null,
                    'Intento fallido de restauración de paciente',
                    'Pacientes',
                    "ID: {$id}, Identificación duplicada: {$paciente['identificacion']}",
                    'WARNING'
                );
                throw new Exception("No se puede restaurar el paciente porque ya existe otro activo con la misma identificación.");
            }

            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
            }

            // Restaurar paciente
            $this->repo->restore($id);

            // Restaurar usuario asociado si estaba eliminado lógicamente
            if (!empty($paciente['usuario_id'])) {
                $usuario = $this->userRepo->getByIdIncludingDeleted($paciente['usuario_id']);
                if ($usuario && $usuario['deleted_at'] !== null) {
                    $this->userRepo->restore($paciente['usuario_id']);
                }
            }

            if ($this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

            $logService = new LogService($this->pdo);
            $logService->register(
                $_SESSION['user_id'] ?? null,
                "Restauración de paciente | ID: {$id}",
                'Pacientes',
                "Paciente ID: {$id}, Nombre: {$paciente['nombre']} {$paciente['apellido']}",
                'INFO'
            );

            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error PatientsService::restorePatient: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verifica si un paciente tiene dependencias críticas.
     */
    private function checkDependencies($id)
    {
        // 1. Historial clínico
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM historial_clinico WHERE paciente_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("No se puede eliminar el paciente porque tiene historial clínico asociado.");
        }

        // 2. Facturas
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM facturas WHERE paciente_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("No se puede eliminar el paciente porque tiene facturas asociadas.");
        }

        // 3. Prescripciones
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM prescripciones WHERE paciente_id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("No se puede eliminar el paciente porque tiene prescripciones activas.");
        }
    }
}

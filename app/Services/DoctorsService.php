<?php
namespace App\Services;

use PDO;
use Exception;
use App\Repositories\DoctorRepository;

class DoctorsService
{
    private $pdo;
    private $repo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->repo = new DoctorRepository($pdo);
    }

    public function create($data)
    {
        try {
            return $this->repo->create($data);
        } catch (\PDOException $e) {
            throw new Exception("Error al guardar el médico: " . $e->getMessage());
        }
    }

    public function update($id, $data)
    {
        try {
            return $this->repo->update($id, $data);
        } catch (\PDOException $e) {
            throw new Exception("Error al actualizar el médico: " . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $doctor = $this->repo->getAllBasic(); // Simplified, should get by ID properly if available, but getAllBasic returns all
            // Find specific doctor in results (optimized would be getById)
            $doctorData = null;
            foreach ($doctor as $d) {
                if ($d['id'] == $id) {
                    $doctorData = $d;
                    break;
                }
            }

            if (!$doctorData) {
                return false;
            }

            $this->checkDependencies($id);

            $res = $this->repo->delete($id);
            if ($res) {
                $logService = new LogService($this->pdo);
                $logService->register($_SESSION['user_id'], 'Eliminación lógica de médico', 'Médicos', "ID: $id, Nombre: {$doctorData['nombre']} {$doctorData['apellido']}", 'ERROR');
            }
            return $res;
        } catch (Exception $e) {
            error_log("Error DoctorsService::delete: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Restaura un médico previamente eliminado (soft delete).
     */
    public function restoreDoctor($id)
    {
        $id = (int) $id;

        $doctor = $this->repo->getByIdIncludingDeleted($id);
        if (!$doctor) {
            throw new Exception("Médico no encontrado.");
        }

        if ($doctor['deleted_at'] === null) {
            throw new Exception("El médico ya está activo, no se puede restaurar.");
        }

        // Política strict_block simple: correo electrónico único activo
        if (!empty($doctor['correo_electronico'])) {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM medicos 
                 WHERE correo_electronico = ? AND deleted_at IS NULL AND id <> ?"
            );
            $stmt->execute([$doctor['correo_electronico'], $id]);
            if ((int) $stmt->fetchColumn() > 0) {
                $logService = new LogService($this->pdo);
                $logService->register(
                    $_SESSION['user_id'] ?? null,
                    'Intento fallido de restauración de médico',
                    'Médicos',
                    "ID: {$id}, Email duplicado: {$doctor['correo_electronico']}",
                    'WARNING'
                );
                throw new Exception("No se puede restaurar el médico porque ya existe otro activo con el mismo correo electrónico.");
            }
        }

        $res = $this->repo->restore($id);

        if ($res) {
            $logService = new LogService($this->pdo);
            $logService->register(
                $_SESSION['user_id'] ?? null,
                "Restauración de médico | ID: {$id}",
                'Médicos',
                "ID: {$id}, Nombre: {$doctor['nombre']} {$doctor['apellido']}",
                'INFO'
            );
        }

        return $res;
    }

    private function checkDependencies($id)
    {
        // Verificar historial clínico asociado al médico
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM historial_clinico WHERE medico_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("No se puede eliminar el médico porque tiene historial clínico asociado.");
        }

        // Verificar citas
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM citas WHERE medico_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("No se puede eliminar el médico porque tiene citas programadas o pasadas.");
        }
    }
}

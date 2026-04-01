<?php
namespace App\Services;

use Exception;
use PDO;
use PDOException;
use App\Repositories\PrescriptionRepository;

class PrescriptionService
{
    private $pdo;
    private $repo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->repo = new PrescriptionRepository($pdo);
    }

    /**
     * Crea una prescripción médica.
     * $data['detalles'] debe ser un array de medicamentos con dosis, frecuencia, duracion, etc.
     */
    public function create($data)
    {
        try {
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
            }

            $prescripcion_id = $this->repo->create(
                $data['cita_id'],
                $data['medico_id'],
                $data['paciente_id'],
                $data['observaciones'] ?? ''
            );

            if (isset($data['detalles']) && is_array($data['detalles'])) {
                foreach ($data['detalles'] as $detalle) {
                    $this->repo->createDetalle($prescripcion_id, $detalle);
                }
            }

            if (!$this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

            return $prescripcion_id;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function getByCita($cita_id)
    {
        return $this->repo->getByCita($cita_id);
    }

    public function getDetalles($prescripcion_id)
    {
        return $this->repo->getDetalles($prescripcion_id);
    }

    public function delete($id)
    {
        try {
            $res = $this->repo->delete($id);
            if ($res) {
                if (isset($_SESSION['user_id'])) {
                    $logService = new LogService($this->pdo);
                    $logService->register($_SESSION['user_id'], 'Eliminación lógica de prescripción', 'Prescripciones', "ID: $id", 'ERROR');
                }
            }
            return $res;
        } catch (Exception $e) {
            error_log("Error PrescriptionService::delete: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Restaura una prescripción previamente eliminada (soft delete).
     */
    public function restorePrescription($id)
    {
        $id = (int) $id;

        $prescripcion = $this->repo->getByIdIncludingDeleted($id);
        if (!$prescripcion) {
            throw new Exception("Prescripción no encontrada.");
        }

        if ($prescripcion['deleted_at'] === null) {
            throw new Exception("La prescripción ya está activa, no se puede restaurar.");
        }

        $res = $this->repo->restore($id);

        if ($res && isset($_SESSION['user_id'])) {
            $logService = new LogService($this->pdo);
            $logService->register(
                $_SESSION['user_id'],
                "Restauración de prescripción | ID: {$id}",
                'Prescripciones',
                "ID: {$id}",
                'INFO'
            );
        }

        return $res;
    }
}

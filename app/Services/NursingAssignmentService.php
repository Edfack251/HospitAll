<?php
namespace App\Services;

use App\Repositories\NursingAssignmentRepository;
use Exception;

class NursingAssignmentService
{
    private $repo;

    public function __construct($pdo)
    {
        $this->repo = new NursingAssignmentRepository($pdo);
    }

    public function asignarPaciente(array $data): bool
    {
        if (empty($data['internamiento_id']) || empty($data['enfermera_id'])) {
            throw new Exception("Datos incompletos para la asignación.");
        }
        return $this->repo->asignar(
            (int) $data['internamiento_id'],
            (int) $data['enfermera_id'],
            (int) $data['asignado_por']
        );
    }

    public function getResumenAsignaciones(): array
    {
        return $this->repo->getAsignacionesDia();
    }

    public function getInternamientosSinAsignar(): array
    {
        return $this->repo->getInternamientosSinAsignar();
    }

    public function getEnfermeras()
    {
        return $this->repo->getEnfermerasDisponibles();
    }

    public function eliminarAsignacion($id)
    {
        return $this->repo->quitarAsignacion($id);
    }
}

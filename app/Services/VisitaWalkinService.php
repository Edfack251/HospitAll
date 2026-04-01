<?php
namespace App\Services;

use App\Repositories\VisitaWalkinRepository;
use Exception;

class VisitaWalkinService
{
    private $pdo;
    private $repo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->repo = new VisitaWalkinRepository($pdo);
    }

    public function crearVisita(int $paciente_id, int $turno_id, string $area)
    {
        return $this->repo->create($paciente_id, $turno_id, $area);
    }

    public function atender(int $visita_id, ?int $medico_id = null)
    {
        $this->repo->markAsAtendido($visita_id);
        if ($medico_id) {
            $this->repo->actualizarEstado($visita_id, 'atendido', $medico_id);
        }
        return true;
    }

    public function getById(int $id)
    {
        return $this->repo->getById($id);
    }
}

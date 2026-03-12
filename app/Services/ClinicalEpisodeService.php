<?php
namespace App\Services;

use App\Core\Validator;
use App\Repositories\ClinicalEpisodeRepository;

class ClinicalEpisodeService
{
    private $repository;

    public function __construct($pdo)
    {
        $this->repository = new ClinicalEpisodeRepository($pdo);
    }

    public function generateEpisode($data)
    {
        Validator::validate($data, [
            'paciente_id' => 'required|numeric',
            'descripcion_problema' => 'required'
        ]);

        if (empty($data['fecha_inicio'])) {
            $data['fecha_inicio'] = date('Y-m-d');
        } else {
            Validator::validate($data, ['fecha_inicio' => 'date']);
        }

        return $this->repository->create($data);
    }

    public function completeEpisode($id)
    {
        return $this->repository->triggerClose($id);
    }

    public function fetchAllByPatient($paciente_id)
    {
        return $this->repository->getAllByPatientWithTimeline($paciente_id);
    }

    public function assignToAppointment($cita_id, $episodio_id)
    {
        return $this->repository->assignAppointmentToEpisode($cita_id, $episodio_id);
    }
}

<?php
namespace App\Controllers;

use App\Services\PatientPortalService;

class PatientPortalController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new PatientPortalService($pdo);
    }

    /**
     * Muestra los datos del portal del paciente.
     * 
     * @param int $paciente_id ID del paciente
     * @return array Datos obtenidos del servicio
     */
    public function show(int $paciente_id): array
    {
        return $this->service->getPatientPortalData($paciente_id);
    }
}

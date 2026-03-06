<?php
namespace App\Controllers;

use App\Services\DashboardService;
use Exception;

class DashboardController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new DashboardService($pdo);
    }

    public function index()
    {
        return [
            'totalPacientes' => $this->service->getTotalPacientes(),
            'totalMedicos' => $this->service->getTotalMedicos(),
            'citasHoy' => $this->service->getCitasHoy(),
            'proximasCitas' => $this->service->getProximasCitas(10)
        ];
    }
}

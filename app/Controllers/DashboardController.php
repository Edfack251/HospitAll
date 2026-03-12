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
        $role = $_SESSION['user_role'] ?? '';

        if ($role === 'farmaceutico') {
            return [
                'bajoStock' => $this->service->getBajoStock(),
                'prescripcionesPendientes' => $this->service->getPrescripcionesPendientes(),
                'ventasRecientes' => $this->service->getVentasRecientes(10)
            ];
        }

        // Default Admin/Staff data
        return [
            'totalPacientes' => $this->service->getTotalPacientes(),
            'totalMedicos' => $this->service->getTotalMedicos(),
            'citasHoy' => $this->service->getCitasHoy(),
            'proximasCitas' => $this->service->getProximasCitas(10)
        ];
    }
}

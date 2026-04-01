<?php
namespace App\Services;

use Exception;
use PDO;
use PDOException;
use App\Repositories\DashboardRepository;

class DashboardService
{
    private $pdo;
    private $repo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->repo = new DashboardRepository($pdo);
    }

    public function getTotalPacientes()
    {
        try {
            return $this->repo->countPacientes();
        } catch (PDOException $e) {
            error_log("Error en DashboardService::getTotalPacientes: " . $e->getMessage());
            return 0;
        }
    }

    public function getTotalMedicos()
    {
        try {
            return $this->repo->countMedicos();
        } catch (PDOException $e) {
            error_log("Error en DashboardService::getTotalMedicos: " . $e->getMessage());
            return 0;
        }
    }

    public function getCitasHoy()
    {
        try {
            return $this->repo->countCitasHoy();
        } catch (PDOException $e) {
            error_log("Error en DashboardService::getCitasHoy: " . $e->getMessage());
            return 0;
        }
    }

    public function getProximasCitas($limite = 10)
    {
        try {
            return $this->repo->getProximasCitasProgramadas($limite);
        } catch (PDOException $e) {
            error_log("Error en DashboardService::getProximasCitas: " . $e->getMessage());
            return [];
        }
    }

    public function getBajoStock()
    {
        try {
            return $this->repo->countBajoStock();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function getPrescripcionesPendientes()
    {
        try {
            return $this->repo->countPrescripcionesPendientes();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function getVentasRecientes($limite = 5)
    {
        try {
            return $this->repo->getRecientesVentas($limite);
        } catch (PDOException $e) {
            return [];
        }
    }
}

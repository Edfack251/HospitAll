<?php
namespace App\Services;

use Exception;
use PDO;
use PDOException;

class DashboardService
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getTotalPacientes()
    {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM pacientes");
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error en DashboardService::getTotalPacientes: " . $e->getMessage());
            return 0;
        }
    }

    public function getTotalMedicos()
    {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM medicos");
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error en DashboardService::getTotalMedicos: " . $e->getMessage());
            return 0;
        }
    }

    public function getCitasHoy()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM citas WHERE fecha = CURDATE()");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error en DashboardService::getCitasHoy: " . $e->getMessage());
            return 0;
        }
    }

    public function getProximasCitas($limite = 10)
    {
        try {
            $sql = "SELECT c.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, 
                           m.nombre as medico_nombre, m.apellido as medico_apellido
                    FROM citas c
                    JOIN pacientes p ON c.paciente_id = p.id
                    JOIN medicos m ON c.medico_id = m.id
                    WHERE c.estado = 'Programada'
                    ORDER BY c.fecha ASC, c.hora ASC
                    LIMIT " . (int) $limite;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en DashboardService::getProximasCitas: " . $e->getMessage());
            return [];
        }
    }
}

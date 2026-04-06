<?php
namespace App\Repositories;

use PDO;
use Exception;

class DashboardRepository
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function countPacientes()
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM pacientes");
        return $stmt->fetchColumn();
    }

    public function countMedicos()
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM medicos");
        return $stmt->fetchColumn();
    }

    public function countCitasHoy()
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM citas WHERE fecha = CURDATE()");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getProximasCitasProgramadas($limite = 10)
    {
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
    }

    public function countBajoStock()
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM medicamentos WHERE stock <= 10");
        return $stmt->fetchColumn();
    }

    public function countPrescripcionesPendientes()
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM prescripciones WHERE estado = 'Pendiente'");
        return $stmt->fetchColumn();
    }

    public function getRecientesVentas($limite = 5)
    {
        $sql = "SELECT v.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido 
                FROM ventas_farmacia v
                JOIN pacientes p ON v.paciente_id = p.id
                ORDER BY v.created_at DESC LIMIT " . (int) $limite;
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}

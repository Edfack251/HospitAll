<?php
namespace App\Repositories;

use PDO;
use Exception;

class PatientPortalRepository
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getPatientIdByUserId(int $usuario_id)
    {
        $stmt = $this->pdo->prepare("SELECT id FROM pacientes WHERE usuario_id = ?");
        $stmt->execute([$usuario_id]);
        return $stmt->fetchColumn();
    }

    public function getCitasProximas($paciente_id)
    {
        $stmt_citas = $this->pdo->prepare("SELECT c.*, m.nombre as medico_nombre, m.apellido as medico_apellido
            FROM citas c
            JOIN medicos m ON c.medico_id = m.id
            WHERE c.paciente_id = ? AND c.estado NOT IN ('Atendida', 'Cancelada', 'No asistió')
            ORDER BY c.fecha ASC, c.hora ASC");
        $stmt_citas->execute([$paciente_id]);
        return $stmt_citas->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getHistorialClinico($paciente_id)
    {
        $stmt_historial = $this->pdo->prepare("SELECT h.*, m.nombre as medico_nombre, m.apellido as medico_apellido, c.fecha,
                (SELECT COUNT(*) FROM ordenes_laboratorio ol WHERE ol.historial_id = h.id AND ol.estado = 'Pendiente') as ordenes_pendientes
            FROM historial_clinico h
            JOIN medicos m ON h.medico_id = m.id
            JOIN citas c ON h.cita_id = c.id
            WHERE h.paciente_id = ?
            ORDER BY c.fecha DESC");
        $stmt_historial->execute([$paciente_id]);
        return $stmt_historial->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrdenesLaboratorio($paciente_id)
    {
        $stmt_lab = $this->pdo->prepare("SELECT ol.*, c.fecha
            FROM ordenes_laboratorio ol
            JOIN historial_clinico h ON ol.historial_id = h.id
            JOIN citas c ON h.cita_id = c.id
            WHERE h.paciente_id = ?
            ORDER BY c.fecha DESC");
        $stmt_lab->execute([$paciente_id]);
        return $stmt_lab->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPrescripcionesMedicas($paciente_id)
    {
        $stmt_prescrip = $this->pdo->prepare("SELECT p.*, m.nombre as medico_nombre, m.apellido as medico_apellido, c.fecha
            FROM prescripciones p
            JOIN medicos m ON p.medico_id = m.id
            JOIN citas c ON p.cita_id = c.id
            WHERE p.paciente_id = ?
            ORDER BY c.fecha DESC");
        $stmt_prescrip->execute([$paciente_id]);
        return $stmt_prescrip->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFacturasPendientes($paciente_id)
    {
        $stmt = $this->pdo->prepare("SELECT f.*, 
                (SELECT COUNT(*) FROM factura_detalle fd WHERE fd.factura_id = f.id) as items_count
            FROM facturas f
            WHERE f.paciente_id = ? AND f.estado = 'Pendiente'
            ORDER BY f.fecha DESC");
        $stmt->execute([$paciente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

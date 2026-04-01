<?php
namespace App\Repositories;

use PDO;

class EmergencyRepository extends BaseRepository
{
    /**
     * Emergencias asignadas al médico (En espera, En atención), ordenadas por triaje y fecha.
     */
    public function getEmergenciasAsignadas(int $medico_id): array
    {
        $sql = "SELECT e.id, e.paciente_id, e.medico_id, e.nivel_triage, e.motivo_ingreso, e.estado, e.fecha_ingreso,
                p.nombre as paciente_nombre, p.apellido as paciente_apellido,
                m.nombre as medico_nombre, m.apellido as medico_apellido
                FROM emergencias e
                JOIN pacientes p ON e.paciente_id = p.id
                LEFT JOIN medicos m ON e.medico_id = m.id
                WHERE e.medico_id = ? AND e.estado IN ('En espera', 'En atención')
                ORDER BY FIELD(e.nivel_triage, 'Rojo', 'Naranja', 'Amarillo', 'Verde'), e.fecha_ingreso ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$medico_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Detalle completo de una emergencia con JOIN a pacientes, medicos y usuario (enfermera).
     */
    public function getEmergenciaById(int $id): ?array
    {
        $sql = "SELECT e.id, e.paciente_id, e.medico_id, e.usuario_id, e.nivel_triage, e.motivo_ingreso, e.estado, e.fecha_ingreso,
                p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.identificacion as paciente_identificacion,
                m.nombre as medico_nombre, m.apellido as medico_apellido,
                u.nombre as usuario_nombre, u.apellido as usuario_apellido
                FROM emergencias e
                JOIN pacientes p ON e.paciente_id = p.id
                LEFT JOIN medicos m ON e.medico_id = m.id
                LEFT JOIN usuarios u ON e.usuario_id = u.id
                WHERE e.id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Signos vitales precargados para una emergencia (desde atenciones por emergencia_id).
     */
    public function getSignosVitalesEmergencia(int $emergencia_id): ?array
    {
        $sql = "SELECT presion_arterial, frecuencia_cardiaca, temperatura, peso, estatura
                FROM atenciones WHERE emergencia_id = ?
                ORDER BY id DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$emergencia_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Inserta una atención médica para emergencia (sin cita).
     */
    public function insertAtencionEmergencia(int $emergencia_id, int $medico_id, int $paciente_id, array $data): bool
    {
        $sql = "INSERT INTO atenciones (emergencia_id, cita_id, medico_id, paciente_id,
                motivo_consulta, sintomas, diagnostico, tratamiento, observaciones,
                presion_arterial, frecuencia_cardiaca, temperatura, peso, estatura)
                VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $emergencia_id,
            $medico_id,
            $paciente_id,
            $data['motivo_consulta'] ?? null,
            $data['sintomas'] ?? null,
            $data['diagnostico'] ?? null,
            $data['tratamiento'] ?? null,
            $data['observaciones'] ?? null,
            $data['presion_arterial'] ?? null,
            isset($data['frecuencia_cardiaca']) && $data['frecuencia_cardiaca'] !== '' ? (int) $data['frecuencia_cardiaca'] : null,
            isset($data['temperatura']) && $data['temperatura'] !== '' ? (float) $data['temperatura'] : null,
            isset($data['peso']) && $data['peso'] !== '' ? (float) $data['peso'] : null,
            isset($data['estatura']) && $data['estatura'] !== '' ? (float) $data['estatura'] : null
        ]);
    }

    /**
     * Actualiza el estado de una emergencia.
     */
    public function actualizarEstadoEmergencia(int $id, string $estado): bool
    {
        $sql = "UPDATE emergencias SET estado = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$estado, $id]);
    }

    public function getEmergenciasParaFlow(): array
    {
        $sql = "SELECT e.id, e.paciente_id, e.medico_id, e.nivel_triage, e.estado, e.fecha_ingreso as hora,
                       p.nombre as paciente_nombre, p.apellido as paciente_apellido,
                       m.apellido as medico_apellido,
                       'emergencia' as tipo_registro
                FROM emergencias e
                JOIN pacientes p ON e.paciente_id = p.id
                LEFT JOIN medicos m ON e.medico_id = m.id
                WHERE e.estado IN ('En espera', 'En atención')";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

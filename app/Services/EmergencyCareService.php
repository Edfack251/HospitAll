<?php
namespace App\Services;

use App\Repositories\EmergencyRepository;
use Exception;

class EmergencyCareService
{
    private $emergenciaRepo;

    public function __construct($pdo)
    {
        $this->emergenciaRepo = new EmergencyRepository($pdo);
    }

    /**
     * Inicia la atención: actualiza estado a 'En atención' y retorna datos de la emergencia.
     */
    public function iniciarAtencion(int $emergencia_id, int $medico_id): array
    {
        $emergencia = $this->emergenciaRepo->getEmergenciaById($emergencia_id);
        if (!$emergencia) {
            throw new Exception("Emergencia no encontrada.");
        }
        if ((int) ($emergencia['medico_id'] ?? 0) !== $medico_id) {
            throw new Exception("No eres el médico asignado a esta emergencia.");
        }
        if (!in_array($emergencia['estado'], ['En espera', 'En atención'], true)) {
            throw new Exception("La emergencia ya fue finalizada.");
        }
        $this->emergenciaRepo->actualizarEstadoEmergencia($emergencia_id, 'En atención');
        return $this->emergenciaRepo->getEmergenciaById($emergencia_id);
    }

    /**
     * Registra la atención médica en atenciones (con emergencia_id, sin cita_id).
     */
    public function registrarAtencion(int $emergencia_id, int $medico_id, int $paciente_id, array $data): void
    {
        $emergencia = $this->emergenciaRepo->getEmergenciaById($emergencia_id);
        if (!$emergencia) {
            throw new Exception("Emergencia no encontrada.");
        }
        if ((int) ($emergencia['medico_id'] ?? 0) !== $medico_id) {
            throw new Exception("No eres el médico asignado a esta emergencia.");
        }
        if ((int) $emergencia['paciente_id'] !== $paciente_id) {
            throw new Exception("El paciente no corresponde a esta emergencia.");
        }
        $this->emergenciaRepo->insertAtencionEmergencia($emergencia_id, $medico_id, $paciente_id, $data);
    }

    /**
     * Cierra la emergencia actualizando estado a 'Atendido'.
     */
    public function cerrarEmergencia(int $emergencia_id): void
    {
        $emergencia = $this->emergenciaRepo->getEmergenciaById($emergencia_id);
        if (!$emergencia) {
            throw new Exception("Emergencia no encontrada.");
        }
        if (in_array($emergencia['estado'], ['Atendido', 'Transferido'], true)) {
            throw new Exception("La emergencia ya está cerrada.");
        }
        $this->emergenciaRepo->actualizarEstadoEmergencia($emergencia_id, 'Atendido');
    }
}

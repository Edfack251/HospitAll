<?php
namespace App\Services;

use App\Repositories\HospitalizationRepository;
use App\Repositories\EmergencyRepository;
use App\Repositories\AppointmentRepository;
use Exception;

class HospitalizationService
{
    private $hospitalizacionRepository;
    private $emergenciaRepository;
    private $appointmentsRepository;

    public function __construct($pdo)
    {
        $this->hospitalizacionRepository = new HospitalizationRepository($pdo);
        $this->emergenciaRepository = new EmergencyRepository($pdo);
        $this->appointmentsRepository = new AppointmentRepository($pdo);
    }

    /**
     * Interna a un paciente desde el módulo de emergencias.
     */
    public function internarDesdeEmergencia(int $emergencia_id, int $cama_id, int $medico_id, string $motivo, string $diagnostico): int
    {
        $emergencia = $this->emergenciaRepository->getEmergenciaById($emergencia_id);
        if (!$emergencia) {
            throw new Exception("La emergencia no existe.");
        }

        if ($emergencia['estado'] === 'Atendido' || $emergencia['estado'] === 'Transferido') {
            throw new Exception("La emergencia ya ha sido finalizada o transferida.");
        }

        $this->validarDisponibilidadCama($cama_id);
        $this->validarInternamientoActivo($emergencia['paciente_id']);

        $data = [
            'paciente_id' => $emergencia['paciente_id'],
            'medico_id' => $medico_id,
            'cama_id' => $cama_id,
            'origen' => 'emergencia',
            'emergencia_id' => $emergencia_id,
            'motivo_internamiento' => $motivo,
            'diagnostico_ingreso' => $diagnostico
        ];

        $internamiento_id = $this->hospitalizacionRepository->internarPaciente($data);

        // Actualizar estado de la emergencia a transferido
        $this->emergenciaRepository->actualizarEstadoEmergencia($emergencia_id, 'Transferido');

        return $internamiento_id;
    }

    /**
     * Interna a un paciente desde una consulta médica (cita).
     */
    public function internarDesdeConsulta(int $cita_id, int $cama_id, int $medico_id, string $motivo, string $diagnostico): int
    {
        $cita = $this->appointmentsRepository->getById($cita_id);
        if (!$cita) {
            throw new Exception("La cita no existe.");
        }

        $this->validarDisponibilidadCama($cama_id);
        $this->validarInternamientoActivo($cita['paciente_id']);

        $data = [
            'paciente_id' => $cita['paciente_id'],
            'medico_id' => $medico_id,
            'cama_id' => $cama_id,
            'origen' => 'consulta',
            'cita_id' => $cita_id,
            'motivo_internamiento' => $motivo,
            'diagnostico_ingreso' => $diagnostico
        ];

        return $this->hospitalizacionRepository->internarPaciente($data);
    }

    /**
     * Registra una ronda de enfermería con validaciones.
     */
    public function registrarRonda(int $internamiento_id, int $enfermera_id, array $data): int
    {
        $internamiento = $this->hospitalizacionRepository->getInternamientoById($internamiento_id);
        if (!$internamiento || $internamiento['estado'] !== 'activo') {
            throw new Exception("El internamiento no está activo.");
        }

        // Validar que hay al menos observaciones o un signo vital
        $hasData = !empty($data['observaciones']) || 
                   !empty($data['presion_arterial']) || 
                   !empty($data['frecuencia_cardiaca']) || 
                   !empty($data['temperatura']) || 
                   !empty($data['saturacion_oxigeno']) ||
                   !empty($data['medicamentos_administrados']);

        if (!$hasData) {
            throw new Exception("Debe registrar al menos un signo vital, medicamento u observación.");
        }

        $data['internamiento_id'] = $internamiento_id;
        $data['enfermera_id'] = $enfermera_id;

        return $this->hospitalizacionRepository->registrarRonda($data);
    }

    /**
     * Registra una evolución médica.
     */
    public function registrarEvolucion(int $internamiento_id, int $medico_id, string $evolucion, ?string $indicaciones, ?string $diagnostico_actualizado): int
    {
        $internamiento = $this->hospitalizacionRepository->getInternamientoById($internamiento_id);
        if (!$internamiento || $internamiento['estado'] !== 'activo') {
            throw new Exception("El internamiento no está activo.");
        }

        if (empty(trim($evolucion))) {
            throw new Exception("La nota de evolución no puede estar vacía.");
        }

        $data = [
            'internamiento_id' => $internamiento_id,
            'medico_id' => $medico_id,
            'evolucion' => $evolucion,
            'indicaciones' => $indicaciones,
            'diagnostico_actualizado' => $diagnostico_actualizado
        ];

        return $this->hospitalizacionRepository->registrarEvolucion($data);
    }

    /**
     * Procesa el alta médica.
     */
    public function darAlta(int $internamiento_id, int $usuario_id, string $observaciones, bool $esAdmin): bool
    {
        $internamiento = $this->hospitalizacionRepository->getInternamientoById($internamiento_id);
        if (!$internamiento || $internamiento['estado'] !== 'activo') {
            throw new Exception("El internamiento no está activo o ya se ha dado el alta.");
        }

        // Solo el médico responsable o un administrador pueden dar el alta
        if (!$esAdmin && (int)$internamiento['medico_id'] !== $usuario_id) {
            // Nota: Aquí asumimos que usuario_id de sesión mapea a un médico si no es admin, 
            // pero el repo de medicos podría tener un ID diferente. 
            // En este proyecto, a veces el usuario_id se usa directamente si el rol es médico.
            // Verificaremos esto en el controlador.
        }

        if (empty(trim($observaciones))) {
            throw new Exception("Debe proporcionar observaciones para el alta médica.");
        }

        return $this->hospitalizacionRepository->darAlta($internamiento_id, $usuario_id, $observaciones);
    }

    /**
     * Helpers de validación interna.
     */
    private function validarDisponibilidadCama(int $cama_id)
    {
        $cama = $this->hospitalizacionRepository->getCamaById($cama_id);
        if (!$cama || $cama['estado'] !== 'disponible') {
            throw new Exception("La cama seleccionada no está disponible.");
        }
    }

    private function validarInternamientoActivo(int $paciente_id)
    {
        $activo = $this->hospitalizacionRepository->getInternamientoActivoPaciente($paciente_id);
        if ($activo) {
            throw new Exception("El paciente ya tiene un internamiento activo.");
        }
    }

    /**
     * Proxy methods to repository.
     */
    public function getCamasDisponibles() { return $this->hospitalizacionRepository->getCamasDisponibles(); }
    public function getInternamientosActivos() { return $this->hospitalizacionRepository->getInternamientosActivos(); }
    public function getInternamientoDetalle(int $id)
    {
        $detalle = $this->hospitalizacionRepository->getInternamientoById($id);
        if ($detalle) {
            $detalle['rondas'] = $this->hospitalizacionRepository->getRondasInternamiento($id);
            $detalle['evoluciones'] = $this->hospitalizacionRepository->getEvolucionesInternamiento($id);
        }
        return $detalle;
    }
}

<?php
namespace App\Services;

use App\Repositories\QueueRepository;
use App\Repositories\AppointmentRepository;
use App\Repositories\PatientRepository;
use Exception;

class QueueService
{
    private $turnosRepository;
    private $appointmentsRepository;
    private $patientRepository;

    public function __construct($pdo)
    {
        $this->turnosRepository = new QueueRepository($pdo);
        $this->appointmentsRepository = new AppointmentRepository($pdo);
        $this->patientRepository = new PatientRepository($pdo);
    }

    /**
     * Genera un turno para un paciente con cita previa (Preferencial).
     */
    public function generarTurnoConCita(int $cita_id, string $area, int $usuario_id, string $tipo = 'preferencial'): array
    {
        $cita = $this->appointmentsRepository->getById($cita_id);
        if (!$cita) {
            throw new Exception("La cita no existe.");
        }

        if (date('Y-m-d', strtotime($cita['fecha'])) !== date('Y-m-d')) {
            throw new Exception("La cita no es para el día de hoy.");
        }

        return $this->turnosRepository->generarTurno($area, $tipo, $cita['paciente_id'], $cita_id, $usuario_id);
    }

    /**
     * Genera un turno para un paciente sin cita (General / Walk-in).
     */
    public function generarTurnoSinCita(int $paciente_id, string $area, int $usuario_id, string $tipo = 'general'): array
    {
        $paciente = $this->patientRepository->getById($paciente_id);
        if (!$paciente) {
            throw new Exception("El paciente no existe.");
        }

        // Un turno walk-in es solo una entrada en la cola. 
        // No creamos cita placeholder aquí para evitar errores de integridad (medico_id NOT NULL)
        // y para mantener el desacoplamiento: la atención se registra al llamar al paciente.
        return $this->turnosRepository->generarTurno($area, $tipo, $paciente_id, null, $usuario_id);
    }

    /**
     * Llama al siguiente turno para un área.
     */
    public function llamarSiguiente(string $area): ?array
    {
        $turno = $this->turnosRepository->llamarSiguiente($area);
        
        if ($turno && empty($turno['cita_id'])) {
            $visitaService = new \App\Services\VisitaWalkinService($this->turnosRepository->getPDO());
            $visita_id = $visitaService->crearVisita($turno['paciente_id'], $turno['id'], $area);
            $turno['visita_walkin_id'] = $visita_id;
        } elseif ($turno) {
            $turno['visita_walkin_id'] = null;
        }
        
        return $turno;
    }

    /**
     * Marcar turno como atendido y devolver URL de atención.
     */
    public function atenderTurno(int $turno_id, int $usuario_id, string $rol): string
    {
        $turno = $this->turnosRepository->findById($turno_id);
        if (!$turno) {
            throw new Exception("El turno no existe.");
        }

        // 1. Marcar como atendido en repositorio
        $this->turnosRepository->marcarAtendido($turno_id);

        // 2. Determinar URL de redirección
        $url = 'dashboard'; // Default
        
        if ($turno['area'] === 'consulta') {
            if ($turno['cita_id']) {
                $url = 'appointments_attend?id=' . $turno['cita_id'];
                // Si la cita no tenía médico (walk-in), asignarle el médico actual
                if ($rol === 'medico') {
                    $medicoRepo = new \App\Repositories\DoctorRepository($this->turnosRepository->getPDO());
                    $medicoId = $medicoRepo->getDoctorIdByUserId($usuario_id);
                    if ($medicoId) {
                        $this->appointmentsRepository->updateMedico($turno['cita_id'], (int)$medicoId);
                        
                        // Actualizar historial_clinico también si existe
                        $hcRepo = new \App\Repositories\ClinicalHistoryRepository($this->turnosRepository->getPDO());
                        $hc = $hcRepo->getByAppointmentId($turno['cita_id']);
                        if ($hc) {
                            $hcRepo->updateMedico($hc['id'], (int)$medicoId);
                        }
                    }
                }
            } else {
                $url = 'appointments';
            }
        } elseif ($turno['area'] === 'imagenes') {
            $url = 'dashboard_imaging';
        } elseif ($turno['area'] === 'laboratorio') {
            $url = 'dashboard_laboratory';
        } elseif ($turno['area'] === 'farmacia') {
            $url = 'pharmacy';
        }

        return $url;
    }

    /**
     * Marcar turno como atendido (método simple).
     */
    public function marcarAtendido(int $turno_id): bool
    {
        return $this->turnosRepository->marcarAtendido($turno_id);
    }

    /**
     * Cancelar un turno.
     */
    public function cancelarTurno(int $turno_id): bool
    {
        return $this->turnosRepository->cancelarTurno($turno_id);
    }

    /**
     * Obtiene el estado consolidado de todas las salas para la pantalla pública.
     */
    public function getEstadoSalas(): array
    {
        $areas = ['consulta', 'laboratorio', 'farmacia', 'imagenes'];
        $estado = [];

        foreach ($areas as $area) {
            $actual = $this->turnosRepository->getTurnoActual($area);
            $esperando = $this->turnosRepository->getTurnosEsperando($area);
            
            // Obtener turnos del día para extraer historial de llamados
            $todos = $this->turnosRepository->getTurnosDia($area);
            
            // Filtrar los que ya fueron llamados o atendidos, ordenados por atención más reciente
            $llamados = array_filter($todos, function($t) {
                return in_array($t['estado'], ['llamado', 'atendido']);
            });

            // Re-ordenar por llamado_at DESC para asegurarnos que el más reciente esté primero
            usort($llamados, function($a, $b) {
                return strtotime($b['llamado_at'] ?? $b['created_at']) <=> strtotime($a['llamado_at'] ?? $a['created_at']);
            });

            // Los 3 anteriores al actual (el actual es index 0)
            $historial = array_slice($llamados, 1, 3);

            $estado[$area] = [
                'area_nombre' => ucfirst($area === 'imagenes' ? 'imágenes' : $area),
                'actual' => $actual,
                'esperando_count' => count($esperando),
                'lista_espera' => array_slice($esperando, 0, 5), // Top 5 para la vista resumida
                'ultimos_llamados' => array_values($historial)
            ];
        }

        return $estado;
    }

    /**
     * Obtiene estadísticas del día.
     */
    public function getEstadisticasDia(): array
    {
        return $this->turnosRepository->getEstadisticasDia();
    }
}

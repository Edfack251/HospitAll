<?php
namespace App\Services;

use App\Repositories\AppointmentsRepository;
use App\Repositories\ClinicalHistoryRepository;
use App\Repositories\LaboratoryRepository;
use App\Repositories\PatientRepository;
use App\Repositories\DoctorRepository;
use App\Services\ClinicalEpisodeService;

use Exception;
use PDO;
use App\Core\Validator;
use App\Services\LogService;

class AppointmentsService
{
    private $pdo;
    private $repo;
    private $historyRepo;
    private $labRepo;
    private $patientRepo;
    private $doctorRepo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->repo = new AppointmentsRepository($pdo);
        $this->historyRepo = new ClinicalHistoryRepository($pdo);
        $this->labRepo = new LaboratoryRepository($pdo);
        $this->patientRepo = new PatientRepository($pdo);
        $this->doctorRepo = new DoctorRepository($pdo);
    }

    public function getAll($limit = null, $offset = 0)
    {
        return $this->repo->getAll($limit, $offset);
    }

    public function getActiveFlowAppointments()
    {
        return $this->repo->getActiveFlowAppointments();
    }

    public function getById($id)
    {
        return $this->repo->getById($id);
    }

    public function getHistorialPrevio($cita_id)
    {
        return $this->historyRepo->getByCitaId($cita_id);
    }

    public function getHistorialCompleto($cita_id)
    {
        return $this->historyRepo->getFullHistoryChainByEncounter($cita_id);
    }

    public function getResultadosLab($paciente_id)
    {
        return $this->labRepo->getCompletedByPaciente($paciente_id);
    }

    public function getPacientes()
    {
        return $this->patientRepo->getAllBasic();
    }

    public function getMedicos()
    {
        return $this->doctorRepo->getAllBasic();
    }

    public function schedule($data)
    {
        Validator::validate($data, [
            'paciente_id' => 'required|numeric',
            'medico_id' => 'required|numeric',
            'fecha' => 'required|date',
            'hora' => 'required'
        ]);

        // Aceptar motivo u observaciones para compatibilidad; el repositorio usa observaciones
        $data['observaciones'] = $data['observaciones'] ?? $data['motivo'] ?? '';

        if ($this->repo->checkConflict($data['medico_id'], $data['fecha'], $data['hora'])) {
            throw new Exception("El médico ya tiene una cita agendada para esa fecha y hora.");
        }
        return $this->repo->schedule($data);
    }

    public function updateStatus($id, $nuevo_estado)
    {
        $estado_actual = $this->repo->getStatus($id);

        if ($estado_actual === false) {
            return false;
        }

        if ($estado_actual === 'Pendiente' || empty($estado_actual)) {
            $estado_actual = 'Programada';
        }

        $transiciones = [
            'Programada' => ['Confirmada', 'Cancelada', 'Programada'],
            'Confirmada' => ['En espera', 'Programada'],
            'En espera' => ['Atendida', 'No asistió'],
        ];

        if (isset($transiciones[$estado_actual]) && in_array($nuevo_estado, $transiciones[$estado_actual])) {
            $result = $this->repo->updateStatus($id, $nuevo_estado);
            if ($result) {
                // Auditoría: Cambio de estado
                $nivel = ($nuevo_estado === 'Cancelada') ? 'WARNING' : 'INFO';
                $logService = new LogService($this->pdo);
                $logService->register($_SESSION['user_id'], 'Cambio de estado', 'Citas', "Cita ID: $id, Nuevo estado: $nuevo_estado", $nivel);

                // Sincronización con Flujo Clínico
                if ($nuevo_estado === 'En espera') {
                    $this->updateEstadoClinico($id, 'check_in');
                }
            }
            return $result;
        }

        return false;
    }

    public function updateEstadoClinico($id, $estado_clinico)
    {
        $estados_validos = [
            'check_in',
            'triaje',
            'esperando_medico',
            'en_consulta',
            'en_procedimiento',
            'observacion',
            'alta'
        ];

        if (!in_array($estado_clinico, $estados_validos)) {
            throw new Exception("Estado clínico inválido.");
        }

        $result = $this->repo->updateEstadoClinico($id, $estado_clinico);

        if ($result && isset($_SESSION['user_id'])) {
            $logService = new LogService($this->pdo);
            $logService->register($_SESSION['user_id'], 'Actualización de flujo clínico', 'Citas', "Cita ID: $id cambió a estado clínico: $estado_clinico", 'INFO');
        }

        return $result;
    }

    public function saveAttention($data)
    {
        try {
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
            }

            $enviar_lab = !empty($data['enviar_laboratorio']);
            $diag_final = $enviar_lab ? "(Pendiente por resultados de laboratorio)" : ($data['diagnostico'] ?? '');
            $treat_final = $enviar_lab ? "(Pendiente por resultados de laboratorio)" : ($data['tratamiento'] ?? '');

            $historial_existente = $this->historyRepo->getByCitaId($data['cita_id']);

            if ($historial_existente) {
                // Modo APPEND-ONLY: Creamos una nueva Adenda Médica
                $is_addendum = isset($data['es_adenda']) && $data['es_adenda'] === '1';

                if ($is_addendum && !empty($data['adenda_texto'])) {
                    $motivo = $data['adenda_motivo'] ?? 'Corrección general';
                    $texto_adenda = "[ADENDA - {$motivo}]: " . $data['adenda_texto'];

                    // Combinar observaciones previas con la adenda actual para no perder el rastro de la cadena en la tabla unificada
                    $observaciones_combinadas = $historial_existente['observaciones'] . "\n\n" . $texto_adenda;

                    $historial_id = $this->historyRepo->createAdenda($historial_existente['id'], $diag_final, $treat_final, $observaciones_combinadas);

                    // Inactivar (lógicamente) el historial previo para que las vistas globales ignoren los obsoletos de la consulta primaria
                    $this->historyRepo->markAsObsolete($historial_existente['id']);
                } else if ($enviar_lab && strpos($historial_existente['diagnostico'], 'Pendiente') !== false) {
                    // Solo en caso de que un laboratorio acabe de llegar y actualicen el diagnóstico pendiente (es un evento automático del flujo)
                    // Este caso específico sí amerita adenda clínica legal
                    $texto_adenda = "[Actualización por Resultados de Laboratorio]: Se establece diagnóstico definitivo.";
                    $historial_id = $this->historyRepo->createAdenda($historial_existente['id'], $diag_final, $treat_final, $texto_adenda);
                    $this->historyRepo->markAsObsolete($historial_existente['id']);
                } else {
                    $historial_id = $historial_existente['id']; // No changes done explicitly
                }

            } else {
                // Modo INSERCIÓN PRIMARIA: Primer registro de esta cita
                $historial_id = $this->historyRepo->create(
                    $data['cita_id'],
                    $data['paciente_id'],
                    $data['medico_id'],
                    $diag_final,
                    $treat_final,
                    $data['observaciones_clinicas']
                );
            }

            if ($enviar_lab) {
                if (!$this->labRepo->hasPendingOrder($historial_id)) {
                    $orden_id = $this->labRepo->createPending($historial_id, $data['laboratorio_descripcion']);

                    // Auto-generación de factura de laboratorio
                    $billingService = new BillingService($this->pdo);
                    $billingService->createLaboratoryInvoice($data['paciente_id'], $orden_id, $data['laboratorio_descripcion']);
                }
            }

            $this->repo->updateStatus($data['cita_id'], 'Atendida');

            // Auto-generación de factura para cobro en Recepción a través de BillingService
            if (!$historial_existente) {
                $billingService = new BillingService($this->pdo);
                $billingService->createConsultationInvoice($data['paciente_id']);
            }

            // Manejo de Prescripción Médica
            if (!empty($data['tratamiento']) && !$enviar_lab) {
                $prescriptionService = new PrescriptionService($this->pdo);
                $prescripData = [
                    'cita_id' => $data['cita_id'],
                    'medico_id' => $data['medico_id'],
                    'paciente_id' => $data['paciente_id'],
                    'observaciones' => $data['observaciones_clinicas'] ?? '',
                    'detalles' => [
                        [
                            'medicamento_texto' => $data['tratamiento'],
                            'dosis' => 'Según indicaciones',
                            'frecuencia' => 'Según indicaciones',
                            'duracion' => 'Según indicaciones'
                        ]
                    ]
                ];
                $prescriptionService->create($prescripData);
            }

            // Sincronización automática de estado clínico para el Patient Flow Dashboard
            $nuevo_estado_clinico = $enviar_lab ? 'observacion' : 'alta';
            $this->updateEstadoClinico($data['cita_id'], $nuevo_estado_clinico);

            // Cerrar episodio clínico si la cita está vinculada y el diagnóstico es definitivo (no pendiente de lab)
            if (!$enviar_lab) {
                $cita = $this->repo->getById($data['cita_id']);
                if (!empty($cita['episodio_id'])) {
                    $episodeService = new ClinicalEpisodeService($this->pdo);
                    $episodeService->completeEpisode($cita['episodio_id']);
                }
            }

            $this->pdo->commit();

            // Auditoría: Guardar atención médica
            $accion = $historial_existente ? 'Modificación de historial clínico' : 'Guardar atención médica';
            $nivel = $historial_existente ? 'WARNING' : 'INFO';

            $logService = new LogService($this->pdo);
            $logService->register($_SESSION['user_id'], $accion, 'Citas/Atención', "Cita ID: $data[cita_id], Paciente ID: $data[paciente_id]", $nivel);

            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function delete($id)
    {
        try {
            $res = $this->repo->delete($id);

            if ($res) {
                $logService = new LogService($this->pdo);
                $logService->register($_SESSION['user_id'], 'Eliminación de cita', 'Citas', "ID: $id", 'ERROR');
            }
            return $res;
        } catch (Exception $e) {
            error_log("Error AppointmentsService::delete: " . $e->getMessage());
            return false;
        }
    }
}

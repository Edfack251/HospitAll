<?php
namespace App\Services;

use App\Repositories\AppointmentRepository;
use App\Repositories\ClinicalHistoryRepository;
use App\Repositories\LaboratoryRepository;
use App\Repositories\ImagingRepository;
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
    private $imagenesRepo;
    private $patientRepo;
    private $doctorRepo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->repo = new AppointmentRepository($pdo);
        $this->historyRepo = new ClinicalHistoryRepository($pdo);
        $this->labRepo = new LaboratoryRepository($pdo);
        $this->imagenesRepo = new ImagingRepository($pdo);
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

    public function hasPendingLabOrder($historial_id)
    {
        return $this->labRepo->hasPendingOrder($historial_id);
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

    /**
     * Genera los bloques de horario disponibles para un médico en una fecha.
     * Bloques de 30 minutos de 07:00 a 18:30 (24 bloques).
     */
    public function getHorariosDisponibles(int $medico_id, string $fecha): array
    {
        $bloques = [];
        $inicio = strtotime('07:00');
        $fin = strtotime('19:00');
        for ($t = $inicio; $t < $fin; $t += 1800) {
            $bloques[] = date('H:i', $t);
        }

        $ocupados = $this->repo->getHorariosOcupados($medico_id, $fecha);
        $disponibles = array_values(array_diff($bloques, $ocupados));

        // Si la fecha es hoy, excluir bloques cuya hora ya pasó
        if ($fecha === date('Y-m-d')) {
            $ahora = date('H:i');
            $disponibles = array_values(array_filter($disponibles, function ($h) use ($ahora) {
                return $h > $ahora;
            }));
        }

        return $disponibles;
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

        // Validación transaccional para evitar condiciones de carrera
        $this->pdo->beginTransaction();
        try {
            // Re-verificar disponibilidad dentro de la transacción
            $disponibles = $this->getHorariosDisponibles((int) $data['medico_id'], $data['fecha']);
            if (!in_array($data['hora'], $disponibles)) {
                $this->pdo->rollBack();
                throw new Exception("El horario seleccionado ya no está disponible, por favor selecciona otro.");
            }

            $result = $this->repo->schedule($data);
            $this->pdo->commit();
            return $result;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function reprogram($id, $fecha, $hora)
    {
        $cita = $this->repo->getById($id);
        if (!$cita) {
            throw new Exception("Cita no encontrada.");
        }
        if (in_array($cita['estado'], ['Atendida', 'Cancelada', 'No asistió'])) {
            throw new Exception("No se puede reprogramar una cita ya atendida, cancelada o no asistida.");
        }
        if ($this->repo->checkConflict($cita['medico_id'], $fecha, $hora, $id)) {
            throw new Exception("El médico ya tiene una cita agendada para esa fecha y hora.");
        }
        $res = $this->repo->updateFechaHora($id, $fecha, $hora);
        if ($res && isset($_SESSION['user_id'])) {
            $logService = new LogService($this->pdo);
            $logService->register($_SESSION['user_id'], 'Reprogramación', 'Citas', "Cita ID: $id, Nueva fecha: $fecha $hora", 'INFO');
        }
        return $res;
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

            $examenes_lab = $data['examenes_laboratorio'] ?? [];
            $estudios_img = $data['estudios_imagenes'] ?? [];
            $enviar_lab = !empty($data['enviar_laboratorio']) || !empty($examenes_lab);
            $enviar_img = !empty($data['enviar_imagenes']) || !empty($estudios_img);
            $pendiente_estudios = $enviar_lab || $enviar_img;
            $diag_final = $pendiente_estudios ? "(Pendiente por resultados de estudios)" : ($data['diagnostico'] ?? '');
            $treat_final = $pendiente_estudios ? "(Pendiente por resultados de estudios)" : ($data['tratamiento'] ?? '');

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
                    !empty($data['cita_id']) ? $data['cita_id'] : null,
                    $data['paciente_id'],
                    $data['medico_id'],
                    $diag_final,
                    $treat_final,
                    $data['observaciones_clinicas']
                );
                
                // Crear atención en tabla atenciones (según nuevos requerimientos)
                $sql_atencion = "INSERT INTO atenciones (cita_id, walkin_id, medico_id, paciente_id, motivo_consulta, diagnostico, tratamiento, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql_atencion);
                $stmt->execute([
                    !empty($data['cita_id']) ? $data['cita_id'] : null,
                    !empty($data['walkin_id']) ? $data['walkin_id'] : null,
                    $data['medico_id'],
                    $data['paciente_id'],
                    $data['observaciones_clinicas'] ?? '',
                    $diag_final,
                    $treat_final,
                    $data['observaciones_clinicas'] ?? ''
                ]);
            }

            if ($enviar_lab) {
                if (!$this->labRepo->hasPendingOrder($historial_id)) {
                    $orden_id = $this->labRepo->createPending($historial_id, $data['laboratorio_descripcion'] ?? '', $examenes_lab);

                    // Auto-generación de factura de laboratorio
                    $billingService = new BillingService($this->pdo);
                    $billingService->createLaboratoryInvoice($data['paciente_id'], $orden_id, $data['laboratorio_descripcion'] ?? '');
                }
            }

            if ($enviar_img && !empty($estudios_img)) {
                $ordenes_ids = $this->imagenesRepo->crearOrdenDesdeConsulta($historial_id, $estudios_img);
                
                // Auto-generación de factura de imágenes
                $billingService = new BillingService($this->pdo);
                foreach ($ordenes_ids as $index => $oid) {
                    $billingService->createImagingInvoice($data['paciente_id'], $oid, $estudios_img[$index] ?? '');
                }
            }

            if (!empty($data['cita_id'])) {
                $this->repo->updateStatus($data['cita_id'], 'Atendida');

                // Sincronización automática de estado clínico para el Patient Flow Dashboard
                $nuevo_estado_clinico = $pendiente_estudios ? 'observacion' : 'alta';
                $this->updateEstadoClinico($data['cita_id'], $nuevo_estado_clinico);

                // Cerrar episodio clínico si la cita está vinculada y el diagnóstico es definitivo (no pendiente de lab)
                if (!$pendiente_estudios) {
                    $cita = $this->repo->getById($data['cita_id']);
                    if (!empty($cita['episodio_id'])) {
                        $episodeService = new ClinicalEpisodeService($this->pdo);
                        $episodeService->completeEpisode($cita['episodio_id']);
                    }
                }
            }
            
            if (!empty($data['walkin_id'])) {
                $visitaRepo = new \App\Repositories\VisitaWalkinRepository($this->pdo);
                $visitaRepo->markAsAtendido((int)$data['walkin_id']);
                $visitaRepo->actualizarEstado((int)$data['walkin_id'], 'atendido', (int)$data['medico_id']);
            }

            // Manejo de Prescripción Médica
            if (!empty($data['tratamiento']) && !$pendiente_estudios) {
                // Fallback para cita_id (la BD prescribe_detalles/prescripciones exige un cita_id, lo dejaremos NULL u dummy si la migración lo permite)
                // Usando un update preventivo dentro del repo si es necesario
                $cita_id_prescripcion = !empty($data['cita_id']) ? $data['cita_id'] : 0; // Ojo, podría fallar la FK de prescripciones si se pasa 0
                
                // Si la FK de prescripciones exige cita_id válida, y es null, omitimos si es walk-in, 
                // pero si el schema lo acepta lo creamos:
                if (!empty($data['cita_id'])) {
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
            }

            $this->pdo->commit();

            // Auditoría: Guardar atención médica
            $accion = $historial_existente ? 'Modificación de historial clínico' : 'Guardar atención médica';
            $nivel = $historial_existente ? 'WARNING' : 'INFO';
            $citaIdRef = !empty($data['cita_id']) ? $data['cita_id'] : 'WALKIN';

            $logService = new LogService($this->pdo);
            $logService->register($_SESSION['user_id'], $accion, 'Citas/Atención', "Cita ID/Ref: $citaIdRef, Paciente ID: $data[paciente_id]", $nivel);

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

    public function getAppointmentsByDate($fecha)
    {
        return $this->repo->getAppointmentsByDate($fecha);
    }

    public function getTodayByPatient($paciente_id)
    {
        $sql = "SELECT c.*, m.nombre as medico_nombre, m.apellido as medico_apellido 
                FROM citas c
                JOIN medicos m ON c.medico_id = m.id
                WHERE c.paciente_id = ? AND c.fecha = CURDATE() AND c.estado != 'Cancelada'
                ORDER BY c.hora ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$paciente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

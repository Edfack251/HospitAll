<?php
namespace App\Services;

use Exception;
use PDO;
use PDOException;
use App\Repositories\DoctorRepository;
use App\Repositories\AppointmentRepository;
use App\Repositories\ClinicalHistoryRepository;
use App\Repositories\LaboratoryRepository;
use App\Repositories\PrescriptionRepository;
use App\Repositories\EmergencyRepository;
use App\Repositories\HospitalizationRepository;

class DoctorDashboardService
{
    private $pdo;
    private $doctorRepo;
    private $appointmentsRepo;
    private $historyRepo;
    private $labRepo;
    private $prescriptionRepo;
    private $emergenciaRepo;
    private $turnosRepo;
    private $hospitalizacionRepo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->doctorRepo = new DoctorRepository($pdo);
        $this->appointmentsRepo = new AppointmentRepository($pdo);
        $this->historyRepo = new ClinicalHistoryRepository($pdo);
        $this->labRepo = new LaboratoryRepository($pdo);
        $this->prescriptionRepo = new PrescriptionRepository($pdo);
        $this->emergenciaRepo = new EmergencyRepository($pdo);
        $this->turnosRepo = new \App\Repositories\QueueRepository($pdo);
        $this->hospitalizacionRepo = new HospitalizationRepository($pdo);
    }

    public function getDashboardData(int $usuario_id): array
    {
        try {
            // 1. Resolver el medico_id a partir del usuario_id
            $medico_id = $this->doctorRepo->getDoctorIdByUserId($usuario_id);

            if (!$medico_id) {
                return [
                    'medico_id' => null,
                    'error' => 'Perfil de médico no encontrado para este usuario.',
                    'citas_hoy' => [],
                    'pacientes_espera' => [],
                    'emergencias_asignadas' => [],
                    'consultas_recientes' => [],
                    'resultados_pendientes' => [],
                    'prescripciones_activas' => []
                ];
            }

            $emergencias_asignadas = $this->emergenciaRepo->getEmergenciasAsignadas($medico_id);

            // 2. Agregar la información requerida
            return [
                'medico_id' => $medico_id,
                'citas_hoy' => $this->appointmentsRepo->getCitasMedicoHoy($medico_id),
                'pacientes_espera' => $this->appointmentsRepo->getPacientesConsulta($medico_id),
                'emergencias_asignadas' => $emergencias_asignadas,
                'count_emergencias_activas' => count($emergencias_asignadas),
                'consultas_recientes' => $this->historyRepo->getConsultasRecientes($medico_id, 5),
                'resultados_pendientes' => $this->labRepo->getResultadosPendientes($medico_id, 5),
                'count_labs_pendientes' => $this->labRepo->getResultadosPendientesCount($medico_id),
                'prescripciones_activas' => $this->prescriptionRepo->getPrescripcionesActivas($medico_id, 5),
                'count_prescripciones_activas' => $this->prescriptionRepo->getPrescripcionesActivasCount($medico_id),
                'turnoActual' => $this->turnosRepo->getTurnoActual('consulta'),
                'turnosEsperandoCount' => count($this->turnosRepo->getTurnosEsperando('consulta')),
                'pacientes_internados' => $this->hospitalizacionRepo->getInternamientosActivos($medico_id)
            ];

        } catch (PDOException $e) {
            error_log("Error en DoctorDashboardService::getDashboardData: " . $e->getMessage());
            throw new Exception("Error interno al obtener los datos del dashboard.");
        }
    }
}

<?php
namespace App\Services;

use Exception;
use PDO;
use PDOException;
use App\Repositories\DoctorRepository;
use App\Repositories\AppointmentsRepository;
use App\Repositories\ClinicalHistoryRepository;
use App\Repositories\LaboratoryRepository;
use App\Repositories\PrescriptionRepository;

class DoctorDashboardService
{
    private $pdo;
    private $doctorRepo;
    private $appointmentsRepo;
    private $historyRepo;
    private $labRepo;
    private $prescriptionRepo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->doctorRepo = new DoctorRepository($pdo);
        $this->appointmentsRepo = new AppointmentsRepository($pdo);
        $this->historyRepo = new ClinicalHistoryRepository($pdo);
        $this->labRepo = new LaboratoryRepository($pdo);
        $this->prescriptionRepo = new PrescriptionRepository($pdo);
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
                    'consultas_recientes' => [],
                    'resultados_pendientes' => [],
                    'prescripciones_activas' => []
                ];
            }

            // 2. Agregar la información requerida
            return [
                'medico_id' => $medico_id,
                'citas_hoy' => $this->appointmentsRepo->getCitasMedicoHoy($medico_id),
                'pacientes_espera' => $this->appointmentsRepo->getPacientesConsulta($medico_id),
                'consultas_recientes' => $this->historyRepo->getConsultasRecientes($medico_id, 5), // Límite de 5
                'resultados_pendientes' => $this->labRepo->getResultadosPendientes($medico_id, 5), // Límite de 5
                'prescripciones_activas' => $this->prescriptionRepo->getPrescripcionesActivas($medico_id, 5) // Límite de 5
            ];

        } catch (PDOException $e) {
            error_log("Error en DoctorDashboardService::getDashboardData: " . $e->getMessage());
            throw new Exception("Error interno al obtener los datos del dashboard.");
        }
    }
}

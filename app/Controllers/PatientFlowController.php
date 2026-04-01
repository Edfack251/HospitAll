<?php
namespace App\Controllers;

use App\Services\AppointmentsService;
use App\Repositories\EmergencyRepository;
use Exception;

class PatientFlowController
{
    private $service;
    private $emergenciaRepo;

    public function __construct($pdo)
    {
        $this->service = new AppointmentsService($pdo);
        $this->emergenciaRepo = new EmergencyRepository($pdo);
    }

    public function index()
    {
        $path = __DIR__ . '/../../views/pages/patient_flow_dashboard.php';
        if (file_exists($path)) {
            global $pdo;
            require_once $path;
        } else {
            http_response_code(404);
            echo "<h1>404 Not Found</h1><p>Vista patient_flow_dashboard.php no encontrada.</p>";
        }
    }

    public function getData()
    {
        try {
            header('Content-Type: application/json');
            $appointments = $this->service->getActiveFlowAppointments();

            $grouped = [
                'check_in' => [],
                'triaje' => [],
                'esperando_medico' => [],
                'en_consulta' => [],
                'en_procedimiento' => [],
                'observacion' => []
            ];

            foreach ($appointments as $apt) {
                // Si no tiene estado clínico pero está en el flujo, por defecto va a check_in
                $estado = !empty($apt['estado_clinico']) ? $apt['estado_clinico'] : 'check_in';

                if (isset($grouped[$estado])) {
                    $grouped[$estado][] = $apt;
                }
            }

            // Integrar Emergencias
            $emergencias = $this->emergenciaRepo->getEmergenciasParaFlow();
            foreach ($emergencias as $emg) {
                // Mapeo: 'En espera' -> esperando_medico, 'En atención' -> en_consulta
                $estadoKanban = ($emg['estado'] === 'En espera') ? 'esperando_medico' : 'en_consulta';
                
                if (isset($grouped[$estadoKanban])) {
                    $grouped[$estadoKanban][] = $emg;
                }
            }

            echo json_encode(['success' => true, 'data' => $grouped]);
            exit();
        } catch (Exception $e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit();
        }
    }

    public function updateStatus()
    {
        try {
            header('Content-Type: application/json');

            $input = json_decode(file_get_contents('php://input'), true);
            $cita_id = $input['cita_id'] ?? null;
            $nuevo_estado = $input['nuevo_estado'] ?? null;

            if (!$cita_id || !$nuevo_estado) {
                throw new Exception("Datos incompletos.");
            }

            $result = $this->service->updateEstadoClinico($cita_id, $nuevo_estado);

            echo json_encode(['success' => $result]);
            exit();
        } catch (Exception $e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit();
        }
    }
}

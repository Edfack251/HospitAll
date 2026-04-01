<?php
namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\UrlHelper;
use App\Policies\PolicyManager;
use App\Repositories\DoctorRepository;
use App\Repositories\EmergencyRepository;
use App\Repositories\PatientRepository;
use App\Services\EmergencyCareService;
use Exception;

class EmergencyCareController
{
    private $pdo;
    private $service;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->service = new EmergencyCareService($pdo);
    }

    /**
     * GET: Carga la vista de atención de emergencia.
     * Requiere ?emergencia_id=X y que el médico autenticado sea el asignado.
     */
    public function index()
    {
        AuthHelper::checkRole(['medico']);
        PolicyManager::authorize($_SESSION['user'] ?? [], 'attend_emergency');

        $emergencia_id = (int) ($_GET['emergencia_id'] ?? 0);
        if ($emergencia_id <= 0) {
            $_SESSION['error'] = 'Emergencia no especificada.';
            UrlHelper::redirect('api/doctor/dashboard');
            return;
        }

        $doctorRepo = new DoctorRepository($this->pdo);
        $emergenciaRepo = new EmergencyRepository($this->pdo);
        $patientRepo = new PatientRepository($this->pdo);

        $usuario_id = (int) ($_SESSION['user_id'] ?? 0);
        $medico_id = $doctorRepo->getDoctorIdByUserId($usuario_id);
        if (!$medico_id) {
            $_SESSION['error'] = 'Perfil de médico no encontrado.';
            UrlHelper::redirect('api/doctor/dashboard');
            return;
        }

        $emergencia = $emergenciaRepo->getEmergenciaById($emergencia_id);
        if (!$emergencia) {
            $_SESSION['error'] = 'Emergencia no encontrada.';
            UrlHelper::redirect('api/doctor/dashboard');
            return;
        }
        if ((int) ($emergencia['medico_id'] ?? 0) !== $medico_id) {
            $_SESSION['error'] = 'No eres el médico asignado a esta emergencia.';
            UrlHelper::redirect('api/doctor/dashboard');
            return;
        }

        // Iniciar atención (actualiza estado si es necesario)
        $this->service->iniciarAtencion($emergencia_id, $medico_id);
        $emergencia = $emergenciaRepo->getEmergenciaById($emergencia_id);

        $identidad = $patientRepo->getIdentityData($emergencia['paciente_id']);
        $signos = $emergenciaRepo->getSignosVitalesEmergencia($emergencia_id);

        extract([
            'emergencia' => $emergencia,
            'identidad' => $identidad ?: [],
            'signos' => $signos ?: []
        ]);
        require_once __DIR__ . '/../../views/pages/emergency_attend.php';
    }

    /**
     * POST JSON: Registra la atención médica.
     */
    public function registrarAtencion()
    {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $emergencia_id = (int) ($input['emergencia_id'] ?? 0);

            if ($emergencia_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Emergencia inválida.']);
                return;
            }

            $doctorRepo = new DoctorRepository($this->pdo);
            $usuario_id = (int) ($_SESSION['user_id'] ?? 0);
            $medico_id = $doctorRepo->getDoctorIdByUserId($usuario_id);
            if (!$medico_id) {
                echo json_encode(['success' => false, 'error' => 'Perfil de médico no encontrado.']);
                return;
            }

            $emergenciaRepo = new EmergencyRepository($this->pdo);
            $emergencia = $emergenciaRepo->getEmergenciaById($emergencia_id);
            if (!$emergencia) {
                echo json_encode(['success' => false, 'error' => 'Emergencia no encontrada.']);
                return;
            }

            $paciente_id = (int) $emergencia['paciente_id'];
            $data = [
                'motivo_consulta' => $input['motivo_consulta'] ?? '',
                'sintomas' => $input['sintomas'] ?? '',
                'diagnostico' => $input['diagnostico'] ?? '',
                'tratamiento' => $input['tratamiento'] ?? '',
                'observaciones' => $input['observaciones'] ?? '',
                'presion_arterial' => $input['presion_arterial'] ?? null,
                'frecuencia_cardiaca' => $input['frecuencia_cardiaca'] ?? null,
                'temperatura' => $input['temperatura'] ?? null,
                'peso' => $input['peso'] ?? null,
                'estatura' => $input['estatura'] ?? null
            ];

            $this->service->registrarAtencion($emergencia_id, $medico_id, $paciente_id, $data);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($e instanceof \PDOException || strpos($e->getMessage(), 'SQLSTATE') !== false) {
                error_log('[HospitAll] Error en EmergencyCareController: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error interno del servidor. Intente de nuevo.']);
            } else {
                error_log('EmergencyCareController::registrarAtencion: ' . $e->getMessage());
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * POST JSON: Cierra la emergencia.
     */
    public function cerrarEmergencia()
    {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $emergencia_id = (int) ($input['emergencia_id'] ?? 0);

            if ($emergencia_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Emergencia inválida.']);
                return;
            }

            $this->service->cerrarEmergencia($emergencia_id);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($e instanceof \PDOException || strpos($e->getMessage(), 'SQLSTATE') !== false) {
                error_log('[HospitAll] Error en EmergencyCareController: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error interno del servidor. Intente de nuevo.']);
            } else {
                error_log('EmergencyCareController::cerrarEmergencia: ' . $e->getMessage());
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }
}

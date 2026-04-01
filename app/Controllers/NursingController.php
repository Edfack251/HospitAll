<?php
namespace App\Controllers;

use App\Helpers\UrlHelper;
use App\Services\NursingService;
use Exception;

class NursingController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new NursingService($pdo);
    }

    public function index()
    {
        UrlHelper::redirect('dashboard_nursing');
    }

    /**
     * POST: Registra signos vitales para una cita.
     */
    public function registrarSignosVitales()
    {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $cita_id = (int) ($input['cita_id'] ?? 0);
            $medico_id = (int) ($input['medico_id'] ?? 0);
            $paciente_id = (int) ($input['paciente_id'] ?? 0);
            $data = $input['data'] ?? [];

            if ($cita_id <= 0 || $medico_id <= 0 || $paciente_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Datos de cita inválidos.']);
                return;
            }

            $this->service->registrarSignosVitales($cita_id, $medico_id, $paciente_id, $data);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($e instanceof \PDOException || strpos($e->getMessage(), 'SQLSTATE') !== false) {
                error_log('[HospitAll] Error en NursingController: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error interno del servidor. Intente de nuevo.']);
            } else {
                error_log('NursingController::registrarSignosVitales: ' . $e->getMessage());
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * POST: Registra una observación de enfermería.
     */
    public function registrarObservacion()
    {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $cita_id = (int) ($input['cita_id'] ?? 0);
            $paciente_id = (int) ($input['paciente_id'] ?? 0);
            $usuario_id = (int) ($_SESSION['user_id'] ?? 0);
            $observaciones = trim($input['observaciones'] ?? '');

            if ($cita_id <= 0 || $paciente_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Datos de cita o paciente inválidos.']);
                return;
            }
            if ($usuario_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Sesión no válida.']);
                return;
            }

            $this->service->registrarObservacion($cita_id, $paciente_id, $usuario_id, $observaciones);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($e instanceof \PDOException || strpos($e->getMessage(), 'SQLSTATE') !== false) {
                error_log('[HospitAll] Error en NursingController: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error interno del servidor. Intente de nuevo.']);
            } else {
                error_log('NursingController::registrarObservacion: ' . $e->getMessage());
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * GET: Obtiene signos vitales de una cita (para precargar en modal).
     */
    public function getSignosVitales()
    {
        header('Content-Type: application/json');
        try {
            $cita_id = (int) ($_GET['cita_id'] ?? 0);
            if ($cita_id <= 0) {
                echo json_encode([]);
                return;
            }
            $signos = $this->service->getSignosVitalesCita($cita_id);
            echo json_encode($signos ?: []);
        } catch (Exception $e) {
            error_log('NursingController::getSignosVitales: ' . $e->getMessage());
            echo json_encode([]);
        }
    }

    /**
     * POST: Registra un ingreso de emergencia.
     */
    public function registrarEmergencia()
    {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $paciente_id = (int) ($input['paciente_id'] ?? 0);
            $usuario_id = (int) ($_SESSION['user_id'] ?? 0);
            $nivel_triage = trim($input['nivel_triage'] ?? '');
            $motivo_ingreso = trim($input['motivo_ingreso'] ?? '');

            if ($paciente_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Paciente inválido.']);
                return;
            }
            if ($usuario_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Sesión no válida.']);
                return;
            }

            $emergencia_id = $this->service->registrarEmergencia($paciente_id, $usuario_id, $nivel_triage, $motivo_ingreso);
            echo json_encode(['success' => true, 'emergencia_id' => $emergencia_id]);
        } catch (Exception $e) {
            if ($e instanceof \PDOException || strpos($e->getMessage(), 'SQLSTATE') !== false) {
                error_log('[HospitAll] Error en NursingController: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error interno del servidor. Intente de nuevo.']);
            } else {
                error_log('NursingController::registrarEmergencia: ' . $e->getMessage());
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * POST: Asigna un médico a una emergencia.
     */
    public function asignarMedico()
    {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $emergencia_id = (int) ($input['emergencia_id'] ?? 0);
            $medico_id = (int) ($input['medico_id'] ?? 0);

            $this->service->asignarMedico($emergencia_id, $medico_id);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($e instanceof \PDOException || strpos($e->getMessage(), 'SQLSTATE') !== false) {
                error_log('[HospitAll] Error en NursingController: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error interno del servidor. Intente de nuevo.']);
            } else {
                error_log('NursingController::asignarMedico: ' . $e->getMessage());
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * POST: Actualiza el estado de una emergencia.
     */
    public function actualizarEstadoEmergencia()
    {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $emergencia_id = (int) ($input['emergencia_id'] ?? 0);
            $nuevo_estado = trim($input['nuevo_estado'] ?? '');

            if ($emergencia_id <= 0) {
                echo json_encode(['success' => false, 'error' => 'Emergencia inválida.']);
                return;
            }

            $this->service->actualizarEstadoEmergencia($emergencia_id, $nuevo_estado);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            if ($e instanceof \PDOException || strpos($e->getMessage(), 'SQLSTATE') !== false) {
                error_log('[HospitAll] Error en NursingController: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error interno del servidor. Intente de nuevo.']);
            } else {
                error_log('NursingController::actualizarEstadoEmergencia: ' . $e->getMessage());
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * POST: Crea un paciente para emergencias (sin cuenta en el portal).
     */
    public function crearPacienteEmergencia()
    {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $paciente_id = $this->service->crearPacienteEmergencia($input);
            echo json_encode(['success' => true, 'paciente_id' => $paciente_id]);
        } catch (Exception $e) {
            if ($e instanceof \PDOException || strpos($e->getMessage(), 'SQLSTATE') !== false) {
                error_log('[HospitAll] Error en NursingController: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error interno del servidor. Intente de nuevo.']);
            } else {
                error_log('NursingController::crearPacienteEmergencia: ' . $e->getMessage());
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * GET: Lista de médicos disponibles para asignar.
     */
    public function getMedicosDisponibles()
    {
        header('Content-Type: application/json');
        try {
            $medicos = $this->service->getMedicosDisponibles();
            echo json_encode(['success' => true, 'medicos' => $medicos]);
        } catch (Exception $e) {
            if ($e instanceof \PDOException || strpos($e->getMessage(), 'SQLSTATE') !== false) {
                error_log('[HospitAll] Error en NursingController: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error interno del servidor. Intente de nuevo.']);
            } else {
                error_log('NursingController::getMedicosDisponibles: ' . $e->getMessage());
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }
}

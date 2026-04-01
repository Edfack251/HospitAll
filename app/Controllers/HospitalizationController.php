<?php
namespace App\Controllers;

use App\Helpers\UrlHelper;
use App\Helpers\AuthHelper;
use App\Policies\PolicyManager;
use App\Services\HospitalizationService;
use Exception;

class HospitalizationController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new HospitalizationService($pdo);
    }

    /**
     * Carga la vista principal de hospitalización.
     */
    public function index()
    {
        AuthHelper::checkRole(['medico', 'enfermera', 'administrador', 'recepcionista']);
        
        $internamientos = $this->service->getInternamientosActivos();
        
        return [
            'internamientos' => $internamientos
        ];
    }

    /**
     * Carga la vista de rondas de enfermería.
     */
    public function showRondas()
    {
        AuthHelper::checkRole(['enfermera', 'administrador']);
        
        $internamientos = $this->service->getInternamientosActivos();
        
        return [
            'internamientos' => $internamientos
        ];
    }

    /**
     * Endpoint API para internar un paciente.
     */
    public function internar()
    {
        header('Content-Type: application/json');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $origen = $data['origen'] ?? '';
            $cama_id = (int)($data['cama_id'] ?? 0);
            $motivo = $data['motivo'] ?? '';
            $diagnostico = $data['diagnostico_ingreso'] ?? '';
            
            $medico_id = $_SESSION['medico_id'] ?? $_SESSION['user_id']; // Fallback a user_id si no hay medico_id

            if ($origen === 'emergencia') {
                $emergencia_id = (int)($data['emergencia_id'] ?? 0);
                $id = $this->service->internarDesdeEmergencia($emergencia_id, $cama_id, $medico_id, $motivo, $diagnostico);
            } else {
                $cita_id = (int)($data['cita_id'] ?? 0);
                $id = $this->service->internarDesdeConsulta($cita_id, $cama_id, $medico_id, $motivo, $diagnostico);
            }

            echo json_encode(['success' => true, 'internamiento_id' => $id]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Endpoint API para registrar una ronda de enfermería.
     */
    public function registrarRonda()
    {
        header('Content-Type: application/json');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $internamiento_id = (int)($data['internamiento_id'] ?? 0);
            $enfermera_id = $_SESSION['user_id'];

            $id = $this->service->registrarRonda($internamiento_id, $enfermera_id, $data);
            echo json_encode(['success' => true, 'ronda_id' => $id]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Endpoint API para registrar una evolución médica.
     */
    public function registrarEvolucion()
    {
        header('Content-Type: application/json');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $internamiento_id = (int)($data['internamiento_id'] ?? 0);
            $medico_id = $_SESSION['medico_id'] ?? $_SESSION['user_id'];
            $evolucion = $data['evolucion'] ?? '';
            $indicaciones = $data['indicaciones'] ?? null;
            $diag_act = $data['diagnostico_actualizado'] ?? null;

            $id = $this->service->registrarEvolucion($internamiento_id, $medico_id, $evolucion, $indicaciones, $diag_act);
            echo json_encode(['success' => true, 'evolucion_id' => $id]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Endpoint API para dar el alta médica.
     */
    public function darAlta()
    {
        header('Content-Type: application/json');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $internamiento_id = (int)($data['internamiento_id'] ?? 0);
            $observaciones = $data['observaciones_alta'] ?? '';
            $usuario_id = $_SESSION['medico_id'] ?? $_SESSION['user_id'];
            $esAdmin = ($_SESSION['user_role'] === 'administrador');

            $this->service->darAlta($internamiento_id, $usuario_id, $observaciones, $esAdmin);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Endpoint API para obtener camas disponibles.
     */
    public function getCamasDisponibles()
    {
        header('Content-Type: application/json');
        try {
            $camas = $this->service->getCamasDisponibles();
            echo json_encode(['success' => true, 'camas' => $camas]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Endpoint API para obtener el detalle de un internamiento.
     */
    public function getDetalleInternamiento()
    {
        header('Content-Type: application/json');
        try {
            $id = (int)($_GET['internamiento_id'] ?? 0);
            $detalle = $this->service->getInternamientoDetalle($id);
            if (!$detalle) {
                throw new Exception("Internamiento no encontrado.");
            }
            echo json_encode(['success' => true, 'detalle' => $detalle]);
        } catch (Exception $e) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}

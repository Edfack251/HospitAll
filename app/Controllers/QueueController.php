<?php
namespace App\Controllers;

use App\Services\QueueService;
use App\Policies\PolicyManager;
use App\Helpers\UrlHelper;
use Exception;

class QueueController extends BaseController
{
    private $turnosService;

    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->turnosService = new QueueService($pdo);
    }

    /**
     * Carga la vista de gestión de turnos para recepción.
     */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        PolicyManager::authorize($_SESSION, 'manage_turnos');
        
        $pageTitle = 'Gestión de Turnos';
        $activePage = 'queue_reception';
        $headerTitle = 'Gestión de Turnos';
        $headerSubtitle = 'Control de flujo de pacientes en tiempo real.';
        
        $estadoSalas = $this->turnosService->getEstadoSalas();
        
        require __DIR__ . '/../../views/pages/queue_reception.php';
    }

    /**
     * Carga la vista del portal de turnos adaptado por perfil.
     */
    public function portalTurnos()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $rol = $_SESSION['user_role'] ?? '';
        $area_map = [
            'medico' => 'consulta',
            'tecnico_laboratorio' => 'laboratorio',
            'tecnico_imagenes' => 'imagenes',
            'farmaceutico' => 'farmacia',
        ];

        $mi_area = $area_map[$rol] ?? null;
        
        $pageTitle = 'Portal de Turnos';
        $activePage = 'queue_portal';
        $headerTitle = 'Portal de Turnos';
        $headerSubtitle = $mi_area ? "Gestión de turnos para el área de " . ucfirst($mi_area) : "Panel general de turnos";

        $estadoSalas = $this->turnosService->getEstadoSalas();

        require __DIR__ . '/../../views/pages/queue_portal.php';
    }

    /**
     * Endpoint para generar un nuevo turno.
     */
    public function generarTurno()
    {
        try {
            if (session_status() === PHP_SESSION_NONE) session_start();
            PolicyManager::authorize($_SESSION, 'generate_turno');
            
            $input = $this->getJsonInput();
            $area = $input['area'] ?? $_POST['area'] ?? null;
            
            $raw_paciente_id = $input['paciente_id'] ?? $_POST['paciente_id'] ?? null;
            $paciente_id = !empty($raw_paciente_id) ? (int)$raw_paciente_id : null;
            
            $raw_cita_id = $input['cita_id'] ?? $_POST['cita_id'] ?? null;
            $cita_id = !empty($raw_cita_id) ? (int)$raw_cita_id : null;
            
            $raw_tipo = $input['tipo'] ?? $_POST['tipo'] ?? null;
            $tipo = !empty($raw_tipo) ? $raw_tipo : null;
            
            if (!$area) {
                throw new Exception("El área es requerida.");
            }

            $turno = null;
            if ($cita_id) {
                $turno = $this->turnosService->generarTurnoConCita($cita_id, $area, $this->getAuthUserId(), $tipo ?? 'preferencial');
            } elseif ($paciente_id) {
                $turno = $this->turnosService->generarTurnoSinCita($paciente_id, $area, $this->getAuthUserId(), $tipo ?? 'general');
            } else {
                throw new Exception("Se requiere seleccionar un paciente o una cita.");
            }

            $this->jsonSuccess([
                'turno' => [
                    'numero' => $turno['numero'],
                    'area' => $turno['area'],
                    'tipo' => $turno['tipo'],
                    'paciente' => $turno['paciente_nombre'] . ' ' . $turno['paciente_apellido']
                ]
            ]);

        } catch (Exception $e) {
            $this->jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Endpoint para llamar al siguiente turno en un área.
     */
    public function llamarSiguiente()
    {
        try {
            if (session_status() === PHP_SESSION_NONE) session_start();
            PolicyManager::authorize($_SESSION, 'call_turno');
            
            $input = $this->getJsonInput();
            $area = $input['area'] ?? $_POST['area'] ?? null;
            
            if (!$area) {
                throw new Exception("El área es requerida.");
            }

            $turno = $this->turnosService->llamarSiguiente($area);
            
            if (!$turno) {
                $this->jsonError("No hay pacientes en espera para esta área.", 200);
            }

            $this->jsonSuccess([
                'turno' => [
                    'numero' => $turno['numero'],
                    'paciente_nombre' => $turno['paciente_nombre'],
                    'paciente_apellido' => $turno['paciente_apellido'],
                    'paciente' => $turno['paciente_nombre'] . ' ' . $turno['paciente_apellido'],
                    'cita_id' => $turno['cita_id'],
                    'visita_walkin_id' => $turno['visita_walkin_id'] ?? null,
                    'area' => $turno['area'],
                    'tipo' => $turno['tipo']
                ]
            ]);

        } catch (Exception $e) {
            $this->jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Endpoint para marcar un turno como atendido.
     */
    public function marcarAtendido()
    {
        try {
            if (session_status() === PHP_SESSION_NONE) session_start();
            
            // Aceptar tanto JSON como FormData
            $input = $this->getJsonInput();
            $turno_id = $input['turno_id']
                ?? $_POST['turno_id']
                ?? null;
            
            if (!$turno_id) {
                throw new Exception("El ID del turno es requerido.");
            }

            $usuario_id = $_SESSION['user_id'] ?? 0;
            $rol = $_SESSION['user_role'] ?? '';

            // Usamos atenderTurno que gestiona la lógica de vinculación y devuelve la URL
            $redirectUrl = $this->turnosService->atenderTurno((int)$turno_id, (int)$usuario_id, $rol);

            echo json_encode(['success' => true, 'redirect_url' => UrlHelper::url($redirectUrl)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Endpoint para cancelar un turno.
     */
    public function cancelarTurno()
    {
        try {
            if (session_status() === PHP_SESSION_NONE) session_start();
            PolicyManager::authorize($_SESSION, 'manage_turnos');
            $input = $this->getJsonInput();
            $turno_id = (int)($input['turno_id'] ?? $_POST['turno_id'] ?? 0);
            
            if (!$turno_id) throw new Exception("ID de turno requerido.");

            $this->turnosService->cancelarTurno($turno_id);
            $this->jsonSuccess();

        } catch (Exception $e) {
            $this->jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Endpoint público para obtener el estado de las salas.
     */
    public function getEstadoSalas()
    {
        try {
            $estado = $this->turnosService->getEstadoSalas();
            $this->jsonSuccess($estado);
        } catch (Exception $e) {
            $this->jsonError($e->getMessage(), 500);
        }
    }

    /**
     * Carga la vista de la pantalla pública.
     */
    public function pantallaPublica()
    {
        $pageTitle = 'Monitor de Turnos - HospitAll';
        $estadoSalas = $this->turnosService->getEstadoSalas();
        require __DIR__ . '/../../views/pages/queue_display.php';
    }
}

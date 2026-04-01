<?php
namespace App\Controllers;

use App\Services\NursingAssignmentService;
use App\Services\AppointmentsService;
use App\Helpers\AuthHelper;
use Exception;

class NursingAssignmentController
{
    private $service;
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->service = new NursingAssignmentService($pdo);
    }
    private $appointmentsService;

    public function index(): void
    {
        AuthHelper::checkRole(['administrador', 'recepcionista', 'medico']);
        
        $asignaciones = $this->service->getResumenAsignaciones();
        $enfermeras = $this->service->getEnfermeras();
        
        // Internamientos activos sin enfermera asignada
        $internamientosSinAsignar = $this->service->getInternamientosSinAsignar();

        $pageTitle = 'Asignación de Enfermería - HospitAll';
        $activePage = 'nursing_assignment';
        $headerTitle = 'Asignación de Enfermería';
        $headerSubtitle = 'Gestión de enfermeras asignadas a pacientes internados.';

        include __DIR__ . '/../../views/pages/nursing_assignment.php';
    }

    public function asignar()
    {
        header('Content-Type: application/json');
        try {
            AuthHelper::checkRole(['administrador', 'recepcionista', 'medico']);
            $data = json_decode(file_get_contents('php://input'), true);
            $data['asignado_por'] = $_SESSION['user_id'];
            
            $res = $this->service->asignarPaciente($data);
            echo json_encode(['success' => $res]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit();
    }

    public function eliminar()
    {
        header('Content-Type: application/json');
        try {
            AuthHelper::checkRole(['administrador', 'recepcionista', 'medico']);
            $id = $_GET['id'] ?? null;
            if (!$id) throw new Exception("ID no proporcionado");
            
            $res = $this->service->eliminarAsignacion($id);
            echo json_encode(['success' => $res]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit();
    }
}

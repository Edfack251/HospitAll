<?php
namespace App\Controllers;

use App\Core\ErrorHandler;
use App\Helpers\UrlHelper;
use App\Services\AppointmentsService;
use Exception;

class AppointmentsController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new AppointmentsService($pdo);
    }

    public function index()
    {
        return $this->service->getAll();
    }

    public function getSchedulingData()
    {
        return [
            'pacientes' => $this->service->getPacientes(),
            'medicos' => $this->service->getMedicos()
        ];
    }

    public function getHorariosDisponibles()
    {
        header('Content-Type: application/json');
        try {
            $medico_id = $_GET['medico_id'] ?? '';
            $fecha = $_GET['fecha'] ?? '';

            if (empty($medico_id) || empty($fecha)) {
                throw new Exception("Médico y fecha son requeridos.");
            }

            if ($fecha < date('Y-m-d')) {
                throw new Exception("La fecha no puede ser anterior a hoy.");
            }

            $horarios = $this->service->getHorariosDisponibles((int) $medico_id, $fecha);
            echo json_encode(['success' => true, 'horarios' => $horarios]);
        } catch (Exception $e) {
            if ($e instanceof \PDOException || strpos($e->getMessage(), 'SQLSTATE') !== false) {
                error_log('[HospitAll] Error en AppointmentsController: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error interno del servidor. Intente de nuevo.']);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }

    public function handleSchedule()
    {
        $this->schedule($_POST);
    }

    public function schedule($data)
    {
        try {
            $this->service->schedule($data);
            $route = ($_SESSION['user_role'] === 'paciente') ? 'patient_portal' : 'appointments';
            UrlHelper::redirect($route, ['success' => '1']);
        } catch (Exception $e) {
            ErrorHandler::handle($e);
        }
    }

    public function getAttendData($id)
    {
        $cita = $this->service->getById($id);
        if (!$cita) {
            UrlHelper::redirect('dashboard', ['error' => 'cita_no_disponible']);
        }

        // Si es médico, validar que la cita le pertenezca
        if ($_SESSION['user_role'] === 'medico' && $cita['medico_id'] != $_SESSION['medico_id']) {
            UrlHelper::redirect('dashboard', ['error' => 'unauthorized']);
        }

        // No permitir abrir atención para citas canceladas o no asistidas
        if (in_array($cita['estado'], ['Cancelada', 'No asistió'])) {
            $route = ($_SESSION['user_role'] === 'medico') ? 'doctor_agenda' : 'appointments';
            UrlHelper::redirect($route, ['info' => 'cita_ya_completada']);
        }

        // Citas Atendidas: permitir solo para "Completar Diagnóstico" (lab terminó, falta actualizar diagnóstico)
        if ($cita['estado'] === 'Atendida') {
            $historial = $this->service->getHistorialPrevio($id);
            $permiteCompletar = $historial
                && strpos($historial['diagnostico'] ?? '', 'Pendiente') !== false
                && !$this->service->hasPendingLabOrder($historial['id']);
            if (!$permiteCompletar) {
                $route = ($_SESSION['user_role'] === 'medico') ? 'doctor_agenda' : 'appointments';
                UrlHelper::redirect($route, ['info' => 'cita_ya_completada']);
            }
        }

        // Flujo clínico automático: al abrir la atención, el paciente pasa a "En consulta"
        $estadoClinico = $cita['estado_clinico'] ?? '';
        if ($estadoClinico !== 'alta' && $estadoClinico !== 'observacion') {
            $this->service->updateEstadoClinico($id, 'en_consulta');
        }

        $historial_previo = $this->service->getHistorialPrevio($id);
        $resultados_lab = $this->service->getResultadosLab($cita['paciente_id']);
        $historial_completo = $this->service->getHistorialCompleto($id);

        return [
            'cita' => $cita,
            'historial_previo' => $historial_previo,
            'historial_completo' => $historial_completo,
            'resultados_lab' => $resultados_lab
        ];
    }

    public function handleUpdateStatus()
    {
        $id = (int) ($_POST['id'] ?? 0);
        $nuevo_estado = $_POST['nuevo_estado'] ?? '';
        $this->updateStatus($id, $nuevo_estado);
    }

    public function updateStatus($id, $nuevo_estado)
    {
        $this->service->updateStatus($id, $nuevo_estado);
        UrlHelper::redirect('appointments');
    }

    public function getReprogramData($id)
    {
        $cita = $this->service->getById($id);
        if (!$cita) {
            UrlHelper::redirect('appointments', ['error' => 'cita_no_disponible']);
        }
        if (in_array($cita['estado'], ['Atendida', 'Cancelada', 'No asistió'])) {
            UrlHelper::redirect('appointments', ['error' => 'no_reprogramable']);
        }
        return [
            'cita' => $cita,
            'pacientes' => $this->service->getPacientes(),
            'medicos' => $this->service->getMedicos()
        ];
    }

    public function handleReprogram()
    {
        $id = (int) ($_POST['cita_id'] ?? 0);
        $fecha = $_POST['fecha'] ?? '';
        $hora = $_POST['hora'] ?? '';
        if (!$id || !$fecha || !$hora) {
            UrlHelper::redirect('appointments_reprogram', ['id' => $id, 'error' => '1', 'msg' => 'Datos incompletos.']);
        }
        try {
            $this->service->reprogram($id, $fecha, $hora);
            UrlHelper::redirect('appointments', ['success' => 'reprogramada']);
        } catch (Exception $e) {
            UrlHelper::redirect('appointments_reprogram', ['id' => $id, 'error' => '1', 'msg' => $e->getMessage()]);
        }
    }

    public function handleSaveAttention()
    {
        $this->saveAttention($_POST);
    }

    public function saveAttention($data)
    {
        try {
            // Asegurar medico_id si el usuario es médico
            if ($_SESSION['user_role'] === 'medico') {
                $data['medico_id'] = $_SESSION['medico_id'];
            }

            // Validar que la cita pertenece al médico
            $cita = $this->service->getById($data['cita_id']);
            if (!$cita || $cita['medico_id'] != $data['medico_id']) {
                UrlHelper::redirect('dashboard', ['error' => 'unauthorized']);
            }

            $this->service->saveAttention($data);

            $route = ($_SESSION['user_role'] === 'medico') ? 'doctor_agenda' : 'appointments';
            $params = ['success_atencion' => '1'];
            if (($data['from'] ?? '') === 'history' && !empty($data['back_id'])) {
                $route = 'patient_portal';
                $params['patient_id'] = $data['back_id'];
            }

            UrlHelper::redirect($route, $params);
        } catch (Exception $e) {
            ErrorHandler::handle($e);
        }
    }

    public function getTodayByPatient()
    {
        header('Content-Type: application/json');
        $paciente_id = (int)($_GET['paciente_id'] ?? 0);
        if (!$paciente_id) {
            echo json_encode([]);
            return;
        }
        $citas = $this->service->getTodayByPatient($paciente_id);
        echo json_encode($citas);
    }
}

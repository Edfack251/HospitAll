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
}

<?php
require_once __DIR__ . '/../services/AppointmentsService.php';

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

    public function schedule($data)
    {
        try {
            $this->service->schedule($data);
            $redirect = ($_SESSION['user_role'] === 'Paciente') ? '../patient_portal.php?success=1' : '../appointments.php?success=1';
            header("Location: " . $redirect);
            exit();
        } catch (Exception $e) {
            die("Error al agendar la cita: " . $e->getMessage());
        }
    }

    public function getAttendData($id)
    {
        $cita = $this->service->getById($id);
        if (!$cita) {
            header("Location: dashboard.php?error=cita_no_disponible");
            exit();
        }

        $historial_previo = $this->service->getHistorialPrevio($id);
        $resultados_lab = $this->service->getResultadosLab($cita['paciente_id']);

        return [
            'cita' => $cita,
            'historial_previo' => $historial_previo,
            'resultados_lab' => $resultados_lab
        ];
    }

    public function updateStatus($id, $nuevo_estado)
    {
        $this->service->updateStatus($id, $nuevo_estado);
        header("Location: appointments.php");
        exit();
    }

    public function saveAttention($data)
    {
        try {
            $this->service->saveAttention($data);

            $redirect = ($_SESSION['user_role'] === 'medico') ? '../doctor_agenda.php' : '../appointments.php';
            if ($data['from'] === 'history' && !empty($data['back_id'])) {
                $redirect = '../patient_portal.php?patient_id=' . $data['back_id'];
            }

            header("Location: " . $redirect . "?success_atencion=1");
            exit();
        } catch (Exception $e) {
            die("Error al guardar la atención médica: " . $e->getMessage());
        }
    }
}

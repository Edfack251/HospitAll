<?php
namespace App\Controllers;

use App\Repositories\AppointmentRepository;
use App\Repositories\NursingRepository;
use App\Repositories\ImagingRepository;
use App\Repositories\LaboratoryRepository;
use App\Repositories\HospitalizationRepository;
use App\Services\DashboardService;
use Exception;

class DashboardController
{
    private $service;
    private $appointmentsRepo;
    private $laboratoryRepo;
    private $imagenesRepo;
    private $enfermeriaRepo;
    private $turnosRepo;
    private $hospitalizacionRepo;

    public function __construct($pdo)
    {
        $this->service = new DashboardService($pdo);
        $this->appointmentsRepo = new AppointmentRepository($pdo);
        $this->laboratoryRepo = new LaboratoryRepository($pdo);
        $this->imagenesRepo = new ImagingRepository($pdo);
        $this->enfermeriaRepo = new NursingRepository($pdo);
        $this->turnosRepo = new \App\Repositories\QueueRepository($pdo);
        $this->hospitalizacionRepo = new HospitalizationRepository($pdo);
    }

    /**
     * Datos exclusivos para el dashboard de enfermería.
     */
    public function getEnfermeraData()
    {
        $pacientesAsignados = [];
        $emergenciasActivas = [];
        $emergenciasHoy = [];
        $pacientes = [];

        try {
            $enfermera_id = null;
            if (($_SESSION['user_role'] ?? '') === 'enfermera') {
                $enfermera_id = (int) ($_SESSION['user_id'] ?? 0);
            }
            $pacientesAsignados = $this->enfermeriaRepo->getPacientesAsignados($enfermera_id);
        } catch (\Throwable $e) {
            // Tablas citas/atenciones pueden no existir o tener estructura distinta
        }
        try {
            $emergenciasActivas = $this->enfermeriaRepo->getEmergenciasActivas();
            $emergenciasHoy = $this->enfermeriaRepo->getEmergenciasHoy();
        } catch (\Throwable $e) {
            // Tabla emergencias puede no existir
        }
        try {
            $pacientes = $this->enfermeriaRepo->getPacientesParaSelect();
        } catch (\Throwable $e) {}

        $internamientosActivos = [];
        try {
            $internamientosActivos = $this->hospitalizacionRepo->getInternamientosActivos();
        } catch (\Throwable $e) {}

        return [
            'pacientesAsignados' => $pacientesAsignados,
            'emergenciasActivas' => $emergenciasActivas,
            'emergenciasHoy' => $emergenciasHoy,
            'pacientes' => $pacientes,
            'internamientosActivos' => $internamientosActivos
        ];
    }

    /**
     * Datos exclusivos para el dashboard de técnico de imágenes médicas.
     */
    public function getTecnicoImagenesData()
    {
        return [
            'ordenesPendientes' => $this->imagenesRepo->getOrdenesPendientes(),
            'ordenesEnProceso' => $this->imagenesRepo->getOrdenesEnProceso(),
            'ordenesCompletadasHoy' => $this->imagenesRepo->getOrdenesCompletadasHoy(),
            'turnoActual' => $this->turnosRepo->getTurnoActual('imagenes'),
            'turnosEsperandoCount' => count($this->turnosRepo->getTurnosEsperando('imagenes'))
        ];
    }

    /**
     * Datos exclusivos para el dashboard de técnico de laboratorio.
     */
    public function getTecnicoLaboratorioData()
    {
        return [
            'ordenesPendientes' => $this->laboratoryRepo->getOrdenesPendientes(),
            'ordenesEnProceso' => $this->laboratoryRepo->getOrdenesEnProceso(),
            'ordenesCompletadasHoy' => $this->laboratoryRepo->getOrdenesCompletadasHoy(),
            'turnoActual' => $this->turnosRepo->getTurnoActual('laboratorio'),
            'turnosEsperandoCount' => count($this->turnosRepo->getTurnosEsperando('laboratorio'))
        ];
    }

    /**
     * Datos exclusivos para el dashboard de recepcionista.
     * Usar este método en dashboard_receptionist.php para garantizar los datos correctos.
     */
    public function getRecepcionistaData()
    {
        return [
            'citasHoy' => $this->appointmentsRepo->getCitasHoy(),
            'pacientesEnEspera' => $this->appointmentsRepo->getCitasPorEstadosHoy(['Programada', 'Confirmada', 'En espera']),
            'proximasCitas' => $this->appointmentsRepo->getProximasCitasFuturas(15),
            'pacientesAtendidos' => $this->appointmentsRepo->getCitasPorEstadoHoy('Atendida')
        ];
    }

    public function index()
    {
        $role = $_SESSION['user_role'] ?? '';

        if ($role === 'paciente') {
            \App\Helpers\UrlHelper::redirect('patient_portal');
        }

        if ($role === 'recepcionista') {
            return $this->getRecepcionistaData();
        }

        if ($role === 'tecnico_laboratorio') {
            return $this->getTecnicoLaboratorioData();
        }

        if ($role === 'tecnico_imagenes') {
            return $this->getTecnicoImagenesData();
        }

        if ($role === 'enfermera') {
            return $this->getEnfermeraData();
        }

        if ($role === 'farmaceutico') {
            return [
                'bajoStock' => $this->service->getBajoStock(),
                'prescripcionesPendientes' => $this->service->getPrescripcionesPendientes(),
                'ventasRecientes' => $this->service->getVentasRecientes(10),
                'turnoActual' => $this->turnosRepo->getTurnoActual('farmacia'),
                'turnosEsperandoCount' => count($this->turnosRepo->getTurnosEsperando('farmacia'))
            ];
        }

        if ($role !== 'administrador' && $role !== 'medico') {
            \App\Helpers\UrlHelper::redirect('login');
        }

        // Default Admin/Staff data
        return [
            'totalPacientes' => $this->service->getTotalPacientes(),
            'totalMedicos' => $this->service->getTotalMedicos(),
            'citasHoy' => $this->service->getCitasHoy(),
            'proximasCitas' => $this->service->getProximasCitas(10)
        ];
    }
}

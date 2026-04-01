<?php
namespace App\Services;

class AdminDashboardService
{
    private $patientRepository;
    private $appointmentsRepository;
    private $billingRepository;
    private $pharmacyRepository;
    private $laboratoryRepository;
    private $imagenesRepository;
    private $enfermeriaRepository;
    private $logRepository;

    public function __construct(
        $patientRepository,
        $appointmentsRepository,
        $billingRepository,
        $pharmacyRepository,
        $laboratoryRepository,
        $imagenesRepository,
        $enfermeriaRepository,
        $logRepository
    ) {
        $this->patientRepository = $patientRepository;
        $this->appointmentsRepository = $appointmentsRepository;
        $this->billingRepository = $billingRepository;
        $this->pharmacyRepository = $pharmacyRepository;
        $this->laboratoryRepository = $laboratoryRepository;
        $this->imagenesRepository = $imagenesRepository;
        $this->enfermeriaRepository = $enfermeriaRepository;
        $this->logRepository = $logRepository;
    }

    public function getDashboardData()
    {
        $emergenciasActivas = [];
        $emergenciasHoy = [];
        $imagenesPendientes = [];
        $imagenesEnProceso = [];

        try {
            $emergenciasActivas = $this->enfermeriaRepository->getEmergenciasActivas();
            $emergenciasHoy = $this->enfermeriaRepository->getEmergenciasHoy();
        } catch (\Throwable $e) {
            // Tabla emergencias puede no existir si no se ejecutó migrate_emergencias.sql
        }

        try {
            $imagenesPendientes = $this->imagenesRepository->getOrdenesPendientes();
            $imagenesEnProceso = $this->imagenesRepository->getOrdenesEnProceso();
        } catch (\Throwable $e) {
            // Tabla ordenes_imagenes puede no existir si no se ejecutó migrate_imagenes_medicas.sql
        }

        return [
            'patient_count' => $this->patientRepository->getPatientCount(),
            'today_appointments' => $this->appointmentsRepository->getTodayAppointmentsCount(),
            'today_attended' => $this->appointmentsRepository->getTodayAttendedCount(),
            'today_revenue' => $this->billingRepository->getTodayRevenue(),
            'pending_invoices' => $this->billingRepository->getPendingInvoicesCount(),
            'low_stock_medicines' => $this->pharmacyRepository->getLowStockCount(),
            'pending_lab_results' => $this->laboratoryRepository->getPendingResultsCount(),
            'emergencias_activas_count' => count($emergenciasActivas),
            'emergencias_hoy_count' => count($emergenciasHoy),
            'emergencias_activas' => $emergenciasActivas,
            'imagenes_pendientes_count' => count($imagenesPendientes),
            'imagenes_en_proceso_count' => count($imagenesEnProceso),
            'recent_logs' => $this->logRepository->getRecentLogs(5)
        ];
    }
}

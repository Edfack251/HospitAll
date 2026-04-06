<?php
namespace App\Services;

class AdminDashboardService
{
    private $patientRepository;
    private $appointmentsRepository;
    private $billingRepository;
    private $pharmacyRepository;
    private $laboratoryRepository;
    private $logRepository;

    public function __construct(
        $patientRepository,
        $appointmentsRepository,
        $billingRepository,
        $pharmacyRepository,
        $laboratoryRepository,
        $logRepository
    ) {
        $this->patientRepository = $patientRepository;
        $this->appointmentsRepository = $appointmentsRepository;
        $this->billingRepository = $billingRepository;
        $this->pharmacyRepository = $pharmacyRepository;
        $this->laboratoryRepository = $laboratoryRepository;
        $this->logRepository = $logRepository;
    }

    public function getDashboardData()
    {
        return [
            'patient_count' => $this->patientRepository->getPatientCount(),
            'today_appointments' => $this->appointmentsRepository->getTodayAppointmentsCount(),
            'today_attended' => $this->appointmentsRepository->getTodayAttendedCount(),
            'today_revenue' => $this->billingRepository->getTodayRevenue(),
            'pending_invoices' => $this->billingRepository->getPendingInvoicesCount(),
            'low_stock_medicines' => $this->pharmacyRepository->getLowStockCount(),
            'pending_lab_results' => $this->laboratoryRepository->getPendingResultsCount(),
            'recent_logs' => $this->logRepository->getRecentLogs(5)
        ];
    }
}

<?php
namespace App\Controllers;

use App\Config\Database;
use App\Policies\PolicyManager;
use App\Services\AdminDashboardService;
use App\Repositories\PatientRepository;
use App\Repositories\AppointmentRepository;
use App\Repositories\BillingRepository;
use App\Repositories\PharmacyRepository;
use App\Repositories\LaboratoryRepository;
use App\Repositories\ImagingRepository;
use App\Repositories\NursingRepository;
use App\Repositories\LogRepository;

class AdminDashboardController
{
    private $pdo;
    private $adminDashboardService;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $patientRepo = new PatientRepository($pdo);
        $appointmentsRepo = new AppointmentRepository($pdo);
        $billingRepo = new BillingRepository($pdo);
        $pharmacyRepo = new PharmacyRepository($pdo);
        $labRepo = new LaboratoryRepository($pdo);
        $imagenesRepo = new ImagingRepository($pdo);
        $enfermeriaRepo = new NursingRepository($pdo);
        $logRepo = new LogRepository($pdo);

        $this->adminDashboardService = new AdminDashboardService(
            $patientRepo,
            $appointmentsRepo,
            $billingRepo,
            $pharmacyRepo,
            $labRepo,
            $imagenesRepo,
            $enfermeriaRepo,
            $logRepo
        );
    }

    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            \App\Helpers\UrlHelper::redirect('login');
        }

        $user = $_SESSION['user'] ?? null;

        PolicyManager::authorize($user, 'view_admin_dashboard');

        $data = $this->adminDashboardService->getDashboardData();

        require_once __DIR__ . '/../../views/admin_dashboard.php';
    }
}

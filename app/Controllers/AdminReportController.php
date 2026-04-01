<?php
namespace App\Controllers;

use App\Config\Database;
use App\Policies\PolicyManager;
use App\Services\AdminReportService;
use App\Repositories\AppointmentRepository;
use App\Repositories\BillingRepository;
use App\Repositories\PatientRepository;
use App\Repositories\PharmacyRepository;
use App\Repositories\LaboratoryRepository;

class AdminReportController
{
    private $pdo;
    private $adminReportService;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        
        $appointmentsRepo = new AppointmentRepository($pdo);
        $billingRepo = new BillingRepository($pdo);
        $patientRepo = new PatientRepository($pdo);
        $pharmacyRepo = new PharmacyRepository($pdo);
        $laboratoryRepo = new LaboratoryRepository($pdo);

        $this->adminReportService = new AdminReportService(
            $appointmentsRepo,
            $billingRepo,
            $patientRepo,
            $pharmacyRepo,
            $laboratoryRepo
        );
    }

    public function generateMonthlyReport()
    {
        // 1. Validar acceso
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Sesión no iniciada']);
            return;
        }

        try {
            PolicyManager::authorize($user, 'view_admin_dashboard');
        } catch (\App\Core\Exceptions\AuthorizationException $e) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            return;
        }

        // 2. Validar parámetros de entrada
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

        if ($year < 2000 || $year > 2100) {
            $year = (int)date('Y');
        }

        if ($month < 1 || $month > 12) {
            $month = (int)date('n');
        }

        // 3. Generar PDF
        $userName = ($user['nombre'] ?? 'Administrador') . ' ' . ($user['apellido'] ?? '');
        $pdfContent = $this->adminReportService->generateMonthlyReportPdf($year, $month, trim($userName));

        // 4. Enviar respuesta como PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="reporte_mensual_' . $year . '_' . $month . '.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $pdfContent;
    }
}

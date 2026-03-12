<?php
namespace App\Controllers;

use App\Services\DoctorDashboardService;
use App\Policies\PolicyManager;
use Exception;

class DoctorDashboardController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new DoctorDashboardService($pdo);
    }

    public function index()
    {
        try {
            // Verificar autorización
            PolicyManager::authorize($_SESSION['user'] ?? [], 'view_doctor_dashboard');

            if (!isset($_SESSION['user_id'])) {
                header("Location: /login.php");
                exit;
            }

            $usuario_id = $_SESSION['user_id'];
            $dashboardData = $this->service->getDashboardData($usuario_id);

            // Si es una petición AJAX/API, devuelvo JSON.
            // Para la vista, extraigo variables e incluyo el archivo PHP.
            extract($dashboardData);
            require_once __DIR__ . '/../../views/doctor_dashboard.php';

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: /views/dashboard.php");
            exit;
        }
    }
}

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
            PolicyManager::authorize($_SESSION, 'view_doctor_dashboard');

            if (!isset($_SESSION['user_id'])) {
                \App\Helpers\UrlHelper::redirect('login');
            }

            $usuario_id = $_SESSION['user_id'];
            $dashboardData = $this->service->getDashboardData($usuario_id);

            // Si es una petición AJAX/API, devuelvo JSON.
            // Para la vista, extraigo variables e incluyo el archivo PHP.
            extract($dashboardData);
            require_once __DIR__ . '/../../views/pages/doctor_dashboard.php';

        } catch (Exception $e) {
            // Log del error
            error_log('DoctorDashboard error: ' . $e->getMessage());
            
            // Mostrar página de error en lugar de redirigir para evitar loops infinitos
            // si el login vuelve a redirigir al dashboard.
            http_response_code(500);
            echo '<div style="padding:40px; font-family:sans-serif; text-align:center;">';
            echo '<h1 style="color:#DC3545;">Error en el Dashboard</h1>';
            echo '<p style="font-size:18px;">Hubo un problema al cargar los datos del médico.</p>';
            echo '<div style="background:#F8D7DA; color:#721C24; padding:20px; border-radius:8px; display:inline-block; margin:20px 0; text-align:left;">';
            echo '<strong>Detalles:</strong> ' . htmlspecialchars($e->getMessage());
            echo '</div>';
            echo '<br><a href="' . \App\Helpers\UrlHelper::url('logout') . '" style="color:#007BFF; text-decoration:none; font-weight:bold;">Cerrar sesión e intentar de nuevo</a>';
            echo '</div>';
            exit;
        }
    }
}

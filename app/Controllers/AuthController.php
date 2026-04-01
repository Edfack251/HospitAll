<?php
namespace App\Controllers;

use App\Helpers\UrlHelper;
use App\Services\AuthService;

class AuthController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $correo_electronico = $_POST['correo_electronico'] ?? '';
            $password = $_POST['password'] ?? '';

            $authService = new AuthService($this->pdo);
            $user = $authService->attemptLogin($correo_electronico, $password);

            if ($user) {
                switch ($_SESSION['user_role']) {
                    case 'paciente':
                        UrlHelper::redirect('patient_portal');
                        break;
                    case 'medico':
                        UrlHelper::redirect('api/doctor/dashboard');
                        break;
                    case 'recepcionista':
                        UrlHelper::redirect('dashboard_receptionist');
                        break;
                    case 'tecnico_laboratorio':
                        UrlHelper::redirect('dashboard_laboratory');
                        break;
                    case 'tecnico_imagenes':
                        UrlHelper::redirect('dashboard_imaging');
                        break;
                    case 'enfermera':
                        UrlHelper::redirect('dashboard_nursing');
                        break;
                    case 'farmaceutico':
                        UrlHelper::redirect('dashboard');
                        break;
                    case 'administrador':
                        UrlHelper::redirect('api/admin/dashboard');
                        break;
                    default:
                        UrlHelper::redirect('login', ['error' => 'rol_invalido']);
                        break;
                }
            } else {
                UrlHelper::redirect('login', ['error' => 'invalid_credentials']);
            }
        }
    }
}

<?php
namespace App\Controllers;

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
                        header("Location: ../patient_portal.php");
                        break;
                    case 'medico':
                        header("Location: ../doctor_agenda.php");
                        break;
                    case 'recepcionista':
                        header("Location: ../appointments.php");
                        break;
                    case 'tecnico_laboratorio':
                        header("Location: ../laboratory.php");
                        break;
                    case 'administrador':
                        header("Location: ../dashboard.php");
                        break;
                    default:
                        header("Location: ../login.php?error=rol_invalido");
                        break;
                }
                exit();
            } else {
                header("Location: ../login.php?error=invalid_credentials");
                exit();
            }
        }
    }
}

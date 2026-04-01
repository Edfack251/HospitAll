<?php
namespace App\Controllers;

use App\Core\ErrorHandler;
use App\Helpers\UrlHelper;
use App\Services\AuthService;
use Exception;

class RegisterController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authService = new AuthService($this->pdo);

            try {
                $authService->registerPatient([
                    'nombre' => $_POST['nombre'] ?? '',
                    'apellido' => $_POST['apellido'] ?? '',
                    'identificacion' => $_POST['identificacion'] ?? '',
                    'identificacion_tipo' => $_POST['identificacion_tipo'] ?? 'Cédula',
                    'genero' => $_POST['genero'] ?? 'Otro',
                    'telefono' => $_POST['telefono'] ?? '',
                    'correo_electronico' => $_POST['correo_electronico'] ?? '',
                    'password' => $_POST['password'] ?? ''
                ]);

                UrlHelper::redirect('login', ['registered' => '1']);
            } catch (Exception $e) {
                ErrorHandler::handle($e);
            }
        }
    }
}

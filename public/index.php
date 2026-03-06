<?php
session_start();
require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;
use App\Core\Router;

try {
    $pdo = Database::getConnection();
    $router = new Router($pdo);

    // Definición de Rutas con Middlewares (Auth, Role, Csrf)
    $router->add('/patients', 'PatientsController@index', ['AuthMiddleware', 'CsrfMiddleware']);
    $router->add('/doctors', 'DoctorsController@index', ['AuthMiddleware', ['RoleMiddleware' => ['administrador']]]);
    $router->add('/appointments', 'AppointmentsController@index', ['AuthMiddleware']);
    $router->add('/pharmacy', 'PharmacyController@index', ['AuthMiddleware', ['RoleMiddleware' => ['farmaceutico', 'administrador']]]);

    // Obtener la URL actual y despachar
    $requestUri = $_SERVER['REQUEST_URI'];

    // Si el router no maneja la ruta, dejamos que el servidor busque archivos .php
    // (Compatibilidad con el sistema actual)
    if (!$router->dispatch($requestUri)) {
        // Si es la raíz, redirigir al login o dashboard
        if ($requestUri === '/' || $requestUri === '/index.php') {
            header("Location: login.php");
            exit();
        }
    }

} catch (Exception $e) {
    \App\Core\ErrorHandler::handle($e);
}

<?php
session_start();
require_once '../../app/autoload.php';
$pdo = \App\Config\Database::getConnection();


use App\Controllers\PatientsController;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;

AuthHelper::checkRole(['administrador', 'recepcionista']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die("Token CSRF inválido.");
    }
    $id = isset($_POST['id']) && is_numeric($_POST['id'])
        ? (int) $_POST['id']
        : null;

    if (!$id) {
        header("Location: ../patients.php?error=invalid_id");
        exit();
    }

    $controller = new PatientsController($pdo);
    $controller->update($id, [
        'nombre' => $_POST['nombre'] ?? '',
        'apellido' => $_POST['apellido'] ?? '',
        'identificacion' => $_POST['identificacion'] ?? '',
        'identificacion_tipo' => $_POST['identificacion_tipo'] ?? 'Cédula',
        'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? '',
        'genero' => $_POST['genero'] ?? '',
        'direccion' => $_POST['direccion'] ?? '',
        'telefono' => $_POST['telefono'] ?? '',
        'correo_electronico' => $_POST['correo_electronico'] ?? ''
    ]);
}
?>
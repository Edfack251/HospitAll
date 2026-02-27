<?php
session_start();
require_once '../../app/config/database.php';
require_once '../../app/helpers/auth_helper.php';
require_once '../../app/autoload.php';

checkRole(['administrador', 'recepcionista']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new PatientsController($pdo);
    $controller->create([
        'nombre' => $_POST['nombre'] ?? '',
        'apellido' => $_POST['apellido'] ?? '',
        'identificacion' => $_POST['identificacion'] ?? '',
        'identificacion_tipo' => $_POST['identificacion_tipo'] ?? 'Cédula',
        'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? '',
        'genero' => $_POST['genero'] ?? '',
        'direccion' => $_POST['direccion'] ?? '',
        'telefono' => $_POST['telefono'] ?? '',
        'correo_electronico' => $_POST['correo_electronico'] ?? '',
        'password' => $_POST['password'] ?? ''
    ]);
}
?>
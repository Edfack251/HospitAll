<?php
session_start();
require_once '../../app/config/database.php';
require_once '../../app/controllers/PatientsController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
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
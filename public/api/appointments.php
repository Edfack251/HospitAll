<?php
session_start();
require_once '../../app/config/database.php';
require_once '../../app/controllers/AppointmentsController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new AppointmentsController($pdo);
    $controller->schedule([
        'paciente_id' => $_POST['paciente_id'] ?? '',
        'medico_id' => $_POST['medico_id'] ?? '',
        'fecha' => $_POST['fecha'] ?? '',
        'hora' => $_POST['hora'] ?? '',
        'observaciones' => $_POST['observaciones'] ?? ''
    ]);
}
?>
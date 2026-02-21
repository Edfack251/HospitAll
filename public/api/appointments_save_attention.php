<?php
session_start();
require_once '../../app/config/database.php';
require_once '../../app/controllers/AppointmentsController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new AppointmentsController($pdo);
    $controller->saveAttention([
        'cita_id' => $_POST['cita_id'] ?? '',
        'paciente_id' => $_POST['paciente_id'] ?? '',
        'medico_id' => $_POST['medico_id'] ?? '',
        'diagnostico' => $_POST['diagnostico'] ?? '',
        'tratamiento' => $_POST['tratamiento'] ?? '',
        'observaciones_clinicas' => $_POST['observaciones_clinicas'] ?? '',
        'laboratorio_descripcion' => $_POST['laboratorio_descripcion'] ?? '',
        'enviar_lab' => isset($_POST['enviar_laboratorio']),
        'from' => $_POST['from'] ?? '',
        'back_id' => $_POST['back_id'] ?? ''
    ]);
}
?>
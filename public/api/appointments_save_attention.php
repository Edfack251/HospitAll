<?php
session_start();
require_once '../../app/config/database.php';
require_once '../../app/controllers/AppointmentsController.php';

require_once '../../app/helpers/auth_helper.php';
checkRole(['administrador', 'medico']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cita_id = isset($_POST['cita_id']) && is_numeric($_POST['cita_id'])
        ? (int) $_POST['cita_id']
        : null;
    $paciente_id = isset($_POST['paciente_id']) && is_numeric($_POST['paciente_id'])
        ? (int) $_POST['paciente_id']
        : null;
    $medico_id = isset($_POST['medico_id']) && is_numeric($_POST['medico_id'])
        ? (int) $_POST['medico_id']
        : null;
    $back_id = isset($_POST['back_id']) && is_numeric($_POST['back_id'])
        ? (int) $_POST['back_id']
        : null;

    if (!$cita_id || !$paciente_id || !$medico_id) {
        header("Location: ../appointments.php");
        exit();
    }

    $controller = new AppointmentsController($pdo);
    $controller->saveAttention([
        'cita_id' => $cita_id,
        'paciente_id' => $paciente_id,
        'medico_id' => $medico_id,
        'diagnostico' => $_POST['diagnostico'] ?? '',
        'tratamiento' => $_POST['tratamiento'] ?? '',
        'observaciones_clinicas' => $_POST['observaciones_clinicas'] ?? '',
        'laboratorio_descripcion' => $_POST['laboratorio_descripcion'] ?? '',
        'enviar_lab' => isset($_POST['enviar_laboratorio']),
        'from' => $_POST['from'] ?? '',
        'back_id' => $back_id
    ]);
}
?>
<?php
session_start();
require_once '../../app/autoload.php';
$pdo = \App\Config\Database::getConnection();


use App\Controllers\AppointmentsController;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;

AuthHelper::requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die("Token CSRF inválido.");
    }
    $paciente_id = isset($_POST['paciente_id']) && is_numeric($_POST['paciente_id'])
        ? (int) $_POST['paciente_id']
        : null;
    $medico_id = isset($_POST['medico_id']) && is_numeric($_POST['medico_id'])
        ? (int) $_POST['medico_id']
        : null;

    if (!$paciente_id || !$medico_id) {
        header("Location: ../appointments.php");
        exit();
    }

    $controller = new AppointmentsController($pdo);
    $controller->schedule([
        'paciente_id' => $paciente_id,
        'medico_id' => $medico_id,
        'fecha' => $_POST['fecha'] ?? '',
        'hora' => $_POST['hora'] ?? '',
        'observaciones' => $_POST['observaciones'] ?? ''
    ]);
}
?>
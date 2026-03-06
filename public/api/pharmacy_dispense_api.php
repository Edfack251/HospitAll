<?php
session_start();
require_once '../../app/autoload.php';

use App\Controllers\PharmacyController;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;

AuthHelper::checkRole(['administrador', 'farmaceutico']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die("Token CSRF inválido.");
    }

    $pdo = \App\Config\Database::getConnection();
    $controller = new PharmacyController($pdo);

    $controller->dispense([
        'paciente_id' => $_POST['paciente_id'] ?? null,
        'medicamento_id' => $_POST['medicamento_id'] ?? null,
        'cantidad' => $_POST['cantidad'] ?? null,
    ]);
} else {
    header("Location: ../pharmacy.php");
    exit();
}

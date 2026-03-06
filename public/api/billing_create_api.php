<?php
session_start();
require_once '../../app/autoload.php';
$pdo = \App\Config\Database::getConnection();

use App\Controllers\BillingController;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;

// Validar Rol de quien lo ejecuta (Control Base)
AuthHelper::checkRole(['administrador', 'recepcionista', 'farmaceutico']);

// Si la petición es POST, verificamos token y derivamos al controlador
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die("Token CSRF inválido.");
    }

    $paciente_id = $_POST['paciente_id'] ?? null;

    if (empty($paciente_id)) {
        header("Location: ../billing.php?error=1&msg=" . urlencode("El ID del paciente es obligatorio."));
        exit();
    }

    $controller = new BillingController($pdo);
    $controller->createInvoice((int) $paciente_id);
} else {
    header("Location: ../billing.php");
    exit();
}

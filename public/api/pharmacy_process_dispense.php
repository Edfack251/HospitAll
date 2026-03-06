<?php
session_start();
require_once '../../app/autoload.php';

use App\Controllers\PharmacyController;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;

AuthHelper::checkRole(['farmaceutico', 'administrador']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die("Token CSRF inválido.");
    }

    $prescripcion_id = $_POST['prescripcion_id'] ?? null;
    $items = $_POST['items'] ?? [];

    if (!$prescripcion_id) {
        header("Location: ../pharmacy.php?error=data_missing");
        exit();
    }

    $pdo = \App\Config\Database::getConnection();
    $controller = new PharmacyController($pdo);

    $controller->processDispense([
        'prescripcion_id' => $prescripcion_id,
        'items' => $items
    ]);
} else {
    header("Location: ../pharmacy.php");
    exit();
}

<?php
session_start();
require_once '../../app/autoload.php';

use App\Controllers\BillingController;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;

// Validar que el que paga es Admin, Recepcionista o Farmacéutico
AuthHelper::checkRole(['administrador', 'recepcionista', 'farmaceutico']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die("Token CSRF inválido.");
    }

    $factura_id = (int) ($_POST['factura_id'] ?? 0);
    $metodo_pago = $_POST['metodo_pago'] ?? 'Efectivo';

    $pdo = \App\Config\Database::getConnection();
    $controller = new BillingController($pdo);

    try {
        $controller->pay($factura_id, $metodo_pago);
    } catch (\Exception $e) {
        header("Location: ../billing_details.php?id=" . $factura_id . "&error=1&msg=" . urlencode($e->getMessage()));
    }
    exit();
} else {
    header("Location: ../billing.php");
    exit();
}

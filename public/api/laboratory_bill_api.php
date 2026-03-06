<?php
session_start();
require_once '../../app/autoload.php';

use App\Services\BillingService;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;

// Validar Rol Administrativo o Recepcionista (los técnicos no cobran)
AuthHelper::checkRole(['administrador', 'recepcionista']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die("Token CSRF inválido.");
    }

    $paciente_id = (int) ($_POST['paciente_id'] ?? 0);
    $orden_id = (int) ($_POST['orden_id'] ?? 0);
    $descripcion_lab = $_POST['descripcion'] ?? 'Análisis Clínico Standard';

    if ($paciente_id <= 0 || $orden_id <= 0) {
        header("Location: ../laboratory.php?error=1&msg=" . urlencode("Parámetros de facturación inválidos."));
        exit();
    }

    $pdo = \App\Config\Database::getConnection();

    try {
        $pdo->beginTransaction();

        $billingService = new BillingService($pdo);
        $factura_id = $billingService->createLaboratoryInvoice($paciente_id, $orden_id, $descripcion_lab);

        $pdo->commit();

        // Redirigir directamente al detalle de la factura para que la recepcionista pueda liquidarla o imprimirla.
        header("Location: ../billing_details.php?id=" . $factura_id . "&success=item_added");
        exit();

    } catch (\Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: ../laboratory.php?error=1&msg=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: ../laboratory.php");
    exit();
}

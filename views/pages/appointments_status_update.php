<?php
use App\Helpers\UrlHelper;
use App\Controllers\AppointmentsController;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;

AuthHelper::checkRole(['administrador', 'recepcionista']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die("Token CSRF inválido.");
    }
    $id = isset($_POST['id']) && is_numeric($_POST['id'])
        ? (int) $_POST['id']
        : null;

    if (!$id) {
        UrlHelper::redirect('appointments');
    }

    $nuevo_estado = $_POST['nuevo_estado'] ?? '';

    if (!empty($nuevo_estado)) {
        $controller = new AppointmentsController($pdo);
        $controller->updateStatus($id, $nuevo_estado);
    } else {
        UrlHelper::redirect('appointments');
    }
}
?>
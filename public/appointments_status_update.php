<?php
session_start();
require_once '../app/config/database.php';
require_once '../app/controllers/AppointmentsController.php';

require_once '../app/helpers/auth_helper.php';

checkRole(['administrador', 'recepcionista']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) && is_numeric($_POST['id'])
        ? (int) $_POST['id']
        : null;

    if (!$id) {
        header("Location: appointments.php");
        exit();
    }

    $nuevo_estado = $_POST['nuevo_estado'] ?? '';

    if (!empty($nuevo_estado)) {
        $controller = new AppointmentsController($pdo);
        $controller->updateStatus($id, $nuevo_estado);
    } else {
        header("Location: appointments.php");
        exit();
    }
}
?>
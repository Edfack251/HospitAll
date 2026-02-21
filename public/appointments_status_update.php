<?php
session_start();
require_once '../app/config/database.php';
require_once '../app/controllers/AppointmentsController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';

    if (!empty($id) && !empty($nuevo_estado)) {
        $controller = new AppointmentsController($pdo);
        $controller->updateStatus($id, $nuevo_estado);
    } else {
        header("Location: appointments.php");
        exit();
    }
}
?>
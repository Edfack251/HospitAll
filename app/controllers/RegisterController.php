<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/AuthService.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authService = new AuthService($pdo);

    try {
        $authService->registerPatient([
            'nombre' => $_POST['nombre'] ?? '',
            'apellido' => $_POST['apellido'] ?? '',
            'identificacion' => $_POST['identificacion'] ?? '',
            'identificacion_tipo' => $_POST['identificacion_tipo'] ?? 'Cédula',
            'genero' => $_POST['genero'] ?? 'Otro',
            'telefono' => $_POST['telefono'] ?? '',
            'correo_electronico' => $_POST['correo_electronico'] ?? '',
            'password' => $_POST['password'] ?? ''
        ]);

        header("Location: ../login.php?registered=1");
        exit();
    } catch (Exception $e) {
        die("Error en el registro: " . $e->getMessage());
    }
}
?>
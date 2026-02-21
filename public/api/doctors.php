<?php
session_start();
require_once '../../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $especialidad = $_POST['especialidad'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $correo_electronico = $_POST['correo_electronico'] ?? '';

    try {
        $sql = "INSERT INTO medicos (nombre, apellido, especialidad, telefono, correo_electronico) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $apellido, $especialidad, $telefono, $correo_electronico]);

        header("Location: ../doctors.php?success=1");
        exit();
    } catch (PDOException $e) {
        die("Error al guardar el médico: " . $e->getMessage());
    }
}
?>
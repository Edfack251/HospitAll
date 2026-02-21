<?php
session_start();
require_once '../../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $identificacion = $_POST['identificacion'] ?? '';
    $identificacion_tipo = $_POST['identificacion_tipo'] ?? 'Cédula';
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
    $genero = $_POST['genero'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $correo_electronico = $_POST['correo_electronico'] ?? '';

    try {
        $sql = "UPDATE pacientes SET nombre=?, apellido=?, identificacion=?, identificacion_tipo=?, fecha_nacimiento=?, genero=?, direccion=?, telefono=?, correo_electronico=? 
                WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $apellido, $identificacion, $identificacion_tipo, $fecha_nacimiento, $genero, $direccion, $telefono, $correo_electronico, $id]);

        header("Location: ../patients.php?updated=1");
        exit();
    } catch (PDOException $e) {
        die("Error al actualizar el paciente: " . $e->getMessage());
    }
}
?>
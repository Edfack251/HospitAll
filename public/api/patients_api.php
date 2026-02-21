<?php
session_start();
require_once '../../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $identificacion = $_POST['identificacion'] ?? '';
    $identificacion_tipo = $_POST['identificacion_tipo'] ?? 'Cédula';
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
    $genero = $_POST['genero'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $correo_electronico = $_POST['correo_electronico'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($nombre) || empty($apellido) || empty($identificacion) || empty($correo_electronico) || empty($password)) {
        die("Todos los campos marcados con (*) son obligatorios, incluyendo la contraseña para el portal.");
    }

    try {
        $pdo->beginTransaction();

        // 1. Crear el usuario para el portal
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Obtener el ID del rol 'Paciente'
        $stmt_rol = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'Paciente' LIMIT 1");
        $stmt_rol->execute();
        $rol_id = $stmt_rol->fetchColumn() ?: 1;

        $sql_usuario = "INSERT INTO usuarios (nombre, apellido, correo_electronico, password, rol_id) VALUES (?, ?, ?, ?, ?)";
        $stmt_usuario = $pdo->prepare($sql_usuario);
        $stmt_usuario->execute([$nombre, $apellido, $correo_electronico, $password_hash, $rol_id]);
        $usuario_id = $pdo->lastInsertId();

        // 2. Crear el paciente vinculado
        $sql_paciente = "INSERT INTO pacientes (usuario_id, nombre, apellido, identificacion, identificacion_tipo, fecha_nacimiento, genero, direccion, telefono, correo_electronico) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_paciente = $pdo->prepare($sql_paciente);
        $stmt_paciente->execute([$usuario_id, $nombre, $apellido, $identificacion, $identificacion_tipo, $fecha_nacimiento, $genero, $direccion, $telefono, $correo_electronico]);

        $pdo->commit();
        header("Location: ../patients.php?success=1");
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($e->getCode() == 23000) {
            die("Error: El correo electrónico o la identificación ya están registrados.");
        }
        die("Error al guardar el paciente: " . $e->getMessage());
    }
}
?>
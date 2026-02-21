<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $identificacion = $_POST['identificacion'] ?? '';
    $identificacion_tipo = $_POST['identificacion_tipo'] ?? 'Cédula';
    $genero = $_POST['genero'] ?? 'Otro';
    $telefono = $_POST['telefono'] ?? '';
    $correo_electronico = $_POST['correo_electronico'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($nombre) || empty($apellido) || empty($identificacion) || empty($correo_electronico) || empty($password)) {
        die("Todos los campos son obligatorios.");
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        // Obtener el ID del rol 'paciente'
        $stmt_rol = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'paciente' LIMIT 1");
        $stmt_rol->execute();
        $rol_id = $stmt_rol->fetchColumn();

        if (!$rol_id) {
            // Fallback en caso de que aún no se haya ejecutado init_roles.php
            $stmt_rol = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'Paciente' LIMIT 1");
            $stmt_rol->execute();
            $rol_id = $stmt_rol->fetchColumn() ?: 5;
        }

        // 1. Crear el usuario
        $sql_usuario = "INSERT INTO usuarios (nombre, apellido, correo_electronico, password, rol_id) VALUES (?, ?, ?, ?, ?)";
        $stmt_usuario = $pdo->prepare($sql_usuario);
        $stmt_usuario->execute([$nombre, $apellido, $correo_electronico, $password_hash, $rol_id]);
        $usuario_id = $pdo->lastInsertId();

        // 2. Crear el paciente asociado
        $sql_paciente = "INSERT INTO pacientes (usuario_id, identificacion, identificacion_tipo, nombre, apellido, correo_electronico, fecha_nacimiento, genero, telefono) VALUES (?, ?, ?, ?, ?, ?, '1900-01-01', ?, ?)";
        $stmt_paciente = $pdo->prepare($sql_paciente);
        $stmt_paciente->execute([$usuario_id, $identificacion, $identificacion_tipo, $nombre, $apellido, $correo_electronico, $genero, $telefono]);

        $pdo->commit();

        header("Location: ../login.php?registered=1");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) {
            die("El correo electrónico o la identificación ya están registrados.");
        }
        die("Error en el registro: " . $e->getMessage());
    }
}
?>
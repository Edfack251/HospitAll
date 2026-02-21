<?php
session_start();
require_once '../../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paciente_id = $_POST['paciente_id'] ?? '';
    $medico_id = $_POST['medico_id'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';

    try {
        // Validar disponibilidad (básico: no citas al mismo tiempo para el médico)
        $stmt_val = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE medico_id = ? AND fecha = ? AND hora = ? AND estado != 'Cancelada'");
        $stmt_val->execute([$medico_id, $fecha, $hora]);
        if ($stmt_val->fetchColumn() > 0) {
            die("El médico ya tiene una cita agendada para esa fecha y hora.");
        }

        $sql = "INSERT INTO citas (paciente_id, medico_id, fecha, hora, observaciones, estado) 
                VALUES (?, ?, ?, ?, ?, 'Programada')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$paciente_id, $medico_id, $fecha, $hora, $observaciones]);

        if ($_SESSION['user_role'] === 'Paciente') {
            header("Location: ../patient_portal.php?success=1");
        } else {
            header("Location: ../appointments.php?success=1");
        }
        exit();
    } catch (PDOException $e) {
        die("Error al agendar la cita: " . $e->getMessage());
    }
}
?>
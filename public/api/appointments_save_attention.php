<?php
session_start();
require_once '../../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cita_id = $_POST['cita_id'] ?? '';
    $paciente_id = $_POST['paciente_id'] ?? '';
    $medico_id = $_POST['medico_id'] ?? '';
    $diagnostico = $_POST['diagnostico'] ?? '';
    $tratamiento = $_POST['tratamiento'] ?? '';
    $observaciones_clinicas = $_POST['observaciones_clinicas'] ?? '';
    $laboratorio_descripcion = $_POST['laboratorio_descripcion'] ?? '';
    $enviar_lab = isset($_POST['enviar_laboratorio']);
    $from = $_POST['from'] ?? '';
    $back_id = $_POST['back_id'] ?? '';

    if (empty($cita_id)) {
        die("ID de cita no válido.");
    }

    // Si NO se envía a lab, el diagnóstico y tratamiento son obligatorios
    if (!$enviar_lab && (empty($diagnostico) || empty($tratamiento))) {
        die("Debe proporcionar un diagnóstico y tratamiento para finalizar la atención.");
    }

    // Si se envía a lab, la descripción es obligatoria
    if ($enviar_lab && empty($laboratorio_descripcion)) {
        die("Debe describir qué exámenes solicita para el laboratorio.");
    }

    try {
        $pdo->beginTransaction();

        // 1. Guardar o Actualizar Historial Clínico
        $diag_final = $enviar_lab ? "(Pendiente por resultados de laboratorio)" : $diagnostico;
        $treat_final = $enviar_lab ? "(Pendiente por resultados de laboratorio)" : $tratamiento;

        // Verificar si ya existe record
        $stmt_check = $pdo->prepare("SELECT id FROM historial_clinico WHERE cita_id = ?");
        $stmt_check->execute([$cita_id]);
        $historial_existente = $stmt_check->fetch();

        if ($historial_existente) {
            $sql_historial = "UPDATE historial_clinico SET diagnostico = ?, tratamiento = ?, observaciones = ? WHERE id = ?";
            $stmt_historial = $pdo->prepare($sql_historial);
            $stmt_historial->execute([$diag_final, $treat_final, $observaciones_clinicas, $historial_existente['id']]);
            $historial_id = $historial_existente['id'];
        } else {
            $sql_historial = "INSERT INTO historial_clinico (cita_id, paciente_id, medico_id, diagnostico, tratamiento, observaciones) 
                              VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_historial = $pdo->prepare($sql_historial);
            $stmt_historial->execute([$cita_id, $paciente_id, $medico_id, $diag_final, $treat_final, $observaciones_clinicas]);
            $historial_id = $pdo->lastInsertId();
        }

        // 2. Si hay descripción de laboratorio, crear orden
        if ($enviar_lab) {
            // Verificar si ya tiene orden
            $stmt_lab_check = $pdo->prepare("SELECT id FROM ordenes_laboratorio WHERE historial_id = ? AND estado = 'Pendiente'");
            $stmt_lab_check->execute([$historial_id]);
            if (!$stmt_lab_check->fetch()) {
                $sql_lab = "INSERT INTO ordenes_laboratorio (historial_id, descripcion, estado) VALUES (?, ?, 'Pendiente')";
                $stmt_lab = $pdo->prepare($sql_lab);
                $stmt_lab->execute([$historial_id, $laboratorio_descripcion]);
            }
        }

        // 3. Actualizar estado de la cita a 'Atendida'
        $stmt_update = $pdo->prepare("UPDATE citas SET estado = 'Atendida' WHERE id = ?");
        $stmt_update->execute([$cita_id]);

        $pdo->commit();

        $redirect = ($_SESSION['user_role'] === 'medico') ? '../doctor_agenda.php' : '../appointments.php';

        if ($from === 'history' && !empty($back_id)) {
            $redirect = '../patient_portal.php?patient_id=' . $back_id;
        }

        header("Location: " . $redirect . "?success_atencion=1");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Error al guardar la atención médica: " . $e->getMessage());
    }
}
?>
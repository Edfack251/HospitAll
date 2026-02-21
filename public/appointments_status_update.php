<?php
session_start();
require_once '../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';

    if (!empty($id) && !empty($nuevo_estado)) {
        try {
            // Obtener estado actual
            $stmt = $pdo->prepare("SELECT estado FROM citas WHERE id = ?");
            $stmt->execute([$id]);
            $cita = $stmt->fetch();

            if ($cita) {
                $estado_actual = $cita['estado'];
                if ($estado_actual === 'Pendiente' || empty($estado_actual)) {
                    $estado_actual = 'Programada';
                }
                $valido = false;

                // Definir transiciones permitidas
                $transiciones = [
                    'Programada' => ['Confirmada', 'Cancelada', 'Programada'],
                    'Confirmada' => ['En espera', 'Programada'],
                    'En espera' => ['Atendida', 'No asistió'],
                    // Atendida, Cancelada, No asistió son estados finales
                ];

                if (isset($transiciones[$estado_actual]) && in_array($nuevo_estado, $transiciones[$estado_actual])) {
                    $valido = true;
                }

                if ($valido) {
                    $stmt = $pdo->prepare("UPDATE citas SET estado = ? WHERE id = ?");
                    $stmt->execute([$nuevo_estado, $id]);
                }
            }
        } catch (PDOException $e) {
            // Manejar error si es necesario
        }
    }
    header("Location: appointments.php");
    exit();
}
?>
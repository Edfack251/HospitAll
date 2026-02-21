<?php
session_start();
require_once '../../app/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orden_id = $_POST['orden_id'] ?? '';
    $resultado = $_POST['resultado'] ?? '';

    // Directorio de subida
    $upload_dir = '../uploads/lab_results/';

    // Asegurar que el directorio existe
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_path = null;

    if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
        $file_name = time() . '_' . basename($_FILES['archivo_pdf']['name']);
        $target_file = $upload_dir . $file_name;

        // Verificar que sea PDF
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if ($file_type !== 'pdf') {
            die("Error: Solo se permiten archivos PDF.");
        }

        if (move_uploaded_file($_FILES['archivo_pdf']['tmp_name'], $target_file)) {
            $file_path = 'uploads/lab_results/' . $file_name;
        } else {
            die("Error al subir el archivo.");
        }
    }

    if (empty($orden_id) || empty($resultado)) {
        die("Datos insuficientes para guardar el resultado.");
    }

    try {
        if ($file_path) {
            $sql = "UPDATE ordenes_laboratorio SET resultado = ?, archivo_pdf = ?, estado = 'Completada', fecha_resultado = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$resultado, $file_path, $orden_id]);
        } else {
            // Solo actualizar texto
            $sql = "UPDATE ordenes_laboratorio SET resultado = ?, estado = 'Completada', fecha_resultado = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$resultado, $orden_id]);
        }

        header("Location: ../laboratory.php?success_lab=1");
        exit();
    } catch (PDOException $e) {
        die("Error al guardar el resultado de laboratorio: " . $e->getMessage());
    }
}
?>
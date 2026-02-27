<?php

class LaboratoryService
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllOrders()
    {
        $sql = "SELECT ol.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido,
                m.nombre as medico_nombre, m.apellido as medico_apellido,
                hc.diagnostico
                FROM ordenes_laboratorio ol
                JOIN historial_clinico hc ON ol.historial_id = hc.id
                JOIN pacientes p ON hc.paciente_id = p.id
                JOIN medicos m ON hc.medico_id = m.id
                ORDER BY ol.estado ASC, ol.created_at DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function uploadResult($data, $files)
    {
        $orden_id = $data['orden_id'] ?? '';
        $resultado = $data['resultado'] ?? '';

        if (empty($orden_id) || empty($resultado)) {
            throw new Exception("Datos insuficientes para guardar el resultado.");
        }

        // Directorio de subida
        $upload_dir = __DIR__ . '/../../public/uploads/lab_results/';

        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                throw new Exception("Error: No se pudo crear el directorio de subida.");
            }
        }

        $file_path = null;

        if (isset($files['archivo_pdf']) && $files['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
            $file_name = time() . '_' . basename($files['archivo_pdf']['name']);
            $target_file = $upload_dir . $file_name;

            $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            if ($file_type !== 'pdf') {
                throw new Exception("Error: Solo se permiten archivos PDF.");
            }

            if (move_uploaded_file($files['archivo_pdf']['tmp_name'], $target_file)) {
                $file_path = 'uploads/lab_results/' . $file_name;
            } else {
                throw new Exception("Error al subir el archivo.");
            }
        }

        try {
            if ($file_path) {
                $sql = "UPDATE ordenes_laboratorio SET resultado = ?, archivo_pdf = ?, estado = 'Completada', fecha_resultado = NOW() WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$resultado, $file_path, $orden_id]);
            } else {
                $sql = "UPDATE ordenes_laboratorio SET resultado = ?, estado = 'Completada', fecha_resultado = NOW() WHERE id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$resultado, $orden_id]);
            }

            // Auditoría: Subir resultado laboratorio
            if (isset($_SESSION['usuario_id'])) {
                $logService = new LogService($this->pdo);
                $logService->register($_SESSION['usuario_id'], 'Subir resultado PDF', 'Laboratorio', "Orden ID: $orden_id");
            }

            return true;
        } catch (PDOException $e) {
            throw new Exception("Error al guardar el resultado de laboratorio: " . $e->getMessage());
        }
    }
}

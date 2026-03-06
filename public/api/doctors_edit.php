<?php
session_start();
require_once '../../app/autoload.php';
$pdo = \App\Config\Database::getConnection();
use App\Helpers\CsrfHelper;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CsrfHelper::validateToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        die("Token CSRF inválido.");
    }
    $id = $_POST['id'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $especialidad = $_POST['especialidad'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $correo_electronico = $_POST['correo_electronico'] ?? '';

    try {
        $sql = "UPDATE medicos SET nombre=?, apellido=?, especialidad=?, telefono=?, correo_electronico=? 
                WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $apellido, $especialidad, $telefono, $correo_electronico, $id]);

        header("Location: ../doctors.php?updated=1");
        exit();
    } catch (PDOException $e) {
        die("Error al actualizar el médico: " . $e->getMessage());
    }
}
?>
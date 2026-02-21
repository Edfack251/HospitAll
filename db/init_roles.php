<?php
require_once __DIR__ . '/../app/config/database.php';

try {
    $roles = [
        ['nombre' => 'administrador', 'descripcion' => 'Acceso total al sistema'],
        ['nombre' => 'medico', 'descripcion' => 'Atención médica y agenda'],
        ['nombre' => 'recepcionista', 'descripcion' => 'Gestión de citas y pacientes'],
        ['nombre' => 'tecnico_laboratorio', 'descripcion' => 'Gestión de órdenes y resultados de laboratorio'],
        ['nombre' => 'paciente', 'descripcion' => 'Acceso personal de paciente']
    ];

    foreach ($roles as $rol) {
        $stmt = $pdo->prepare("INSERT INTO roles (nombre, descripcion) VALUES (?, ?) ON DUPLICATE KEY UPDATE descripcion = ?");
        $stmt->execute([$rol['nombre'], $rol['descripcion'], $rol['descripcion']]);
    }

    echo "Roles inicializados o actualizados correctamente.";
} catch (PDOException $e) {
    die("Error al inicializar roles: " . $e->getMessage());
}
?>
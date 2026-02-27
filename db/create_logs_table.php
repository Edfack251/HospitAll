<?php
require_once __DIR__ . '/../app/config/database.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        accion VARCHAR(100) NOT NULL,
        modulo VARCHAR(100) NOT NULL,
        descripcion TEXT,
        ip VARCHAR(45),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "Tabla 'logs' creada o ya existe correctamente.";
} catch (PDOException $e) {
    die("Error al crear la tabla logs: " . $e->getMessage());
}
?>
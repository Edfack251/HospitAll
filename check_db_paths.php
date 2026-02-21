<?php
require_once 'app/config/database.php';
$stmt = $pdo->query("SELECT id, descripcion, archivo_pdf FROM ordenes_laboratorio WHERE archivo_pdf IS NOT NULL LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id'] . " | Path: " . $row['archivo_pdf'] . "\n";
}
?>
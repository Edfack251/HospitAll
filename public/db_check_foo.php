<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
header('Content-Type: text/plain');
try {
    $pdo = App\Config\Database::getConnection();
    
    echo "--- TURNO TABLE ---\n";
    $stmt = $pdo->query("SELECT * FROM turnos ORDER BY created_at DESC LIMIT 10");
    $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($turnos);

    echo "\n--- DESCRIBE asignaciones_enfermeria ---\n";
    $stmt = $pdo->query("DESCRIBE asignaciones_enfermeria");
    $desc = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($desc);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

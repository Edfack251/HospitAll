<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\Services\DoctorDashboardService;

try {
    $pdo = Database::getConnection();
    $service = new DoctorDashboardService($pdo);
    
    echo "Testeando getDashboardData para usuario_id = 2...\n";
    $data = $service->getDashboardData(2);
    
    echo "Resultado: " . (isset($data['medico_id']) ? "OK (medico_id: " . $data['medico_id'] . ")" : "Error") . "\n";
    echo "Citas hoy: " . count($data['citas_hoy']) . "\n";
    echo "Internados: " . count($data['pacientes_internados']) . "\n";
    echo "Labs pendientes: " . count($data['resultados_pendientes']) . "\n";
    echo "Exito!\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

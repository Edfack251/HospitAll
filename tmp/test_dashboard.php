<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\Services\DoctorDashboardService;
use App\Repositories\DoctorRepository;

try {
    $pdo = Database::getConnection();

    // Find a medico user
    $stmt = $pdo->query("SELECT u.id, u.correo_electronico, m.id as medico_id FROM usuarios u JOIN medicos m ON u.correo_electronico = m.correo_electronico LIMIT 1");
    $user = $stmt->fetch();

    if (!$user) {
        echo "No se encontró ningún usuario con rol de médico en la base de datos para probar.\n";
        exit;
    }

    echo "Probando Dashboard para Usuario ID: {$user['id']} (Médico ID: {$user['medico_id']})\n";

    $service = new DoctorDashboardService($pdo);
    $data = $service->getDashboardData($user['id']);

    echo "\n--- DATO AGREGADO EXITOSAMENTE ---\n";
    echo "Citas programadas para hoy: " . count($data['citas_hoy']) . "\n";
    echo "Pacientes en espera/consulta: " . count($data['pacientes_espera']) . "\n";
    echo "Consultas recientes: " . count($data['consultas_recientes']) . "\n";
    echo "Labs pendientes: " . count($data['resultados_pendientes']) . "\n";
    echo "Prescripciones activas: " . count($data['prescripciones_activas']) . "\n";
    echo "----------------------------------\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Services\ClinicalHistoryService;
use App\Config\Database;

try {
    $pdo = Database::getConnection();
    $service = new ClinicalHistoryService($pdo);

    // 1. Obtener un paciente real
    $stmt = $pdo->query("SELECT id FROM pacientes LIMIT 1");
    $patient_id = $stmt->fetchColumn();

    if (!$patient_id) {
        die("No hay pacientes en la base de datos.");
    }

    echo "Probando generación de PDF para Paciente ID: $patient_id\n";

    $start = microtime(true);
    $pdfContent = $service->generatePatientHistoryPdf($patient_id);
    $end = microtime(true);

    $time = $end - $start;
    echo "PDF generado exitosamente en " . round($time, 4) . " segundos.\n";

    if (strlen($pdfContent) > 0) {
        $outputPath = 'tmp/test_history.pdf';
        file_put_contents($outputPath, $pdfContent);
        echo "Archivo guardado en: $outputPath (" . strlen($pdfContent) . " bytes)\n";
    } else {
        echo "ERROR: El contenido del PDF está vacío.\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

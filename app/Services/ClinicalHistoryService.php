<?php
namespace App\Services;

use Exception;
use PDO;
use DateTime;
use App\Repositories\ClinicalHistoryRepository;

class ClinicalHistoryService
{
    private $pdo;
    private $repo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->repo = new ClinicalHistoryRepository($pdo);
    }

    /**
     * Get the search list for patients.
     * @return array
     */
    public function getPatientSearchList(): array
    {
        return $this->repo->getPatientSearchList();
    }

    /**
     * Get full clinical history for a patient.
     */
    public function getPatientFullHistory(int $patient_id): array
    {
        return $this->getFullHistoryData($patient_id);
    }

    /**
     * Obtiene todos los datos necesarios para el historial clínico.
     */
    public function getFullHistoryData(int $patient_id): array
    {
        $data = $this->repo->getFullClinicalHistoryByPatientId($patient_id);

        if ($data && isset($data['patient'])) {
            // Calcular edad
            $cumpleanos = new DateTime($data['patient']['fecha_nacimiento']);
            $hoy = new DateTime();
            $data['patient']['edad'] = $hoy->diff($cumpleanos)->y;
        }

        return $data ?: [];
    }

    /**
     * Genera un documento PDF del historial clínico.
     */
    public function generatePatientHistoryPdf(int $patient_id)
    {
        $data = $this->getFullHistoryData($patient_id);

        if (empty($data)) {
            throw new Exception("No se encontraron datos para el paciente ID: $patient_id");
        }

        // Cargar plantilla HTML
        $templatePath = __DIR__ . '/../../views/pdf/patient_history_template.php';
        if (!file_exists($templatePath)) {
            throw new Exception("La plantilla de PDF no existe.");
        }

        // Renderizar plantilla a una variable (buffer de salida)
        ob_start();
        $patient = $data['patient'];
        $history = $data['history'];
        include $templatePath;
        $html = ob_get_clean();

        // Configurar Dompdf
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Verifica si un médico tiene acceso al historial de un paciente.
     */
    public function canDoctorAccessPatient($medico_id, $paciente_id)
    {
        return $this->repo->hasDoctorTreatedPatient($medico_id, $paciente_id);
    }
}

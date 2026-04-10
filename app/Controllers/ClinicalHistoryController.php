<?php
namespace App\Controllers;

use App\Services\ClinicalHistoryService;

class ClinicalHistoryController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new ClinicalHistoryService($pdo);
    }

    /**
     * Search for patients.
     * @return array
     */
    public function search(): array
    {
        return $this->service->getPatientSearchList();
    }

    /**
     * Show full clinical history for a patient.
     * @param int $patient_id
     * @return array
     */
    public function show(int $patient_id): array
    {
        return $this->service->getPatientFullHistory($patient_id);
    }

    /**
     * Exporta el historial clínico del paciente a PDF.
     * GET /api/clinical-history/export-pdf?id={patient_id}
     */
    public function exportPdf()
    {
        $patient_id = (int) ($_GET['id'] ?? 0);
        if ($patient_id <= 0) {
            header('HTTP/1.1 400 Bad Request');
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'ID de paciente inválido.']);
            return;
        }

        // 1. Obtener usuario de la sesión
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $user = $_SESSION['user'] ?? null;

        if (!$user) {
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['error' => 'Usuario no autenticado']);
            return;
        }

        // 2. Verificar autorización básica mediante PolicyManager
        // Primero validamos si el usuario es un paciente (solo puede ver su propio historial)
        // O si es administrador. Los médicos tienen prohibido DESCARGAR el PDF.

        $role = strtolower($user['role'] ?? $user['rol'] ?? '');
        
        if ($role === 'medico') {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['error' => 'Los médicos no tienen autorización para descargar historiales clínicos por motivos de seguridad.']);
            return;
        }

        if ($role === 'paciente') {
            // El paciente solo puede descargar SU propio historial
            $session_patient_id = $_SESSION['paciente_id'] ?? null;
            if (!$session_patient_id || (int)$session_patient_id !== $patient_id) {
                header('HTTP/1.1 403 Forbidden');
                echo json_encode(['error' => 'No tienes autorización para descargar este historial clínico.']);
                return;
            }
        } else {
            // Para otros roles (Administrador)
            try {
                \App\Policies\PolicyManager::authorize($user, 'view_patient_history');
            } catch (\Exception $e) {
                header('HTTP/1.1 403 Forbidden');
                echo json_encode(['error' => $e->getMessage()]);
                return;
            }
        }

        // 4. Generar PDF
        try {
            $pdfContent = $this->service->generatePatientHistoryPdf($patient_id);

            // 5. Enviar headers y contenido
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="historial_clinico_' . $patient_id . '.pdf"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');

            echo $pdfContent;
            exit;
        } catch (\Exception $e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['error' => 'Error al generar el PDF: ' . $e->getMessage()]);
            return;
        }
    }
}

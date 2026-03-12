<?php
namespace App\Controllers;

use App\Services\PatientPortalService;

class PatientPortalController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new PatientPortalService($pdo);
    }

    /**
     * Obtiene los datos del dashboard para el paciente autenticado.
     * 
     * @return array Datos agregados (citas, historial, lab, prescripciones, facturas)
     */
    public function index(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user = $_SESSION['user'] ?? null;

        // Autorizar: Solo el paciente puede ver su propia información
        // (Nota: Si un admin/medico quisiera ver esto, se manejaría con otra lógica o permiso)
        \App\Policies\PolicyManager::authorize($user, 'view_own_patient_portal');

        return $this->service->getDashboardData($user['id']);
    }

    /**
     * Mantenido por compatibilidad si es necesario, pero se prefiere index()
     */
    public function show(int $paciente_id): array
    {
        return $this->service->getPatientPortalData($paciente_id);
    }
}

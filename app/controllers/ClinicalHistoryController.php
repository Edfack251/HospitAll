<?php

require_once __DIR__ . '/../services/ClinicalHistoryService.php';

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
}

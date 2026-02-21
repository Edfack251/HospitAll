<?php
require_once __DIR__ . '/../services/PatientsService.php';

class PatientsController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new PatientsService($pdo);
    }

    public function index($search = null, $isMedico = false)
    {
        if ($isMedico && !empty($search)) {
            return $this->service->searchByIdentification($search);
        } elseif (!$isMedico) {
            return $this->service->getAll();
        }
        return [];
    }

    public function getById($id)
    {
        $paciente = $this->service->getById($id);
        if (!$paciente) {
            header("Location: patients.php");
            exit();
        }
        return $paciente;
    }

    public function create($data)
    {
        try {
            $this->service->create($data);
            header("Location: ../patients.php?success=1");
            exit();
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    public function update($id, $data)
    {
        try {
            $this->service->update($id, $data);
            header("Location: ../patients.php?updated=1");
            exit();
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
}

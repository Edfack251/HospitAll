<?php
require_once __DIR__ . '/../services/LaboratoryService.php';

class LaboratoryController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new LaboratoryService($pdo);
    }

    public function index()
    {
        return $this->service->getAllOrders();
    }

    public function uploadResult($data, $files)
    {
        try {
            $this->service->uploadResult($data, $files);
            header("Location: ../laboratory.php?success_lab=1");
            exit();
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
}

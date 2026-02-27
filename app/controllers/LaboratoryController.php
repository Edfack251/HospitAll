<?php
namespace App\Controllers;

use App\Core\ErrorHandler;
use App\Services\LaboratoryService;
use Exception;

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
            ErrorHandler::handle($e);
        }
    }
}

<?php
namespace App\Controllers;

use App\Services\LogService;

class LogController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new LogService($pdo);
    }

    /**
     * Retorna la lista de logs filtrada.
     */
    public function index()
    {
        $filters = [
            'usuario_id' => $_GET['usuario_id'] ?? null,
            'modulo' => $_GET['modulo'] ?? null,
            'fecha_desde' => $_GET['fecha_desde'] ?? null,
            'fecha_hasta' => $_GET['fecha_hasta'] ?? null
        ];

        return $this->service->search($filters);
    }

    public function getFilterData()
    {
        return [
            'modulos' => $this->service->getDistinctModules(),
            'usuarios' => $this->service->getUsersForFilter()
        ];
    }
}

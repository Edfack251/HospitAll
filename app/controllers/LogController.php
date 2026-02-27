<?php

class LogController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new LogService($pdo);
    }

    /**
     * Retorna la lista de logs para la vista administrativa.
     */
    public function index()
    {
        return $this->service->getAll();
    }
}

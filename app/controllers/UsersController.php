<?php

class UsersController
{
    private $service;

    public function __construct($pdo)
    {
        $this->service = new UsersService($pdo);
    }

    public function index()
    {
        return $this->service->getAll();
    }

    public function getById($id)
    {
        $user = $this->service->getById($id);
        if (!$user) {
            header("Location: users.php");
            exit();
        }
        return $user;
    }

    public function getFormData($excludePatient = false)
    {
        return [
            'roles' => $this->service->getRoles($excludePatient)
        ];
    }

    public function create($data)
    {
        try {
            $this->service->create($data);
            return "Usuario creado correctamente.";
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function update($id, $data)
    {
        try {
            $this->service->update($id, $data);
            return "Usuario actualizado correctamente.";
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}

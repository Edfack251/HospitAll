<?php
namespace App\Repositories;

use PDO;
use Exception;

class AuthRepository
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getUserByEmail($email)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $stmt = $this->pdo->prepare("SELECT u.*, r.nombre as rol_nombre FROM usuarios u LEFT JOIN roles r ON u.rol_id = r.id WHERE u.correo_electronico = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getPatientIdByUserId($user_id)
    {
        $stmt = $this->pdo->prepare("SELECT id FROM pacientes WHERE usuario_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    }

    public function getDoctorIdByEmail($email)
    {
        $stmt = $this->pdo->prepare("SELECT id FROM medicos WHERE correo_electronico = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn();
    }

    public function getPatientRoleId()
    {
        $stmt_rol = $this->pdo->prepare("SELECT id FROM roles WHERE LOWER(nombre) = 'paciente' LIMIT 1");
        $stmt_rol->execute();
        return $stmt_rol->fetchColumn();
    }

    public function createPatientUser($userData, $patientData)
    {
        $sql_usuario = "INSERT INTO usuarios (nombre, apellido, correo_electronico, password, rol_id) VALUES (?, ?, ?, ?, ?)";
        $stmt_usuario = $this->pdo->prepare($sql_usuario);
        $stmt_usuario->execute([
            $userData['nombre'],
            $userData['apellido'],
            $userData['correo_electronico'],
            $userData['password_hash'],
            $userData['rol_id']
        ]);
        $usuario_id = $this->pdo->lastInsertId();

        $sql_paciente = "INSERT INTO pacientes (usuario_id, identificacion, identificacion_tipo, nombre, apellido, correo_electronico, fecha_nacimiento, genero, telefono) 
                         VALUES (?, ?, ?, ?, ?, ?, '1900-01-01', ?, ?)";
        $stmt_paciente = $this->pdo->prepare($sql_paciente);
        $stmt_paciente->execute([
            $usuario_id,
            $patientData['identificacion'],
            $patientData['identificacion_tipo'],
            $patientData['nombre'],
            $patientData['apellido'],
            $patientData['correo_electronico'],
            $patientData['genero'],
            $patientData['telefono']
        ]);

        return $usuario_id;
    }
}
